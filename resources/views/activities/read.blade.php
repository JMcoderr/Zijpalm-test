{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-page-wrapper page="{{$activity->title}}"
                x-data="{
                    signupModal: {{$errors->signupActivity->any() ? 'true' : 'false'}},
                    reminderMailModal: {{$errors->reminderMail->any() ? 'true' : 'false'}},
                    addParticipantModal: {{$errors->addParticipant->any() ? 'true' : 'false'}},
                    announcementMailModal: {{$errors->announcementMail->any() ? 'true' : 'false'}}}"
                x-init="$watch('signupModal', v => document.body.classList.toggle('overflow-hidden', v));
                        $watch('reminderMailModal', v => document.body.classList.toggle('overflow-hidden', v));
                        $watch('addParticipantModal', v => document.body.classList.toggle('overflow-hidden', v));
                        $watch('announcementMailModal', v => document.body.classList.toggle('overflow-hidden', v));">
                <x-zijpalm-div title="{{$activity->title}}" color="transparent" :editable="false"/>
        {{-- Show buttons if a user is logged in --}}
            @if(auth()->user())

        {{-- Action buttons --}}
            <div class="flex flex-row flex-wrap gap-4 justify-center items-stretch mt-2 w-full">
                {{-- Sign up button (always left) --}}
                @if(!$activity->isCancelled() && $activity->type !== \App\ActivityType::Weekly && !$activity->userApplied() && $activity->period->registration)
                    <x-zijpalm-button :label="$activity->participants->capacity === 0 ? 'Meld je aan voor de reservelijst' : 'Meld je aan!'" type="action" variant="obvious" x-on:click="signupModal = true" class="h-full"/>
                @endif
                {{-- Unsubscribe button if applicable --}}
                @if(!$activity->isCancelled() && $activity->type !== \App\ActivityType::Weekly && $activity->userApplied() && $activity->period->cancellation)
                    <x-zijpalm-button label="Afmelden" variant="obvious" x-on:click.prevent="$el.nextElementSibling.submit()" class="h-full"/>
                    <form method="POST" action="{{route('application.destroy', $activity->userApplied())}}" class="hidden"> @csrf @method('DELETE')</form>
                @endif

                {{-- Admin buttons --}}
                @if(auth()->user()?->isAdmin())
                    @if($activity->end?->isPast() && $activity->hasReport())
                        <x-zijpalm-button :href="route('report.show', $activity->report)" label="Bekijk verslag" variant="obvious" class="h-full"/>
                    @elseif($activity->end?->isPast() && !$activity->hasReport())
                        <x-zijpalm-button :href="route('report.create', $activity)" label="Creëer verslag" variant="obvious" class="h-full"/>
                    @endif
                    <x-zijpalm-button :href="route('activity.edit', $activity)" label="Bewerk activiteit" variant="obvious" type="redirect" class="h-full"/>
                    @if($activity->type === \App\ActivityType::Archived)
                        <form id="activity-restore" method="POST" action="{{route('activity.restore', $activity)}}" onsubmit="return confirm('Weet je zeker dat je deze activiteit terug wilt zetten naar de normale activiteiten?')" class="h-full">
                            @csrf
                            <x-zijpalm-button type="submit" form="activity-restore" label="Terug naar activiteiten" variant="obvious" class="h-full"/>
                        </form>
                    @elseif($activity->type !== \App\ActivityType::Cancelled)
                        <form id="activity-mark-late" method="POST" action="{{route('activity.markLate', $activity)}}" onsubmit="return confirm('Weet je zeker dat je deze activiteit handmatig op Te laat wilt zetten?')" class="h-full">
                            @csrf
                            <x-zijpalm-button type="submit" form="activity-mark-late" label="Te laat zetten" variant="obvious" class="h-full"/>
                        </form>
                    @endif
                    <x-zijpalm-button label="Verstuur aankondiging" type="action" x-on:click="announcementMailModal = true" variant="obvious" class="h-full"/>
                    <x-zijpalm-modal text="Activiteit aankondiging" livewire include="activity-announcement-mail" modal="announcementMailModal" :variables="['activity' => $activity, 'errors' => $errors->announcementMail->all()]"/>
                    <form id="activity-copy" method="POST" action="{{route('activity.copy', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} te kopiëren. Doorgaan?')" class="h-full">
                        @csrf
                        <x-zijpalm-button type="submit" form="activity-copy" label="Kopieer activiteit" variant="obvious" class="h-full"/>
                    </form>
                    <form id="activity-destroy" method="POST" action="{{route('activity.destroy', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} te annuleren. Alle ingeschreven leden krijgen hun inschrijvingskosten teruggestort.')" class="h-full">
                        @csrf
                        @method('delete')
                        <x-zijpalm-button type="submit" form="activity-destroy" label="Annuleer activiteit" variant="obvious" class="h-full"/>
                    </form>
                    <form id="activity-permanentDelete" method="POST" action="{{route('activity.permanentDelete', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} permanent te verwijderen. Betaalde inschrijfgelden worden niet teruggestort. Deze actie kan niet ongedaan worden gemaakt.')" class="h-full">
                        @csrf
                        @method('delete')
                        <x-zijpalm-button type="submit" form="activity-permanentDelete" label="Permanent verwijderen" variant="obvious" class="h-full"/>
                    </form>
                @endif
            </div>

            {{-- To Do: Show when updating applications becomes a thing as well --}}
            {{-- Modal, only shown if not applied --}}
            @if(auth()->user() && !$activity->userApplied())
                <x-zijpalm-modal title="Aanmeldformulier" text="{{$activity->title}}" livewire include="application-form" modal="signupModal" :variables="['activity' => $activity, 'errors' => $errors->signupActivity->all()]"/>
            @endif
        @endif

        {{-- General Activity Info & Participation List (Admin/User) --}}
        <div class="flex flex-wrap flex-col lg:flex-row gap-5 flex-1">

            {{-- General Activity information and image --}}
            <x-zijpalm-div :editable="false" width="w-full lg:w-1/2" padding="p-0" class="flex flex-1 self-stretch overflow-hidden min-h-fit md:h-[40rem]">
                <div class="flex flex-col md:flex-row lg:flex-col flex-1">

                    {{-- Activity Image --}}
                    {{-- Don't remove the min-h-0. --}}
                    <div class="flex flex-col min-h-0 flex-1 sm:min-w-1/2">
                        <img src="{{$activity->image}}" class="size-full object-cover flex-1">
                    </div>

                    {{-- Activity details --}}
                    <div class="flex flex-col justify-evenly gap-2 p-2 lg:flex-row lg:flex-wrap">
                        {{-- Organiser(s) and location --}}
                        <div class="flex flex-col lg:flex-1 self-stretch">
                            {{-- Title --}}
                            <span class="text-xl font-bold">Algemeen</span>

                            <div class="flex justify-evenly items-center flex-wrap bg-[rgba(0,0,0,0.1)] rounded-xl p-1 gap-x-2 flex-1">
                                {{-- Data --}}
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold"> Organisator(en) </span>
                                    <span> {{$activity->organizer}} </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold"> Locatie </span>
                                    <span class="text-wrap"> {{$activity->location}} </span>
                                </div>
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold"> Gratis organisator plekken </span>
                                    @php
                                        $usedFreeOrganizers = $activity->applications->where('status', App\ApplicationStatus::Active)->filter(function($app) use ($activity) {
                                            return stripos($activity->organizer, $app->user->name) !== false;
                                        })->count();
                                    @endphp
                                    @if($activity->free_organizer_count)
                                        <span>{{ $usedFreeOrganizers }} / {{ $activity->free_organizer_count }}</span>
                                    @else
                                        <span></span>
                                    @endif
                                </div>
                            </div>
                        </div>

                        @auth
                            @if($activity->type === \App\ActivityType::Weekly)
                            {{-- Price --}}
                            <div class="flex flex-col self-stretch">
                                <span class="font-bold text-xl">Prijs</span>
                                <div class="flex flex-col flex-wrap bg-[rgba(0,0,0,0.1)] rounded-xl p-4 gap-x-2 flex-1">
                                    {{-- Show 'Gratis' if price is 0, otherwise show the formatted price --}}
                                    <span> {{ $activity->price > 0 ? formatPrice($activity->price) : 'Gratis' }} </span>
                                </div>
                            </div>
                            @else
                            {{-- Participant info --}}
                            <div class="flex flex-col lg:flex-1 self-stretch">
                                {{-- Title --}}
                                <span class="text-xl font-bold">Deelnemers</span>

                                <div class="flex justify-evenly items-center flex-wrap bg-[rgba(0,0,0,0.1)] rounded-xl p-1 gap-x-2 flex-1">
                                    @if($activity->maxParticipants > 0)
                                        {{-- Participant capacity --}}
                                        <div class="flex flex-col">
                                            <span class="font-bold"> Open plekken </span>
                                            <span> {{$activity->participants->capacity}} {{$activity->participants->reserve->isNotEmpty() ? '('.$activity->participants->reserve->count().' reserves)' : ''}}</span>
                                        </div>
                                        @else
                                        {{-- Current participants --}}
                                        <div class="flex flex-col">
                                            <span class="font-bold"> Aantal </span>
                                            <span> {{$activity->participants->all->count()}}</span>
                                        </div>
                                    @endif

                                    @if($activity->maxGuests > 0)
                                        {{-- Maximum guests --}}
                                        <div class="flex flex-col">
                                            <span class="font-bold"> Gastenlimiet </span>
                                            <span> {{$activity->maxGuests}} </span>
                                        </div>
                                    @endif


                                    {{-- Price per participant: show 'Gratis' if price is 0 --}}
                                    <div class="flex flex-col">
                                        <span class="font-bold">Prijs per deelnemer</span>
                                        @if($activity->hasAnyCost())
                                            <span>{{ $activity->price > 0 ? formatPrice($activity->price) : '' }}</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endauth

                        {{-- Start & End times and dates --}}
                        <div class="flex flex-col lg:flex-1 self-stretch">
                            {{-- Title --}}
                            <span class="text-xl font-bold">Wanneer</span>

                            <div class="flex justify-evenly items-center flex-wrap bg-[rgba(0,0,0,0.1)] rounded-xl p-1 gap-x-2 flex-1">
                                {{-- Start and end dates/times --}}
                                <div class="flex flex-col">
                                    @if($activity->type === App\ActivityType::Weekly)
                                        <span class="text-lg font-bold">Dag van de week</span>
                                        @php
                                            $dayNames = ['maandag', 'dinsdag', 'woensdag', 'donderdag', 'vrijdag', 'zaterdag', 'zondag'];
                                            $dayIndex = ($activity->start?->dayOfWeekIso ?? 1) - 1;
                                        @endphp
                                        <span>{{ ucfirst($dayNames[$dayIndex] ?? '') }}</span>
                                    @else
                                        <span class="text-lg font-bold">Datum</span>
                                        <span>
                                            {{$activity->formattedDatesAndTimes->activity->start->date}}
                                            @if($activity->formattedDatesAndTimes->activity->end->date !== $activity->formattedDatesAndTimes->activity->start->date)
                                                t/m {{$activity->formattedDatesAndTimes->activity->end->date}}
                                            @endif
                                        </span>
                                    @endif
                                </div>

                                @if($activity->type != App\ActivityType::Weekly)
                                    {{-- Registration period --}}
                                    <div class="flex flex-col">
                                        <span class="text-lg font-bold">Aanmeldperiode</span>
                                        <span> {{$activity->formattedDatesAndTimes->registration->full}} </span>
                                    </div>
                                    {{-- Cancellation period --}}
                                    <div class="flex flex-col">
                                        @if($activity->cancellationEnd)
                                            <span class="text-lg font-bold">Kosteloos annuleren kan t/m</span>
                                            <span> {{$activity->formattedDatesAndTimes->cancellation->end->date}}</span>
                                        @else
                                            <span class="text-lg font-bold">Annuleren is niet mogelijk.</span>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </x-zijpalm-div>

            {{-- Registrations for the Activity, Admin Only --}}
            @if(auth()->user()?->isAdmin() && $activity->type !== \App\ActivityType::Weekly)
                {{-- Registrations --}}
                <x-zijpalm-div :editable="false" color="light" 
                    title="Aanmeldingen ({{ $applications->reduce(function($carry, $application) { return $carry + 1 + $application->guests->count(); }, 0) }}{{$activity->maxParticipants ? '/' . $activity->maxParticipants : ''}})" 
                    width="w-full lg:max-w-1/2" class="flex flex-col flex-1 self-stretch max-h-[40rem] md:h-[40rem]">
                    <div class="absolute top-2 right-2 flex items-center gap-2">
                        {{-- Admins may add any member by hand, paid or not --}}
                        <flux:tooltip content="Deelnemer toevoegen">
                            <flux:button x-on:click="addParticipantModal = true" variant="primary" class="bg-linear-to-t from-zijpalm-700 to-zijpalm-500 inset-shadow-400 border-none size-8! hover:scale-110 hover:brightness-110 duration-300 rounded-xl! group">
                                <flux:icon name="user-plus" class="text-zinc-100 size-7! stroke-2 group-hover:scale-105 duration-600 p-0.5"/>
                            </flux:button>
                        </flux:tooltip>
                        @if($applications->isNotEmpty())
                            <!-- remindermailModal and button are linked together-->
                            <x-zijpalm-modal text="Herinnering versturen" livewire include="reminder-mail" modal="reminderMailModal" :variables="['activity'=>$activity, 'errors'=>$errors->reminderMail->all()]"></x-zijpalm-modal>
                            <flux:tooltip content="Verstuur herinnering">
                                <flux:button x-on:click="reminderMailModal =  true" variant="primary" class="bg-linear-to-t from-zijpalm-700 to-zijpalm-500 inset-shadow-400 border-none size-8! hover:scale-110 hover:brightness-110 duration-300 rounded-xl! group">
                                    <flux:icon name="mail" class="text-zinc-100 size-7! stroke-2 group-hover:scale-105 duration-600 p-0.5"/>
                                </flux:button>
                            </flux:tooltip>
                            <flux:tooltip content="Download deelnemerslijst">
                                <flux:button :href="route('admin.activities.download', $activity)" variant="primary" class="bg-linear-to-t from-zijpalm-700 to-zijpalm-500 inset-shadow-400 border-none size-8! hover:scale-110 hover:brightness-110 duration-300 rounded-xl! group">
                                    <flux:icon name="arrow-down-tray" class="text-zinc-100 size-7! stroke-2 group-hover:scale-105 duration-600 p-0.5"/>
                                </flux:button>
                            </flux:tooltip>
                        @endif
                    </div>

                    {{-- Form to put a member on the participant list without a payment --}}
                    <x-zijpalm-modal title="Deelnemer toevoegen" text="{{$activity->title}}" modal="addParticipantModal">
                        @if($errors->addParticipant->any())
                            <div class="bg-red-100 border border-red-400 text-red-700 rounded-md p-3 text-left mb-3">
                                <p class="font-bold">Er ging iets mis:</p>
                                <ul class="list-disc ps-5">
                                    @foreach($errors->addParticipant->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        @if($assignableUsers->isEmpty())
                            <p class="text-lg text-left">Alle leden staan al op de deelnemerslijst.</p>
                        @else
                            @php
                                // Small list for the search box, the search value is prepared here so the page stays simple.
                                $memberOptions = $assignableUsers->map(fn ($member) => [
                                    'id' => $member->id,
                                    'name' => $member->name,
                                    'email' => $member->email,
                                    'search' => mb_strtolower($member->name),
                                ])->values();
                            @endphp

                            <form id="add-participant-form" method="POST" action="{{ route('application.adminStore', $activity) }}" class="flex flex-col gap-6 text-left text-lg"
                                  x-data="{
                                      query: '',
                                      chosen: null,
                                      listOpen: false,
                                      members: {{ \Illuminate\Support\Js::from($memberOptions) }},
                                      results() {
                                          const needle = this.query.trim().toLowerCase();
                                          if (needle === '') return this.members;
                                          return this.members.filter(member => member.search.includes(needle));
                                      }
                                  }">
                                @csrf
                                <input type="hidden" name="user_id" :value="chosen ? chosen.id : ''">

                                {{-- Step 1: pick the member --}}
                                <div class="flex flex-col gap-2">
                                    <p class="text-2xl font-bold">Stap 1 &ndash; Kies een lid</p>
                                    <label class="font-bold" for="add-participant-search">Typ (een deel van) de naam</label>
                                    <input id="add-participant-search" type="text" x-model="query" x-on:input="listOpen = true" autocomplete="off" placeholder="Bijvoorbeeld: Jan" class="bg-zinc-100 rounded-md shadow-sm px-3 h-11 text-lg focus:outline-0 focus:ring-2 focus:ring-zijpalm-400">

                                    {{-- The list starts closed so the form stays short --}}
                                    <button type="button" x-on:click="listOpen = !listOpen"
                                            class="flex items-center justify-between gap-2 bg-zinc-200 rounded-md px-3 h-11 border border-zinc-400 cursor-pointer hover:bg-zijpalm-100">
                                        <span class="font-bold" x-text="listOpen ? 'Verberg de ledenlijst' : 'Toon de ledenlijst (' + results().length + ')'"></span>
                                        <flux:icon name="chevron-down" x-show="!listOpen"/>
                                        <flux:icon name="chevron-up" x-show="listOpen" x-cloak/>
                                    </button>

                                    <div x-show="listOpen" x-cloak class="max-h-64 overflow-y-auto flex flex-col gap-1 bg-zinc-100 rounded-md p-2 border border-zinc-300">
                                        <template x-for="member in results()" :key="member.id">
                                            <button type="button" x-on:click="chosen = member; listOpen = false"
                                                    class="text-left px-3 py-2 rounded-md cursor-pointer hover:bg-zijpalm-100 border border-transparent"
                                                    :class="chosen && chosen.id === member.id ? 'bg-zijpalm-200 border-zijpalm-500 font-bold' : ''">
                                                <span class="block text-lg" x-text="member.name"></span>
                                                <span class="block text-sm opacity-60" x-text="member.email"></span>
                                            </button>
                                        </template>
                                        <p x-show="results().length === 0" class="px-3 py-2 opacity-70">Geen lid gevonden met deze naam.</p>
                                    </div>

                                    <div class="rounded-md px-3 py-2 border" :class="chosen ? 'bg-emerald-50 border-emerald-400' : 'bg-zinc-100 border-zinc-300'">
                                        <span x-show="!chosen">Er is nog geen lid gekozen. Typ een naam of open de ledenlijst en klik op een naam.</span>
                                        <span x-show="chosen" x-cloak>Gekozen lid: <strong x-text="chosen ? chosen.name : ''"></strong></span>
                                    </div>
                                </div>

                                {{-- Step 2: how many people --}}
                                <div class="flex flex-col gap-2">
                                    <p class="text-2xl font-bold">Stap 2 &ndash; Met hoeveel personen komt dit lid?</p>
                                    <p class="text-base opacity-70">Tel het lid zelf mee. Komt het lid alleen? Vul dan 1 in.</p>
                                    <input id="add-participant-count" type="number" name="participants" min="1" max="50" step="1" value="{{ old('participants', 1) }}" required class="bg-zinc-100 rounded-md shadow-sm px-3 h-11 w-28 text-lg focus:outline-0 focus:ring-2 focus:ring-zijpalm-400">
                                </div>

                                {{-- Step 3: status in plain words --}}
                                <div class="flex flex-col gap-2">
                                    <p class="text-2xl font-bold">Stap 3 &ndash; Hoe zet je dit lid op de lijst?</p>
                                    @php
                                        $statusChoices = [
                                            'active' => ['Gewoon op de deelnemerslijst', 'Het lid doet mee. Er hoeft niets (meer) betaald te worden.'],
                                            'pending' => ['Op de lijst, maar moet nog betalen', 'Het lid doet mee, maar de betaling is nog niet binnen.'],
                                            'reserve' => ['Op de reservelijst', 'Het lid doet alleen mee als er een plek vrijkomt.'],
                                        ];
                                        $chosenStatus = old('status', 'active');
                                    @endphp
                                    @foreach($statusChoices as $value => $choice)
                                        <label class="flex items-start gap-3 bg-zinc-100 rounded-md p-3 border border-zinc-300 cursor-pointer hover:bg-zijpalm-100">
                                            <input type="radio" name="status" value="{{ $value }}" class="mt-1 size-5" @checked($chosenStatus === $value) required>
                                            <span>
                                                <span class="block font-bold">{{ $choice[0] }}</span>
                                                <span class="block text-base opacity-70">{{ $choice[1] }}</span>
                                            </span>
                                        </label>
                                    @endforeach
                                </div>

                                {{-- Optional note --}}
                                <div class="flex flex-col gap-2">
                                    <label class="text-2xl font-bold" for="add-participant-comment">Opmerking</label>
                                    <p class="text-base opacity-70">Dit veld mag leeg blijven.</p>
                                    <input id="add-participant-comment" type="text" name="comment" maxlength="500" value="{{ old('comment') }}" placeholder="Bijvoorbeeld: contant betaald op kantoor" class="bg-zinc-100 rounded-md shadow-sm px-3 h-11 text-lg focus:outline-0 focus:ring-2 focus:ring-zijpalm-400">
                                </div>

                                <x-zijpalm-button type="submit" form="add-participant-form" label="Lid toevoegen" variant="obvious" center="horizontal"
                                                  x-bind:disabled="!chosen" x-bind:class="!chosen ? 'opacity-50 pointer-events-none' : ''"/>
                            </form>
                        @endif
                    </x-zijpalm-modal>

                    @if($applications->isEmpty())
                        <div class="opacity-50"> Er zijn nog geen aanmeldingen voor deze activiteit </div>
                    @else
                        {{-- List of registered users --}}
                        <div name="List" class="flex flex-col gap-y-1 overflow-auto px-2">

                            {{-- List header --}}
                            <div class="flex font-bold">
                                <span class="flex-1">Datum</span>
                                <span class="flex-1">Naam</span>
                                <span class="flex-1">Telefoon</span>
                                <span class="flex-1 hidden md:flex">Mail</span>
                                <span class="w-8 shrink-0"></span>
                            </div>

                            <flux:separator/>

                            {{-- List items --}}
                            @foreach($applications as $application)
                                {{-- If there are reserves, add a header before the first of them --}}
                                @if($reserves->isNotEmpty() && $reserves->first() == $application)
                                    <flux:separator/>
                                    <div class="flex font-bold self-center">Reserves</div>
                                @elseif($pending->isNotEmpty() && $pending->first() == $application)
                                    <flux:separator/>
                                    <div class="flex font-bold self-center">In afwachting van betaling</div>
                                @endif
                                <div class="bg-[rgba(0,0,0,0.05)] p-1 rounded-xl flex flex-col gap-y-1 {{$reserves->contains($application) ? 'opacity-50' : ''}} {{$pending->contains($application) ? 'opacity-75' : ''}}">

                                    {{-- User data --}}
                                    <div class="flex items-center gap-x-1">
                                        <div class="flex flex-1 min-w-0 px-2 gap-x-2 rounded-full overflow-clip bg-linear-to-t from-zijpalm-500 to-zijpalm-300 inset-shadow-zijpalm-200 text-zinc-100">
                                            <span class="flex-1 truncate">{{ $application->created_at?->format('d/m/Y') }}</span>
                                            <span class="flex-1 truncate">{{$application->user?->name ?? 'Onbekend lid'}}</span>
                                            <span class="flex-1 truncate">{{formatPhoneNumber($application->phone)}}</span>
                                            <span class="flex-1 hidden md:flex truncate">{{$application->email}}</span>
                                        </div>
                                        {{-- Admins can take anyone off the list, paid or not --}}
                                        <form method="POST" action="{{route('application.destroy', $application)}}" class="shrink-0"
                                              onsubmit="return confirm('Weet je zeker dat je {{ $application->user?->name ?? 'deze deelnemer' }} van de deelnemerslijst wilt halen? Een eventuele betaling wordt teruggestort.')">
                                            @csrf
                                            @method('DELETE')
                                            <flux:tooltip content="Verwijder van deelnemerslijst">
                                                <flux:button type="submit" variant="primary" icon="trash" square="true" class="cursor-pointer bg-linear-to-t from-zijpalm-700 to-zijpalm-500 border-none size-6!"/>
                                            </flux:tooltip>
                                        </form>
                                    </div>

                                    {{-- Guest data --}}
                                    @foreach($application->guests as $guest)
                                        <div class="flex items-center gap-x-1">
                                            <div class="flex flex-1 min-w-0 px-2 gap-x-2 rounded-full overflow-clip bg-linear-to-t from-zinc-300 to-zinc-200 inset-shadow-zinc-50 border-b border-[rgba(0,0,0,0.15)]">
                                                <span class="flex-1 truncate">{{ $application->created_at?->format('d/m/Y') }}</span>
                                                <span class="flex-1 truncate">{{$guest->name}}</span>
                                                <span class="flex-1 truncate">{{formatPhoneNumber($guest->phone)}}</span>
                                                <span class="flex-1 hidden md:flex truncate">{{$guest->email}}</span>
                                            </div>
                                            <span class="w-6 shrink-0"></span>
                                        </div>
                                    @endforeach
                                </div>
                            @endforeach
                        </div>
                    @endif
                </x-zijpalm-div>
            @endif

            {{-- Activity Description + Cost overview (admin) --}}
            @if(auth()->user()?->isAdmin())
                @php
                    $activeApplications = $applications->where('status', App\ApplicationStatus::Active)->values();
                    $freeOrganizerApplications = $activeApplications->filter(fn ($application) => $application->isFreeOrganizerApplication());
                    $confirmedParticipants = $activeApplications->sum('participants');
                    $freeOrganizerCount = $freeOrganizerApplications->count();
                    $paidParticipants = $activeApplications->sum(fn ($application) => $application->isFreeOrganizerApplication() ? max(0, $application->participants - 1) : $application->participants);
                    $pendingParticipants = $pending->sum('participants');
                    $reserveParticipants = $reserves->sum('participants');
                    $baseRevenue = $activeApplications->sum(fn ($application) => $application->calculateBaseCost());
                    $extrasRevenue = $activeApplications->sum(fn ($application) => $application->calculateExtrasCost());
                    $totalDue = $activeApplications->sum(fn ($application) => $application->calculateTotalCost());
                    $totalPaid = $applications->sum(fn ($application) => $application->calculateTotalPaid());
                    $manualFinanceEntries = collect($activity->manual_income_entries ?? [])->filter(function ($entry) {
                        $description = trim((string) ($entry['description'] ?? ''));
                        $hasDescription = $description !== '';
                        $hasQuantity = array_key_exists('quantity', $entry) && $entry['quantity'] !== null && $entry['quantity'] !== '';
                        $hasUnitPrice = array_key_exists('unit_price', $entry) && $entry['unit_price'] !== null && $entry['unit_price'] !== '';

                        return $hasDescription && $hasQuantity && $hasUnitPrice;
                    })->values();
                    $hasManualFinance = $manualFinanceEntries->isNotEmpty();
                    $manualFinanceTotal = $manualFinanceEntries->sum(fn ($entry) => (float) ($entry['total'] ?? 0));
                @endphp

                <div class="flex flex-col lg:flex-row gap-5 w-full">
                    <x-zijpalm-div title="Beschrijving" :editable="false" width="w-full lg:w-2/3" class="">
                        <div class="bg-[rgba(0,0,0,0.15)] rounded-md flex flex-col p-2 text-left space-y-3 whitespace-pre-line">
                            {!!$activity->descriptionHTML!!}
                        </div>
                    </x-zijpalm-div>

                    <x-zijpalm-div title="Kostenoverzicht" :editable="false" width="w-full lg:w-1/3" class="">
                        <div class="bg-[rgba(0,0,0,0.15)] rounded-md overflow-x-auto p-0 text-left">
                            <table class="w-full text-sm">
                                <thead class="bg-[rgba(0,0,0,0.2)] border-b border-[rgba(0,0,0,0.3)]">
                                    <tr>
                                        <th class="text-left font-semibold p-2">Omschrijving</th>
                                        <th class="text-right font-semibold p-2">Aantal</th>
                                        <th class="text-right font-semibold p-2">Per stuk</th>
                                        <th class="text-right font-semibold p-2">Totaal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-[rgba(0,0,0,0.15)]">
                                    @if($hasManualFinance)
                                        @foreach($manualFinanceEntries as $entry)
                                            <tr class="hover:bg-[rgba(0,0,0,0.05)]">
                                                <td class="p-2 font-semibold">{{ ($entry['description'] ?? '') !== '' ? $entry['description'] : 'Zonder omschrijving' }}</td>
                                                <td class="p-2 text-right">{{ $entry['quantity'] ?? 0 }}</td>
                                                <td class="p-2 text-right">€{{ number_format((float) $entry['unit_price'], 2, ',', '.') }}</td>
                                                <td class="p-2 text-right font-bold">{{ formatPrice((float) ($entry['total'] ?? 0)) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-[rgba(0,0,0,0.06)] font-semibold border-t-2 border-[rgba(255,255,255,0.7)]">
                                            <td colspan="3" class="p-2 text-right">Totaal:</td>
                                            <td class="p-2 text-right font-bold">{{ formatPrice((float) $manualFinanceTotal) }}</td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>
                    </x-zijpalm-div>
                </div>

                <x-zijpalm-div title="Wie heeft wat betaald?" :editable="false" width="w-full" class="mt-5">
                    <div class="bg-[rgba(0,0,0,0.15)] rounded-md overflow-x-auto p-0 text-left">
                        <table class="w-full text-sm">
                            <thead class="bg-[rgba(0,0,0,0.2)] border-b border-[rgba(0,0,0,0.3)]">
                                <tr>
                                    <th class="text-left font-semibold p-2">Naam</th>
                                    <th class="text-left font-semibold p-2">Status</th>
                                    <th class="text-right font-semibold p-2">Basis</th>
                                    <th class="text-right font-semibold p-2">Extra's</th>
                                    <th class="text-right font-semibold p-2">Totaal</th>
                                    <th class="text-right font-semibold p-2">Betaald</th>
                                    <th class="text-right font-semibold p-2">Actie</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-[rgba(0,0,0,0.15)]">
                                @foreach($applications as $application)
                                    @php
                                        $baseCost = $application->calculateBaseCost();
                                        $extraCost = $application->calculateExtrasCost();
                                        $totalCost = $application->calculateTotalCost();
                                        $paidAmount = $application->calculateTotalPaid();
                                        $statusLabel = match($application->status->value) {
                                            'active' => 'Actief',
                                            'pending' => 'In afwachting betaling',
                                            'reserve' => 'Reserve',
                                            default => 'Geannuleerd',
                                        };
                                    @endphp
                                    <tr class="align-top hover:bg-[rgba(0,0,0,0.05)] {{ $application->status === App\ApplicationStatus::Reserve ? 'opacity-60' : '' }} {{ $application->status === App\ApplicationStatus::Pending ? 'opacity-80' : '' }}">
                                        <td class="p-2">
                                            <div class="font-semibold">{{ $application->user?->name ?? 'Onbekend lid' }}</div>
                                            @if($application->guests->isNotEmpty())
                                                <div class="text-xs opacity-70">Gasten: {{ $application->guests->pluck('name')->join(', ') }}</div>
                                            @endif
                                            @if($application->isFreeOrganizerApplication())
                                                <div class="text-xs font-semibold text-emerald-600">Gratis organisator</div>
                                            @endif
                                        </td>
                                        <td class="p-2">{{ $statusLabel }}</td>
                                        <td class="p-2 text-right">{{ formatPrice($baseCost) }}</td>
                                        <td class="p-2 text-right">
                                            <div>{{ formatPrice($extraCost) }}</div>
                                            @if($application->answers->filter(fn ($answer) => getAnswerPrice($answer) > 0)->isNotEmpty())
                                                <details class="mt-1 text-xs text-left">
                                                    <summary class="cursor-pointer select-none opacity-70">Bekijk extra's</summary>
                                                    <ul class="mt-1 space-y-1">
                                                        @foreach($application->answers as $answer)
                                                            @php($answerPrice = getAnswerPrice($answer))
                                                            @if($answerPrice > 0)
                                                                <li class="flex justify-between gap-3">
                                                                    <span>{{ $answer->question->query }}</span>
                                                                    <span>{{ formatPrice($answerPrice) }}</span>
                                                                </li>
                                                            @endif
                                                        @endforeach
                                                    </ul>
                                                </details>
                                            @endif
                                        </td>
                                        <td class="p-2 text-right font-semibold">{{ formatPrice($totalCost) }}</td>
                                        <td class="p-2 text-right font-semibold">{{ formatPrice($paidAmount) }}</td>
                                        <td class="p-2 text-right">
                                            <div class="flex items-center justify-end gap-2">
                                                @if($application->status === App\ApplicationStatus::Pending)
                                                    <form id="mark-paid-{{ $application->id }}" method="POST" action="{{ route('application.update', $application) }}" class="inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="active">
                                                    </form>
                                                    <x-zijpalm-button form="mark-paid-{{ $application->id }}" type="submit" label="Zet op betaald" variant="default" />
                                                @endif
                                                {{-- Removing works for everyone, paid or not --}}
                                                <form method="POST" action="{{ route('application.destroy', $application) }}"
                                                      onsubmit="return confirm('Weet je zeker dat je {{ $application->user?->name ?? 'deze deelnemer' }} van de deelnemerslijst wilt halen? Een eventuele betaling wordt teruggestort.')">
                                                    @csrf
                                                    @method('DELETE')
                                                    <flux:tooltip content="Verwijder van deelnemerslijst">
                                                        <flux:button type="submit" variant="primary" icon="trash" square="true" class="cursor-pointer bg-linear-to-t from-zijpalm-700 to-zijpalm-500 border-none"/>
                                                    </flux:tooltip>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-zijpalm-div>
            @else
                {{-- Activity Description --}}
                <x-zijpalm-div title="Beschrijving" :editable="false" width="w-full" class="">
                    <div class="bg-[rgba(0,0,0,0.15)] rounded-md flex flex-col p-2 text-left space-y-3 whitespace-pre-line">
                        {!!$activity->descriptionHTML!!}
                    </div>
                </x-zijpalm-div>
            @endif
        </div>
</x-page-wrapper>
