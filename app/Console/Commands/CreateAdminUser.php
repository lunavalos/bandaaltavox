<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;

class CreateAdminUser extends Command
{
    protected $signature = 'app:create-admin
                            {--name= : Full name (prompted if omitted)}
                            {--email= : Email address (prompted if omitted)}';

    protected $description = 'Create (or promote) a Super Admin user interactively, without committing credentials';

    public function handle(): int
    {
        if (! Role::where('name', 'Super Admin')->exists()) {
            $this->error('Role "Super Admin" not found. Run: php artisan db:seed --class=RoleSeeder --force');

            return self::FAILURE;
        }

        $name  = $this->option('name') ?: $this->ask('Name');
        $email = $this->option('email') ?: $this->ask('Email');
        $password = $this->secret('Password (input hidden)');
        $confirm  = $this->secret('Confirm password');

        $validator = Validator::make(
            compact('name', 'email', 'password', 'confirm'),
            [
                'name'     => ['required', 'string', 'max:255'],
                'email'    => ['required', 'email', 'max:255'],
                'password' => ['required', 'string', 'min:8', 'same:confirm'],
            ]
        );

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        $existing = User::where('email', $email)->exists();

        $user = User::updateOrCreate(
            ['email' => $email],
            [
                'name'              => $name,
                'password'          => Hash::make($password),
                'is_active'         => true,
                'email_verified_at' => now(),
            ]
        );

        $user->syncRoles('Super Admin');

        $this->info($existing
            ? "Existing user {$email} promoted to Super Admin."
            : "Super Admin {$email} created successfully.");

        return self::SUCCESS;
    }
}
