<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App;

enum PaymentStatus: string
{
    case open = "open";
    case pending = "pending";
    case authorized = "authorized";
    case paid = "paid";
    case canceled = "canceled";
    case expired = "expired";
    case failed = "failed";

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
