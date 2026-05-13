<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App;

enum FileType: string
{
    case Image = 'image';
    case Pdf = 'pdf';
    case Video = 'video';
    case Audio = 'audio';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
