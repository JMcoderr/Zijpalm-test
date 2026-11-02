<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;
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
            'files' => ['required', 'array', 'min:1', 'max:100'],
            'files.*' => ['required', File::types(['doc', 'docx', 'xls', 'xlsx', 'pdf'])->max(20480), 'extensions:doc,docx,xls,xlsx,pdf'],
            'current_folder' => ['nullable', 'string', 'max:255'],
            'relative_paths' => ['sometimes', 'array', 'max:100'],
            'relative_paths.*' => ['required', 'string', 'max:2048'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            if ($validator->errors()->isNotEmpty()) {
                return;
            }

            $files = $this->file('files', []);
            $relativePaths = $this->input('relative_paths');
            $currentFolder = (string) $this->input('current_folder', '');

            if ($relativePaths !== null && count($relativePaths) !== count($files)) {
                $validator->errors()->add('relative_paths', 'De mapstructuur kon niet worden verwerkt. Kies de map opnieuw.');

                return;
            }

            if (!$this->hasSafePath($currentFolder, true)) {
                $validator->errors()->add('current_folder', 'De gekozen doelmap is ongeldig.');

                return;
            }

            foreach ($files as $index => $file) {
                $relativePath = $relativePaths[$index] ?? $file->getClientOriginalName();

                if (!is_string($relativePath) || !$this->hasSafePath($relativePath)) {
                    $validator->errors()->add("files.$index", 'Een bestands- of mapnaam bevat een ongeldig pad.');

                    continue;
                }

                $segments = explode('/', str_replace('\\', '/', $relativePath));
                $fileName = array_pop($segments);
                $folderPath = trim($currentFolder . '/' . implode('/', $segments), '/');

                if ($fileName !== $file->getClientOriginalName()) {
                    $validator->errors()->add("files.$index", 'Een bestandsnaam komt niet overeen met het geselecteerde bestand.');
                }

                if (mb_strlen($fileName) > 255 || mb_strlen($folderPath) > 255) {
                    $validator->errors()->add("files.$index", 'De bestandsnaam of mapstructuur is te lang.');
                }
            }
        });
    }

    public function messages(): array
    {
        return [
            'files.required' => 'Kies minimaal een bestand.',
            'files.max' => 'Upload maximaal 100 bestanden tegelijk.',
            'files.*.mimes' => 'Alleen PDF-, Word- en Excel-bestanden zijn toegestaan (pdf, doc, docx, xls, xlsx).',
            'files.*.extensions' => 'Alleen PDF-, Word- en Excel-bestanden zijn toegestaan (pdf, doc, docx, xls, xlsx).',
            'files.*.max' => 'Een bestand mag maximaal 20 MB groot zijn.',
            'files.*.uploaded' => 'Het bestand kon niet worden ontvangen. Controleer de bestandsgrootte.',
            'relative_paths.max' => 'Upload maximaal 100 bestanden tegelijk.',
        ];
    }

    private function hasSafePath(string $path, bool $allowEmpty = false): bool
    {
        if ($path === '') {
            return $allowEmpty;
        }

        $path = str_replace('\\', '/', $path);

        if (str_starts_with($path, '/') || preg_match('/^[A-Za-z]:/', $path) || preg_match('/[\x00-\x1F\x7F]/', $path)) {
            return false;
        }

        foreach (explode('/', $path) as $segment) {
            if ($segment === '' || $segment === '.' || $segment === '..') {
                return false;
            }
        }

        return true;
    }
}