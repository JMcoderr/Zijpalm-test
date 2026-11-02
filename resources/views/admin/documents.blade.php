<x-page-wrapper page="Bestanden" x-data="{ search: '', uploadDocumentsModal: {{ $errors->any() ? 'true' : 'false' }}, chosenFiles: [], selectedFiles: [], uploadMode: 'files', uploading: false }">
    <x-zijpalm-div title="Bestanden" color="transparent" :editable="false" />

    <x-zijpalm-div color="light" :editable="false" width="w-full" padding="p-4 sm:p-6" class="min-w-0 text-left" data-document-explorer>
        @if(session('success'))
            <p role="status" class="mb-4 border-l-4 border-emerald-600 bg-emerald-50 px-3 py-2 text-sm text-emerald-900">{{ session('success') }}</p>
        @endif

        <div class="mb-5 flex flex-wrap items-center justify-between gap-3 border-b border-zinc-300/80 pb-5">
            <div class="flex min-w-0 flex-wrap items-center gap-2 text-sm">
                <nav aria-label="Mappennavigatie" class="flex min-w-0 flex-wrap items-center gap-2">
                    <a href="{{ route('admin.documents.index') }}" @class(['font-semibold text-zijpalm-800 hover:underline', 'text-zinc-900' => $currentFolder === '']) @if($currentFolder === '') aria-current="page" @endif>Alle bestanden</a>
                    @foreach($breadcrumbs as $breadcrumb)
                        <flux:icon.chevron-right class="size-4 shrink-0 text-zinc-400" aria-hidden="true"/>
                        @if($loop->last)
                            <span class="max-w-full break-words font-semibold text-zinc-900" aria-current="page">{{ $breadcrumb['name'] }}</span>
                        @else
                            <a href="{{ route('admin.documents.index', ['folder' => $breadcrumb['path']]) }}" class="max-w-full break-words text-zijpalm-800 hover:underline">{{ $breadcrumb['name'] }}</a>
                        @endif
                    @endforeach
                </nav>
                <span class="text-xs text-zinc-500">{{ $totalFiles }} {{ $totalFiles === 1 ? 'bestand' : 'bestanden' }} totaal</span>
            </div>
            <div class="flex w-full flex-wrap items-center justify-between gap-3 sm:w-auto sm:justify-end">
                <div class="relative w-full sm:max-w-md">
                    <label for="document-search" class="sr-only">Zoek in deze map</label>
                    <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-500"/>
                    <input id="document-search" type="text" x-model="search" x-ref="documentSearch" placeholder="Zoek in deze map" class="h-11 w-full rounded-lg border border-zinc-300 bg-white pl-9 pr-10 text-sm text-zinc-900 shadow-sm placeholder:text-zinc-500 focus:border-zijpalm-500 focus:outline-0 focus:ring-2 focus:ring-zijpalm-400">
                    <button type="button" x-cloak x-show="search" x-on:click="search = ''; $refs.documentSearch.focus()" aria-label="Zoekopdracht wissen" class="absolute right-2 top-1/2 flex size-7 -translate-y-1/2 items-center justify-center rounded-md text-zinc-500 transition-colors hover:bg-zinc-100 hover:text-zinc-900 focus-visible:outline-2 focus-visible:outline-zijpalm-500">
                        <flux:icon.x-mark class="size-4"/>
                    </button>
                </div>
                <flux:button icon="plus" variant="primary" x-on:click="uploadDocumentsModal = true" class="h-11">Uploaden</flux:button>
            </div>
        </div>

        <div data-document-items x-data="{ names: {{ \Illuminate\Support\Js::from($searchNames) }} }">
            @if($folders->isNotEmpty())
                <section aria-labelledby="document-folders-title" class="mb-7">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 id="document-folders-title" class="text-base font-bold text-zinc-900">Mappen</h2>
                        <span class="text-xs text-zinc-500">{{ $folders->count() }} {{ $folders->count() === 1 ? 'map' : 'mappen' }}</span>
                    </div>
                    <div class="grid min-w-0 grid-cols-1 gap-2 sm:grid-cols-2 xl:grid-cols-3">
                        @foreach($folders as $folder)
                            <a data-folder-row data-search="{{ mb_strtolower($folder['name']) }}" x-show="$el.dataset.search.includes(search.toLowerCase().trim())" href="{{ route('admin.documents.index', ['folder' => $folder['path']]) }}" class="group flex min-w-0 items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-3 shadow-sm transition-colors hover:border-sky-300 hover:bg-sky-50 focus-visible:outline-2 focus-visible:outline-sky-600 sm:px-4">
                                <span class="flex size-10 shrink-0 items-center justify-center rounded-lg bg-sky-100 text-sky-800">
                                    <flux:icon.folder class="size-5"/>
                                </span>
                                <span class="min-w-0 flex-1">
                                    <span class="block break-words text-sm font-semibold text-zijpalm-900 group-hover:underline">{{ $folder['name'] }}</span>
                                    <span class="mt-0.5 block text-xs text-zinc-500">{{ $folder['count'] }} {{ $folder['count'] === 1 ? 'bestand' : 'bestanden' }}</span>
                                </span>
                                <flux:icon.chevron-right class="size-4 shrink-0 text-zinc-400"/>
                            </a>
                        @endforeach
                    </div>
                </section>
            @endif

            @if($folderDocuments->isNotEmpty())
                <section aria-labelledby="document-files-title">
                    <div class="mb-3 flex items-center justify-between gap-3 border-b border-zinc-300/80 pb-3">
                        <h2 id="document-files-title" class="text-base font-bold text-zinc-900">Bestanden</h2>
                        <span class="text-xs text-zinc-500">{{ $folderDocuments->count() }} {{ $folderDocuments->count() === 1 ? 'bestand' : 'bestanden' }}</span>
                    </div>
                    @foreach($folderDocuments as $document)
                        @php
                            $extension = strtoupper(pathinfo($document->original_name, PATHINFO_EXTENSION));
                            $searchValue = mb_strtolower($document->original_name . ' ' . $document->uploader?->name);
                        @endphp
                        <div data-document-row data-search="{{ $searchValue }}" x-show="$el.dataset.search.includes(search.toLowerCase().trim())" class="group mb-2 flex min-w-0 items-center gap-3 rounded-lg border border-zinc-200 bg-white px-3 py-3 shadow-sm transition-colors hover:border-zinc-300 hover:bg-zinc-50 sm:gap-4 sm:px-4">
                            <span @class(['hidden size-10 shrink-0 items-center justify-center rounded-lg sm:flex', 'bg-red-50 text-red-700' => $extension === 'PDF', 'bg-sky-50 text-sky-700' => in_array($extension, ['DOC', 'DOCX'], true), 'bg-emerald-50 text-emerald-700' => in_array($extension, ['XLS', 'XLSX'], true)]) aria-hidden="true">
                                <flux:icon.document-text class="size-5"/>
                            </span>
                            <div class="min-w-0 flex-1">
                                <a href="{{ route('admin.documents.download', $document) }}" class="block break-words text-sm font-semibold leading-snug text-zijpalm-900 hover:text-zijpalm-700 hover:underline focus-visible:outline-2 focus-visible:outline-zijpalm-500">{{ $document->original_name }}</a>
                                <p class="mt-1.5 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs text-zinc-600">
                                    <span @class(['rounded px-1.5 py-0.5 text-[10px] font-bold', 'bg-red-100 text-red-800' => $extension === 'PDF', 'bg-sky-100 text-sky-800' => in_array($extension, ['DOC', 'DOCX'], true), 'bg-emerald-100 text-emerald-800' => in_array($extension, ['XLS', 'XLSX'], true)])>{{ $extension }}</span>
                                    <span class="inline-flex items-center gap-1"><flux:icon.archive-box class="size-3.5 text-zinc-400"/>{{ $document->size >= 1048576 ? number_format($document->size / 1048576, 1, ',', '.') . ' MB' : number_format($document->size / 1024, 0, ',', '.') . ' KB' }}</span>
                                    <time datetime="{{ $document->created_at->toIso8601String() }}" class="inline-flex items-center gap-1"><flux:icon.calendar-days class="size-3.5 text-zinc-400"/>{{ $document->created_at->format('d-m-Y') }}</time>
                                    <span class="inline-flex min-w-0 items-center gap-1 break-words"><flux:icon.user class="size-3.5 shrink-0 text-zinc-400"/>{{ $document->uploader?->name ?? 'Onbekende uploader' }}</span>
                                </p>
                            </div>
                            <div class="flex shrink-0 items-center gap-1 border-l border-zinc-200 pl-2">
                                <flux:tooltip content="Download bestand">
                                    <flux:button :href="route('admin.documents.download', $document)" :aria-label="'Download ' . $document->original_name" icon="arrow-down-tray" variant="ghost" square size="sm" class="text-zinc-700 hover:bg-zijpalm-50 hover:text-zijpalm-800" />
                                </flux:tooltip>
                                <form method="POST" action="{{ route('admin.documents.destroy', ['document' => $document, 'folder' => $currentFolder]) }}" onsubmit="return confirm('Weet je zeker dat je dit bestand wilt verwijderen?')">
                                    @csrf
                                    @method('DELETE')
                                    <flux:tooltip content="Verwijder bestand">
                                        <flux:button type="submit" :aria-label="'Verwijder ' . $document->original_name" icon="trash" variant="ghost" square size="sm" class="text-red-700!" />
                                    </flux:tooltip>
                                </form>
                            </div>
                        </div>
                    @endforeach
                </section>
            @endif

            @if($folderDocuments->isEmpty() && $folders->isEmpty())
                <div class="flex min-h-40 flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-white/60 px-4 py-8 text-center">
                    <flux:icon.folder-open class="mb-3 size-8 text-zinc-400"/>
                    <p class="text-sm font-semibold text-zinc-800">{{ $currentFolder === '' ? 'Nog geen bestanden of mappen' : 'Deze map is leeg' }}</p>
                    <p class="mt-1 text-xs text-zinc-500">Upload losse bestanden of kies een complete map.</p>
                    <button type="button" x-on:click="uploadDocumentsModal = true" class="mt-4 inline-flex items-center gap-2 rounded-md bg-zijpalm-700 px-4 py-2 text-sm font-semibold text-white hover:bg-zijpalm-800 focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-zijpalm-700">
                        <flux:icon.arrow-up-tray class="size-4"/>Uploaden
                    </button>
                </div>
            @endif

            @if($searchNames->isNotEmpty())
                <div x-cloak x-show="!names.some(name => name.includes(search.toLowerCase().trim()))" class="flex min-h-28 flex-col items-center justify-center rounded-lg border border-dashed border-zinc-300 bg-white/60 px-4 py-6 text-center">
                    <flux:icon.magnifying-glass class="mb-2 size-5 text-zinc-400"/>
                    <p class="text-sm font-medium text-zinc-700">Geen resultaten in deze map</p>
                    <p class="mt-1 text-xs text-zinc-500">Pas je zoekopdracht aan of wis het zoekveld.</p>
                </div>
            @endif
        </div>
    </x-zijpalm-div>

    <x-zijpalm-modal modal="uploadDocumentsModal" width="w-[min(36rem,92vw)] max-w-[92vw]" role="dialog" aria-modal="true" aria-labelledby="document-upload-title" x-trap.inert.noscroll="uploadDocumentsModal" x-on:keydown.escape.window="uploadDocumentsModal = false">
        <h2 id="document-upload-title" class="mb-2 text-left text-xl font-bold">Bestanden of map uploaden</h2>
        <p class="mb-4 text-left text-sm text-zinc-600">Doelmap: <span class="font-semibold text-zinc-900">{{ $currentFolder === '' ? 'Alle bestanden' : $currentFolder }}</span></p>
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
            <input type="hidden" name="current_folder" value="{{ $currentFolder }}">
            <template x-for="(file, index) in selectedFiles" :key="index">
                <input type="hidden" name="relative_paths[]" :value="uploadMode === 'folder' ? (file.webkitRelativePath || file.name) : file.name">
            </template>

            <div class="grid grid-cols-2 rounded-lg bg-zinc-100 p-1" role="group" aria-label="Uploadtype">
                <button type="button" x-on:click="if (uploadMode !== 'files') { $refs.documentFolder.value = ''; selectedFiles = []; chosenFiles = [] }; uploadMode = 'files'" x-bind:aria-pressed="uploadMode === 'files'" x-bind:class="uploadMode === 'files' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-600 hover:text-zinc-900'" class="flex min-h-10 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition-colors">
                    <flux:icon.document-text class="size-4"/>Bestanden
                </button>
                <button type="button" x-on:click="if (uploadMode !== 'folder') { $refs.documentFiles.value = ''; selectedFiles = []; chosenFiles = [] }; uploadMode = 'folder'" x-bind:aria-pressed="uploadMode === 'folder'" x-bind:class="uploadMode === 'folder' ? 'bg-white text-zinc-900 shadow-sm' : 'text-zinc-600 hover:text-zinc-900'" class="flex min-h-10 items-center justify-center gap-2 rounded-md px-3 py-2 text-sm font-semibold transition-colors">
                    <flux:icon.folder class="size-4"/>Hele map
                </button>
            </div>

            <div x-show="uploadMode === 'files'">
                <label for="document-files" class="mb-2 block text-sm font-semibold">Selecteer bestanden</label>
                <input id="document-files" x-ref="documentFiles" type="file" name="files[]" multiple required accept=".pdf,.doc,.docx,.xls,.xlsx" x-bind:disabled="uploadMode !== 'files'" x-on:change="selectedFiles = Array.from($event.target.files); chosenFiles = selectedFiles.map(file => file.name)" class="block w-full min-w-0 rounded-md border border-zinc-300 bg-white p-2 text-sm text-black file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zijpalm-100 file:px-3 file:py-2 file:text-zijpalm-800" aria-describedby="document-file-limits">
            </div>
            <div x-show="uploadMode === 'folder'">
                <label for="document-folder" class="mb-2 block text-sm font-semibold">Selecteer een complete map</label>
                <input id="document-folder" x-ref="documentFolder" type="file" name="files[]" multiple webkitdirectory directory accept=".pdf,.doc,.docx,.xls,.xlsx" required x-bind:disabled="uploadMode !== 'folder'" x-on:change="selectedFiles = Array.from($event.target.files); chosenFiles = selectedFiles.map(file => file.webkitRelativePath || file.name)" class="block w-full min-w-0 rounded-md border border-zinc-300 bg-white p-2 text-sm text-black file:mr-3 file:cursor-pointer file:rounded file:border-0 file:bg-zijpalm-100 file:px-3 file:py-2 file:text-zijpalm-800" aria-describedby="document-file-limits">
            </div>
            <p id="document-file-limits" class="-mt-2 text-xs text-zinc-600">PDF, DOC, DOCX, XLS en XLSX. Maximaal 100 bestanden per keer, 20 MB per bestand.</p>
            <ul x-cloak x-show="chosenFiles.length" class="max-h-40 list-disc overflow-y-auto rounded-md border border-zinc-200 bg-zinc-50 px-4 py-2 text-sm">
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