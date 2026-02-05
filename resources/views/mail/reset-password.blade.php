<x-layouts.mail.header>
    {!! $content->textHTML !!}
    {{-- <p style="margin: 10px; text-align: center; padding: 10px 20px;">
        <a href="{{ route('password.reset', ['token' => $resetPasswordToken, 'email' => $user->email]) }}" style="background-color: #054d7c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Wachtwoord instellen</a>
    </p> --}}
    <x-mail.button :href="$resetUrl" label="Wachtwoord vernieuwen" />

    <p>Deze link verloopt over {{$expire}} minuten.</p>
    <p>Als u geen wachtwoord heeft aangevraagd hoedt u verder niks te doen.</p>
{{--    <x-mail.button :href="route('password.reset', ['token' => $resetPasswordToken, 'email' => $user->email])" label="Wachtwoord instellen"/>--}}
    <p class="text-sm">Als de 'Wachtwoord vernieuwen' knop niet werkt, kopieer en plak de URL hieronder in uw web browser: <span style="text-wrap: wrap">{{$resetUrl}}</span></p>
</x-layouts.mail.header>
