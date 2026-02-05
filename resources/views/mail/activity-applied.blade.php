@php
    $totalPrice = 0;
    if($application->answers && $application->answers->count() > 0) {
        foreach($application->answers as $answer) {
            $totalPrice += getAnswerPrice($answer);
        }
    }
@endphp
@push('styles')
    <style>
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #000;
        }
        th {
            /* background-color: #f2f2f2; */
        }
        tfoot {
            border-top: 3px solid #000;
        }
        #email {
            word-break: break-all;
        }
    </style>
@endpush

<x-layouts.mail.header :user="$user">
    @if($reserve)
        {!! $reserveContent->textHTML !!}
    @endif

    {!! $content->textHTML !!}


    <table style="margin-top: 10px; margin-bottom: 10px; width: 100%;">
        <tr>
            <td><strong>{{ $activity->title }}</strong></td>
        </tr>
        <tr>
            <td><strong>Locatie</strong></td>
            <td>{{ $activity->location }}</td>
        </tr>
        <tr>
            <td><strong>Starttijd</strong></td>
            <td>{{ formatDate($activity->start) }} om {{ formatTime($activity->start) }} uur</td>
        </tr>
        <tr>
            <td><strong>Eindtijd</strong></td>
            <td>{{ formatDate($activity->end) }} om {{ formatTime($activity->end) }} uur</td>
        </tr>
        <tr>
            <td><strong>Organisator(en)</strong></td>
            <td>{{ $activity->organizer }}</td>
        </tr>
        <tr>
            <td><strong>Uw e-mailadres:</strong></td>
            <td>{{ $application->email }}</td>
        </tr>
        <tr>
            <td><strong>Telefoonnummer:</strong></td>
            <td>{{ $application->phone }}</td>
        </tr>
        <tr>
            <td><strong>Aantal personen:</strong></td>
            <td>{{ $application->participants }}</td>
        </tr>
        @if(!empty($application->comment))
            <tr>
                <td><strong>Opmerkingen:</strong></td>
                <td>{{ $application->comment }}</td>
            </tr>
        @endif
    </table>

    @if($application->guests && count($application->guests) > 0)
        <br>
        <strong>Gast(en):</strong>
        <table style="margin-top: 10px; margin-bottom: 10px; width: 100%;">
            <thead>
                <tr>
                    <th>Naam</th>
                    <th>E-mailadres</th>
                    <th>Telefoonnummer</th>
                </tr>
            </thead>
            <tbody>
                @foreach($application->guests as $guest)
                    <tr>
                        <td>{{ $guest->name }}</td>
                        <td id="email">{{ $guest->email }}</td>
                        <td>{{ $guest->phone }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    @if($application->answers && $application->answers->count() > 0)
        <br>
        <strong>Beantwoorde vragen:</strong>
        <table style="margin-top: 10px; margin-bottom: 10px; width: 100%;">
            <thead>
                <th>Vraag</th>
                <th>Antwoord</th>
                <th>Prijs</th>
            </thead>
            <tbody>
                @foreach($application->answers as $answer)
                    <tr>
                        <td><strong>{{ $answer->question->query }}</strong></td>
                        <td>{{ $answer->answer }}</td>
                        <td>{{formatprice(getAnswerPrice($answer))}}</td>
                    </tr>
                @endforeach
            </tbody>
            <tfoot>
                <tr>
                    <td></td>
                    <td><strong>Totaalprijs vragen:</strong></td>
                    <td>{{ formatPrice($totalPrice) }}</td>
                </tr>
                <tr>
                    <td></td>
                    <td><strong>Totaalprijs:</strong></td>
                    <td>{{ formatPrice($totalPrice + ($activity->price * $application->participants)) }}</td>
                </tr>
            </tfoot>
        </table>
    @endif

    {{-- If there is a whatsapp group and the user is not on the reserve list, show the whatsapp group link/qrcode --}}
    @if(isset($qrcode) && !$reserve)
        <br>
        <div style="text-align: center;">
            <strong>Whatsapp groep</strong><br>
            <img src="{{ $message->embedData($qrcode, 'whatsapp-qrcode.png', 'image/png') }}" width="192" height="192" alt="Whatsapp QR Code">
            <p>Scan de QR-code om lid te worden van de groep of klik op de knop hieronder</p>
        </div>
        <x-mail.button :href="$activity->whatsappUrl" label="Voeg groep toe">
            <img src="https://cdn-icons-png.flaticon.com/512/4423/4423697.png" width="20" height="20" alt="Whatsapp" style="vertical-align: middle; padding-bottom: 2px;">
        </x-mail.button>
    @endif

    <p>Kosteloos annuleren kan tot en met: {{formatDate($activity->cancellationEnd)}}</p>
</x-layouts.mail.header>