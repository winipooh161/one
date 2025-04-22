<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Tag;
use Illuminate\Support\Str;

class TagSeeder extends Seeder
{
    /**
     * Заполнение тегов для рецептов.
     *
     * @return void
     */
    public function run()
    {
        $tags = [
            'быстро', 'легко', 'полезно', 'диетическое', 'праздничное', 
            'детское', 'веганское', 'вегетарианское', 'осеннее', 'летнее',
            'зимнее', 'весеннее', 'завтрак', 'обед', 'ужин', 'перекус', 
            'пикник', 'на скорую руку', 'торт', 'мясо', 'рыба', 'овощи', 
            'фрукты', 'десерт', 'суп', 'салат', 'напиток', 'бургер', 'пицца', 'паста'
        ];

        foreach ($tags as $tagName) {
            Tag::firstOrCreate(
                ['name' => $tagName],
                [
                    'slug' => Str::slug($tagName),
                    'created_at' => now(),
                ]
            );
        }
    }
}
