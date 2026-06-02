{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@use('App\FileType')

@push('scripts')
    @vite('resources/js/forms/display-uploaded-file.js')
    @vite('resources/js/forms/display-uploaded-file-name.js')
@endpush

<x-page-wrapper page="Bewerken {{$content->displayName}}">
    @if (session('success'))
        <x-zijpalm-div color="light" :editable=false :text="session('success')" width="w-max" success/>
    @endif

    <x-zijpalm-div title="Bewerken {{$content->displayName}}" color="transparent" :editable=false />

    <x-zijpalm-div color="light" :editable=false width="w-full sm:w-1/2">
        @if ($errors->any())
            <div class="text-red-500">
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form id="content-update" enctype="multipart/form-data" autocomplete="off" method="post" action="{{route('content.update', $content)}}" class="flex flex-col">
            @csrf
            @method('PUT')

            <x-input-group>
                @if ($content->fileType == FileType::Pdf && $content->report)
                    <x-input-field id="name" type="text" label="Name" value="{{$content->name}}" />
                @endif

                <x-input-field id="title" type="text" label="Titel" value="{{$content->title}}" />

                {{-- Show editor when the content has text OR when this is an email template (even if empty) --}}
                @if (isset($content->text) || (isset($content->name) && Str::startsWith($content->name, 'email-')))
                    {{-- If the content is a member or file show a simple text input, otherwise prefer the editor.
                         Also allow editor for email templates such as 'email-nieuwe-activiteit'. --}}
                    @if ($content->type == 'bestuurslid' || $content->type == 'file')
                        <x-input-field id="description" type="text" label="Beschrijving" required :value="$content->text" />
                    @elseif(isset($content->name) && Str::startsWith($content->name, 'email-'))
                        {{-- Force rich editor for email templates so admins can add body text. --}}
                        <x-input-field id="description" type="editor" label="Beschrijving" height="h-72" required :value="$content->text" />
                        {{-- No extra field required here anymore --}}
                    @else
                        <x-input-field id="description" type="editor" label="Beschrijving" height="h-72" required :value="$content->text" />
                    @endif
                @endif

                {{-- If the type is "bestuurslid" make an image upload, else default to pdf upload --}}
                @if (isset($content->fileType))
                    @if ($content->fileType == FileType::Image)
                        <x-input-field id="image" type="file" accept="image/*" label="Afbeelding" action="displayUploadedFile(this, 'image-preview'); displayUploadedFileName(this)"/>
                    @elseif ($content->fileType == FileType::Pdf)
                        <x-input-field id="pdf" type="file" accept=".pdf" label="Pdf" action="displayUploadedFile(this, 'pdf-preview'); displayUploadedFileName(this)"/>
                        @if ($content->report)
                            <x-input-field id="report-image" type="file" accept="image/*" label="Omslag afbeelding (optioneel)" action="displayUploadedFileName(this)"/>
                        @endif
                    @endif
                    <p class="">Huidige bestandsnaam: {{ basename($content->file) }} </p>
                @endif

                @if ($content->fileType == FileType::Image)
                    {{-- Placeholder for the image --}}
                    <img alt="{{$content->title}}" id="image-preview" class="mx-auto min-h-0 min-w-0 max-w-72 bg-[rgba(0,0,0,0.15)] rounded-md size-full p-2" src="{{$content->file}}">
                @elseif ($content->fileType == FileType::Pdf)
                    {{-- Placeholder for the pdf --}}
                    <embed src="{{ $content->file }}#toolbar=0" id="pdf-preview" type="application/pdf" width="100%" height="600px" />
                @endif
            </x-input-group>
        </form>
    </x-zijpalm-div>
    <x-zijpalm-button type="submit" label="Bewerken" form="content-update" center="horizontal" variant="obvious" />
</x-page-wrapper>

<script>
    // Safety: remove any leftover UI nodes that contain the old 'Extra tekst' label.
    document.addEventListener('DOMContentLoaded', function () {
        const texts = [
            'Extra tekst voor Nieuwe Activiteit',
            'Extra tekst voor Nieuwe Activiteit (optioneel)'
        ];

        texts.forEach(t => {
            // Find elements containing the label text and remove their parent input group if present
            Array.from(document.querySelectorAll('label, *')).forEach(el => {
                if (!el.textContent) return;
                if (el.textContent.trim().includes(t)) {
                    // remove nearest input wrapper or the element itself
                    const wrapper = el.closest('.input-group') || el.closest('x-input-field') || el.parentElement;
                    if (wrapper) wrapper.remove();
                    else el.remove();
                }
            });
        });
    });
</script>
