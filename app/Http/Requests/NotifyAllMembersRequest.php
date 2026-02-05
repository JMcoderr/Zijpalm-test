<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NotifyAllMembersRequest extends FormRequest
{
    protected array $delay;
    protected array $batchSize;

    public function __construct()
    {
        parent::__construct();
        $this->delay = config('mail.power_automate.delay');
        $this->batchSize = config('mail.power_automate.batch_size');
    }

    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->isAdmin();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'subject' => 'required|string',
            'description' => 'required|json',
            'delay' => "required|integer|between:{$this->delay['min']},{$this->delay['max']}",
            'batch_size' => "required|integer|between:{$this->batchSize['min']}, {$this->batchSize['max']}"
        ];
    }

    /**
     * @return string[]
     */
    public function messages(): array{
        return [
            'subject.required' => 'Beschrijving is verplicht.',
            'subject.string' => 'De beschrijving mag alleen tekst bevatten.',
            'description.required' => 'Beschrijving is verplicht.',
            'description.json' => 'Er is iets fout gegaan bij het aanmaken van de beschrijving.',
            'delay.required' => 'Wachttijd is verplicht.',
            'delay.integer' => 'Wachttijd moet een heel nummer zijn.',
            'delay.between' => "De wachttijd moet tussen {$this->delay['min']} en {$this->delay['max']} seconden zijn.",
            'batch_size.required' => 'Aantal ontvangers per mail is verplicht.',
            'batch_size.integer' => 'Aantal ontvangers per mail moet een heel nummer zijn.',
            'batch_size.between' => "Het aantal ontvangers per mail moet tussen {$this->batchSize['min']} en {$this->batchSize['max']} liggen.",
        ];
    }
}
