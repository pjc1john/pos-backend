<?php

namespace Database\Seeders;

use App\Models\Subscriber;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $subscriber = Subscriber::create([
            'name' => 'Default Business',
            'email' => 'admin@business.com',
        ]);

        User::create([
            'subscriber_id' => $subscriber->id,
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'admin@business.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
    }
}
