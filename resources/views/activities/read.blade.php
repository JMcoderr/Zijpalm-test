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
                        <x-zijpalm-button label="Meld je aan!" type="action" variant="obvious" x-on:click="signupModal = true"/>
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
                @if(auth()->user()?->isAdmin() && $activity->start?->isFuture())
{{--                    <x-zijpalm-button :href="route('activity.update', $activity)" label="Bewerk activiteit" variant="obvious"/>--}}
                    <x-zijpalm-button label="Verstuur aankondiging" type="action" x-on:click="announcementMailModal = true" variant="obvious"/>
                    <x-zijpalm-modal text="Activiteit aankondiging" livewire include="activity-announcement-mail" modal="announcementMailModal" :variables="['activity' => $activity, 'errors' => $errors->announcementMail->all()]"/>
                    {{-- If the activity has not started --}}
                    @if($activity->start?->isFuture())
                        <form id="activity-destroy" method="POST" action="{{route('activity.destroy', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} te annuleren. Alle ingeschreven leden krijgen hun inschrijvingskosten teruggestort.')">
                            @csrf
                            @method('delete')
                            <x-zijpalm-button type="submit" form="activity-destroy" label="Annuleer activiteit" variant="obvious"/>
                        </form>
                    @endif
                @endif

                @if(auth()->user()?->isAdmin() && ($activity->type === \App\ActivityType::Weekly || $activity->end?->isPast()))
                    <form id="activity-permanentDelete" method="POST" action="{{route('activity.permanentDelete', $activity)}}" onsubmit="return confirm('Je staat op het punt de activiteit {{$activity->title}} te verwijderen.')">
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
                            </div>
                        </div>

                        @auth
                            @if($activity->type === \App\ActivityType::Weekly)
                            {{-- Price --}}
                            <div class="flex flex-col self-stretch">
                                <span class="font-bold text-xl">Prijs</span>
                                <div class="flex flex-col flex-wrap bg-[rgba(0,0,0,0.1)] rounded-xl p-4 gap-x-2 flex-1">
                                    <span> {{formatPrice($activity->price)}} </span>
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


                                    {{-- Price per participant --}}
                                    <div class="flex flex-col">
                                        <span class="font-bold">Prijs per deelnemer</span>
                                        <span> {{formatPrice($activity->price)}} </span>
                                    </div>
                                </div>
                            </div>
                            @endif
                        @endauth

                        {{-- Start & End times and dates --}}
                        <div class="flex flex-col lg:flex-1 self-stretch">
                            {{-- Title --}}
                            <span class="text-xl font-bold">Datums & Tijden</span>

                            <div class="flex justify-evenly items-center flex-wrap bg-[rgba(0,0,0,0.1)] rounded-xl p-1 gap-x-2 flex-1">
                                {{-- Start and end dates/times --}}
                                <div class="flex flex-col">
                                    <span class="text-lg font-bold">Start- en Eindtijd</span>
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
                <x-zijpalm-div :editable="false" color="light" title="Aanmeldingen ({{$activity->participants->all->count()}}{{$activity->maxParticipants ? '/' . $activity->maxParticipants : ''}})" width="w-full lg:max-w-1/2" class="flex flex-col flex-1 self-stretch max-h-[40rem] md:h-[40rem]">
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

            {{-- Activity Description --}}
            <x-zijpalm-div title="Beschrijving" :editable="false" width="w-full" class="">
                <div class="bg-[rgba(0,0,0,0.15)] rounded-md flex flex-col p-2">
                    {!!$activity->descriptionHTML!!}
                </div>
            </x-zijpalm-div>
        </div>
</x-page-wrapper>
