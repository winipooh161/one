<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Comment extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово назначать.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'recipe_id',
        'user_id',
        'content',
        'is_approved',
    ];

    /**
     * Атрибуты, которые должны быть преобразованы в булевы значения.
     *
     * @var array<int, string>
     */
    protected $casts = [
        'is_approved' => 'boolean',
    ];

    /**
     * Связь с рецептом
     */
    public function recipe()
    {
        return $this->belongsTo(Recipe::class);
    }

    /**
     * Связь с пользователем
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
