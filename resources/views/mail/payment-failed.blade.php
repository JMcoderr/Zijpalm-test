<x-layouts.mail.header :user="$user">
    {!! $content->textHTML !!}

    <p style="margin-top: 0px;">Betreft: {{$payment->description}}</p>
</x-layouts.mail.header>