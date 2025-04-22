<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'content',
        'description',
        'image',
        'user_id',
        'is_published',
        'views',
        'meta_title',
        'meta_description',
        'meta_keywords'
    ];

    /**
     * Получить пользователя, создавшего статью
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Получить URL изображения статьи
     */
    public function getImageUrl()
    {
        if ($this->image) {
            return asset('storage/' . $this->image);
        }
        
        return asset('images/article-placeholder.jpg');
    }
    
    /**
     * Получить отформатированную дату создания
     */
    public function getFormattedDate()
    {
        return $this->created_at->format('d.m.Y');
    }
    
    /**
     * Получить время чтения статьи в минутах
     */
    public function getReadingTime()
    {
        $wordCount = str_word_count(strip_tags($this->content));
        $minutes = ceil($wordCount / 200); // Среднее количество слов в минуту для чтения
        
        return $minutes;
    }
}
