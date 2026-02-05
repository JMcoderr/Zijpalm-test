<?php

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
