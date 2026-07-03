<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class CreateDefaultUser extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'user:create-default';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Create a default admin user if none exists';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        if (User::count() === 0) {
            User::create([
                'name' => 'Administrator',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
            ]);
            
            $this->info('Default admin user created successfully!');
            $this->info('Email: admin@example.com');
            $this->info('Password: password');
            $this->warn('Please change the password after first login!');
        } else {
            $this->info('Users already exist. Skipping default user creation.');
        }
    }
}
