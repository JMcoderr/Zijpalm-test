<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Only admins can update
        return auth()->check() && auth()->user()->is_admin;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // General details
            'title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable'],
            'image-upload' => ['nullable', 'image'], // Optional for updates
            'location' => ['nullable', 'string', 'max:255'],
            'organizer' => ['nullable', 'string', 'max:255'],
            'maxParticipants' => ['nullable', 'integer', 'min:1'],
            'maxGuests' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'regex:/^\d+([.,]\d{2})?$/'],
            'whatsappUrl' => ['nullable', 'url'],
            'free_organizer_count' => ['nullable', 'integer', 'min:0'],
            'personalConfirmationEnabled' => ['nullable'],
            'personalConfirmation' => ['nullable', 'required_if:personalConfirmationEnabled,on'],

            // Times
            'start-date' => ['nullable', 'date'],
            'start-time' => ['nullable', 'date_format:H:i'],
            'end-date' => ['required', 'date', 'after_or_equal:start-date'],
            'end-time' => ['nullable', 'date_format:H:i'],
            'registrationStart' => ['nullable', 'date'],
            'registrationEnd' => ['nullable', 'date', 'after_or_equal:registrationStart', 'before_or_equal:end-date'],
            'noCancellation' => ['nullable'],
            'cancellationEnd' => ['nullable', 'date', 'after_or_equal:registrationStart', 'before_or_equal:end-date'],

            // Questions
            'questions' => ['nullable', 'array'],
            'questions.*.type' => ['required_if:questions,array', 'in:select,text,number,checkbox'],
            'questions.*.vraag' => ['required_if:questions,array', 'string', 'max:255'],
            'questions.*.prijs' => ['nullable', 'regex:/^\d+([.,]\d{2})?$/'],
            'questions.*.max' => ['nullable', 'integer', 'min:1'],

            // Select question's options
            'questions.*.options' => ['required_if:questions.*.type,select', 'array', 'min:2'],
            'questions.*.options.*.optie' => ['required_with:questions.*.options', 'string', 'max:255'],
            'questions.*.options.*.prijs' => ['nullable', 'regex:/^\d+([.,]\d{2})?$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'end-date.required' => 'De einddatum is verplicht.',
            'personalConfirmation.required_if' => 'Persoonlijke bevestiging is verplicht wanneer deze optie aan staat.',
        ];
    }
}
