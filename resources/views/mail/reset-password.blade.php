{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
<x-layouts.mail.header>
    @php
        $introHtml = str_replace(
            'U ontvangt deze email omdat we een wachtwoord reset aanvraag hebben ontvangen voor uw account.',
            'Je ontvangt deze e-mail omdat we een wachtwoordresetaanvraag voor je account hebben ontvangen.',
            $content->textHTML
        );
    @endphp

    {!! $introHtml !!}
    {{-- <p style="margin: 10px; text-align: center; padding: 10px 20px;">
        <a href="{{ route('password.reset', ['token' => $resetPasswordToken, 'email' => $user->email]) }}" style="background-color: #054d7c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Wachtwoord instellen</a>
    </p> --}}
    <x-mail.button :href="$resetUrl" label="Wachtwoord vernieuwen" />

    <p>Deze link verloopt over {{$expire}} minuten.</p>
    <p>Als je geen wachtwoord hebt aangevraagd, hoef je verder niets te doen.</p>
{{--    <x-mail.button :href="route('password.reset', ['token' => $resetPasswordToken, 'email' => $user->email])" label="Wachtwoord instellen"/>--}}
    <p class="text-sm">Als de 'Wachtwoord vernieuwen' knop niet werkt, kopieer en plak de URL hieronder in je webbrowser: <span style="text-wrap: wrap">{{$resetUrl}}</span></p>
</x-layouts.mail.header>
