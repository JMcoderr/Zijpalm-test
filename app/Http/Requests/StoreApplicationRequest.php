<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use App\Rules\GuestRequest;

class StoreApplicationRequest extends FormRequest
{
    protected $errorBag = 'signupActivity';

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Personal info
            'phone' => ['required', 'string', 'regex:/^(06\d{8}|\d{8})$/'],
            'email' => ['required', 'email', 'max:255'],

            // Guests, if any field is filled, require all, except adult checkbox
            'guests.*' => [new GuestRequest],
            'guests.*.firstName' => ['nullable', 'string', 'max:255'],
            'guests.*.lastName' => ['nullable', 'string', 'max:255'],
            'guests.*.phone' => ['nullable', 'string', 'regex:/^(06\d{8}|\d{8})$/'],
            'guests.*.email' => ['nullable', 'email', 'max:255'],
            'guests.*.adult' => ['nullable'],

            // Comment
            'comment' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            // Personal info
            'phone.required' => 'Het telefoonnummer is verplicht.',
            'phone.regex' => 'Het telefoonnummer moet een geldig 06-nummer zijn.',

            'email.required' => 'Het e-mailadres is verplicht.',
            'email.email' => 'Het e-mailadres moet een geldig e-mailadres zijn.',
            'email.max' => 'Het e-mailadres mag maximaal 255 tekens bevatten.',

            // Guests
            // First Name
            'guests.*.firstName.max' => 'De voornaam van elke gast mag maximaal 255 tekens bevatten.',

            // Last Name
            'guests.*.lastName.max' => 'De achternaam van elke gast mag maximaal 255 tekens bevatten.',

            // Phone
            'guests.*.phone.regex' => 'Het telefoonnummer van elke gast moet een geldig 06-nummer zijn.',

            // E-mail
            'guests.*.email.email' => 'Het e-mailadres van elke gast moet een geldig e-mailadres zijn.',
            'guests.*.email.max' => 'Het e-mailadres van elke gast mag maximaal 255 tekens bevatten.',

            // Answers
            'questions.*.required' => 'Antwoorden zijn verplicht.',
        ];
    }
}
