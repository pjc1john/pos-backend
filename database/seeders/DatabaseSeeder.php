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
            'email' => 'pjc1john@gmail.com',
        ]);

        User::create([
            'subscriber_id' => $subscriber->id,
            'username' => 'admin',
            'name' => 'Administrator',
            'email' => 'pjc1john@gmail.com',
            'password' => Hash::make('password'),
            'role_id' => 1,
        ]);
    }
}
