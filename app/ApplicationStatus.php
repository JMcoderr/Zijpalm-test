<?php

namespace App;

enum ApplicationStatus: string
{
    case Active = 'active'; // User is registered for the activity
    case Pending = 'pending'; // User has not completed the payment yet
    case Reserve = 'reserve'; // User is on the waiting list
    case Cancelled = 'cancelled'; // User has cancelled the application

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
