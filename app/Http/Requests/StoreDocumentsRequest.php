<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreDocumentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'files' => ['required', 'array', 'min:1', 'max:10'],
            'files.*' => ['required', File::types(['doc', 'docx', 'xls', 'xlsx'])->max(20480), 'extensions:doc,docx,xls,xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Kies minimaal een bestand.',
            'files.max' => 'Upload maximaal 10 bestanden tegelijk.',
            'files.*.mimes' => 'Alleen Word- en Excel-bestanden zijn toegestaan (doc, docx, xls, xlsx).',
            'files.*.extensions' => 'Alleen Word- en Excel-bestanden zijn toegestaan (doc, docx, xls, xlsx).',
            'files.*.max' => 'Een bestand mag maximaal 20 MB groot zijn.',
            'files.*.uploaded' => 'Het bestand kon niet worden ontvangen. Controleer de bestandsgrootte.',
        ];
    }
}