<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Tag;
use App\Models\User;
use Illuminate\Support\Str;

class RecipeSeeder extends Seeder
{
    /**
     * Заполнение таблицы рецептов.
     *
     * @return void
     */
    public function run()
    {
        // Получаем категории и теги для назначения рецептам
        $categories = Category::all();
        $tags = Tag::all();
        $adminUser = User::where('role', 'admin')->first() ?? User::first();

        // Пример рецептов
        $recipes = [
            [
                'title' => 'Паста Карбонара',
                'description' => 'Классическая итальянская паста с беконом и сливочно-яичным соусом.',
                'ingredients' => "Спагетти - 400 г\nБекон или панчетта - 200 г\nЯйца - 4 шт\nСыр пармезан - 100 г\nЧеснок - 2 зубчика\nСоль и перец по вкусу",
                'instructions' => "1. Отварите спагетти в подсоленной воде до состояния аль денте.\n2. Нарежьте бекон кубиками и обжарьте до хрустящей корочки.\n3. Смешайте яйца и тертый пармезан.\n4. Слейте воду с пасты, оставив немного.\n5. Смешайте горячую пасту с беконом, добавьте яично-сырную смесь и быстро перемешайте.\n6. Подавайте сразу, посыпав черным перцем и пармезаном.",
                'cooking_time' => 20,
                'servings' => 4,
                'calories' => 450,
                'proteins' => 20,
                'fats' => 18,
                'carbs' => 52,
                'is_published' => true,
                'categories' => [3, 4], // ID категорий
                'tags' => [1, 2, 27, 29] // ID тегов
            ],
            [
                'title' => 'Шоколадный торт',
                'description' => 'Нежный шоколадный торт с кремом из темного шоколада.',
                'ingredients' => "Мука - 2 стакана\nСахар - 1,5 стакана\nКакао-порошок - 3/4 стакана\nРазрыхлитель - 1,5 чайной ложки\nСода - 1,5 чайной ложки\nСоль - 1 чайная ложка\nЯйца - 2 шт\nМолоко - 1 стакан\nРастительное масло - 1/2 стакана\nВанильный экстракт - 2 чайные ложки\nКипяток - 1 стакан",
                'instructions' => "1. Разогрейте духовку до 180 градусов.\n2. Смешайте все сухие ингредиенты в большой миске.\n3. Добавьте яйца, молоко, масло и ваниль, взбейте миксером 2 минуты.\n4. Постепенно влейте кипяток, перемешивая тесто (оно будет жидким).\n5. Разлейте тесто по формам и выпекайте 30-35 минут.\n6. Для крема растопите шоколад со сливками и дайте остыть.\n7. Когда коржи остынут, смажьте их кремом и соберите торт.",
                'cooking_time' => 60,
                'servings' => 12,
                'calories' => 380,
                'proteins' => 5,
                'fats' => 18,
                'carbs' => 48,
                'is_published' => true,
                'categories' => [5, 6], // ID категорий
                'tags' => [3, 5, 24, 28] // ID тегов
            ],
            [
                'title' => 'Греческий салат',
                'description' => 'Свежий и легкий салат с овощами и сыром фета.',
                'ingredients' => "Помидоры - 3 шт\nОгурцы - 2 шт\nКрасный лук - 1 шт\nСыр фета - 200 г\nМаслины или оливки - 100 г\nОливковое масло - 4 столовые ложки\nЛимонный сок - 2 столовые ложки\nОрегано - 1 чайная ложка\nСоль и перец по вкусу",
                'instructions' => "1. Нарежьте помидоры и огурцы крупными кубиками.\n2. Нарежьте лук тонкими полукольцами.\n3. Порежьте сыр фета кубиками.\n4. Смешайте все овощи, добавьте маслины.\n5. В отдельной миске смешайте оливковое масло, лимонный сок, орегано, соль и перец.\n6. Полейте салат заправкой, посыпьте сыром и аккуратно перемешайте.",
                'cooking_time' => 15,
                'servings' => 4,
                'calories' => 230,
                'proteins' => 8,
                'fats' => 19,
                'carbs' => 6,
                'is_published' => true,
                'categories' => [3, 10], // ID категорий
                'tags' => [2, 3, 10, 23, 26] // ID тегов
            ]
        ];

        foreach ($recipes as $recipeData) {
            $categories_ids = $recipeData['categories'];
            $tags_ids = $recipeData['tags'];
            
            unset($recipeData['categories']);
            unset($recipeData['tags']);
            
            $recipe = Recipe::firstOrCreate(
                ['title' => $recipeData['title']],
                array_merge($recipeData, [
                    'slug' => Str::slug($recipeData['title']),
                    'user_id' => $adminUser->id,
                    'created_at' => now(),
                    'approved_at' => now(),
                    'approved_by' => $adminUser->id,
                ])
            );
            
            // Прикрепляем категории
            $recipe->categories()->sync($categories_ids);
            
            // Прикрепляем теги
            $recipe->tags()->sync($tags_ids);
        }
    }
}
