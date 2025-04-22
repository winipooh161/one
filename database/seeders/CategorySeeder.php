<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Category;
use Illuminate\Support\Str;

class CategorySeeder extends Seeder
{
    /**
     * Заполнение категорий рецептов.
     *
     * @return void
     */
    public function run()
    {
        $categories = [
            'Завтраки' => 'Рецепты для идеального начала дня',
            'Супы' => 'Горячие и холодные супы на любой вкус',
            'Салаты' => 'Свежие и оригинальные салаты',
            'Основные блюда' => 'Мясные, рыбные и вегетарианские блюда',
            'Выпечка' => 'Пироги, пирожки, булочки и многое другое',
            'Десерты' => 'Сладкие блюда, торты и пирожные',
            'Напитки' => 'Коктейли, смузи, чаи и другие напитки',
            'Соусы' => 'Соусы для разных блюд',
            'Закуски' => 'Быстрые и вкусные закуски для вечеринки',
            'Вегетарианское' => 'Блюда без мяса и рыбы',
        ];

        foreach ($categories as $name => $description) {
            Category::firstOrCreate(
                ['name' => $name],
                [
                    'slug' => Str::slug($name),
                    'description' => $description,
                    'created_at' => now(),
                ]
            );
        }
    }
}
