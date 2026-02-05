@props(['user'])

<!DOCTYPE html>
<html lang="nl">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0" />
        <style>
            .h-12 { height: 72px; }
            .w-auto { width: auto; }
            .my-1 { margin: 4px auto; }
            #logo { margin: auto; text-align: center; }
        </style>
        @stack('styles')
    </head>
    <body style="padding:0; margin:0; background-color: #fff; max-width: 100%; font-size: large;">
        <div id="header" style="padding: 20px; background-color: #96c8e6; width: 100%; max-width: 100%; box-sizing: border-box;">
            <div id="inner" style="margin: auto; max-width: 400px; width: 100%;">
                <x-app-logo />
                <p style="margin-bottom: 15px;">Beste {{$user->name ?? "Collega"}},</p>
                {{$slot}}
                <div style="margin-top: 15px;">
                    <p style="margin: 0px;">Met vriendelijke groet,</p>
                    <p style="margin: 0px;">Zijpalm</p>
                </div>
            </div>
        </div>
    </body>
</html>
