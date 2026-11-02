<x-page-wrapper page="Bestanden" x-data="{ search: '', uploadDocumentsModal: {{ $errors->any() ? 'true' : 'false' }}, chosenFiles: [], uploading: false }">
    <x-zijpalm-div title="Bestanden" color="transparent" :editable="false" />

    <x-zijpalm-div color="light" :editable="false" width="w-full" padding="p-4 sm:p-6" class="min-w-0 text-left" data-document-library>
        @if(session('success'))
            <p role="status" class="mb-4 border-l-4 border-emerald-600 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">{{ session('success') }}</p>
        @endif

        <div class="mb-6 flex flex-wrap items-center justify-between gap-3 border-b border-zinc-300/80 pb-5">
            <div class="relative w-full sm:max-w-md">
                <label for="document-search" class="sr-only">Zoek bestand</label>
                <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-500"/>
                <input id="document-search" type="text" x-model="search" x-ref="documentSearch" placeholder="Zoek op bestandsnaam of uploader" class="h-11 w-full rounded-lg border border-zinc-300 bg-white pl-9 pr-10 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-500 focus:border-zijpalm-500 focus:outline-0 focus:ring-2 focus:ring-zijpalm-400">
                <button type="button" x-cloak x-show="search" x-on:click="search = ''; $refs.documentSearch.focus()" aria-label="Zoekopdracht wissen" class="absolute right-2 top-1/2 flex size-7 -translate-y-1/2 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-zijpalm-500">
                    <flux:icon.x-mark class="size-4"/>
                </button>
            </div>
            <flux:button icon="plus" variant="primary" x-on:click="uploadDocumentsModal = true" class="h-11">Bestanden toevoegen</flux:button>
        </div>

        <div class="grid min-w-0 grid-cols-1 gap-8 xl:grid-cols-2">
            @foreach(['word' => 'Word', 'excel' => 'Excel'] as $category => $label)
                @php
                    $categoryDocuments = $documents->get($category, collect());
                    $searchNames = $categoryDocuments->map(fn ($document) => mb_strtolower($document->original_name . ' ' . $document->uploader?->name))->values();
                @endphp
                <section data-document-category="{{ $category }}" class="min-w-0" x-data="{ names: {{ \Illuminate\Support\Js::from($searchNames) }} }">
                    <div class="mb-3 flex items-center gap-3 border-b border-zinc-300/80 pb-3">
                        <span @class(['flex size-11 shrink-0 items-center justify-center rounded-lg', 'bg-sky-100 text-sky-800' => $category === 'word', 'bg-emerald-100 text-emerald-800' => $category === 'excel'])>
                            <flux:icon :name="$category === 'word' ? 'document-text' : 'table-cells'" class="size-6"/>
                        </span>
                        <div class="min-w-0">
                            <h2 class="text-lg font-bold leading-tight text-zinc-900">{{ $label }}</h2>
                            <p class="mt-0.5 text-xs font-medium text-zinc-500">{{ $category === 'word' ? 'DOC / DOCX' : 'XLS / XLSX' }}</p>
                        </div>
                        <span @class(['ml-auto shrink-0 rounded-full px-3 py-1 text-xs font-semibold tabular-nums', 'bg-sky-100 text-sky-900' => $category === 'word', 'bg-emerald-100 text-emerald-900' => $category === 'excel'])>{{ $categoryDocuments->count() }} {{ $categoryDocuments->count() === 1 ? 'bestand' : 'bestanden' }}</span>
                    </div>

                    @forelse($categoryDocuments as $document)
                        <div data-document-row data-search="{{ mb_strtolower($document->original_name . ' ' . $document->uploader?->name) }}" x-show="$el.dataset.search.includes(search.toLowerCase().trim())" class="group mb-2 flex min-w-0 items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-3 shadow-sm transition-colors hover:border-zinc-300 hover:bg-zinc-50 sm:gap-4 sm:px-4">
                            <span @class(['hidden size-10 shrink-0 items-center justify-center rounded-lg sm:flex', 'bg-sky-50 text-sky-700' => $category === 'word', 'bg-emerald-50 text-emerald-700' => $category === 'excel']) aria-hidden="true">
                                <flux:icon :name="$category === 'word' ? 'document-text' : 'table-cells'" class="size-5"/>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.documents.download', $document) }}" class="block break-words text-sm font-semibold leading-snug text-zijpalm-900 hover:text-zijpalm-700 hover:underline focus-visible:outline-2 focus-visible:outline-zijpalm-500">{{ $document->original_name }}</a>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-600">
                                    <span class="inline-flex items-center gap-1"><flux:icon.archive-box class="size-3.5 text-zinc-400"/>{{ $document->size >= 1048576 ? number_format($document->size / 1048576, 1, ',', '.') . ' MB' : number_format($document->size / 1024, 0, ',', '.') . ' KB' }}</span>
                                    <time datetime="{{ $document->created_at->toIso8601String() }}" class="inline-flex items-center gap-1"><flux:icon.calendar-days class="size-3.5 text-zinc-400"/>{{ $document->created_at->format('d-m-Y') }}</time>
                                    <span class="inline-flex min-w-0 items-center gap-1 break-words"><flux:icon.user class="size-3.5 shrink-0 text-zinc-400"/>{{ $document->uploader?->name ?? 'Onbekende uploader' }}</span>
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1 border-l border-zinc-200 pl-2">
                                <flux:tooltip content="Download bestand">
                                    <flux:button :href="route('admin.documents.download', $document)" :aria-label="'Download ' . $document->original_name" icon="arrow-down-tray" variant="ghost" square size="sm" class="text-zinc-700 hover:bg-zijpalm-50 hover:text-zijpalm-800" />
                                </flux:tooltip>
                                <form method="POST" action="{{ route('admin.documents.destroy', $document) }}" onsubmit="return confirm('Weet je zeker dat je dit bestand wilt verwijderen?')">
                                    @csrf
                                    @method('DELETE')
                                    <flux:tooltip content="Verwijder bestand">
                                        <flux:button type="submit" :aria-label="'Verwijder ' . $document->original_name" icon="trash" variant="ghost" square size="sm" class="text-red-700!" />
                                    </flux:tooltip>
                                </form>
                            </div>
                        </div>
                    @empty
                        <div class="flex min-h-28 flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-white/60 px-4 py-6 text-center">
                            <flux:icon :name="$category === 'word' ? 'document-text' : 'table-cells'" class="mb-2 size-6 text-zinc-400"/>
                            <p class="text-sm font-medium text-zinc-700">Nog geen {{ $label }}-bestanden</p>
                            <p class="mt-1 text-xs text-zinc-500">Toegevoegde bestanden verschijnen hier.</p>
                        </div>
                    @endforelse
                    @if($categoryDocuments->isNotEmpty())
                        <div x-cloak x-show="!names.some(name => name.includes(search.toLowerCase().trim()))" class="flex min-h-28 flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-white/60 px-4 py-6 text-center">
                            <flux:icon.magnifying-glass class="mb-2 size-5 text-zinc-400"/>
                            <p class="text-sm font-medium text-zinc-700">Geen {{ $label }}-bestanden gevonden</p>
                            <p class="mt-1 text-xs text-zinc-500">Pas je zoekopdracht aan of wis het zoekveld.</p>
                        </div>
                    @endif
                </section>
            @endforeach
        </div>
    </x-zijpalm-div>

    <x-zijpalm-modal modal="uploadDocumentsModal" width="w-[min(36rem,92vw)] max-w-[92vw]" role="dialog" aria-modal="true" aria-labelledby="document-upload-title" x-trap.inert.noscroll="uploadDocumentsModal" x-on:keydown.escape.window="uploadDocumentsModal = false">
        <h2 id="document-upload-title" class="mb-4 text-left text-xl font-bold">Bestanden toevoegen</h2>
        @if($errors->any())
            <div role="alert" class="mb-4 border-l-4 border-red-600 bg-red-50 p-3 text-left text-sm text-red-900">
                <ul class="list-disc pl-4">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif
        <form method="POST" action="{{ route('admin.documents.store') }}" enctype="multipart/form-data" x-on:submit="uploading = true" class="flex flex-col gap-4 text-left">
            @csrf
            <div>
                <label for="document-files" class="mb-2 block text-sm font-semibold">Word- of Excel-bestanden</label>
                <input id="document-files" type="file" name="files[]" multiple required accept=".doc,.docx,.xls,.xlsx" x-on:change="chosenFiles = Array.from($event.target.files, file => file.name)" class="block w-full min-w-0 rounded-md border border-zinc-300 bg-white p-2 text-sm text-black file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zijpalm-100 file:px-3 file:py-2 file:text-zijpalm-800" aria-describedby="document-file-limits">
                <p id="document-file-limits" class="mt-2 text-xs text-zinc-600">DOC, DOCX, XLS, XLSX. Maximaal 10 bestanden, 20 MB per bestand.</p>
            </div>
            <ul x-cloak x-show="chosenFiles.length" class="max-h-40 list-disc overflow-y-auto pl-5 text-sm">
                <template x-for="(file, index) in chosenFiles" :key="index">
                    <li x-text="file" class="break-words py-1"></li>
                </template>
            </ul>
            <div class="flex flex-wrap justify-end gap-2">
                <flux:button type="button" x-on:click="uploadDocumentsModal = false" x-bind:disabled="uploading">Annuleren</flux:button>
                <flux:button type="submit" icon="arrow-up-tray" variant="primary" x-bind:disabled="uploading" class="min-w-32">
                    <span x-text="uploading ? 'Uploaden...' : 'Uploaden'">Uploaden</span>
                </flux:button>
            </div>
        </form>
    </x-zijpalm-modal>
</x-page-wrapper>