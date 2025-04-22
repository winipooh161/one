<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    /**
     * Заполнение тестовыми пользователями.
     *
     * @return void
     */
    public function run()
    {
        // Создаем администратора
        User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Администратор',
                'password' => Hash::make('password'),
                'role' => 'admin',
                'created_at' => now(),
                'email_verified_at' => now(),
            ]
        );

        // Создаем обычного пользователя
        User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'Пользователь',
                'password' => Hash::make('password'),
                'role' => 'user',
                'created_at' => now(),
                'email_verified_at' => now(),
            ]
        );
    }
}
