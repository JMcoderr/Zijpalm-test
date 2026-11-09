<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class ReplaceDocumentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() ?? false;
    }

    public function rules(): array
    {
        return [
            'file' => ['required', File::types(['pdf', 'doc', 'docx', 'xls', 'xlsx'])->max(20480), 'extensions:pdf,doc,docx,xls,xlsx'],
        ];
    }

    public function messages(): array
    {
        return [
            'file.required' => 'Kies de bewerkte versie van het bestand.',
            'file.mimes' => 'Alleen PDF-, Word- en Excel-bestanden zijn toegestaan.',
            'file.extensions' => 'Alleen PDF-, Word- en Excel-bestanden zijn toegestaan.',
            'file.max' => 'Een bestand mag maximaal 20 MB groot zijn.',
            'file.uploaded' => 'Het bestand kon niet worden ontvangen. Controleer de bestandsgrootte.',
        ];
    }
}