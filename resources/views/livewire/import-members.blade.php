<div class="size-full">
    <div class="flex flex-col py-2">
        {{-- Show errors, if any --}}
        @if(!empty($errors))
            <x-zijpalm-div color="light" title="Foutmelding(en)" :editable="false" error id="error-messages" onclick="this.remove()">
                <ul class="text-center">
                    @foreach($errors as $error)
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

        <form id="{{ $id }}" class="flex flex-col gap-y-2.5" method="POST" enctype="multipart/form-data" autocomplete="off" action="{{ $endpoint }}">
            @csrf

            <x-input-field type="file" label="Leden lijst" id="{{$id}}-members-list" required accept=".xlsx, .xls, .csv"/>
        </form>
    </div>
    <x-zijpalm-button form="{{ $id }}" type="submit" label="Inlezen" center="horizontal"/>
</div>
