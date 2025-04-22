<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class NewsComment extends Model
{
    use HasFactory;

    protected $fillable = [
        'news_id',
        'user_id',
        'content',
        'is_approved'
    ];

    protected $casts = [
        'is_approved' => 'boolean',
        'created_at' => 'datetime',
    ];

    /**
     * Связь с новостями
     */
    public function news()
    {
        return $this->belongsTo(News::class);
    }

    /**
     * Связь с пользователем
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
