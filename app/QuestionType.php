<?php

namespace App;

enum QuestionType: string
{
    case Text = 'text';
    case Checkbox = 'checkbox';
    case Number = 'number';
    case Select = 'select';

    // Returns the value of the enum case in array form
    public static function toArray(): array{
        return array_column(self::cases(), 'value');
    }

    // Function to provide readable labels for the enum cases
    public function label(): string{
        return match($this){
            self::Text => 'Tekst',
            self::Checkbox => 'Ja/Nee',
            self::Number => 'Aantal',
            self::Select => 'Selectie',
        };
    }

    // Function to return the enum cases as a label/value pair
    public static function labelledCases(): array{
        return array_map(
            fn(self $case) => [
                'label' => $case->label(),
                'type' => $case->value,
            ],
            self::cases()
        );
    }
}
