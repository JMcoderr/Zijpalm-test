<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Imports;

use App\Models\User;
use App\UserType;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MembersImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    protected array $seenMembers = [];

    /**
     * Process each chunk of rows.
     */
    public function collection(Collection $collection)
    {
        $batch = [];
        $batchSize = 100; // adjust for your memory limits

        foreach ($collection as $row) {
            $email = Str::lower(trim((string) ($row['mailadres'] ?? '')));
            $rawType = Str::of((string) ($row['functie'] ?? ''))
                ->trim()
                ->lower()
                ->replace(['-', '_'], ' ')
                ->squish()
                ->value();

            $type = match ($rawType) {
                'gepensioneerd' => UserType::Gepensioneerde,
                'gepensioneerde' => UserType::Gepensioneerde,
                'inhuur' => UserType::Inhuur,
                'ingehuurd' => UserType::Inhuur,
                'erelid' => UserType::EreLid,
                'ere lid' => UserType::EreLid,
                default => null,
            };

            if (empty($email) || !in_array($type?->value, UserType::toArray())) {
                continue;
            }

            $this->seenMembers[] = $email;

            $batch[] = [
                'firstName' => $row['voornaam'],
                'lastName' => $row['achternaam'],
                'email' => $email,
                'notifications' => 61,
                'deleted_at' => null,
                'type' => $type
            ];

            if (count($batch) >= $batchSize) {
                User::query()->upsert(
                    $batch,
                    ['email'], // unique key
                    ['firstName', 'lastName', 'notifications', 'deleted_at', 'type']
                );
                $batch = []; // free memory
            }
        }

        // flush remaining batch
        if (!empty($batch)) {
            User::query()->upsert(
                $batch,
                ['email'],
                ['firstName', 'lastName', 'notifications', 'deleted_at', 'type']
            );
        }

        $this->softDeleteMissingMembers();
    }

//    public function headingRow(): int
//    {
//        return 6; // your header row
//    }

    public function chunkSize(): int
    {
        return 100; // adjust chunk size to reduce memory
    }

    /**
     * Soft delete imported member types that are no longer present in the latest file.
     */
    protected function softDeleteMissingMembers(): void
    {
        $seenMembers = array_values(array_unique($this->seenMembers));

        if (empty($seenMembers)) {
            return;
        }

        User::query()
            ->whereIn('type', [
                UserType::Gepensioneerde,
                UserType::Inhuur,
                UserType::EreLid,
            ])
            ->whereNotIn('email', $seenMembers)
            ->delete();
    }
}
