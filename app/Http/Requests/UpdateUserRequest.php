<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

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
        $user = $this->route('user');
        $userId = $user instanceof User ? $user->id : null;

        return [
            'firstName' => ['sometimes', 'required', 'string', 'max:255'],
            'lastName' => ['sometimes', 'required', 'string', 'max:255'],
            'phone' => ['sometimes', 'nullable', 'string', 'regex:/^\d{8}$/'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($userId)],
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
            'phone.regex' => 'Het telefoonnummer moet uit precies 8 cijfers bestaan',
            'email.email' => 'Voer een geldig e-mailadres in',
            'email.max' => 'Het e-mailadres mag niet langer zijn dan 255 tekens',
            'is_admin.boolean' => 'De waarde voor beheerder moet waar of onwaar zijn',
            'type.in' => 'Het geselecteerde type is ongeldig',
        ];
    }
}
