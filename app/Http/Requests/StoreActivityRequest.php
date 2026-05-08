<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreActivityRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Should only be true for admins!
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array{
        return [
            // General details
            'title' => ['required', 'string', 'max:255'],
            'description' => ['required'],
            'image-upload' => ['required', 'image'],
            'location' => ['required', 'string', 'max:255'],
            'organizer' => ['required', 'string', 'max:255'],
            'maxParticipants' => ['nullable', 'integer', 'min:1'],
            'maxGuests' => ['nullable', 'integer', 'min:0'],
            'price' => ['nullable', 'regex:/^\d+([.,]\d{2})?$/'],
            'whatsappUrl' => ['nullable', 'url'],
            'free_organizer_count' => ['required', 'integer', 'min:0'],
            'personalConfirmationEnabled' => ['nullable'],
            'personalConfirmation' => ['nullable', 'required_if:personalConfirmationEnabled,on'],
            'manual_budget' => ['nullable', 'regex:/^\d+([.,]\d{1,2})?$/'],
            'manual_finance_entries' => ['nullable', 'array'],
            'manual_finance_entries.*.description' => ['nullable', 'string', 'max:255'],
            'manual_finance_entries.*.quantity' => ['nullable', 'numeric'],
            'manual_finance_entries.*.unit_price' => ['nullable', 'numeric'],

            // Times
            'start-date' => ['nullable', 'date'],
            'start-time' => ['nullable', 'date_format:H:i'],
            'recurring_weekday' => [Rule::requiredIf(fn () => $this->boolean('recurring')), 'nullable', 'integer', 'between:1,7'],
            'end-date' => [Rule::requiredIf(fn () => !$this->boolean('recurring')), 'nullable', 'date', 'after_or_equal:start-date'],
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


    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array{
        return [
            // General
            'title.required' => 'De titel is verplicht.',
            'description.required' => 'Beschrijving is verplicht.',
            'image-upload.required' => 'Er moet een afbeelding worden geüpload.',
            'location.required' => 'De locatie is verplicht.',
            'organizer.required' => 'De organisator is verplicht.',
            'maxParticipants.integer' => 'Het maximum aantal deelnemers moet een geldig getal zijn.',
            'price.regex' => 'Het prijsformaat is ongeldig. Prijzen dienen opgeschreven te worden als "0.00"',
            'whatsappUrl.url' => 'De WhatsApp-URL moet een geldige URL zijn.',
            'personalConfirmation.required_if' => 'Persoonlijke bevestiging is verplicht wanneer deze optie aan staat.',
            'manual_budget.regex' => 'Begroot bedrag moet een geldig bedrag zijn.',
            'manual_finance_entries.*.unit_price.regex' => 'Bijdrage per deelnemer moet in formaat 0.00 staan.',

            // Times
            'start-date.date' => 'De startdatum moet een geldige datum zijn.',
            'start-time.date_format' => 'De starttijd moet in het formaat HH:MM zijn.',
            'recurring_weekday.required' => 'Kies een dag van de week voor een herhalende activiteit.',
            'recurring_weekday.integer' => 'De gekozen weekdag is ongeldig.',
            'recurring_weekday.between' => 'Kies een geldige weekdag.',

            // End times
            'end-date.required' => 'De einddatum is verplicht.',
            'end-date.date' => 'De einddatum moet een geldige datum zijn.',
            'end-date.after_or_equal' => 'De einddatum moet gelijk zijn aan of na de startdatum.',
            'end-time.date_format' => 'De eindtijd moet in het formaat HH:MM zijn.',

            // Registration Period
            'registrationStart.date' => 'De startdatum van de inschrijving moet een geldige datum zijn.',
            'registrationEnd.date' => 'De einddatum van de inschrijving moet een geldige datum zijn.',
            'registrationEnd.after_or_equal' => 'De einddatum van de inschrijving moet op of na de startdatum van de inschrijving liggen.',
            'registrationEnd.before_or_equal' => 'De einddatum van de inschrijving moet op of vóór de einddatum van de activiteit liggen.',

            // Cancellation date
            'cancellationEnd.date' => 'De annuleringsdatum moet een geldige datum zijn.',
            'cancellationEnd.after_or_equal' => 'De annuleringsdatum moet op of na de startdatum van de inschrijving liggen.',
            'cancellationEnd.before_or_equal' => 'De annuleringsdatum moet op of vóór de einddatum van de activiteit liggen.',

            // Questions
            'questions.*.prijs.regex' => 'De prijs van een vraag moet in het formaat "0.00" zijn.',
            'questions.*.max.integer' => 'Het maximum aantal voor een vraag moet een geldig getal zijn.',
            'questions.*.options.required_if' => 'Opties zijn verplicht voor vragen van het type "select".',
            'questions.*.options.*.optie.required_with' => 'Elke optie moet een geldige waarde hebben.',
            'questions.*.options.*.prijs.regex' => 'De prijs van een optie moet in het formaat "0.00" zijn.',
        ];
    }
}
