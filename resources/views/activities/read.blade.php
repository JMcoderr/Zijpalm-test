<x-page-wrapper page="{{$activity->title}}"
                x-data="{
                    signupModal: {{$errors->signupActivity->any() ? 'true' : 'false'}},
                    reminderMailModal: {{$errors->reminderMail->any() ? 'true' : 'false'}},
                    announcementMailModal: {{$errors->announcementMail->any() ? 'true' : 'false'}}}"
                x-init="$watch('signupModal', v => document.body.classList.toggle('overflow-hidden', v));
                        $watch('reminderMailModal', v => document.body.classList.toggle('overflow-hidden', v));
                        $watch('announcementMailModal', v => document.body.classList.toggle('overflow-hidden', v));">
    <x-zijpalm-div title="{{$activity->title}}" color="transparent" :editable="false"/>
        {{-- If a user is logged in, show buttons --}}
        @if(auth()->user())
            {{-- Buttons --}}
            <div class="flex justify-center flex-wrap gap-4">
                {{-- If the activity is not cancelled, show the register and cancel buttons --}}
                @if(!$activity->isCancelled() && $activity->type !== \App\ActivityType::Weekly)
                    @if(!$activity->userApplied() && $activity->period->registration)
                        <x-zijpalm-button :label="$activity->participants->capacity === 0 ? 'Meld je aan voor de reservelijst' : 'Meld je aan!'" type="action" variant="obvious" x-on:click="signupModal = true"/>
                    @elseif($activity->userApplied() && $activity->period->cancellation)
                        <x-zijpalm-button label="Afmelden" variant="obvious" x-on:click.prevent="$el.nextElementSibling.submit()"/>
                        <form method="POST" action="{{route('application.destroy', $activity->userApplied())}}" class="hidden"> @csrf @method('DELETE')</form>
                    @endif
                @endif

                {{-- If the activity has ended, and there is a report, link to the report, or to create if one is not available --}}
                @if($activity->end?->isPast())
                    @if($activity->hasReport())
                        <x-zijpalm-button :href="route('report.show', $activity->report)" label="Bekijk verslag" variant="obvious"/>
                        @elseif(auth()->user()?->isAdmin() && !$activity->hasReport())
                        <x-zijpalm-button :href="route('report.create', $activity)" label="Creëer verslag" variant="obvious"/>
                    @endif
                @endif

                {{-- If the user is an admin, show the update activity button --}}
                @if(auth()->user()?->isAdmin() && !$activity->end?->isPast())
                    <x-zijpalm-button :href="route('activity.edit', $activity)" label="Bewerk activiteit" variant="obvious" type="redirect"/>
                    <x-zijpalm-button label="Verstuur aankondiging" type="action" x-on:click="announcementMailModal = true" variant="obvious"/>
                    <x-zijpalm-modal text="Activiteit aankondiging" livewire include="activity-announcement-mail" modal="announcementMailModal" :variables="['activity' => $activity, 'errors' => $errors->announcementMail->all()]"/>
                    {{-- If the activity has not ended --}}
                    @if(!$activity->end?->isPast())
                        <div class="flex flex-wrap gap-4 justify-center">
                            <form id="activity-copy" method="POST" action="{{route('activity.copy', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} te kopieren. Doorgaan?')">
                                @csrf
                                <x-zijpalm-button type="submit" form="activity-copy" label="Kopieer activiteit" variant="obvious"/>
                            </form>

                            <form id="activity-destroy" method="POST" action="{{route('activity.destroy', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} te annuleren. Alle ingeschreven leden krijgen hun inschrijvingskosten teruggestort.')">
                                @csrf
                                @method('delete')
                                <x-zijpalm-button type="submit" form="activity-destroy" label="Annuleer activiteit" variant="obvious"/>
                            </form>

                            <form id="activity-permanentDelete-inline" method="POST" action="{{route('activity.permanentDelete', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} permanent te verwijderen. Betaalde inschrijfgelden worden niet teruggestort. Deze actie kan niet ongedaan worden gemaakt.')">
                                @csrf
                                @method('delete')
                                <x-zijpalm-button type="submit" form="activity-permanentDelete-inline" label="Permanent verwijderen" variant="obvious"/>
                            </form>
                        </div>
                    @endif
                @endif

                @if(auth()->user()?->isAdmin() && ($activity->type === \App\ActivityType::Weekly || $activity->end?->isPast()))
                    <form id="activity-permanentDelete" method="POST" action="{{route('activity.permanentDelete', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} permanent te verwijderen. Betaalde inschrijfgelden worden niet teruggestort. Deze actie kan niet ongedaan worden gemaakt.')">
                        @csrf
                        @method('delete')
                        <x-zijpalm-button type="submit" form="activity-permanentDelete" label="Permanent verwijderen" variant="obvious"/>
                    </form>
                @endif
            </div>

            {{-- To Do: Show when Updating applications becomes a thing as well --}}
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
                                    <span class="text-lg font-bold">Datum</span>
                                    <span> {{$activity->formattedDatesAndTimes->activity->full}}</span>
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
                    @if($applications->isNotEmpty())
                        <div class="absolute top-2 right-2 flex items-center gap-2">
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
                        </div>
                    @endif

                    @if($applications->isEmpty())
                        <div class="opacity-50"> Er zijn nog geen aanmeldingen voor deze activiteit </div>
                    @else
                        {{-- List of registered users --}}
                        <div name="List" class="flex flex-col gap-y-1 overflow-auto px-2">

                            {{-- List header --}}
                            <div class="flex font-bold">
                                <span class="flex-1">Naam</span>
                                <span class="flex-1">Telefoon</span>
                                <span class="flex-1 hidden md:flex">Mail</span>
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
                                    <div class="flex px-2 gap-x-2 rounded-full overflow-clip bg-linear-to-t from-zijpalm-500 to-zijpalm-300 inset-shadow-zijpalm-200 text-zinc-100">
                                        <span class="flex-1 truncate">{{$application->user->name}}</span>
                                        <span class="flex-1 truncate">{{formatPhoneNumber($application->phone)}}</span>
                                        <span class="flex-1 hidden md:flex truncate">{{$application->email}}</span>
                                    </div>

                                    {{-- Guest data --}}
                                    @foreach($application->guests as $guest)
                                        <div class="flex px-2 gap-x-2 rounded-full overflow-clip bg-linear-to-t from-zinc-300 to-zinc-200 inset-shadow-zinc-50 border-b border-[rgba(0,0,0,0.15)]">
                                            <span class="flex-1 truncate">{{$guest->name}}</span>
                                            <span class="flex-1 truncate">{{formatPhoneNumber($guest->phone)}}</span>
                                            <span class="flex-1 hidden md:flex truncate">{{$guest->email}}</span>
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
                    $manualIncomeEntries = collect($activity->manual_income_entries ?? []);
                    $manualExpenseEntries = collect($activity->manual_expense_entries ?? []);
                    $hasManualFinance = $manualIncomeEntries->isNotEmpty() || $manualExpenseEntries->isNotEmpty();
                    $manualIncomeTotal = $manualIncomeEntries->sum(fn ($entry) => (float) ($entry['total'] ?? 0));
                    $manualExpenseTotal = $manualExpenseEntries->sum(fn ($entry) => (float) ($entry['total'] ?? 0));
                    $manualBalanceTotal = $manualIncomeTotal - $manualExpenseTotal;
                    $manualBudget = $activity->manual_budget !== null ? (float) $activity->manual_budget : null;
                @endphp

                <div class="flex flex-col lg:flex-row gap-5 w-full">
                    <x-zijpalm-div title="Beschrijving" :editable="false" width="w-full lg:w-2/3" class="">
                        <div class="bg-[rgba(0,0,0,0.15)] rounded-md flex flex-col p-2 text-left">
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
                                        @foreach($manualIncomeEntries as $entry)
                                            <tr class="hover:bg-[rgba(0,0,0,0.05)]">
                                                <td class="p-2 font-semibold">{{ $entry['description'] ?? 'Inkomst' }}</td>
                                                <td class="p-2 text-right">{{ $entry['quantity'] ?? 0 }}</td>
                                                <td class="p-2 text-right">{{ formatPrice((float) ($entry['unit_price'] ?? 0)) }}</td>
                                                <td class="p-2 text-right font-semibold">{{ formatPrice((float) ($entry['total'] ?? 0)) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-[rgba(0,0,0,0.06)] font-semibold">
                                            <td colspan="3" class="p-2 text-right">Totaal inkomsten:</td>
                                            <td class="p-2 text-right">{{ formatPrice($manualIncomeTotal) }}</td>
                                        </tr>

                                        @foreach($manualExpenseEntries as $entry)
                                            <tr class="hover:bg-[rgba(0,0,0,0.05)] opacity-90">
                                                <td class="p-2">{{ $entry['description'] ?? 'Uitgave' }}</td>
                                                <td class="p-2 text-right">{{ $entry['quantity'] ?? 0 }}</td>
                                                <td class="p-2 text-right">{{ formatPrice((float) ($entry['unit_price'] ?? 0)) }}</td>
                                                <td class="p-2 text-right font-semibold">{{ formatPrice((float) ($entry['total'] ?? 0)) }}</td>
                                            </tr>
                                        @endforeach
                                        <tr class="bg-[rgba(0,0,0,0.06)] font-semibold">
                                            <td colspan="3" class="p-2 text-right">Totaal uitgaven:</td>
                                            <td class="p-2 text-right">{{ formatPrice($manualExpenseTotal) }}</td>
                                        </tr>
                                        <tr class="bg-[rgba(0,0,0,0.1)] font-semibold border-t-2 border-[rgba(0,0,0,0.3)]">
                                            <td colspan="3" class="p-2 text-right">Saldo:</td>
                                            <td class="p-2 text-right">{{ formatPrice($manualBalanceTotal) }}</td>
                                        </tr>
                                        @if(!is_null($manualBudget))
                                            <tr class="bg-[rgba(0,0,0,0.08)] font-semibold">
                                                <td colspan="3" class="p-2 text-right">Begroot:</td>
                                                <td class="p-2 text-right">{{ formatPrice($manualBudget) }}</td>
                                            </tr>
                                            <tr class="bg-[rgba(0,0,0,0.12)] font-semibold">
                                                <td colspan="3" class="p-2 text-right">Afwijking t.o.v. begroot:</td>
                                                <td class="p-2 text-right">{{ formatPrice($manualBalanceTotal - $manualBudget) }}</td>
                                            </tr>
                                        @endif
                                    @else
                                        <tr class="hover:bg-[rgba(0,0,0,0.05)]">
                                            <td class="p-2 font-semibold">Betaalde deelnemers</td>
                                            <td class="p-2 text-right">{{ $paidParticipants }}</td>
                                            <td class="p-2 text-right">{{ $activity->price > 0 ? formatPrice($activity->price) : 'Gratis' }}</td>
                                            <td class="p-2 text-right font-semibold">{{ formatPrice($baseRevenue) }}</td>
                                        </tr>
                                        @if($freeOrganizerCount > 0)
                                            <tr class="hover:bg-[rgba(0,0,0,0.05)] opacity-60">
                                                <td class="p-2 text-sm">Gratis organisatoren</td>
                                                <td class="p-2 text-right text-sm">{{ $freeOrganizerCount }}</td>
                                                <td class="p-2 text-right text-sm">Gratis</td>
                                                <td class="p-2 text-right font-semibold text-sm">{{ formatPrice(0) }}</td>
                                            </tr>
                                        @endif
                                        <tr class="hover:bg-[rgba(0,0,0,0.05)]">
                                            <td class="p-2 font-semibold">Extra's (vragen)</td>
                                            <td class="p-2 text-right">—</td>
                                            <td class="p-2 text-right">—</td>
                                            <td class="p-2 text-right font-semibold">{{ formatPrice($extrasRevenue) }}</td>
                                        </tr>
                                        @if($pendingParticipants > 0)
                                            <tr class="hover:bg-[rgba(0,0,0,0.05)] opacity-60">
                                                <td class="p-2 text-sm">In afwachting betaling</td>
                                                <td class="p-2 text-right text-sm">{{ $pendingParticipants }}</td>
                                                <td class="p-2 text-right text-sm">{{ $activity->price > 0 ? formatPrice($activity->price) : '–' }}</td>
                                                <td class="p-2 text-right font-semibold text-sm">{{ formatPrice($pendingParticipants * (float) $activity->price) }}</td>
                                            </tr>
                                        @endif
                                        @if($reserveParticipants > 0)
                                            <tr class="hover:bg-[rgba(0,0,0,0.05)] opacity-60">
                                                <td class="p-2 text-sm">Reserves</td>
                                                <td class="p-2 text-right text-sm">{{ $reserveParticipants }}</td>
                                                <td class="p-2 text-right text-sm">{{ $activity->price > 0 ? formatPrice($activity->price) : '–' }}</td>
                                                <td class="p-2 text-right font-semibold text-sm">{{ formatPrice($reserveParticipants * (float) $activity->price) }}</td>
                                            </tr>
                                        @endif
                                        <tr class="bg-[rgba(0,0,0,0.1)] font-semibold border-t-2 border-[rgba(0,0,0,0.3)]">
                                            <td colspan="3" class="p-2 text-right">Totaal ontvangen:</td>
                                            <td class="p-2 text-right">{{ formatPrice($totalPaid) }}</td>
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
                                            <div class="font-semibold">{{ $application->user->name }}</div>
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
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </x-zijpalm-div>
            @else
                {{-- Activity Description --}}
                <x-zijpalm-div title="Beschrijving" :editable="false" width="w-full" class="">
                    <div class="bg-[rgba(0,0,0,0.15)] rounded-md flex flex-col p-2 text-left">
                        {!!$activity->descriptionHTML!!}
                    </div>
                </x-zijpalm-div>
            @endif
        </div>
</x-page-wrapper>
