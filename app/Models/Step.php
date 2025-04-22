<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Step extends Model
{
    use HasFactory;

    protected $fillable = [
        'recipe_id',
        'description',
        'order',
        'image',
        'time',
        'tips',
        'moderation_status',
        'moderation_message'
    ];

    /**
     * Получить рецепт, к которому относится шаг
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }
}
