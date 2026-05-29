<?php
// This file is part of the app logic and has a short comment so it is easier to read.


namespace App\Imports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ToCollection;
use Maatwebsite\Excel\Concerns\WithStartRow;

class NotifyImport implements ToCollection, WithStartRow
{
    /**
    * @param Collection $collection
    */
    public function collection(Collection $collection)
    {

    }

    public function startRow(): int
    {
        return 2;
    }
}
