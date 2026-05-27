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
<div id="{{$holderId}}" {{$editorAttributes->except(['value'])}}></div>

{{-- Image upload UI for admins: click to choose image, uploads to admin endpoint and inserts into editor --}}
<div class="mt-2 flex gap-2 items-center">
    <button type="button" class="inline-flex items-center px-3 py-1 bg-zijpalm-500 text-white rounded-md" id="{{$holderId}}-image-button">Afbeelding invoegen</button>
    <input id="{{$holderId}}-image-input" type="file" accept="image/*" class="hidden" />
    <span id="{{$holderId}}-image-status" class="text-sm text-zinc-500"></span>
</div>

@push('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function(){
            const holderId = @json($holderId);
            const btn = document.getElementById(holderId + '-image-button');
            const input = document.getElementById(holderId + '-image-input');
            const status = document.getElementById(holderId + '-image-status');

            if (!btn || !input) return;

            btn.addEventListener('click', () => input.click());

            input.addEventListener('change', async (e) => {
                const file = e.target.files && e.target.files[0];
                if (!file) return;

                // Basic client-side validation
                if (file.size > 5 * 1024 * 1024) { // 5MB
                    status.textContent = 'Bestand is te groot (max 5MB).';
                    return;
                }

                status.textContent = 'Uploaden...';

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
                            // Try the most common shapes used by editorjs-image
                            try { holderEl.editorInstance.blocks.insert('image', { file: { url } }); status.textContent = 'Upload geslaagd'; return; } catch (e) {}
                            try { holderEl.editorInstance.blocks.insert('image', { data: { url } }); status.textContent = 'Upload geslaagd'; return; } catch (e) {}
                            // If insertion by block type fails, try inserting raw HTML into the editable area
                        }
                    } catch (err) {
                        // ignore and fall back
                    }

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

                    // Trigger save to update the hidden input
                    try { holderEl?.editorInstance?.save().then((output)=>{ /* no-op */ }); } catch (err) {}

                    status.textContent = 'Upload geslaagd';
                } catch (err) {
                    console.error(err);
                    status.textContent = 'Upload mislukt';
                }
            });
        });
    </script>
@endpush

