{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@props([
    'id' => 'editorjs-data',
    'size' => 'size-full',
    'editordata' => null,
])

@php
    $holderId = $id . '-editor';
    $inputId = $id . '-data';

    $editorClasses = [
        $size,
        'font-normal',
        'p-2',
        'bg-zinc-100',
        'rounded-sm',
        'shadow-sm',
        'text-black',
        'focus:outline-0',
        'text-left',
        'overflow-x-hidden',
        'overflow-y-auto',
    ];

    $editorAttributes = $attributes->except(['id', 'class'])->merge([
        'class' => implode(' ', $editorClasses),
        'data-editor-holder' => $holderId,
        'data-editor-input' => $inputId,
    ]);

    $editorDataHTMLSafe = $editordata;

    // Prefer raw JSON first to avoid unnecessary entity transformations (e.g. links/query params).
    if ($editordata) {
        $decodedData = json_decode($editordata, true);

        // Fallback for legacy HTML-entity encoded payloads.
        if (!is_array($decodedData)) {
            $decodedData = json_decode(html_entity_decode($editordata, ENT_QUOTES, 'UTF-8'), true);
        }

        $editordata = is_array($decodedData) ? $decodedData : null;
    }
@endphp

@push('scripts')
    <script>
        window.editorDataRegistry = window.editorDataRegistry || {};
        window.editorDataRegistry[@json($holderId)] = @json($editordata);
    </script>
    @vite('resources/js/editor/editorjs.js')
@endpush

@push('styles')
    {{-- Global text editor styles --}}
    <style>
        .cdx-block{
            padding: 0;
        }

        .ce-paragraph{
            line-height: 1.25em;
        }

        .cdx-marker{
            background: yellow !important;
        }
    </style>
@endpush

{{-- Hidden input field --}}
<input type="hidden" id="{{$inputId}}" name="{{$id}}" {{$editorAttributes['required'] ? 'required' : ''}} value="{{$editorDataHTMLSafe}}" />

{{-- Editor input --}}
<div id="{{$holderId}}" {{$editorAttributes->except(['value'])}} style="position:relative"></div>

{{-- Hidden file input and status used when admin clicks the EditorJS "+" control --}}
<input id="{{$holderId}}-image-input" type="file" accept="image/*" class="hidden" />
<span id="{{$holderId}}-image-status" class="text-sm text-zinc-500 hidden"></span>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const holderId = @json($holderId);
            const input = document.getElementById(holderId + '-image-input');
            const status = document.getElementById(holderId + '-image-status');

            if (!input) return;

            // Add paste handler to accept images from clipboard (png and jpeg only)
            const holderElForPaste = document.querySelector('[data-editor-holder="' + holderId + '"]');
            if (holderElForPaste) {
                holderElForPaste.addEventListener('paste', async function (ev) {
                    try {
                        const items = (ev.clipboardData || ev.originalEvent?.clipboardData)?.items;
                        if (!items) return;

                        for (const item of items) {
                            if (item.kind === 'file' && (item.type === 'image/png' || item.type === 'image/jpeg')) {
                                ev.preventDefault();
                                const file = item.getAsFile();
                                if (!file) return;

                                // Basic client-side validation
                                if (file.size > 5 * 1024 * 1024) { // 5MB
                                    status.textContent = 'Bestand is te groot (max 5MB).';
                                    status.classList.remove('hidden');
                                    return;
                                }

                                status.textContent = 'Uploaden...';
                                status.classList.remove('hidden');

                                // mark upload in progress so form submission can wait
                                if (holderElForPaste) holderElForPaste.dataset.uploadInProgress = 'true';

                                const fd = new FormData();
                                fd.append('image', file);

                                const resp = await fetch('/admin/editor/image-upload', {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    body: fd,
                                    headers: {
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                                    }
                                });

                                if (!resp.ok) {
                                    throw new Error('Upload failed: ' + resp.statusText);
                                }

                                const data = await resp.json();
                                const url = data.url;

                                // Insert image into editor (prefer image block insertion at caret)
                                try {
                                    if (holderElForPaste?.editorInstance && holderElForPaste.editorInstance.blocks) {
                                        let insertIndex = undefined;
                                        try { if (typeof holderElForPaste.editorInstance.blocks.getCurrentBlockIndex === 'function') insertIndex = holderElForPaste.editorInstance.blocks.getCurrentBlockIndex(); } catch (e) {}

                                        if (typeof insertIndex === 'number') {
                                            holderElForPaste.editorInstance.blocks.insert('image', { file: { url } }, {}, insertIndex);
                                        } else {
                                            holderElForPaste.editorInstance.blocks.insert('image', { file: { url } });
                                        }

                                        // Persist editor JSON to hidden input
                                        const inputId = holderElForPaste.getAttribute('data-editor-input');
                                        if (inputId && typeof holderElForPaste.editorInstance.save === 'function') {
                                            try {
                                                const output = await holderElForPaste.editorInstance.save();
                                                const hiddenInput = document.getElementById(inputId);
                                                if (hiddenInput) hiddenInput.value = JSON.stringify(output);
                                            } catch (e) { console.error('Failed to save after paste insert', e); }
                                        }

                                        status.textContent = 'Upload geslaagd';
                                        status.classList.remove('hidden');
                                        if (holderElForPaste) delete holderElForPaste.dataset.uploadInProgress;
                                        return;
                                    }
                                } catch (err) {
                                    console.warn('Image block insert failed, falling back to HTML insert', err);
                                }

                                // Fallback: insert centered HTML into editable area
                                const editable = holderElForPaste?.querySelector('[contenteditable="true"]');
                                const centeredHtml = '<center><img src="' + url + '" alt="Afbeelding" style="max-width:600px;width:100%;height:auto;display:block;margin:0 auto;"/></center>';
                                if (editable && document.queryCommandSupported && document.queryCommandSupported('insertHTML')) {
                                    editable.focus();
                                    document.execCommand('insertHTML', false, centeredHtml);
                                } else if (holderElForPaste?.editorInstance) {
                                    try { holderElForPaste.editorInstance.blocks.insert('paragraph', { text: centeredHtml }); } catch (e) {}
                                }

                                // Persist editor content after fallback
                                try {
                                    const inputId = holderElForPaste?.getAttribute('data-editor-input');
                                    if (inputId && holderElForPaste?.editorInstance && typeof holderElForPaste.editorInstance.save === 'function') {
                                        const output = await holderElForPaste.editorInstance.save();
                                        const hiddenInput = document.getElementById(inputId);
                                        if (hiddenInput) hiddenInput.value = JSON.stringify(output);
                                    }
                                } catch (e) { console.error(e); }

                                if (holderElForPaste) delete holderElForPaste.dataset.uploadInProgress;

                                status.textContent = 'Upload geslaagd';
                                status.classList.remove('hidden');

                                return;
                            }
                        }
                        } catch (e) {
                        console.error('Paste image handling failed', e);
                        if (holderElForPaste) delete holderElForPaste.dataset.uploadInProgress;
                        status.textContent = 'Plakken mislukt';
                        status.classList.remove('hidden');
                    }
                });
            }

            // When admin clicks the EditorJS "+" control within this editor, open the file picker.
            document.addEventListener('click', function (ev) {
                const target = ev.target;

                // Find the editor holder ancestor for the clicked element, if any
                const clickedHolder = target.closest('[data-editor-holder]');
                if (!clickedHolder) return;
                if (clickedHolder.getAttribute('data-editor-holder') !== holderId) return;

                // Detect common plus/button controls generated by EditorJS or toolboxes.
                const isPlus = target.classList?.contains('cdx-plus')
                    || target.classList?.contains('ce-block__plus')
                    || target.classList?.contains('ce-action__plus')
                    || target.getAttribute('title') === 'Add'
                    || target.textContent?.trim() === '+';

                if (!isPlus) return;

                // Open the hidden file input for this editor instance
                input.click();
            });

            // Also inject an "Image" item into the EditorJS insert toolbox when it appears so users
            // can explicitly choose Image from the menu without rebuilding frontend assets.
            const tryInjectImageItem = () => {
                // Look for any toolbox/palette that contains the standard tool labels (Text, Heading, Table...)
                const candidates = Array.from(document.querySelectorAll('div, ul'));
                for (const el of candidates) {
                    const text = (el.textContent || '').trim();
                    if (!text) continue;

                    // crude heuristic: toolbox contains the word 'Heading' and 'Text'
                    if (text.includes('Heading') && text.includes('Text')) {
                        // if our image item already exists, stop
                        if (el.querySelector('.zijpalm-image-tool')) return;

                        // Create a simple item similar to editor items
                        const item = document.createElement('div');
                        item.className = 'zijpalm-image-tool cursor-pointer py-1 px-2 text-sm text-left';
                        item.style.display = 'flex';
                        item.style.alignItems = 'center';
                        item.style.gap = '0.5rem';
                        item.innerHTML = '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><rect width="24" height="24" rx="2" fill="#E5E7EB"></rect><path d="M6 15l2-2 3 3 4-4 4 4v1H6v-2z" fill="#374151"></path></svg><span>Image</span>';

                        item.addEventListener('click', (ev) => {
                            ev.stopPropagation();
                            input.click();
                        });

                        // Append to toolbox element (if it's a list or div)
                        el.appendChild(item);
                        return;
                    }
                }
            };

            // Observe DOM mutations to inject when the toolbox appears
            const observer = new MutationObserver((mutations) => {
                tryInjectImageItem();
            });
            observer.observe(document.body, { childList: true, subtree: true });

            input.addEventListener('change', async (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;

                // Basic client-side validation
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    status.textContent = 'Bestand is te groot (max 5MB).';
                    return;
                }

                    status.textContent = 'Uploaden...';
                    status.classList.remove('hidden');

                    // mark upload in progress so form submission can wait
                    if (holderEl) holderEl.dataset.uploadInProgress = 'true';

                const fd = new FormData();
                fd.append('image', file);

                try {
                    const resp = await fetch('/admin/editor/image-upload', {
                        method: 'POST',
                        credentials: 'same-origin',
                        body: fd,
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                        }
                    });

                    if (!resp.ok) {
                        throw new Error('Upload failed: ' + resp.statusText);
                    }

                    const data = await resp.json();
                    const url = data.url;

                    // Insert image into the editor. Prefer a proper image block if the Image tool is registered,
                    // otherwise fall back to inserting centered HTML which the mail sanitizer will allow.
                    const holderEl = document.querySelector('[data-editor-holder="' + holderId + '"]');
                    const editable = holderEl?.querySelector('[contenteditable="true"]');

                    const centeredHtml = '<center><img src="' + url + '" alt="Afbeelding" style="max-width:600px;width:100%;height:auto;display:block;margin:0 auto;"/></center>';

                    // Try EditorJS block insertion (image tool). Different image tools expect different payload shapes,
                    // attempt a few common ones.
                    try {
                        if (holderEl?.editorInstance && holderEl.editorInstance.blocks) {
                            // Determine insert index (prefer current block index so the image appears where the caret is)
                            let insertIndex = undefined;
                            try {
                                if (typeof holderEl.editorInstance.blocks.getCurrentBlockIndex === 'function') {
                                    insertIndex = holderEl.editorInstance.blocks.getCurrentBlockIndex();
                                }
                            } catch (e) { /* ignore */ }

                            // Try common payload shapes and insert at the computed index when possible
                            try {
                                if (typeof insertIndex === 'number') {
                                    holderEl.editorInstance.blocks.insert('image', { file: { url } }, {}, insertIndex);
                                } else {
                                    holderEl.editorInstance.blocks.insert('image', { file: { url } });
                                }

                                // After insertion, persist the editor JSON into the hidden input so saving the form retains the image
                                const inputId = holderEl.getAttribute('data-editor-input');
                                if (inputId && holderEl.editorInstance && typeof holderEl.editorInstance.save === 'function') {
                                    try {
                                        const output = await holderEl.editorInstance.save();
                                        const hiddenInput = document.getElementById(inputId);
                                        if (hiddenInput) hiddenInput.value = JSON.stringify(output);
                                    } catch (e) {
                                        console.error('Failed to save editor after image insert', e);
                                    }
                                }

                                status.textContent = 'Upload geslaagd';
                                status.classList.remove('hidden');
                                if (holderEl) delete holderEl.dataset.uploadInProgress;
                                return;
                            } catch (e) {
                                // if block insertion fails, fall back to HTML insertion below
                                console.warn('Block insertion failed, falling back to HTML insert', e);
                            }
                        }

                        // Fallback: insert as centered HTML into the editable area
                        if (editable && document.queryCommandSupported && document.queryCommandSupported('insertHTML')) {
                            editable.focus();
                            document.execCommand('insertHTML', false, centeredHtml);
                        } else if (holderEl?.editorInstance) {
                            try {
                                holderEl.editorInstance.blocks.insert('paragraph', { text: centeredHtml });
                            } catch (err) {
                                // ignore
                            }
                        }

                        // Persist the editor content into hidden input after fallback insertion as well
                        try {
                            const inputId = holderEl?.getAttribute('data-editor-input');
                            if (inputId && holderEl?.editorInstance && typeof holderEl.editorInstance.save === 'function') {
                                const output = await holderEl.editorInstance.save();
                                const hiddenInput = document.getElementById(inputId);
                                if (hiddenInput) hiddenInput.value = JSON.stringify(output);
                            }
                        } catch (e) { console.error(e); }
                        if (holderEl) delete holderEl.dataset.uploadInProgress;

                        status.textContent = 'Upload geslaagd';
                        status.classList.remove('hidden');
                    } catch (err) {
                        console.error(err);
                        if (holderEl) delete holderEl.dataset.uploadInProgress;
                        status.textContent = 'Upload mislukt';
                        status.classList.remove('hidden');
                    }
                } catch (err) {
                    console.error(err);
                    status.textContent = 'Upload mislukt';
                    status.classList.remove('hidden');
                }
            });

            // Attach a guard to the form to wait for uploads and ensure editor content is saved
            (function attachFormGuard(){
                const holderEl = document.querySelector('[data-editor-holder="' + holderId + '"]');
                if (!holderEl) return;
                const form = holderEl.closest('form');
                if (!form) return;

                form.addEventListener('submit', function (ev) {
                    const inProgress = holderEl.dataset.uploadInProgress === 'true';
                    if (!inProgress) {
                        // ensure latest editor content is saved before submit
                        try {
                            if (holderEl.editorInstance && typeof holderEl.editorInstance.save === 'function') {
                                ev.preventDefault();
                                holderEl.editorInstance.save().then((output) => {
                                    const inputId = holderEl.getAttribute('data-editor-input');
                                    const hiddenInput = document.getElementById(inputId);
                                    if (hiddenInput) hiddenInput.value = JSON.stringify(output);
                                    form.submit();
                                }).catch(() => { form.submit(); });
                                return;
                            }
                        } catch (e) { /* ignore */ }
                        return;
                    }

                    // Wait up to 10s for upload to finish
                    ev.preventDefault();
                    const maxWait = 10000;
                    const interval = 200;
                    let waited = 0;
                    const waiter = setInterval(async function(){
                        if (holderEl.dataset.uploadInProgress !== 'true') {
                            clearInterval(waiter);
                            try {
                                if (holderEl.editorInstance && typeof holderEl.editorInstance.save === 'function') {
                                    const output = await holderEl.editorInstance.save();
                                    const inputId = holderEl.getAttribute('data-editor-input');
                                    const hiddenInput = document.getElementById(inputId);
                                    if (hiddenInput) hiddenInput.value = JSON.stringify(output);
                                }
                            } catch (e) { console.error('Failed saving editor before submit', e); }
                            form.submit();
                            return;
                        }

                        waited += interval;
                        if (waited >= maxWait) {
                            clearInterval(waiter);
                            form.submit();
                        }
                    }, interval);
                });
            })();
        });
    </script>
@endpush

