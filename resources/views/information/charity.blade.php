<x-page-wrapper page="Lief & Leed">
    <x-zijpalm-div color="transparent" :id="$banner->id" :name="$banner->name" :title="$banner->title" :editable=true :editables="['Titel']" />

    <x-zijpalm-div color="zijpalm" :id="$info->id" :name="$info->name" :title="$info->title" :text="$info->textHTML" :editable=true :editables="['Titel', 'Tekst']" />
    <x-zijpalm-div color="light" :id="$contributions->id" :name="$contributions->name" :title="$contributions->title" :text="$contributions->textHTML" :editable=true :editables="['Titel', 'Tekst']" />

    <div class="flex flex-col md:flex-row justify-center self-center gap-5 lg:w-5/6">
        <x-zijpalm-div class="self-stretch gap-y-2.5 pb-3" :id="$participants->id" :name="$participants->name" :editables="['Tekst']" flex="flex flex-col items-center">
            <flux:icon.user-group class="size-16" />
            <p>{!! $participants->textHTML !!}</p>
            <x-zijpalm-button href="https://82393.afasinsite.nl/hrm-a-z/lief-en-leed" target="blank" :label="$participants->title" margin="mt-auto" />
        </x-zijpalm-div>
        <x-zijpalm-div class="self-stretch gap-y-2.5 pb-3" :id="$contact->id" :name="$contact->name" :editables="['Tekst']" flex="flex flex-col items-center">
            <flux:icon.document-text class="size-16" />
            <p>{!! $contact->textHTML !!}</p>
{{--            TODO: change to production url--}}
            <x-zijpalm-button href="http://liefenleed.zijpalm.nl" target="blank" :label="$contact->title" margin="mt-auto" />
        </x-zijpalm-div>
    </div>
</x-page-wrapper>
