<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Services\SeoService;
use App\Services\SearchService;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use App\Models\Category;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class SearchController extends Controller
{
    protected $seoService;
    protected $searchService;

    /**
     * Создаем новый экземпляр контроллера.
     *
     * @param SeoService $seoService
     * @param SearchService $searchService
     */
    public function __construct(SeoService $seoService, SearchService $searchService = null)
    {
        $this->seoService = $seoService;
        $this->searchService = $searchService;
    }

    /**
     * Отображение результатов поиска.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\View\View
     */
    public function index(Request $request)
    {
        $query = $request->input('query');
        $results = null;
        $recipesCount = 0;
        
        if ($query) {
            try {
                if ($this->searchService) {
                    // Используем SearchService если доступен
                    $results = $this->searchService->search($query);
                } else {
                    // Иначе используем простой поиск по базе данных
                    $results = Recipe::where('title', 'like', "%{$query}%")
                        ->orWhere('description', 'like', "%{$query}%")
                        ->where('is_published', true)
                        ->orderBy('views', 'desc')
                        ->paginate(12);
                }
                
                $recipesCount = $results->total();
            } catch (\Exception $e) {
                Log::error("Search error: " . $e->getMessage());
                // В случае ошибки создаем пустую коллекцию с пагинацией
                $results = new LengthAwarePaginator(
                    [], // Пустой массив данных
                    0,  // Всего элементов
                    12, // Элементов на страницу
                    1,  // Текущая страница
                    ['path' => route('search')] // Опции маршрута
                );
            }
        } else {
            // Если нет запроса, создаем пустую коллекцию с пагинацией
            $results = new LengthAwarePaginator(
                [], 
                0, 
                12, 
                1, 
                ['path' => route('search')]
            );
        }

        // SEO-метаданные для страницы поиска
        $this->seoService->setTitle('Поиск рецептов' . ($query ? ': ' . $query : ''))
            ->setDescription('Поиск по кулинарным рецептам' . ($query ? '. Результаты для запроса: ' . $query : '.'))
            ->setKeywords('поиск рецептов, кулинария, ' . $query)
            ->setCanonical(url()->current())
            ->setOgType('website')
            ->setOgImage(asset('images/og-search.jpg'));
    
        return view('search.index', compact('results', 'query'));
    }

    /**
     * Ajax запрос автозаполнения для поиска.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function autocomplete(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }
        
        try {
            if ($this->searchService) {
                $suggestions = $this->searchService->getAutocompleteSuggestions($query);
            } else {
                $suggestions = Recipe::select('title')
                    ->where('title', 'like', "%{$query}%")
                    ->where('is_published', true)
                    ->limit(10)
                    ->pluck('title')
                    ->toArray();
            }
            
            return response()->json($suggestions);
        } catch (\Exception $e) {
            Log::error("Autocomplete error: " . $e->getMessage());
            return response()->json([]);
        }
    }

    /**
     * Сохранить клик по результату поиска
     */
    public function recordClick(Request $request)
    {
        $recipeId = $request->input('recipe_id');
        $query = $request->input('query');
        
        if (auth()->check() && $recipeId && $query) {
            // Находим последний поиск пользователя с этим запросом
            $lastSearch = DB::table('search_histories')
                ->where('user_id', auth()->id())
                ->where('query', $query)
                ->latest()
                ->first();
                
            if ($lastSearch) {
                DB::table('search_histories')
                    ->where('id', $lastSearch->id)
                    ->update(['clicked_recipe_id' => $recipeId]);
            }
        }
        
        return response()->json(['success' => true]);
    }
    
    /**
     * Расчет процентного соответствия рецепта поисковому запросу
     */
    private function calculateRelevancePercent($recipe, $searchTerms)
    {
        $totalTerms = count($searchTerms);
        $matchedTerms = 0;
        $weightedMatches = 0;
        
        // Объединяем все текстовые поля для поиска
        $allText = strtolower($recipe->title . ' ' . $recipe->description . ' ' . $recipe->ingredients);
        
        foreach ($searchTerms as $term) {
            if (mb_strlen($term) >= 3) {
                $termLower = strtolower($term);
                
                // Проверяем наличие в разных полях с разными весами
                $weightForThisTerm = 0;
                
                if (Str::contains(strtolower($recipe->title), $termLower)) {
                    $weightForThisTerm += 2; // Высокий вес для заголовка
                }
                
                if (Str::contains(strtolower($recipe->ingredients), $termLower)) {
                    $weightForThisTerm += 1.5; // Средний вес для ингредиентов
                }
                
                if (Str::contains(strtolower($recipe->description), $termLower)) {
                    $weightForThisTerm += 1; // Нормальный вес для описания
                }
                
                if (Str::contains(strtolower($recipe->instructions), $termLower)) {
                    $weightForThisTerm += 0.5; // Низкий вес для инструкций
                }
                
                if ($weightForThisTerm > 0) {
                    $matchedTerms++;
                    $weightedMatches += $weightForThisTerm;
                }
            }
        }
        
        if ($totalTerms > 0) {
            // Базовый процент - процент найденных слов
            $basePercent = ($matchedTerms / $totalTerms) * 100;
            
            // Весовой процент - с учетом веса найденных слов
            $maxPossibleWeight = $totalTerms * 5; // Максимально возможный вес
            $weightPercent = ($weightedMatches / $maxPossibleWeight) * 100;
            
            // Комбинированный процент (60% базовый + 40% весовой)
            $combinedPercent = ($basePercent * 0.6) + ($weightPercent * 0.4);
            
            return round($combinedPercent);
        }
        
        return 0;
    }
    
    /**
     * Получить предложения для поиска при малом количестве результатов
     */
    private function getSuggestions($query)
    {
        // Разбиваем запрос на слова
        $terms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        
        // Если запрос состоит из одного слова, предлагаем похожие слова
        if (count($terms) === 1 && mb_strlen($terms[0]) >= 3) {
            $term = $terms[0];
            
            // Ищем рецепты с похожими словами
            $recipes = Recipe::where('is_published', true)
                ->select('title')
                ->limit(100)
                ->get();
                
            $allWords = collect();
            
            foreach ($recipes as $recipe) {
                $words = preg_split('/\s+/', $recipe->title, -1, PREG_SPLIT_NO_EMPTY);
                $allWords = $allWords->merge($words);
            }
            
            $allWords = $allWords->map(function($word) {
                return preg_replace('/[^\p{L}\p{N}]/u', '', $word);
            })->unique()->filter();
            
            $suggestions = [];
            
            foreach ($allWords as $word) {
                if (mb_strlen($word) >= 3) {
                    $distance = $this->searchService->levenshtein_utf8(mb_strtolower($term), mb_strtolower($word));
                    
                    if ($distance <= 2 && $distance > 0) {
                        $suggestions[] = $word;
                    }
                }
            }
            
            return array_slice($suggestions, 0, 5);
        }
        
        // Если запрос из нескольких слов, предлагаем исключить некоторые слова
        if (count($terms) > 1) {
            $suggestions = [];
            
            foreach ($terms as $i => $term) {
                $newTerms = $terms;
                array_splice($newTerms, $i, 1);
                $suggestions[] = implode(' ', $newTerms);
            }
            
            return array_slice($suggestions, 0, 3);
        }
        
        return [];
    }

    /**
     * Поиск рецептов
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\View
     */
    public function search(Request $request)
    {
        $query = trim($request->input('query', $request->input('q', '')));
        $filters = [
            'category' => $request->input('category'),
            'cooking_time' => $request->input('cooking_time'),
            'difficulty' => $request->input('difficulty')
        ];
        $sort = $request->input('sort', 'relevance');

        // Если поисковый запрос пустой и нет фильтров, показываем пустую страницу поиска
        if (empty($query) && empty(array_filter($filters))) {
            $results = collect([]);
            $resultsCount = 0;
        } else {
            // Выполняем поиск через сервис
            $results = $this->searchService->search($query, $filters, $sort);
            $resultsCount = $results->total();
        }

        // Получаем категории для фильтра
        $categories = Category::withCount('recipes')
                     ->orderByDesc('recipes_count')
                     ->limit(10)
                     ->get();

        // Устанавливаем SEO метаданные
        $this->seoService->setTitle($query ? "Поиск: $query" : "Поиск рецептов")
            ->setDescription($query ? "Результаты поиска рецептов по запросу: $query" : "Поиск кулинарных рецептов на сайте")
            ->setKeywords("поиск рецептов, $query, кулинария, блюда")
            ->setCanonical(empty($query) ? route('search') : route('search') . "?query=" . urlencode($query))
            ->setRobots(false, true); // Запрещаем индексацию поисковой выдачи

        return view('search.index', compact(
            'query', 
            'results', 
            'resultsCount', 
            'categories', 
            'filters', 
            'sort'
        ));
    }
}
