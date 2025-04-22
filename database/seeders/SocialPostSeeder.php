<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\SocialPost;
use App\Models\Recipe;

class SocialPostSeeder extends Seeder
{
    /**
     * Заполнение таблицы постов в соцсетях.
     *
     * @return void
     */
    public function run()
    {
        // Получаем рецепты
        $carbonara = Recipe::where('title', 'Паста Карбонара')->first();
        
        if ($carbonara) {
            // Создаем тестовый пост для Telegram
            SocialPost::firstOrCreate(
                [
                    'recipe_id' => $carbonara->id,
                    'platform' => 'telegram'
                ],
                [
                    'title' => $carbonara->title,
                    'content' => "🍳 *{$carbonara->title}*\n\n{$carbonara->description}\n\n⏱ *Время приготовления:* {$carbonara->cooking_time} минут\n👥 *Порций:* {$carbonara->servings}\n\n📋 *Ингредиенты:*\n• Спагетти - 400 г\n• Бекон - 200 г\n• Яйца - 4 шт\n• Сыр пармезан - 100 г\n• Чеснок - 2 зубчика\n• Соль и перец по вкусу\n\nПодробнее на сайте: https://example.com/recipes/{$carbonara->slug}",
                    'image_url' => $carbonara->image_url,
                    'status' => 'published',
                    'telegram_status' => true,
                    'telegram_posted_at' => now()->subDays(2),
                    'published_at' => now()->subDays(2),
                    'created_at' => now()->subDays(2),
                ]
            );
        }
    }
}
