{{-- This view file shows part of the interface and is kept simple so it is easy to follow. --}}
@props(['user' => null, 'hideGreeting' => false])

<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <style>
            #logo { margin: 0 auto; text-align: center; }
            #logo img { display: block; width: 240px; height: auto; max-width: 100%; margin: 4px auto; }
        </style>
        @stack('styles')
    </head>
    <body style="padding:0; margin:0; background-color: #fff; max-width: 100%; font-size: large;">
        <div id="header" style="padding: 20px; background-color: #96c8e6; width: 100%; max-width: 100%; box-sizing: border-box;">
            <div id="inner" style="margin: 0 auto; max-width: 400px; width: 100%;">
                <div id="logo" style="margin: 0 auto; text-align: center;">
                    <img src="https://zijpalm.nl/storage/images/zijpalm_logo_rgba5_77_124.png" alt="Logo Zijpalm" width="240" style="display: block; width: 240px; height: auto; max-width: 100%; margin: 4px auto;">
                </div>
                @if(!$hideGreeting && !empty($user))
                    <p style="margin-bottom: 15px;">Beste {{ $user->name }},</p>
                @endif
                {{$slot}}
                @if(!$hideGreeting)
                    <div style="margin-top: 15px;">
                        <p style="margin: 0px;">Met vriendelijke groet,</p>
                        <p style="margin: 0px;">Het Bestuur</p>
                    </div>
                @endif
            </div>
        </div>
    </body>
</html>