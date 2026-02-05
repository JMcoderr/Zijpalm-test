<?php

namespace App\Imports;

use App\Models\User;
use App\UserType;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithHeadingRow;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Illuminate\Support\Collection;

class UsersImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected array $seenEmployees = [];

    /**
     * Process each chunk of rows.
     */
    public function collection(Collection $collection)
    {
        $batch = [];
        $batchSize = 100; // adjust for your memory limits

        foreach ($collection as $row) {
            $id = $row['persnr'] ?? null;
            $email = $row['e_mail'] ?? null;
            $company = $row['omschrijving'] ?? null;

            if (empty($id) || empty($email) || $company !== 'Zijpalm') {
                continue;
            }

            $this->seenEmployees[] = $email;

            $name = $this->splitName($row['naam_medewerkster'] ?? '');
            $batch[] = [
                'employee_number' => $id,
                'firstName' => $name['firstName'],
                'lastName' => trim($name['infix'] . ' ' . $name['lastName']),
                'email' => $email,
//                'phone' => $row['telefoon'] ? $this->getLast8Digits($row['telefoon']) : null,
                'notifications' => 61,
                'deleted_at' => null,
                'type' => UserType::Medewerker
            ];

            if (count($batch) >= $batchSize) {
                User::query()->upsert(
                    $batch,
                    ['email'], // unique key
                    ['firstName', 'lastName', 'employee_number', 'notifications', 'deleted_at', 'type']
                );
                $batch = []; // free memory
            }
        }

        // flush remaining batch
        if (!empty($batch)) {
            User::query()->upsert(
                $batch,
                ['email'],
                ['firstName', 'lastName', 'employee_number', 'notifications', 'deleted_at', 'type']
            );
        }

        // soft delete missing users
        $this->softDeleteMissingUsers();
    }

    public function headingRow(): int
    {
        return 6; // your header row
    }

    public function chunkSize(): int
    {
        return 100; // adjust chunk size to reduce memory
    }

    /**
     * Soft delete users not in the current import.
     */
    protected function softDeleteMissingUsers(): void
    {
        User::query()
            ->where('type', UserType::Medewerker)
            ->whereNotIn('email', $this->seenEmployees)
            ->delete();
    }

    /**
     * @param string $fullName Example: "Aken, H.A. van (Henriëtte)"
     * @return array
     */
    protected function splitName(string $fullName): array
    {
        // Match the pattern: LastName, [Infix] (FirstName)
        // Example: "Aken, H.A. van (Henriëtte)"
        preg_match('/^(.*?),\s*(.*?)\s*(?:\((.*?)\))?$/u', $fullName, $matches);

        // $matches[1] = Last name
        // $matches[2] = Infix + initials
        // $matches[3] = First name (from parentheses)

        $lastName = $matches[1] ?? '';
        $infixAndInitials = $matches[2] ?? '';
        $firstName = $matches[3] ?? '';

        $parts = explode(' ', $infixAndInitials);
        $infix = '';
        $initials = '';

        foreach ($parts as $part) {
            if (mb_strtolower($part) === $part) {
                $infix .= ($infix ? ' ' : '') . $part;
            } else {
                $initials .= ($initials ? ' ' : '') . $part;
            }
        }

        return [
            'firstName' => $firstName,
            'infix' => $infix,
            'lastName' => $lastName,
            'initials' => $initials,
        ];
    }

    /**
     * @param string $input
     * @return string
     */
    protected function getLast8Digits(string $input): string
    {
        // Remove all non-digit characters
        $digits = preg_replace('/\D+/', '', $input);
        return substr($digits, -8);
    }
}
