<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
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
            'firstName' => ['sometimes', 'string', 'max:255'],
            'lastName' => ['sometimes', 'string', 'max:255'],
            'phone' => ['sometimes', 'string', 'min:8', 'max:8', 'regex:/^\d{8}$/'],
            'email' => ['sometimes', 'email', 'max:255'],
            'is_admin' => ['sometimes', 'boolean'],
            'type' => ['sometimes', 'in:' . implode(',', \App\UserType::toArray())],
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
            'firstName.string' => 'De voornaam moet een tekst zijn',
            'firstName.max' => 'De voornaam mag niet langer zijn dan 255 tekens',
            'lastName.string' => 'De achternaam moet een tekst zijn',
            'lastName.max' => 'De achternaam mag niet langer zijn dan 255 tekens',
            'phone.string' => 'Het telefoonnummer moet een tekst zijn',
            'phone.min' => 'Het telefoonnummer moet uit 06 + 8 cijfers bestaan',
            'phone.max' => 'Het telefoonnummer moet uit 06 + 8 cijfers bestaan',
            'phone.regex' => 'Het telefoonnummer moet uit 06 + 8 cijfers bestaan',
            'email.email' => 'Voer een geldig e-mailadres in',
            'email.max' => 'Het e-mailadres mag niet langer zijn dan 255 tekens',
            'is_admin.boolean' => 'De waarde voor beheerder moet waar of onwaar zijn',
            'type.in' => 'Het geselecteerde type is ongeldig',
        ];
    }
}
