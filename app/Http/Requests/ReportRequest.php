<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ReportRequest extends FormRequest{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(){
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(){
        return [
            // General information
//            'report-type' => ['required','in:Activiteit,Jaar'],
            'report-title' => ['required','max:255', 'unique:contents,name'],
            'report-file' => ['required','file','mimes:pdf','max:2048'],
            'report-is-year' => ['nullable', 'regex:/^\d{4}$|^-$/'],

            // Activity specific information
//            'activity-select' => ['nullable'],
//            'activity-report-text' => ['required_if:report-type,Activiteit'],
//            'activity-report-image' => ['required_if:report-type,Activiteit','image'],

            // Yearly specific information
//            'yearly-report-year' => ['required_if:report-type,Jaar'],
//            'yearly-report-file' => ['required_if:report-type,Jaar','file','mimes:pdf'],
        ];
    }

    /**
     * Get custom error messages for validator errors.
     */
    public function messages(): array{
        return [
            'report-type.required' => 'Het type verslag is verplicht.',

            'report-title.required' => 'De titel van het verslag is verplicht.',
            'report-title.max' => 'De titel van het verslag mag maximaal 255 tekens bevatten.',
            'report-title.unique' => 'De naam van het verslag is al ingebruik.',

            'report-file.required' => 'Een bestand is verplicht voor een verslag.',
            'report-file.mimes' => 'Het verslagbestand moet een PDF-bestand zijn.',

            'report-is-year.regex' => 'Het jaar moet een heel getal zijn of \'-\' zijn.',

            'activity-report-text.required_if' => 'Tekst is verplicht voor activiteitsverslagen.',
            'activity-report-image.required_if' => 'Een afbeelding is verplicht voor activiteitsverslagen.',
            'activity-report-image.image' => 'Het geüploade bestand moet een afbeelding zijn.',

            'yearly-report-year.required_if' => 'Het jaartal is verplicht voor jaarverslagen.',
            'yearly-report-file.required_if' => 'Een bestand is verplicht voor jaarverslagen.',
            'yearly-report-file.mimes' => 'Het verslagbestand moet een PDF-bestand zijn.',
        ];
    }
}
