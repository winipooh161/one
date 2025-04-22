<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class Category extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'slug',
        'description',
        'image_path',
        'parent_id',
        'sort_order',
        'meta_title',
        'meta_description',
        'meta_keywords'
    ];

    /**
     * Автоматически создаем slug при создании категории, если он не указан
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($category) {
            if (empty($category->slug)) {
                $category->slug = Str::slug($category->name);
            }
        });
    }

    /**
     * Получить все рецепты, принадлежащие к этой категории
     */
    public function recipes()
    {
        return $this->belongsToMany(Recipe::class, 'category_recipe', 'category_id', 'recipe_id');
    }
    
    /**
     * Получить родительскую категорию
     */
    public function parent()
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Получить дочерние категории
     */
    public function children()
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Количество опубликованных рецептов в категории
     */
    public function getPublishedRecipesCountAttribute()
    {
        return $this->recipes()->where('is_published', true)->count();
    }
    
    /**
     * Получить URL изображения категории или вернуть изображение по умолчанию
     */
    public function getImageUrl()
    {
        if (!empty($this->image_path) && file_exists(public_path($this->image_path))) {
            return asset($this->image_path);
        }
        
        // Проверяем наличие изображения по пути в storage
        if (!empty($this->image) && \Storage::disk('public')->exists($this->image)) {
            return \Storage::url($this->image);
        }
        
        // Возвращаем стандартные иконки для категорий
        $categoryIcons = [
            'завтрак' => 'breakfast.jpg',
            'обед' => 'lunch.jpg',
            'ужин' => 'dinner.jpg',
            'десерт' => 'dessert.jpg',
            'суп' => 'soup.jpg',
            'салат' => 'salad.jpg',
            'закуска' => 'appetizer.jpg',
            'напиток' => 'drink.jpg',
            'выпечка' => 'baking.jpg',
            'мясо' => 'meat.jpg',
            'рыба' => 'fish.jpg',
            'овощи' => 'vegetables.jpg',
        ];
        
        // Проверяем по ключевым словам в названии категории
        $lowerName = mb_strtolower($this->name);
        foreach ($categoryIcons as $keyword => $icon) {
            if (strpos($lowerName, $keyword) !== false) {
                if (file_exists(public_path('images/categories/' . $icon))) {
                    return asset('images/categories/' . $icon);
                }
            }
        }
        
        // Если ничего не подошло, возвращаем изображение по умолчанию
        return asset('images/category-placeholder.jpg');
    }
    
    /**
     * Получить цвет фона для категории
     */
    public function getColorClass()
    {
        $colors = [
            'bg-primary', 'bg-success', 'bg-info', 'bg-warning', 
            'bg-danger', 'bg-secondary', 'bg-dark', 'bg-indigo'
        ];
        
        // Используем ID категории для выбора цвета (чтобы один и тот же цвет 
        // всегда использовался для одной и той же категории)
        return $colors[$this->id % count($colors)];
    }
}
