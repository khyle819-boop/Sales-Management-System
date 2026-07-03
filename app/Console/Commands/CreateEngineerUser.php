<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CreateEngineerUser extends Command
{
    protected $signature = 'user:create-engineer {--email=engineer@example.com} {--password=password} {--name=Engineer User}';
    protected $description = 'Create a default engineer user (or override via options)';

    public function handle(): int
    {
        $email = (string) $this->option('email');
        $password = (string) $this->option('password');
        $name = (string) $this->option('name');

        if (User::where('email', $email)->exists()) {
            $this->warn('A user with this email already exists: '.$email);
            return self::SUCCESS;
        }

        $user = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => 'engineer',
        ]);

        $this->info('Engineer user created successfully.');
        $this->line('Email: '.$user->email);
        $this->line('Password: '.$password);
        return self::SUCCESS;
    }
}


