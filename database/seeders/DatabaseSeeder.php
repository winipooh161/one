<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Запуск сидеров базы данных.
     *
     * @return void
     */
    public function run()
    {
        // Порядок важен из-за связей между таблицами
        $this->call([
            UserSeeder::class,
            CategorySeeder::class,
            TagSeeder::class, 
            RecipeSeeder::class,
            IngredientSeeder::class,
            StepSeeder::class,
            RatingSeeder::class,
            SocialPostSeeder::class,
            NotificationSeeder::class,
            TelegramBotSettingsSeeder::class,
            TelegramChatSeeder::class,
            TelegramMessageSeeder::class,
        ]);
    }
}
