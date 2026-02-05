<?php

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
