<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
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
            'type' => 'required|string|in:bestuurslid,test',
            'image' => 'nullable|image|max:65536',
            'pdf' => 'nullable|mimes:pdf|max:65536',
            'title' => 'required|string|max:255',
            'description' => 'required',
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
            'type.required' => 'Het type is verplicht.',
            'type.string' => 'Het type moet een tekst zijn.',
            'type.in' => 'Het type moet een van de volgende waarden zijn: bestuurslid, test.',
            'image.image' => 'De afbeelding moet een geldig afbeeldingsbestand zijn.',
            'pdf.mimes' => 'Het PDF-bestand moet van het type pdf zijn.',
            'title.required' => 'De titel is verplicht.',
            'title.string' => 'De titel moet een tekst zijn.',
            'title.max' => 'De titel mag niet langer zijn dan 255 tekens.',
            'description.required' => 'De beschrijving is verplicht.',
            'image.max' => 'De afbeelding mag niet groter zijn dan 64MB',
            'pdf.max' => 'Het PDF-bestand mag niet groter zijn dan 64MB',
        ];
    }
}
