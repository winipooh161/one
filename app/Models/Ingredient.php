<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Ingredient extends Model
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
        'quantity',
        'unit',
        'optional',
        'state',
        'notes',
        'priority',
        'position'
    ];

    /**
     * Атрибуты, которые должны быть приведены к определенным типам.
     *
     * @var array
     */
    protected $casts = [
        'optional' => 'boolean',
    ];

    /**
     * Получить рецепт, к которому относится ингредиент
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Получить группу ингредиентов, к которой относится ингредиент (если есть)
     */
    public function group()
    {
        return $this->belongsTo(IngredientGroup::class, 'ingredient_group_id');
    }

    /**
     * Преобразует ингредиент в строку
     */
    public function toString(): string
    {
        $result = $this->name;
        
        if ($this->quantity) {
            $result .= " - {$this->quantity} {$this->unit}";
        } elseif ($this->unit && $this->unit !== 'по вкусу') {
            $result .= " - {$this->unit}";
        } elseif ($this->unit === 'по вкусу') {
            $result .= " - по вкусу";
        }
        
        if ($this->notes) {
            $result .= " ({$this->notes})";
        }
        
        if ($this->optional) {
            $result .= " (по желанию)";
        }
        
        return $result;
    }
    
    /**
     * Конвертирует ингредиент в формат для JSON
     */
    public function toArray()
    {
        $array = parent::toArray();
        
        // Добавляем вычисляемые поля
        $array['text'] = $this->toString();
        
        return $array;
    }
}
