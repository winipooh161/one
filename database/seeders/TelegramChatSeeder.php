<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TelegramChat;

class TelegramChatSeeder extends Seeder
{
    /**
     * Заполнение таблицы чатов Telegram.
     *
     * @return void
     */
    public function run()
    {
        // Пример тестовых данных
        $chats = [
            [
                'chat_id' => '123456789',
                'type' => 'private',
                'username' => 'testuser1',
                'first_name' => 'Иван',
                'last_name' => 'Петров',
                'is_active' => true,
                'last_activity_at' => now(),
            ],
            [
                'chat_id' => '987654321',
                'type' => 'private',
                'username' => 'testuser2',
                'first_name' => 'Мария',
                'last_name' => 'Иванова',
                'is_active' => true,
                'last_activity_at' => now()->subHours(2),
            ],
            [
                'chat_id' => '-100123456789',
                'type' => 'group',
                'username' => null,
                'first_name' => null,
                'last_name' => null,
                'is_active' => true,
                'last_activity_at' => now()->subDays(1),
                'additional_data' => json_encode(['title' => 'Тестовая группа']),
            ],
        ];
        
        foreach ($chats as $chatData) {
            TelegramChat::firstOrCreate(
                ['chat_id' => $chatData['chat_id']],
                [
                    'type' => $chatData['type'],
                    'username' => $chatData['username'],
                    'first_name' => $chatData['first_name'],
                    'last_name' => $chatData['last_name'],
                    'is_active' => $chatData['is_active'],
                    'last_activity_at' => $chatData['last_activity_at'],
                    'additional_data' => $chatData['additional_data'] ?? null,
                    'created_at' => now(),
                ]
            );
        }
    }
}
