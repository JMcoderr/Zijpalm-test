@push('scripts')
    @vite('resources/js/forms/display-uploaded-file.js')
    @vite('resources/js/forms/display-uploaded-file-name.js')
@endpush

<x-page-wrapper page="Nieuw {{ucfirst($type)}}">
    @if (session('success'))
        <x-zijpalm-div color="light" :editable=false :text="session('success')" width="w-max" success/>
    @endif

    <x-zijpalm-div title="Nieuw {{ucfirst($type)}}" color="transparent" :editable=false/>

    <x-zijpalm-div color="light" :editable=false width="w-full sm:w-1/2">
        <form id="content-create" enctype="multipart/form-data" autocomplete="off" method="post" action="{{route('content.store')}}" class="flex flex-col">
            @csrf
            <input type="hidden" id="type" name="type" value="{{$type}}"/>

            <x-input-group>
                <x-input-field id="title" type="text" label="Titel" required/>

                {{-- If the type is "bestuurslid" make an text description, else default to the editor --}}
                @if ($type === 'bestuurslid')
                    <x-input-field id="description" type="text" label="Beschrijving" required/>
                @else
                    <x-input-field id="description" type="editor" label="Beschrijving" height="h-72" required/>
                @endif
                
                {{-- If the type is "bestuurslid" make an image upload, else default to pdf upload --}}
                @if ($type === 'bestuurslid')
                    <x-input-field id="image" type="file" accept="image/*" label="Afbeelding" required action="displayUploadedFile(this, 'image-preview'); displayUploadedFileName(this)"/>
                @else
                    <x-input-field id="pdf" type="file" accept=".pdf" label="Pdf" required action="displayUploadedFile(this, 'pdf-preview'); displayUploadedFileName(this)"/>
                @endif

                {{-- Placeholder for the image --}}
                <img alt="Placeholder" id="image-preview" class="hidden mx-auto min-h-0 min-w-0 max-w-72">
                <embed id="pdf-preview" type="application/pdf" width="100%" height="600px" class="hidden mx-auto min-h-0 min-w-0" />
            </x-input-group>

            @if ($errors->any())
                <div class="text-red-500">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>                
            @endif
        </form>
    </x-zijpalm-div>
    <x-zijpalm-button type="submit" label="Aanmaken" form="content-create" center="horizontal" variant="obvious" />

</x-page-wrapper>