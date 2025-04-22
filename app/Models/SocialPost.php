<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SocialPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'content',
        'image_url',
        'recipe_id',
        'platform',
        'post_id',
        'post_url',
        'status',
        'published_at',
        'telegram_status',
        'telegram_posted_at',
        'vk_status',
        'vk_post_id',
        'vk_posted_at'
    ];

    protected $casts = [
        'published_at' => 'datetime',
        'telegram_posted_at' => 'datetime',
        'vk_posted_at' => 'datetime',
        'telegram_status' => 'boolean',
        'vk_status' => 'boolean'
    ];

    /**
     * Проверяет, был ли пост опубликован в Telegram
     */
    public function isPublishedToTelegram()
    {
        return $this->telegram_status == true;
    }

    /**
     * Проверяет, был ли пост опубликован во ВКонтакте
     */
    public function isPublishedToVk()
    {
        return $this->vk_status == true;
    }

    /**
     * Связь с рецептом
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Получает URL рецепта, связанного с этим постом, если таковой имеется
     *
     * @return string|null URL рецепта или null, если рецепт не найден
     */
    public function getRecipeUrl()
    {
        if ($this->recipe) {
            return route('recipes.show', $this->recipe->slug);
        }
        return null;
    }

    /**
     * Проверяет, содержит ли контент ссылку на рецепт
     *
     * @return bool
     */
    public function hasRecipeLink()
    {
        if (!$this->recipe) {
            return false;
        }
        
        $recipeUrl = route('recipes.show', $this->recipe->slug);
        return str_contains($this->content, $recipeUrl);
    }
}
