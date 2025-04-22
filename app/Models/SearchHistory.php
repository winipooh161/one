<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'query',
        'results_count',
        'clicked_recipe_id'
    ];

    /**
     * Получить пользователя, выполнившего поиск
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить кликнутый рецепт (если был клик по результату)
     */
    public function clickedRecipe()
    {
        return $this->belongsTo(Recipe::class, 'clicked_recipe_id');
    }
}
