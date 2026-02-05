<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

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
        return [
            'name' => 'nullable|string|max:255|unique:contents,name',
            'image' => 'nullable|image|max:65536',
            'pdf' => 'nullable|mimes:pdf|max:65536',
            'title' => 'nullable|string|max:255',
            'description' => 'nullable',
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
