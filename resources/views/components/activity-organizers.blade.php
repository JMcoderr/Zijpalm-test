@props(['users', 'selected' => []])

@php
    $selectedIds = collect(old('organizer_user_ids', old('organizer_user_ids_present') ? [] : $selected))
        ->filter(fn ($id) => is_scalar($id))->map(fn ($id) => (string) $id)->all();
    $searchNames = $users->map(fn ($user) => mb_strtolower($user->name . ' ' . $user->email))->values();
@endphp

<fieldset data-organizer-picker class="col-span-full min-w-0 p-2 text-start" x-data="{ search: '', selected: {{ \Illuminate\Support\Js::from($selectedIds) }}, names: {{ \Illuminate\Support\Js::from($searchNames) }} }">
    <legend class="w-full text-start font-bold">Medeorganisatoren</legend>
    <input type="hidden" name="organizer_user_ids_present" value="1">
    <div class="flex flex-wrap items-center gap-3">
        <div class="relative min-w-0 flex-1">
            <label for="organizer-search" class="sr-only">Zoek medeorganisator</label>
            <flux:icon.magnifying-glass class="pointer-events-none absolute left-3 top-1/2 size-4 -translate-y-1/2 text-zinc-500"/>
            <input id="organizer-search" type="search" x-model="search" placeholder="Zoek op naam of e-mail" class="h-10 w-full rounded-md border border-zinc-300 bg-white pl-9 pr-3 text-sm text-black focus:outline-0 focus:ring-2 focus:ring-zijpalm-400">
        </div>
        <span class="shrink-0 text-sm text-zijpalm-700" aria-live="polite"><span x-text="selected.length">{{ count($selectedIds) }}</span> geselecteerd</span>
    </div>
    <div class="mt-2 grid max-h-56 grid-cols-1 gap-1 overflow-y-auto border-y border-zinc-300 bg-zinc-100/60 py-1 text-black sm:grid-cols-2">
        @forelse($users as $organizerUser)
            <label class="flex min-w-0 cursor-pointer items-start gap-3 rounded-md px-3 py-2 transition-colors hover:bg-white has-checked:bg-zijpalm-50 focus-within:ring-2 focus-within:ring-zijpalm-400" x-show="$el.textContent.toLowerCase().includes(search.toLowerCase().trim())">
                <input type="checkbox" name="organizer_user_ids[]" value="{{ $organizerUser->id }}" x-model="selected" @checked(in_array((string) $organizerUser->id, $selectedIds, true)) class="mt-1 size-4 shrink-0 accent-zijpalm-600">
                <span class="min-w-0">
                    <span class="block break-words text-sm font-medium">{{ $organizerUser->name }}</span>
                    <span class="block break-all text-xs text-zinc-600">{{ $organizerUser->email }}</span>
                </span>
            </label>
        @empty
            <p class="col-span-full p-3 text-sm text-zinc-600">Geen leden beschikbaar.</p>
        @endforelse
        @if($users->isNotEmpty())
            <p x-cloak x-show="!names.some(name => name.includes(search.toLowerCase().trim()))" class="col-span-full p-3 text-sm text-zinc-600">Geen leden gevonden.</p>
        @endif
    </div>
</fieldset>