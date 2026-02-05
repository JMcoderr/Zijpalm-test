<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class GuestRequest implements ValidationRule{
    public function validate(string $attribute, mixed $value, Closure $fail): void{

        // What fields are required together
        $requiredFields = [
            'firstName',
            'lastName',
            'phone',
            'email',
        ];

        // If one of the required fields is filled, or if checkbox for adult is checked
        if(collect($requiredFields)->contains(fn($field) => !empty($value[$field])) || (!empty($value['adult']))){
            foreach($requiredFields as $field){
                if(empty($value[$field])){
                    $fail("Vul alstublieft alle informatie over uw gast in.");
                    break;
                }
            }
        }
    }
}
