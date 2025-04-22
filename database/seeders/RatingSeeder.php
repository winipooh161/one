<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Rating;
use App\Models\Recipe;
use App\Models\User;

class RatingSeeder extends Seeder
{
    /**
     * Заполнение таблицы оценок.
     *
     * @return void
     */
    public function run()
    {
        // Получаем рецепты и пользователей
        $recipes = Recipe::all();
        $users = User::all();
        
        if ($recipes->isNotEmpty() && $users->isNotEmpty()) {
            // Тестовые оценки для рецептов
            $ratings = [
                [
                    'recipe_id' => 1, // ID рецепта
                    'user_id' => 2, // ID пользователя
                    'rating' => 5,
                    'comment' => 'Отличный рецепт! Очень вкусно получилось.'
                ],
                [
                    'recipe_id' => 1,
                    'user_id' => 1,
                    'rating' => 4,
                    'comment' => 'Хороший рецепт, но я добавил больше чеснока.'
                ],
                [
                    'recipe_id' => 2,
                    'user_id' => 2,
                    'rating' => 5,
                    'comment' => 'Торт получился изумительный! Всем рекомендую.'
                ],
                [
                    'recipe_id' => 3,
                    'user_id' => 1,
                    'rating' => 5,
                    'comment' => 'Салат простой и вкусный.'
                ],
            ];
            
            foreach ($ratings as $ratingData) {
                Rating::firstOrCreate(
                    [
                        'recipe_id' => $ratingData['recipe_id'],
                        'user_id' => $ratingData['user_id']
                    ],
                    [
                        'rating' => $ratingData['rating'],
                        'comment' => $ratingData['comment'],
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
