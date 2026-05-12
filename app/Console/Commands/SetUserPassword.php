<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class SetUserPassword extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:set-password {email} {--password=}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Set a new password for a user. If no password is provided, a random one will be generated.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $email = $this->argument('email');
        $password = $this->option('password');

        // Find the user
        $user = User::where('email', $email)->first();

        if (!$user) {
            $this->error("User with email '{$email}' not found.");
            return self::FAILURE;
        }

        // Generate random password if not provided
        if (!$password) {
            $password = Str::random(12);
        }

        // Update password
        $user->password = Hash::make($password);
        $user->save();

        $this->info("Password updated successfully for user: {$email}");
        $this->info("New password: {$password}");
        $this->warn("Make sure to share this password securely with the user.");

        return self::SUCCESS;
    }
}
