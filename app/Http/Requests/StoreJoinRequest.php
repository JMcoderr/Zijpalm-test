<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreJoinRequest extends FormRequest
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
            'firstname' => 'required|string|max:255',
            'lastname' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone' => 'required|string|max:255|regex:^\d{1,10}$^',
            'type' => 'required|in:inhuur,gepensioneerde,stagiair',
            'endDate' => [
                Rule::requiredIf($this->input('type') === 'stagiar'),
                'nullable',
                'date',
                Rule::date()->after(now()->lastOfMonth()),
            ],
            'privacy' => 'required|accepted',
            'rules' => 'required|accepted',
        ];
    }

    /**
     * Get the error messages for the defined validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'firstname.required' => 'Voornaam is verplicht',
            'firstname.max' => 'Voornaam mag niet langer zijn dan 255 tekens',

            'lastname.required' => 'Achternaam is verplicht',
            'lastname.max' => 'Achternaam mag niet langer zijn dan 255 tekens',

            'email.required' => 'E-mailadres is verplicht',
            'email.email' => 'Voer een geldig e-mailadres in',
            'email.max' => 'E-mailadres mag niet langer zijn dan 255 tekens',
            'email.unique' => 'E-mailadres is al in gebruik',

            'phone.max' => 'Telefoonnummer mag niet langer zijn dan 255 tekens',
            'phone.required' => 'Telefoonnummer is verplicht',
            'phone.regex' => 'Telefoonnummer moet een geldig nummer zijn van maximaal 10 tekens',

            'type.required' => 'Type is verplicht',
            'type.in' => 'Type moet één van de volgende zijn: inhuur, pensionado, stagiar',

            'endDate.required_if' => 'Einddatum is verplicht als het type "stagiar" is',
            'endDate.date' => 'Einddatum moet een geldige datum zijn',
            'endDate.after' => 'Einddatum moet na de laatste dag van de huidige maand liggen',

            'privacy.required' => 'Je moet akkoord gaan met het privacybeleid',
            'privacy.accepted' => 'Je moet akkoord gaan met het privacybeleid',

            'rules.required' => 'Je moet akkoord gaan met het huishoudelijk reglement',
            'rules.accepted' => 'Je moet akkoord gaan met het huishoudelijk reglement',
        ];
    }
}
