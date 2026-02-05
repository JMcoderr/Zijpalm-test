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
    </style>
@endpush

<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}
    <table>
        @foreach($members as $member)
                <tr>
                    <td>
                        <span><strong>Naam:</strong> {{ $member->name }}</span><br>
                        <span><strong>Email:</strong> {{ $member->email }}</span><br>
                        <span><strong>Gebruikerstype:</strong> {{ ucfirst($member->type->value) }}</span>
                    </td>
                </tr>
        @endforeach
    </table>
</x-layouts.mail.header>