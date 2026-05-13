<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App;

enum UserType: string
{
    case Medewerker = 'medewerker';
    case Stagiair = 'stagiair';
    case Inhuur = 'inhuur';
    case Gepensioneerde = 'gepensioneerde';
    case EreLid = 'erelid';
    case System = 'system';

    public static function toArray(): array
    {
        return array_column(self::cases(), 'value');
    }
}
