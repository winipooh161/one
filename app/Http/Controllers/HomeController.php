<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SeoService;
use App\Models\Recipe;
use App\Models\Category;
use App\Services\SearchService;
use Illuminate\Support\Facades\Cache;

class HomeController extends Controller
{
    protected $seoService;
    protected $searchService;

    /**
     * Создание нового экземпляра контроллера.
     *
     * @param SeoService $seoService
     * @param SearchService $searchService
     */
    public function __construct(SeoService $seoService, SearchService $searchService)
    {
        $this->seoService = $seoService;
        $this->searchService = $searchService;
    }

    /**
     * Отображение главной страницы.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index()
    {
        // Новые рецепты для верхнего слайдера
        $newRecipes = Cache::remember('home_new_recipes', 60, function () {
            return Recipe::with('categories', 'user')
                ->where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->limit(6)
                ->get();
        });

        // Популярные рецепты
        $popularRecipes = Cache::remember('home_popular_recipes', 60, function () {
            return Recipe::with('categories', 'user')
                ->where('is_published', true)
                ->orderBy('views', 'desc')
                ->limit(8)
                ->get();
        });

        // Рецепты по категориям (оптимизированный запрос)
        $featuredCategories = Cache::remember('home_featured_categories', 60 * 60, function () {
            // Сначала получаем ID категорий, у которых минимум 3 рецепта
            $categoryIds = \DB::table('category_recipe')
                ->select('category_id')
                ->groupBy('category_id')
                ->havingRaw('COUNT(DISTINCT recipe_id) >= 3')
                ->pluck('category_id');
            
            // Затем получаем только эти категории с подсчетом рецептов
            return Category::whereIn('id', $categoryIds)
                ->withCount(['recipes' => function($query) {
                    $query->where('is_published', true);
                }])
                ->orderBy('recipes_count', 'desc')
                ->limit(4)
                ->get();
        });

        // Загружаем рецепты для каждой избранной категории
        foreach ($featuredCategories as $category) {
            $category->featuredRecipes = Cache::remember('home_category_' . $category->id, 60, function () use ($category) {
                return Recipe::whereHas('categories', function ($query) use ($category) {
                    $query->where('categories.id', $category->id);
                })
                ->where('is_published', true)
                ->orderBy('views', 'desc')
                ->limit(4)
                ->get();
            });
        }

        // Популярные категории для раздела категорий
        $popularCategories = Cache::remember('home_popular_categories', 60 * 12, function () {
            return Category::withCount('recipes')
                ->orderByDesc('recipes_count')
                ->limit(12)
                ->get();
        });

        // Быстрые рецепты (до 30 минут)
        $quickRecipes = Cache::remember('home_quick_recipes', 60, function () {
            return Recipe::with('categories', 'user')
                ->where('is_published', true)
                ->where('cooking_time', '<=', 30)
                ->orderBy('created_at', 'desc')
                ->limit(4)
                ->get();
        });

        // Сезонные рецепты (соответствуют текущему сезону)
        $season = $this->getCurrentSeason();
        $seasonalRecipes = Cache::remember('home_seasonal_recipes_' . $season, 60, function () use ($season) {
            // Изменен подход к получению сезонных рецептов
            return Recipe::where(function($query) use ($season) {
                // Поиск по заголовку рецепта
                $query->where('title', 'like', '%' . $season . '%')
                    // Или по описанию
                    ->orWhere('description', 'like', '%' . $season . '%');
            })
            ->where('is_published', true)
            ->orderBy('views', 'desc')
            ->limit(4)
            ->get();
        });

        // Получаем последние рецепты
        $latestRecipes = Recipe::with('categories')
            ->latest()
            ->take(6)
            ->get();

        // Устанавливаем SEO-данные для главной страницы
        $this->seoService->setTitle('Лучшие кулинарные рецепты - Яедок')
            ->setDescription('Кулинарные рецепты с пошаговыми инструкциями, фото и списком ингредиентов. Готовьте вкусно с Яедок!')
            ->setKeywords('рецепты, кулинария, еда, готовка, блюда, домашняя кухня, пошаговые рецепты, рецепты с фото')
            ->setCanonical(url('/'))
            ->setOgType('website')
            ->setOgImage(asset('images/og-home.jpg'));

        // Настройка SEO для главной страницы
        $this->seoService->setTitle('Лучшие кулинарные рецепты - пошаговые инструкции');
        $this->seoService->setDescription('Кулинарные рецепты с пошаговыми инструкциями для приготовления вкусных и разнообразных блюд. Простые рецепты для домашней кухни.');
        $this->seoService->setKeywords('рецепты, кулинария, блюда, еда, готовка, кухня, приготовление');
        $this->seoService->setOgType('website');
        
        // Создаем Schema.org разметку для главной страницы
        $schemaWebsite = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'url' => url('/'),
            'name' => config('app.name'),
            'description' => 'Кулинарные рецепты с пошаговыми инструкциями для приготовления вкусных и разнообразных блюд',
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search?q={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            ]
        ];
        
        // Добавляем разметку Organization
        $schemaOrganization = [
            '@context' => 'https://schema.org',
            '@type' => 'Organization',
            'name' => config('app.name'),
            'url' => url('/'),
            'logo' => asset('images/logo.png')
        ];
        
        $this->seoService->setSchema([$schemaWebsite, $schemaOrganization]);

        return view('home', compact(
            'newRecipes', 
            'popularRecipes', 
            'featuredCategories', 
            'popularCategories', 
            'quickRecipes', 
            'seasonalRecipes', 
            'season',
            'latestRecipes'
        ));
    }

    /**
     * Автодополнение для поиска на главной странице
     */
    public function autocomplete(Request $request)
    {
        $query = $request->input('query');
        
        if (empty($query) || strlen($query) < 2) {
            return response()->json([]);
        }
        
        $suggestions = $this->searchService->getAutocompleteSuggestions($query);
        
        return response()->json($suggestions);
    }

    /**
     * Определяет текущий сезон
     */
    private function getCurrentSeason()
    {
        $month = date('n');
        
        if ($month >= 3 && $month <= 5) {
            return 'весна';
        } elseif ($month >= 6 && $month <= 8) {
            return 'лето';
        } elseif ($month >= 9 && $month <= 11) {
            return 'осень';
        } else {
            return 'зима';
        }
    }

    /**
     * Страница "О нас"
     */
    public function about()
    {
        return view('about');
    }
    
    /**
     * Страница контактов
     */
    public function contact()
    {
        return view('contact');
    }
    
    /**
     * Обработка контактной формы
     */
    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'subject' => 'required|string|max:255',
            'message' => 'required|string|min:10'
        ]);
        
        // Здесь логика отправки сообщения
        // Mail::to('admin@example.com')->send(new ContactMail($request->all()));
        
        return redirect()->back()->with('success', 'Ваше сообщение успешно отправлено.');
    }
}
