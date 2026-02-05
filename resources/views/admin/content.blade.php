<x-page-wrapper page="Admin Content">
    <x-zijpalm-div :editable=false color="light">
        <x-admin.layout :heading="__('Content')" :subheading="__('Bekijk en beheer alle contentstukken van de website')">
            {{-- Creates a dropdown with all the content pieces for each content group --}}
            @foreach ($contentGroups as $name => $contents)
                @if (count($contents) > 0)
                    <x-dropdown :title="$name" :open="$loop->first">
                        @foreach ($contents as $content)
                        {{-- If the type is "bestuurslid", show the title for more obvious names --}}
                            <x-admin.card
                                :title="$content->type === 'bestuurslid' ? $content->title : $content->displayName"
                                :href="route('content.edit', $content)"
                                :buttons="array_merge(
                                    ['edit' => route('content.edit', $content)],
                                    $content->type === 'bestuurslid'
                                        ? ['delete' => route('content.destroy', $content)]
                                        : []
                                )"
                            />
                        @endforeach
                    </x-dropdown>
                @endif
            @endforeach
        </x-admin.layout>
    </x-zijpalm-div>
</x-page-wrapper>
