<?php

namespace App\Console\Commands;

use App\Models\User;
use App\UserType;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RestoreDeletedMembers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:restore-members {--since= : Restore members deleted since this timestamp (Y-m-d H:i:s) } {--minutes= : Restore members deleted within the last N minutes } {--dry-run : Do not perform changes, only show affected rows }';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Restore soft-deleted member types (gepensioneerde, inhuur, erelid) deleted after a given time';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $since = $this->option('since');
        $minutes = $this->option('minutes');
        $dryRun = $this->option('dry-run');

        if (!$since && !$minutes) {
            $this->error('Please provide --since="Y-m-d H:i:s" or --minutes=NN');
            return 1;
        }

        if ($minutes) {
            $sinceAt = Carbon::now()->subMinutes((int) $minutes);
        } else {
            try {
                $sinceAt = Carbon::createFromFormat('Y-m-d H:i:s', $since);
            } catch (\Throwable $e) {
                $this->error('Invalid --since format, use Y-m-d H:i:s');
                return 1;
            }
        }

        $types = [
            UserType::Gepensioneerde->value,
            UserType::Inhuur->value,
            UserType::EreLid->value,
        ];

        $query = User::withTrashed()
            ->whereIn('type', $types)
            ->whereNotNull('deleted_at')
            ->where('deleted_at', '>=', $sinceAt->toDateTimeString());

        $count = $query->count();

        $this->info("Found {$count} deleted member(s) since {$sinceAt->toDateTimeString()}.");

        if ($count === 0) {
            return 0;
        }

        if ($dryRun) {
            $this->table(['id', 'email', 'deleted_at', 'type'], $query->get(['id', 'email', 'deleted_at', 'type'])->toArray());
            return 0;
        }

        $this->info('Restoring...');
        $restored = $query->restore();

        $this->info("Restored {$restored} users.");

        return 0;
    }
}
