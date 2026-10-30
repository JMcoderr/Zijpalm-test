{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header :user="$user">
    <div style="font-family: 'Trebuchet MS', Verdana, sans-serif; color: #173642; line-height: 1.55;">
        <h1 style="margin: 24px 0 16px; font-size: 24px; line-height: 1.3; color: #054b70;">Activiteiten &amp; Shop / Overig</h1>
        <div style="font-size: 16px;">{!! $introHtml !!}</div>

        <p style="margin: 20px 0; padding: 12px 0; border-top: 1px solid #6fa7c5; border-bottom: 1px solid #6fa7c5; font-size: 14px;">
            De links staan als gewone tekst in deze mail. Kopieer de gewenste link naar de adresbalk van je browser, of ga naar zijpalm.nl.
        </p>

        @foreach(['Lopende activiteiten' => $runningActivities, 'Komende activiteiten' => $activities] as $heading => $items)
            @if($items->isNotEmpty())
                <h2 style="margin: 28px 0 0; padding-bottom: 8px; border-bottom: 2px solid #054b70; font-size: 20px; color: #054b70;">{{ $heading }}</h2>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                    @foreach($items as $activity)
                        <tr>
                            <td style="padding: 16px 0; border-bottom: 1px solid #6fa7c5;">
                                <p style="margin: 0 0 6px; font-size: 18px; font-weight: bold; overflow-wrap: anywhere;">{{ $activity->title }}</p>
                                <p style="margin: 0; font-size: 15px;">
                                    {{ $activity->type === \App\ActivityType::Weekly ? ucfirst($activity->start->locale('nl')->dayName) : formatDate($activity->start) }}
                                    @if($activity->location)<br>{{ $activity->location }}@endif
                                </p>
                                <p style="margin: 10px 0 0; font-size: 14px; color: #054b70; word-break: break-all; overflow-wrap: anywhere;">zijpalm.nl/activiteiten/{{ $activity->id }}</p>
                            </td>
                        </tr>
                    @endforeach
                </table>
            @endif
        @endforeach

        @if($products->isNotEmpty())
            <h2 style="margin: 28px 0 0; padding-bottom: 8px; border-bottom: 2px solid #054b70; font-size: 20px; color: #054b70;">Shop / Overig</h2>
            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width: 100%; border-collapse: collapse; table-layout: fixed;">
                @foreach($products as $product)
                    <tr>
                        <td style="padding: 16px 0; border-bottom: 1px solid #6fa7c5;">
                            <p style="margin: 0 0 6px; font-size: 18px; font-weight: bold; overflow-wrap: anywhere;">{{ $product->name }}</p>
                            <p style="margin: 0; font-size: 15px;">
                                @if($product->activity_starts_at)
                                    {{ formatDate($product->activity_starts_at) }}<br>
                                @endif
                                {{ formatPrice($product->price) }}
                                @if($product->pickupLocation)<br>{{ $product->pickupLocation }}@endif
                            </p>
                            <p style="margin: 10px 0 0; font-size: 14px; color: #054b70; word-break: break-all; overflow-wrap: anywhere;">zijpalm.nl/shop/{{ $product->id }}</p>
                        </td>
                    </tr>
                @endforeach
            </table>
        @endif
    </div>
</x-layouts.mail.header>
