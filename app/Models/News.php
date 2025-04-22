<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class News extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'short_description',
        'content',
        'image_url',
        'views',
        'user_id',
        'category_id',
        'is_published',
        // Добавляем новые поля для видео
        'video_iframe',
        'video_author_name',
        'video_author_link',
        'video_tags',
        'video_title',
        'video_description',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function setTitleAttribute($value)
    {
        $this->attributes['title'] = $value;
        $this->attributes['slug'] = Str::slug($value);
    }

    public function getRouteKeyName()
    {
        return 'slug';
    }

    /**
     * Получение похожих новостей
     */
    public function getRelatedNews($limit = 4)
    {
        return self::where('id', '!=', $this->id)
            ->where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Получение популярных новостей
     */
    public static function getPopular($limit = 5)
    {
        return self::where('is_published', true)
            ->orderBy('views', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Расчет времени чтения
     */
    public function calculateReadingTime()
    {
        // Среднее количество слов в минуту для чтения
        $wordsPerMinute = 200;
        
        $wordCount = str_word_count(strip_tags($this->content));
        $readingTime = ceil($wordCount / $wordsPerMinute);
        
        return max(1, $readingTime); // Минимум 1 минута
    }

    /**
     * Форматированная дата публикации
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('d.m.Y');
    }

    /**
     * Получение изображения для OG
     */
    public function getOgImage()
    {
        if ($this->image_url) {
            return $this->getImageUrl();
        }
        
        return asset('images/og-news.jpg');
    }
    
    /**
     * Получение полного URL изображения с учетом различных путей хранения
     */
    public function getImageUrl()
    {
        if (!$this->image_url) {
            return asset('images/news-placeholder.jpg');
        }
        
        // Проверяем, существует ли файл по обычному пути uploads/
        $regularPath = public_path('uploads/' . $this->image_url);
        if (file_exists($regularPath)) {
            return asset('uploads/' . $this->image_url);
        }
        
        // Проверяем, существует ли файл по пути storage/
        $storagePath = public_path('storage/' . $this->image_url);
        if (file_exists($storagePath)) {
            return asset('storage/' . $this->image_url);
        }
        
        // Если путь к изображению содержит только имя файла без поддиректорий
        if (!Str::contains($this->image_url, '/')) {
            // Пробуем поискать в разных подкаталогах
            $possiblePaths = [
                'uploads/news/' . $this->image_url,
                'uploads/thumbnails/' . $this->image_url,
                'storage/news/' . $this->image_url
            ];
            
            foreach ($possiblePaths as $path) {
                if (file_exists(public_path($path))) {
                    return asset($path);
                }
            }
        }
        
        // Возвращаем исходный путь, если ничего не найдено
        return asset('uploads/' . $this->image_url);
    }

    /**
     * Получение URL для thumbnail изображения
     */
    public function getThumbnailUrl()
    {
        if (!$this->image_url) {
            return asset('images/news-thumbnail.jpg');
        }
        
        // Если это путь к обложке видео, которая уже является миниатюрой
        if (Str::startsWith($this->image_url, 'thumbnails/')) {
            return asset('uploads/' . $this->image_url);
        }
        
        // Для обычных изображений проверяем наличие миниатюры
        $pathInfo = pathinfo($this->image_url);
        $thumbPath = $pathInfo['dirname'] . '/thumb_' . $pathInfo['basename'];
        $fullThumbPath = public_path('uploads/' . $thumbPath);
        
        if (file_exists($fullThumbPath)) {
            return asset('uploads/' . $thumbPath);
        }
        
        // Возвращаем оригинальное изображение, если миниатюра не найдена
        return $this->getImageUrl();
    }
    
    /**
     * Следующая новость
     */
    public function getNextNews()
    {
        return self::where('is_published', true)
            ->where('created_at', '>', $this->created_at)
            ->orderBy('created_at', 'asc')
            ->first();
    }
    
    /**
     * Предыдущая новость
     */
    public function getPreviousNews()
    {
        return self::where('is_published', true)
            ->where('created_at', '<', $this->created_at)
            ->orderBy('created_at', 'desc')
            ->first();
    }

    /**
     * Связь с комментариями
     */
    public function comments()
    {
        return $this->hasMany(NewsComment::class);
    }

    /**
     * Проверяет, содержит ли новость видео
     * 
     * @return bool
     */
    public function hasVideo()
    {
        return !empty($this->video_iframe);
    }

    /**
     * Получить массив тегов видео
     *
     * @return array
     */
    public function getVideoTagsArray()
    {
        if (empty($this->video_tags)) {
            return [];
        }

        return explode(',', $this->video_tags);
    }

    /**
     * Очищает iframe от потенциально опасных атрибутов
     *
     * @param string $iframe
     * @return string
     */
    public function sanitizeIframe($iframe)
    {
        // Разрешаем только iframe с домена vk.com
        if (Str::contains($iframe, 'vk.com')) {
            // Используем регулярное выражение для извлечения src
            preg_match('/src="([^"]+)"/', $iframe, $matches);
            
            if (isset($matches[1])) {
                $src = $matches[1];
                
                // Создаем безопасный iframe
                return '<iframe src="' . $src . '" 
                    width="100%" height="400" 
                    frameborder="0" 
                    allowfullscreen 
                    allow="autoplay; encrypted-media; picture-in-picture">
                </iframe>';
            }
        }
        
        return '';
    }
}
