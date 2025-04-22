<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Rating extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array
     */
    protected $fillable = [
        'recipe_id',
        'user_id',
        'rating',
        'comment'
    ];

    /**
     * Отношение к рецепту
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Отношение к пользователю
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
