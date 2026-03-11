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

    <form id="signup-form" class="flex flex-col gap-y-2.5" method="POST" enctype="multipart/form-data" autocomplete="off" action="{{route('application.store', $activity)}}" >
        @csrf

        {{-- Personal info --}}
        <x-input-group id="contact-info" title="Contactgegevens" grid="grid md:grid-cols-2 grid-cols-1">
            <x-input-field type="text" label="Voornaam" id="first-name" value="{{auth()->user()->firstName}}" required disabled/>
            <x-input-field type="text" label="Achternaam" id="last-name" value="{{auth()->user()->lastName}}" required disabled/>
            <x-input-field type="text" label="Telefoonnummer" id="phone" value="{{ '06' . substr(preg_replace('/\D/', '', (string) (auth()->user()->phone ?? '')), -8) }}" required tooltip="Voer alstublieft het nummer in waar u tijdens de activiteit op gecontacteerd kunt worden"/>
            <x-input-field type="email" label="E-mail" id="email" value="{{auth()->user()->email}}" required/>
        </x-input-group>

        <flux:separator/>

        {{-- Guests --}}
        {{-- Show only if guests are allowed and if the current participants is below maxParticipants minus 1 (the user) --}}
        @if($activity->maxGuests > 0 && $activity->participants->all->count() <= ($activity->maxParticipants - 1))
            <x-input-group id="guests" title="Introducees">
                <livewire:guest-builder :$activity :guests="old('guests')"/>
            </x-input-group>
            <flux:separator/>
        @endif

        {{-- Questions --}}
        @if($questions->isNotEmpty())
            <x-input-group id="questions" title="Vragen" grid="grid sm:grid-cols-2 grid-cols-1">
                @foreach($questions as $question)
                    <x-input-field :attributes="$this->questionAttributes($question)"/>
                @endforeach
            </x-input-group>
            <flux:separator/>
        @endif

        <div class="grid grid-cols-1 sm:grid-cols-2">
            {{-- Comment --}}
            <x-input-group id="comment" title="Opmerking" grid="grid grid-cols-1">
                <x-input-field type="text" id="comment"/>
            </x-input-group>

            {{-- Costs --}}
            <x-input-group id="payment" title="Te Betalen" grid="grid grid-cols-3 p-2">
                <div class="flex flex-col">
                    <span class="rounded-sm shadow-sm h-7 text-black font-medium bg-zinc-100 px-1 py-0.5" wire:model="costs.base">
                        @if(isset($showFreeOrganizerBase) && $showFreeOrganizerBase && $participants == 1)
                            &euro;,-
                        @else
                            {{formatPrice($costs['base'])}}
                        @endif
                    </span>
                    <span class="font-bold">Basis</span>
                </div>
                <div class="flex flex-col">
                    <span class="rounded-sm shadow-sm h-7 text-black font-medium bg-zinc-100 px-1 py-0.5" wire:model="costs.options">{{formatPrice($costs['options'])}}</span>
                    <span class="font-bold">Opties</span>
                </div>
                <div class="flex flex-col">
                    <span class="rounded-sm shadow-sm h-7 text-black font-medium bg-zinc-100 px-1 py-0.5" wire:model="costs.total">{{formatPrice($costs['total'])}}</span>
                    <span class="font-bold">Totaal</span>
                </div>
            </x-input-group>
        </div>
    </form>
</div>
<x-zijpalm-button form="signup-form" type="submit" label="Aanmelden" variant="obvious" center="horizontal"/>
</div>

<script>
// function setupTooltip(inputSelector, message) {
//     const input = document.querySelector(inputSelector);
//     if (!input) return;

//     const tooltip = document.createElement('div');
//     tooltip.textContent = message;
//     tooltip.style.cssText = 'position:absolute;background:#000;color:#fff;padding:4px 8px;border-radius:3px;font-size:12px;z-index:1000;display:none;';
//     document.body.appendChild(tooltip);

//     function toggleTooltip(show) {
//         tooltip.style.display = show ? 'block' : 'none';
//         if (show) {
//             const rect = input.getBoundingClientRect();
//             tooltip.style.top = (window.scrollY + rect.bottom + 4) + 'px';
//             tooltip.style.left = (window.scrollX + rect.left) + 'px';
//         }
//     }

//     input.addEventListener('focus', () => toggleTooltip(true));
//     input.addEventListener('blur', () => toggleTooltip(false));
//     window.addEventListener('resize', () => toggleTooltip(false));
// }

// window.addEventListener('DOMContentLoaded', () => {
//     setupTooltip('#phone', 'Use a number you can actually be called on—not a scam hotline.');
// });
</script>
