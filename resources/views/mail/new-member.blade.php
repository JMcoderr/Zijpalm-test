<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}
    {{-- <p style="margin: 10px; text-align: center; padding: 10px 20px;">
        <a href="{{ route('password.reset', ['token' => $resetPasswordToken, 'email' => $user->email]) }}" style="background-color: #054d7c; color: white; padding: 10px 20px; text-decoration: none; border-radius: 5px;">Wachtwoord instellen</a>
    </p> --}}
    <x-mail.button :href="route('password.reset', ['token' => $resetPasswordToken, 'email' => $user->email])" label="Wachtwoord instellen"/>
</x-layouts.mail.header>