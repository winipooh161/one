<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SearchQuery extends Model
{
    use HasFactory;

    /**
     * Атрибуты, которые можно массово присваивать.
     *
     * @var array
     */
    protected $fillable = [
        'query',
        'count',
        'results_count',
        'last_searched_at'
    ];

    /**
     * Атрибуты с преобразованием типов.
     *
     * @var array
     */
    protected $casts = [
        'count' => 'integer',
        'results_count' => 'integer',
        'last_searched_at' => 'datetime',
    ];

    /**
     * Добавляет поисковый запрос в базу или обновляет существующий
     *
     * @param string $query Поисковый запрос
     * @param int $resultsCount Количество результатов
     * @return self
     */
    public static function addOrIncrement(string $query, int $resultsCount = 0): self
    {
        // Нормализуем запрос (приводим к нижнему регистру и удаляем лишние пробелы)
        $query = trim(mb_strtolower($query));
        
        if (empty($query)) {
            return new self(); // Возвращаем пустую модель, если запрос пустой
        }
        
        // Находим существующий запрос или создаем новый
        $searchQuery = self::firstOrNew(['query' => $query]);
        
        // Увеличиваем счетчик
        $searchQuery->count = ($searchQuery->count ?? 0) + 1;
        $searchQuery->results_count = $resultsCount;
        $searchQuery->last_searched_at = now();
        
        $searchQuery->save();
        
        return $searchQuery;
    }

    /**
     * Получает популярные поисковые запросы
     *
     * @param int $limit Количество запросов
     * @return \Illuminate\Support\Collection
     */
    public static function getPopular(int $limit = 10)
    {
        return self::orderByDesc('count')
            ->limit($limit)
            ->get();
    }
}
