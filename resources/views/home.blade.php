@php
    // Get the homepage banner
    $banner = \App\Models\Content::where('name', 'homepage-banner')->first();
    // Get the homepage info
    $info = \App\Models\Content::where('name', 'homepage-info')->first();
    // Get the homepage activity idea
    $idea = \App\Models\Content::where('name', 'homepage-activity-idea')->first();
@endphp

<x-page-wrapper page="Home">
    {{-- If there is a payment status, show the payment status modal --}}
    @includeWhen(session()->has('payment_status'), 'payments.status', ['status' => session('payment_status')])

    {{-- Title & subtitle --}}
    {{-- id={{$homepageBanner->name}} --}}
    {{-- src={{$homepageBanner->getFile(returns either <a> or <img> link)}} --}}
    <x-zijpalm-div :id="$banner->id" :name="$banner->name" color='transparent' :editables="['Titel', 'Tekst']" :title="$banner->title" :text="$banner->textHTML" textSize="text-xl"/>

    {{-- Buttons --}}
    <div class="flex flex-wrap items-center justify-items-center justify-center gap-2.5">
        {{-- Button 1 - Leftmost button--}}
        <x-zijpalm-button id="homepage-activity-button" :href="route('activity.index')" label="Activiteiten"/>

        {{-- Button 2 --}}
        <x-zijpalm-button id="homepage-reports-button" :href="route('report.index')" label="Verslagen"/>

        {{-- Button 3 --}}
        @guest
            <x-zijpalm-button id="homepage-become-member-button" href="{{route('information.join')}}" label="Lid Worden"/>
        @endguest
    </div>

    {{-- Spacer with smoothened transition --}}
    <div class="transition-all duration-1000 md:h-full h-0"></div>

    {{-- Middle div / Introduction, description --}}
    <x-zijpalm-div :id="$info->id" :name="$info->name" :editables="['Titel', 'Tekst']" :title="$info->title" :text="$info->textHTML"/>

    {{-- Bottom div / Activity idea box --}}
    <x-zijpalm-div :id="$idea->id" :name="$idea->name" color="light" :textIsLink=false textColor="text-zijpalm-400" :editables="['Titel', 'Tekst']" titleFontSize="text-2xl" :title="$idea->title" :text="$idea->textHTML" />
</x-page-wrapper>
