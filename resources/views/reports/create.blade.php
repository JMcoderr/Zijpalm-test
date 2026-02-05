@push('scripts')
    @vite('resources/js/forms/display-uploaded-file.js')
    @vite('resources/js/forms/display-uploaded-file-name.js')
    @vite('resources/js/forms/toggle-required.js')

    {{-- Alpine logic separated to keep things dry, toggleRequired functions loaded --}}
    <script>
        document.addEventListener(
            'DOMContentLoaded',
            function(){
                // Initially assign alpine data on load
                let alpineData = Alpine.$data(document.querySelector('[x-ref="form"]'));

                // Update the alpine variable when something in the window changes
                window.addEventListener('change', () => alpineData = Alpine.$data(document.querySelector('[x-ref="form"]')));
            }
        );
    </script>
@endpush

<x-page-wrapper page="Aanmaken Verslag">
    {{-- Show errors, if any --}}
    @if($errors->any())
        <x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" width="min-w-min" error id="error-messages" onclick="this.remove()">
            <ul class="text-center">
                @foreach($errors->all() as $error)
                    <li class="">{{$error}}</li>
                @endforeach
            </ul>
        </x-zijpalm-div>
        <script>
            setTimeout(function(){
                const errorDiv = document.getElementById('error-messages');
                if(errorDiv){
                    errorDiv.remove();
                }
            }, 5000);
        </script>
    @endif

    <x-zijpalm-div title="Nieuw Verslag" color="transparent" :editable=false />

    {{-- Show title if the report is for a specific activity --}}
{{--    @if(isset($activity))--}}
{{--        <x-zijpalm-div id="report-activity" title="Voor {{$activity->title}}" color="transparent" :editable=false />--}}
{{--    @endif--}}

    {{-- Div containing form, x-data initializes available report types and the currently selected one, x-init changes selected type to activity if it's available. --}}
    <x-zijpalm-div id="report-form-container" color="light" :editable=false width="w-full sm:w-3/4 lg:w-1/2" height="max-h-[90dvh]" x-ref="form">

        {{-- Form for creating a report --}}
        <form id="report-form" enctype="multipart/form-data" autocomplete="off" method="post" action="{{route('report.store')}}" class="flex flex-col w-full">
            @csrf
            {{-- Select, selects 'Activiteiten' and disables it if an activity is given --}}
{{--            <x-input-field id="report-type" type="select" label="Type verslag" :options="['Activiteit', 'Jaar']" optionOnly x-model="selectedType" :fakedisabled="isset($activity) || $yearly" :selected="isset($activity) ? 'Activiteit' : ($yearly ? 'Jaar' : '')" x-on:change="selectedType = $event.target.value; reportTitle = ''" required />--}}

            {{-- Exists for both types --}}
            <x-input-field id="report-title" type="text" label="Titel" required/>
            {{-- Show file upload for yearly reports --}}
            <div class="flex flex-col">
                <x-input-field id="report-file" type="file" accept=".pdf" label="Bestand" action="displayUploadedFile(this, 'pdf-preview'); displayUploadedFileName(this)"/>
                <embed id="pdf-preview" type="application/pdf" class="hidden max-w-full rounded-xl mx-2 my-1 aspect-[1/1.41]"/>
            </div>
            <x-input-field  id="report-is-year" type="select" :options="$yearsAvailable" optionOnly label="Is jaar verslag"/>
        </form>
    </x-zijpalm-div>

    <x-zijpalm-button type="submit" label="Aanmaken" form="report-form" center="horizontal" variant="obvious"/>

</x-page-wrapper>
