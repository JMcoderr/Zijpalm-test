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

    // If there is editordata, make sure that EditorJS can read it (removing @json and json_decode makes it NOT work!)
    if($editordata){
        $editordata = json_decode(html_entity_decode($editordata, ENT_QUOTES, 'UTF-8'), true);
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

{{-- Re-initialize editors when rendered inside Livewire (e.g. modals/popups) --}}
<script>
    (function () {
        function tryInit() {
            if (typeof window.initializeEditorJsHolders === 'function') {
                window.initializeEditorJsHolders();
            } else {
                setTimeout(tryInit, 100);
            }
        }
        tryInit();
    })();
</script>

