<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App;

enum ActivityType: string
{
    case OneDay = 'one-day';
    case MultiDay = 'multi-day';
    case Weekly = 'weekly';
    case Cancelled = 'cancelled';
    case Archived = 'archived';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
