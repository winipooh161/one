<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Tag extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug'];

    /**
     * Получить рецепты, связанные с этим тегом.
     */
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'recipe_tag', 'tag_id', 'recipe_id');
    }
    
    /**
     * Находит или создает теги по именам
     *
     * @param array $tagNames Массив имен тегов
     * @return array Массив ID тегов
     */
    public static function findOrCreateTags(array $tagNames)
    {
        $tagIds = [];
        $tagNames = array_unique(array_filter($tagNames, function($tag) {
            return mb_strlen(trim($tag)) > 2 && mb_strlen(trim($tag)) < 50;
        }));
        
        foreach ($tagNames as $name) {
            $name = trim($name);
            if (empty($name)) continue;
            
            // Нормализуем название (первая буква заглавная, остальные строчные)
            $name = mb_ucfirst(mb_strtolower($name));
            
            // Ищем существующий тег
            $tag = static::firstWhere('name', $name);
            
            if (!$tag) {
                // Создаем новый тег
                $tag = static::create([
                    'name' => $name,
                    'slug' => Str::slug($name)
                ]);
            }
            
            $tagIds[] = $tag->id;
        }
        
        return $tagIds;
    }

    /**
     * Поиск тега по наименованию (нечувствительный к регистру).
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $name
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeFindByName($query, $name)
    {
        return $query->whereRaw('LOWER(name) = ?', [strtolower($name)]);
    }
}

/**
 * Вспомогательная функция для работы с многобайтовыми строками
 */
if (!function_exists('mb_ucfirst')) {
    function mb_ucfirst($string) {
        $firstChar = mb_substr($string, 0, 1);
        $then = mb_substr($string, 1);
        return mb_strtoupper($firstChar) . $then;
    }
}
