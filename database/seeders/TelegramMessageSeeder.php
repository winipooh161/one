<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TelegramMessage;

class TelegramMessageSeeder extends Seeder
{
    /**
     * Заполнение таблицы сообщений Telegram.
     *
     * @return void
     */
    public function run()
    {
        // Пример тестовых сообщений
        $messages = [
            [
                'message_id' => '1001',
                'chat_id' => '123456789',
                'text' => '/start',
                'direction' => 'incoming',
                'created_at' => now()->subMinutes(30),
            ],
            [
                'message_id' => '1002',
                'chat_id' => '123456789',
                'text' => 'Добро пожаловать в бот кулинарных рецептов! Я помогу вам найти рецепты по интересующим вас ингредиентам или названиям блюд.',
                'direction' => 'outgoing',
                'created_at' => now()->subMinutes(29),
            ],
            [
                'message_id' => '1003',
                'chat_id' => '123456789',
                'text' => 'паста карбонара',
                'direction' => 'incoming',
                'created_at' => now()->subMinutes(20),
            ],
            [
                'message_id' => '1004',
                'chat_id' => '123456789',
                'text' => 'Найдено 1 рецептов по запросу "паста карбонара":\n\n1. Паста Карбонара\nКлассическая итальянская паста с беконом и сливочно-яичным соусом.\n\nДля просмотра полного рецепта отправьте номер рецепта.',
                'direction' => 'outgoing',
                'created_at' => now()->subMinutes(19),
            ],
        ];
        
        foreach ($messages as $messageData) {
            TelegramMessage::firstOrCreate(
                [
                    'message_id' => $messageData['message_id'],
                    'chat_id' => $messageData['chat_id']
                ],
                [
                    'text' => $messageData['text'],
                    'direction' => $messageData['direction'],
                    'created_at' => $messageData['created_at'],
                ]
            );
        }
    }
}
