<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Step;
use App\Models\Recipe;

class StepSeeder extends Seeder
{
    /**
     * Заполнение таблицы шагов приготовления.
     *
     * @return void
     */
    public function run()
    {
        // Шаги для Пасты Карбонара
        $carbonara = Recipe::where('title', 'Паста Карбонара')->first();
        if ($carbonara) {
            $steps = [
                ['description' => 'Отварите спагетти в подсоленной воде до состояния аль денте.', 'order' => 1],
                ['description' => 'Нарежьте бекон кубиками и обжарьте до хрустящей корочки.', 'order' => 2],
                ['description' => 'Смешайте яйца и тертый пармезан.', 'order' => 3],
                ['description' => 'Слейте воду с пасты, оставив немного.', 'order' => 4],
                ['description' => 'Смешайте горячую пасту с беконом, добавьте яично-сырную смесь и быстро перемешайте.', 'order' => 5],
                ['description' => 'Подавайте сразу, посыпав черным перцем и пармезаном.', 'order' => 6],
            ];
            
            foreach ($steps as $data) {
                Step::firstOrCreate(
                    [
                        'recipe_id' => $carbonara->id,
                        'order' => $data['order']
                    ],
                    [
                        'description' => $data['description'],
                        'created_at' => now(),
                    ]
                );
            }
        }
        
        // Шаги для Шоколадного торта
        $chocolateCake = Recipe::where('title', 'Шоколадный торт')->first();
        if ($chocolateCake) {
            $steps = [
                ['description' => 'Разогрейте духовку до 180 градусов.', 'order' => 1],
                ['description' => 'Смешайте все сухие ингредиенты в большой миске.', 'order' => 2],
                ['description' => 'Добавьте яйца, молоко, масло и ваниль, взбейте миксером 2 минуты.', 'order' => 3],
                ['description' => 'Постепенно влейте кипяток, перемешивая тесто (оно будет жидким).', 'order' => 4],
                ['description' => 'Разлейте тесто по формам и выпекайте 30-35 минут.', 'order' => 5],
                ['description' => 'Для крема растопите шоколад со сливками и дайте остыть.', 'order' => 6],
                ['description' => 'Когда коржи остынут, смажьте их кремом и соберите торт.', 'order' => 7],
            ];
            
            foreach ($steps as $data) {
                Step::firstOrCreate(
                    [
                        'recipe_id' => $chocolateCake->id,
                        'order' => $data['order']
                    ],
                    [
                        'description' => $data['description'],
                        'created_at' => now(),
                    ]
                );
            }
        }
        
        // Шаги для Греческого салата
        $greekSalad = Recipe::where('title', 'Греческий салат')->first();
        if ($greekSalad) {
            $steps = [
                ['description' => 'Нарежьте помидоры и огурцы крупными кубиками.', 'order' => 1],
                ['description' => 'Нарежьте лук тонкими полукольцами.', 'order' => 2],
                ['description' => 'Порежьте сыр фета кубиками.', 'order' => 3],
                ['description' => 'Смешайте все овощи, добавьте маслины.', 'order' => 4],
                ['description' => 'В отдельной миске смешайте оливковое масло, лимонный сок, орегано, соль и перец.', 'order' => 5],
                ['description' => 'Полейте салат заправкой, посыпьте сыром и аккуратно перемешайте.', 'order' => 6],
            ];
            
            foreach ($steps as $data) {
                Step::firstOrCreate(
                    [
                        'recipe_id' => $greekSalad->id,
                        'order' => $data['order']
                    ],
                    [
                        'description' => $data['description'],
                        'created_at' => now(),
                    ]
                );
            }
        }
    }
}
