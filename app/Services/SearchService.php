<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\SearchQuery;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SearchService
{
    /**
     * Выполняет полнотекстовый поиск рецептов
     */
    public function fullTextSearch(string $query, array $filters = [])
    {
        // Начальный запрос к рецептам
        $recipesQuery = Recipe::where('is_published', true);
        
        // Полнотекстовый поиск если строка не пустая
        if (!empty($query)) {
            // Проверяем наличие FULLTEXT индекса безопасным способом
            $hasFulltextIndex = $this->hasFulltextIndex();
            
            // Используем разные способы поиска в зависимости от наличия FULLTEXT индекса
            $recipesQuery->where(function($q) use ($query, $hasFulltextIndex) {
                // Если есть FULLTEXT индекс, пробуем использовать его
                if ($hasFulltextIndex) {
                    try {
                        $q->whereRaw('MATCH(title, description, ingredients) AGAINST(? IN BOOLEAN MODE)', [$query . '*']);
                    } catch (\Exception $e) {
                        // В случае ошибки используем LIKE
                        $this->applyLikeSearch($q, $query);
                    }
                } else {
                    // Используем LIKE поиск если индекса нет
                    $this->applyLikeSearch($q, $query);
                }
            });
        }
        
        // Применяем фильтры
        if (!empty($filters['category_id'])) {
            $recipesQuery->whereHas('categories', function($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }
        
        if (!empty($filters['cooking_time'])) {
            list($min, $max) = explode('-', $filters['cooking_time']) + [0, 9999];
            $recipesQuery->whereBetween('cooking_time', [$min, $max]);
        }
        
        // Сортировка
        $sort = $filters['sort'] ?? 'relevance';
        switch ($sort) {
            case 'newest':
                $recipesQuery->orderBy('created_at', 'desc');
                break;
            case 'oldest':
                $recipesQuery->orderBy('created_at', 'asc');
                break;
            case 'popular':
                $recipesQuery->orderBy('views', 'desc');
                break;
            case 'a-z':
                $recipesQuery->orderBy('title', 'asc');
                break;
            case 'z-a':
                $recipesQuery->orderBy('title', 'desc');
                break;
            case 'relevance':
            default:
                // Для релевантности используем подходящий метод сортировки
                if (!empty($query)) {
                    if ($this->hasFulltextIndex()) {
                        try {
                            $recipesQuery->orderByRaw('MATCH(title, description, ingredients) AGAINST(? IN BOOLEAN MODE) DESC', [$query . '*']);
                        } catch (\Exception $e) {
                            // При ошибке сортируем по релевантности названия
                            $recipesQuery->orderByRaw("CASE WHEN title LIKE ? THEN 1 WHEN title LIKE ? THEN 2 ELSE 3 END", 
                                ['%' . $query . '%', '%' . $query . '%']);
                        }
                    } else {
                        // Если нет FULLTEXT, сортируем по соответствию в названии
                        $recipesQuery->orderByRaw("CASE WHEN title LIKE ? THEN 1 WHEN title LIKE ? THEN 2 ELSE 3 END", 
                            ['%' . $query . '%', '%' . $query . '%']);
                    }
                } else {
                    $recipesQuery->orderBy('created_at', 'desc');
                }
                break;
        }
        
        // Получаем результаты с пагинацией
        $results = $recipesQuery->paginate(12)->withQueryString();
        
        // Сохраняем поисковый запрос в историю
        if (!empty($query)) {
            $this->updateSearchResultsCount($query, $results->total());
        }
        
        return $results;
    }
    
    /**
     * Применяет поиск LIKE для каждого поля
     */
    private function applyLikeSearch($query, $searchTerm)
    {
        $escapedTerm = '%' . $searchTerm . '%';
        $query->where('title', 'LIKE', $escapedTerm)
              ->orWhere('description', 'LIKE', $escapedTerm)
              ->orWhere('ingredients', 'LIKE', $escapedTerm);
    }
    
    /**
     * Проверяет наличие FULLTEXT индексов в таблице
     */
    private function hasFulltextIndex()
    {
        try {
            // Проверяем, есть ли FULLTEXT индексы через запрос к информационной схеме
            $result = DB::select("
                SELECT COUNT(*) as count FROM INFORMATION_SCHEMA.STATISTICS 
                WHERE TABLE_SCHEMA = DATABASE() 
                AND TABLE_NAME = 'recipes' 
                AND INDEX_TYPE = 'FULLTEXT' 
                AND COLUMN_NAME IN ('title', 'description', 'ingredients')
            ");
            
            return $result[0]->count >= 3; // Возвращаем true если все 3 колонки индексированы
        } catch (\Exception $e) {
            // В случае ошибки возвращаем false
            return false;
        }
    }
    
    /**
     * Обновляет статистику поисковых запросов
     */
    public function updateSearchResultsCount(string $query, int $resultsCount): void
    {
        // Используем новую модель SearchQuery
        SearchQuery::addOrIncrement($query, $resultsCount);
    }
    

  

    /**
     * Поиск рецептов по запросу
     *
     * @param string $query Поисковый запрос
     * @param array $filters Дополнительные фильтры
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function search($query, array $filters = [])
    {
        $recipesQuery = Recipe::where('is_published', true)
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('ingredients', 'like', "%{$query}%");
                  
                // Проверяем существование поля instructions в таблице recipes
                if (Schema::hasColumn('recipes', 'instructions')) {
                    $q->orWhere('instructions', 'like', "%{$query}%");
                }
            });

        // Применяем фильтры, если они есть
        if (!empty($filters['category_id'])) {
            $recipesQuery->whereHas('categories', function($q) use ($filters) {
                $q->where('categories.id', $filters['category_id']);
            });
        }
        
        if (!empty($filters['cooking_time'])) {
            $cookingTime = (int) $filters['cooking_time'];
            $recipesQuery->where('cooking_time', '<=', $cookingTime);
        }

        // Рейтинговый поиск - сначала точные совпадения в заголовке
        $recipesQuery->orderByRaw("CASE 
            WHEN title LIKE '{$query}' THEN 1
            WHEN title LIKE '{$query}%' THEN 2
            WHEN title LIKE '%{$query}%' THEN 3
            ELSE 4
        END")
        ->orderBy('views', 'desc');
        
        return $recipesQuery->paginate(12)->withQueryString();
    }

    /**
     * Получить предложения для автозаполнения
     *
     * @param string $query Часть поискового запроса
     * @return array
     */
    public function getAutocompleteSuggestions($query)
    {
        // Базовые предложения из названий рецептов
        $titleSuggestions = Recipe::select('title')
            ->where('title', 'like', "%{$query}%")
            ->where('is_published', true)
            ->limit(7)
            ->pluck('title')
            ->toArray();
        
        // Предложения на основе популярных ингредиентов
        $ingredientSuggestions = Recipe::select(DB::raw('SUBSTRING_INDEX(ingredients, ",", 1) as ingredient'))
            ->where('ingredients', 'like', "%{$query}%")
            ->where('is_published', true)
            ->groupBy('ingredient')
            ->limit(3)
            ->pluck('ingredient')
            ->map(function($ingredient) {
                return 'Рецепты с ' . mb_strtolower($ingredient);
            })
            ->toArray();
        
        // Объединяем результаты и возвращаем уникальный список
        $suggestions = array_merge($titleSuggestions, $ingredientSuggestions);
        $suggestions = array_unique($suggestions);
        
        return array_slice($suggestions, 0, 10);
    }

    /**
     * Расчет расстояния Левенштейна для строк UTF-8
     *
     * @param string $s1 Первая строка
     * @param string $s2 Вторая строка
     * @return int Расстояние между строками
     */
    public function levenshtein_utf8($s1, $s2)
    {
        // Если одна из строк пуста, расстояние равно длине другой
        if (empty($s1)) {
            return mb_strlen($s2);
        }
        if (empty($s2)) {
            return mb_strlen($s1);
        }

        // Преобразуем строки в массивы символов UTF-8
        $arr1 = preg_split('//u', $s1, -1, PREG_SPLIT_NO_EMPTY);
        $arr2 = preg_split('//u', $s2, -1, PREG_SPLIT_NO_EMPTY);
        
        $len1 = count($arr1);
        $len2 = count($arr2);
        
        // Инициализируем массив расстояний
        $d = [];
        
        // Инициализация первой строки
        for ($i = 0; $i <= $len1; $i++) {
            $d[$i][0] = $i;
        }
        
        // Инициализация первого столбца
        for ($j = 0; $j <= $len2; $j++) {
            $d[0][$j] = $j;
        }
        
        // Заполнение массива расстояний
        for ($i = 1; $i <= $len1; $i++) {
            for ($j = 1; $j <= $len2; $j++) {
                $cost = ($arr1[$i-1] === $arr2[$j-1]) ? 0 : 1;
                
                $d[$i][$j] = min(
                    $d[$i-1][$j] + 1,      // удаление
                    $d[$i][$j-1] + 1,      // вставка
                    $d[$i-1][$j-1] + $cost // замена или совпадение
                );
            }
        }
        
        // Возвращаем расстояние между строками
        return $d[$len1][$len2];
    }
    
    /**
     * Записывает историю поиска пользователя
     *
     * @param int $userId ID пользователя
     * @param string $query Поисковый запрос
     * @param int $resultsCount Количество найденных результатов
     * @return bool
     */
    public function recordSearchHistory($userId, $query, $resultsCount = 0)
    {
        try {
            // Проверяем существование таблицы search_histories
            if (Schema::hasTable('search_histories')) {
                DB::table('search_histories')->insert([
                    'user_id' => $userId,
                    'query' => $query,
                    'results_count' => $resultsCount,
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }
            return true;
        } catch (\Exception $e) {
            // В случае ошибки просто логируем и продолжаем
            \Log::error("Error recording search history: " . $e->getMessage());
            return false;
        }
    }
}
