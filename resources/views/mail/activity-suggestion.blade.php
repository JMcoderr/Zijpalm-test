{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@push('styles')
    <style>
        /* Make this specific mail wider than the default 400px template width. */
        #inner {
            max-width: 720px !important;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 10px;
            margin-bottom: 10px;
        }
        td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #000;
            vertical-align: top;
        }
        .label-cell {
            width: 190px;
            font-weight: 700;
            white-space: nowrap;
        }
        .value-cell {
            word-break: break-word;
        }
        .description-cell {
            white-space: pre-line;
            line-height: 1.4;
            word-break: break-word;
        }
        .intro {
            margin-bottom: 8px;
        }
        .closing {
            margin-top: 16px;
        }
    </style>
@endpush

<x-layouts.mail.header :hideGreeting="true">
    <p class="intro"><strong>Nieuwe activiteitssuggestie ontvangen.</strong></p>
    <p class="intro">Hieronder staan de gegevens van de inzending.</p>

    <table>
        <tr>
            <td class="label-cell">Naam indiener</td>
            <td class="value-cell">{{ $name }}</td>
        </tr>
        <tr>
            <td class="label-cell">E-mailadres indiener</td>
            <td class="value-cell">{{ $email }}</td>
        </tr>
        <tr>
            <td class="label-cell">Voorgestelde activiteit</td>
            <td class="value-cell">{{ $activityName }}</td>
        </tr>
        <tr>
            <td class="label-cell">Beschrijving</td>
            <td class="value-cell">Zie hieronder</td>
        </tr>
        <tr>
            <td colspan="2" class="description-cell">{{ $description }}</td>
        </tr>
        @if(count($attachments) > 0)
            <tr>
                <td class="label-cell">Bijlagen ({{ count($attachments) }})</td>
                <td class="value-cell">
                    @foreach($attachments as $attachment)
                        <div>{{ $attachment['name'] }}</div>
                    @endforeach
                </td>
            </tr>
        @endif
        </table>

    <div class="closing">
        <p style="margin: 0;">Met vriendelijke groet,</p>
        <p style="margin: 0;">{{ $name }}</p>
    </div>
</x-layouts.mail.header>
