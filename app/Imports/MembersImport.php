<?php

namespace App\Imports;

use App\Models\User;
use App\UserType;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithChunkReading;
use Maatwebsite\Excel\Concerns\WithHeadingRow;

class MembersImport implements ToCollection, WithHeadingRow, WithChunkReading
{
    /**
     * Process each chunk of rows.
     */
    public function collection(Collection $collection)
    {
        $batch = [];
        $batchSize = 100; // adjust for your memory limits

        foreach ($collection as $row) {
            $email = $row['mailadres'] ?? null;
            $type = match ($row['functie'] ?? null) {
                'gepensioneerd' => UserType::Gepensioneerde,
                'inhuur' => UserType::Inhuur,
                'erelid' => UserType::EreLid,
                default => null,
            };

            if (empty($email) || !in_array($type?->value, UserType::toArray())) {
                continue;
            }

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
    }

//    public function headingRow(): int
//    {
//        return 6; // your header row
//    }

    public function chunkSize(): int
    {
        return 100; // adjust chunk size to reduce memory
    }
}
