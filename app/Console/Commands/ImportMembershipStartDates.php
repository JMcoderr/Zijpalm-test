<?php

namespace App\Console\Commands;

use App\Models\User;
use DateTimeImmutable;
use DateTimeInterface;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\Cell\Cell;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ImportMembershipStartDates extends Command
{
    protected $signature = 'members:import-start-dates
        {file : Path to an ODS, XLSX, XLS, or CSV file}
        {--apply : Write valid changes to the database; without this option the command only previews}
        {--overwrite : Replace existing start dates; only effective with --apply}';

    protected $description = 'Import membership start dates by matching spreadsheet email addresses';

    public function handle(): int
    {
        if ($this->option('overwrite') && ! $this->option('apply')) {
            $this->error('The --overwrite option requires --apply.');

            return self::FAILURE;
        }

        $path = $this->argument('file');
        if (! is_file($path) || ! is_readable($path)) {
            $this->error("The input file does not exist or cannot be read: {$path}");

            return self::FAILURE;
        }

        $spreadsheet = IOFactory::load($path);
        $worksheet = $this->findImportWorksheet($spreadsheet->getAllSheets());

        if ($worksheet === null) {
            $this->error('No sheet was found with an Ingangsdatum column and an email or Mailadres column.');
            $spreadsheet->disconnectWorksheets();

            return self::FAILURE;
        }

        $headers = $this->headerColumns($worksheet);
        $emailColumn = $headers['email'] ?? $headers['mailadres'];
        $sourceEmailColumn = $headers['mailadres'] ?? null;
        $dateColumn = $headers['ingangsdatum'];
        $emailColumnsDiffer = isset($headers['email'], $headers['mailadres'])
            && $headers['email'] !== $headers['mailadres'];

        $updates = [];
        $report = [];
        $errors = [];
        $seenEmails = [];
        $blankDates = 0;
        $keptExisting = 0;
        $alreadyCorrect = 0;

        for ($rowNumber = 2; $rowNumber <= $worksheet->getHighestDataRow(); $rowNumber++) {
            $dateCell = $worksheet->getCell($dateColumn.$rowNumber);
            $rawDate = $this->cellValue($dateCell);

            if ($rawDate === null || (is_string($rawDate) && trim($rawDate) === '')) {
                if ($dateCell->getDataType() === DataType::TYPE_FORMULA) {
                    $errors[] = "Row {$rowNumber}: the Ingangsdatum formula has no cached value.";
                    $report[] = [$rowNumber, '', '', '', 'Error: missing formula result'];
                } else {
                    $blankDates++;
                }

                continue;
            }

            $email = strtolower(trim((string) $this->cellValue($worksheet->getCell($emailColumn.$rowNumber))));
            $sourceEmail = $sourceEmailColumn === null
                ? ''
                : strtolower(trim((string) $this->cellValue($worksheet->getCell($sourceEmailColumn.$rowNumber))));

            if ($email !== '' && $sourceEmail !== '' && $email !== $sourceEmail && $emailColumnsDiffer) {
                $errors[] = "Row {$rowNumber}: the email and Mailadres values do not match.";
                $report[] = [$rowNumber, $email, '', '', 'Error: conflicting email addresses'];

                continue;
            }

            $email = $email !== '' ? $email : $sourceEmail;
            if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $errors[] = "Row {$rowNumber}: no valid email address was found.";
                $report[] = [$rowNumber, $email, '', '', 'Error: invalid email'];

                continue;
            }

            if (isset($seenEmails[$email])) {
                $errors[] = "Row {$rowNumber}: email address is repeated (also on row {$seenEmails[$email]}).";
                $report[] = [$rowNumber, $email, '', '', 'Error: duplicate email'];

                continue;
            }
            $seenEmails[$email] = $rowNumber;

            $startDate = $this->parseDate($rawDate);
            if ($startDate === null) {
                $errors[] = "Row {$rowNumber}: Ingangsdatum is not a valid date.";
                $report[] = [$rowNumber, $email, (string) $rawDate, '', 'Error: invalid date'];

                continue;
            }

            $users = User::query()
                ->where(function ($query) use ($email) {
                    foreach (['email', 'emailSecondary', 'emailTertiary'] as $column) {
                        $query->orWhereRaw("LOWER(TRIM({$column})) = ?", [$email]);
                    }
                })
                ->get();

            if ($users->count() !== 1) {
                $reason = $users->isEmpty() ? 'no active user matched' : 'email matched multiple active users';
                $errors[] = "Row {$rowNumber}: {$reason} for {$email}.";
                $report[] = [$rowNumber, $email, $startDate, '', 'Error: '.$reason];

                continue;
            }

            $user = $users->first();
            $existingDate = $user->membership_start_date?->format('Y-m-d') ?? '';

            if ($existingDate === $startDate) {
                $alreadyCorrect++;
                $report[] = [$rowNumber, $email, $startDate, $existingDate, 'Already correct'];

                continue;
            }

            if ($existingDate !== '' && ! $this->option('overwrite')) {
                $keptExisting++;
                $report[] = [$rowNumber, $email, $startDate, $existingDate, 'Kept existing date'];

                continue;
            }

            $updates[] = ['user' => $user, 'date' => $startDate];
            $report[] = [
                $rowNumber,
                $email,
                $startDate,
                $existingDate,
                $this->option('apply') ? 'Will update' : 'Would update',
            ];
        }

        $spreadsheet->disconnectWorksheets();

        if ($report !== []) {
            $this->table(['Row', 'Email', 'Spreadsheet date', 'Current date', 'Action'], $report);
        }

        if ($errors !== []) {
            $this->newLine();
            $this->error('No changes were written. Resolve the following issues and run the command again:');
            foreach ($errors as $error) {
                $this->line('- '.$error);
            }
        } elseif ($this->option('apply')) {
            DB::transaction(function () use ($updates) {
                foreach ($updates as $update) {
                    $user = $update['user'];
                    $user->membership_start_date = $update['date'];

                    if (! $user->save()) {
                        throw new \RuntimeException("Could not save membership start date for user ID {$user->getKey()}.");
                    }
                }
            });

            $this->info('Updated '.count($updates).' membership start date(s).');
        } else {
            $this->info('Preview only; no database changes were made. Add --apply to write the changes.');
        }

        $this->line("Blank dates skipped: {$blankDates}; existing dates kept: {$keptExisting}; already correct: {$alreadyCorrect}; issues: ".count($errors));

        return $errors === [] ? self::SUCCESS : self::FAILURE;
    }

    /**
     * @param  array<int, Worksheet>  $worksheets
     */
    private function findImportWorksheet(array $worksheets): ?Worksheet
    {
        foreach ($worksheets as $worksheet) {
            $headers = $this->headerColumns($worksheet);

            if (isset($headers['ingangsdatum']) && (isset($headers['email']) || isset($headers['mailadres']))) {
                return $worksheet;
            }
        }

        return null;
    }

    /**
     * @return array<string, string>
     */
    private function headerColumns(Worksheet $worksheet): array
    {
        $headers = [];

        foreach ($worksheet->getRowIterator(1, 1) as $row) {
            foreach ($row->getCellIterator() as $cell) {
                $header = strtolower(trim((string) $cell->getValue()));
                if ($header !== '') {
                    $headers[$header] = $cell->getColumn();
                }
            }
        }

        return $headers;
    }

    private function cellValue(Cell $cell): mixed
    {
        if ($cell->getDataType() === DataType::TYPE_FORMULA) {
            return $cell->getOldCalculatedValue();
        }

        return $cell->getValue();
    }

    private function parseDate(mixed $value): ?string
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d');
        }

        if (is_int($value) || is_float($value) || (is_string($value) && is_numeric(trim($value)))) {
            $serial = (float) $value;
            if ($serial < 1 || $serial > 2958465) {
                return null;
            }

            return Date::excelToDateTimeObject($serial)->format('Y-m-d');
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $value = trim($value);
        foreach ([
            '!Y-m-d',
            '!d-m-Y',
            '!j-n-Y',
            '!d/m/Y',
            '!j/n/Y',
            '!d.m.Y',
            '!j.n.Y',
            '!d-M-y',
            '!j-M-y',
            '!d-M-Y',
            '!j-M-Y',
        ] as $format) {
            $date = DateTimeImmutable::createFromFormat($format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date !== false && ($errors === false || ($errors['warning_count'] === 0 && $errors['error_count'] === 0))) {
                return $date->format('Y-m-d');
            }
        }

        return null;
    }
}
