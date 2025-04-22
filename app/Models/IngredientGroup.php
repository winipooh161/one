<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IngredientGroup extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array
     */
    protected $fillable = [
        'recipe_id',
        'name',
        'position'
    ];

    /**
     * Получить рецепт, к которому относится группа ингредиентов
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Получить ингредиенты, относящиеся к этой группе
     */
    public function ingredients()
    {
        return $this->hasMany(Ingredient::class, 'ingredient_group_id')->orderBy('position');
    }
}
