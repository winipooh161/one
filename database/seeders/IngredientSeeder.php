<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Ingredient;
use App\Models\Recipe;

class IngredientSeeder extends Seeder
{
    /**
     * Заполнение таблицы ингредиентов.
     *
     * @return void
     */
    public function run()
    {
        // Ингредиенты для Пасты Карбонара
        $carbonara = Recipe::where('title', 'Паста Карбонара')->first();
        if ($carbonara) {
            $ingredients = [
                ['name' => 'Спагетти', 'quantity' => '400', 'unit' => 'г', 'position' => 1],
                ['name' => 'Бекон или панчетта', 'quantity' => '200', 'unit' => 'г', 'position' => 2],
                ['name' => 'Яйца', 'quantity' => '4', 'unit' => 'шт.', 'position' => 3],
                ['name' => 'Сыр пармезан', 'quantity' => '100', 'unit' => 'г', 'position' => 4],
                ['name' => 'Чеснок', 'quantity' => '2', 'unit' => 'зубчика', 'position' => 5],
                ['name' => 'Соль', 'quantity' => null, 'unit' => 'по вкусу', 'position' => 6],
                ['name' => 'Перец', 'quantity' => null, 'unit' => 'по вкусу', 'position' => 7],
            ];
            
            foreach ($ingredients as $data) {
                Ingredient::firstOrCreate(
                    [
                        'recipe_id' => $carbonara->id,
                        'name' => $data['name'],
                        'position' => $data['position']
                    ],
                    [
                        'quantity' => $data['quantity'],
                        'unit' => $data['unit'],
                        'optional' => false,
                        'created_at' => now(),
                    ]
                );
            }
        }
        
        // Ингредиенты для Шоколадного торта
        $chocolateCake = Recipe::where('title', 'Шоколадный торт')->first();
        if ($chocolateCake) {
            $ingredients = [
                ['name' => 'Мука', 'quantity' => '2', 'unit' => 'стакана', 'position' => 1],
                ['name' => 'Сахар', 'quantity' => '1.5', 'unit' => 'стакана', 'position' => 2],
                ['name' => 'Какао-порошок', 'quantity' => '0.75', 'unit' => 'стакана', 'position' => 3],
                ['name' => 'Разрыхлитель', 'quantity' => '1.5', 'unit' => 'ч.л.', 'position' => 4],
                ['name' => 'Сода', 'quantity' => '1.5', 'unit' => 'ч.л.', 'position' => 5],
                ['name' => 'Соль', 'quantity' => '1', 'unit' => 'ч.л.', 'position' => 6],
                ['name' => 'Яйца', 'quantity' => '2', 'unit' => 'шт.', 'position' => 7],
                ['name' => 'Молоко', 'quantity' => '1', 'unit' => 'стакан', 'position' => 8],
                ['name' => 'Растительное масло', 'quantity' => '0.5', 'unit' => 'стакана', 'position' => 9],
                ['name' => 'Ванильный экстракт', 'quantity' => '2', 'unit' => 'ч.л.', 'position' => 10],
                ['name' => 'Кипяток', 'quantity' => '1', 'unit' => 'стакан', 'position' => 11],
            ];
            
            foreach ($ingredients as $data) {
                Ingredient::firstOrCreate(
                    [
                        'recipe_id' => $chocolateCake->id,
                        'name' => $data['name'],
                        'position' => $data['position']
                    ],
                    [
                        'quantity' => $data['quantity'],
                        'unit' => $data['unit'],
                        'optional' => false,
                        'created_at' => now(),
                    ]
                );
            }
        }
        
        // Ингредиенты для Греческого салата
        $greekSalad = Recipe::where('title', 'Греческий салат')->first();
        if ($greekSalad) {
            $ingredients = [
                ['name' => 'Помидоры', 'quantity' => '3', 'unit' => 'шт.', 'position' => 1],
                ['name' => 'Огурцы', 'quantity' => '2', 'unit' => 'шт.', 'position' => 2],
                ['name' => 'Красный лук', 'quantity' => '1', 'unit' => 'шт.', 'position' => 3],
                ['name' => 'Сыр фета', 'quantity' => '200', 'unit' => 'г', 'position' => 4],
                ['name' => 'Маслины или оливки', 'quantity' => '100', 'unit' => 'г', 'position' => 5],
                ['name' => 'Оливковое масло', 'quantity' => '4', 'unit' => 'ст.л.', 'position' => 6],
                ['name' => 'Лимонный сок', 'quantity' => '2', 'unit' => 'ст.л.', 'position' => 7],
                ['name' => 'Орегано', 'quantity' => '1', 'unit' => 'ч.л.', 'position' => 8],
                ['name' => 'Соль', 'quantity' => null, 'unit' => 'по вкусу', 'position' => 9],
                ['name' => 'Перец', 'quantity' => null, 'unit' => 'по вкусу', 'position' => 10],
            ];
            
            foreach ($ingredients as $data) {
                Ingredient::firstOrCreate(
                    [
                        'recipe_id' => $greekSalad->id,
                        'name' => $data['name'],
                        'position' => $data['position']
                    ],
                    [
                        'quantity' => $data['quantity'],
                        'unit' => $data['unit'],
                        'optional' => false,
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
