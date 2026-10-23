<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateContentRequest extends FormRequest
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
        $content = $this->route('content');
        $isReport = $content?->report !== null;

        return [
            'name' => ['nullable', 'string', 'max:255', Rule::unique('contents', 'name')->ignore($content?->id)],
            'image' => 'nullable|image|max:65536',
            'pdf' => 'nullable|mimes:pdf|max:65536',
            'report-image' => 'nullable|image|max:65536',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable',
            'report-type' => $isReport ? ['sometimes', 'required', Rule::in(['activity', 'year'])] : ['prohibited'],
            'report-year' => $isReport ? ['exclude_unless:report-type,year', 'required_if:report-type,year', 'integer', 'digits:4'] : ['prohibited'],
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
            'image.image' => 'Het bestand moet een afbeelding zijn.',
            'image.max' => 'De afbeelding mag niet groter zijn dan 64MB.',
            'pdf.mimes' => 'Het bestand moet een PDF zijn.',
            'pdf.max' => 'De PDF mag niet groter zijn dan 64MB.',
            'report-image.image' => 'De omslag moet een afbeelding zijn.',
            'report-image.max' => 'De omslagafbeelding mag niet groter zijn dan 64MB.',
            'report-type.required' => 'Kies een soort verslag.',
            'report-type.in' => 'Kies Normaal verslag of Jaarverslag.',
            'report-type.prohibited' => 'Deze inhoud is geen verslag.',
            'report-year.required_if' => 'Vul het jaartal van het jaarverslag in.',
            'report-year.integer' => 'Vul een geldig jaartal in.',
            'report-year.digits' => 'Vul een jaartal van vier cijfers in.',
            'report-year.prohibited' => 'Deze inhoud is geen verslag.',
            'title.string' => 'De titel moet een tekst zijn.',
            'title.max' => 'De titel mag niet langer zijn dan 255 tekens.',
            'name.string' => 'De naam moet een tekst zijn.',
            'name.max' => 'De naam mag niet langer zijn dan 255 tekens.',
            'name.prohibited' => 'De naam mag niet verandert worden als dit geen verslag is.',
            'name.unique' => 'Dit bestands naam be staalt al.',
        ];
    }

    /**
     * Custom validation rule for content so that only if it has a report the name can get changed.
     *
     * @param $validator
     * @return void
     */
    public function withValidator($validator): void
    {
        $validator->sometimes('name', 'prohibited', function () {
            $content = $this->route('content'); // or however you get the model

            // If the content has NO report relation => name is NOT allowed
            return !$content || !$content->report()->exists();
        });
    }
}
