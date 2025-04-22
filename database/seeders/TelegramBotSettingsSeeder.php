<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\TelegramBotSetting;

class TelegramBotSettingsSeeder extends Seeder
{
    /**
     * Заполнение настроек Telegram бота.
     *
     * @return void
     */
    public function run()
    {
        // Базовые настройки для Telegram бота
        $settings = [
            [
                'key' => 'not_found_message',
                'value' => 'К сожалению, я не нашел рецептов по вашему запросу. Попробуйте другие ключевые слова или параметры поиска.',
                'description' => 'Сообщение при отсутствии результатов поиска'
            ],
            [
                'key' => 'max_results',
                'value' => '5',
                'description' => 'Максимальное количество результатов поиска'
            ],
            [
                'key' => 'search_mode',
                'value' => 'broad',
                'description' => 'Режим поиска: strict (строгий) или broad (широкий)'
            ],
            [
                'key' => 'welcome_message',
                'value' => 'Добро пожаловать в бот кулинарных рецептов! Я помогу вам найти рецепты по интересующим вас ингредиентам или названиям блюд.',
                'description' => 'Приветственное сообщение для новых пользователей'
            ],
            [
                'key' => 'help_message',
                'value' => 'Отправьте мне название блюда или ингредиенты, и я найду подходящие рецепты. Используйте команду /random для случайного рецепта.',
                'description' => 'Справочное сообщение'
            ]
        ];
        
        foreach ($settings as $setting) {
            TelegramBotSetting::firstOrCreate(
                ['key' => $setting['key']],
                [
                    'value' => $setting['value'],
                    'description' => $setting['description'],
                    'created_at' => now(),
                ]
            );
        }
    }
}
