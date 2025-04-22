<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Services\SeoService;
use Illuminate\Support\Str;

class RecipeController extends Controller
{
    protected $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    public function index(Request $request)
    {
        // Инициализируем переменные, чтобы избежать ошибки compact()
        $category = null;
        $categoryId = null;
        $search = trim($request->input('search', $request->input('q', '')));
        $cookingTime = $request->input('cooking_time');

        $query = Recipe::select('recipes.*')
            ->with('categories', 'user')
            ->withCount('ratings');
            
        $query->leftJoin('ratings', 'recipes.id', '=', 'ratings.recipe_id')
            ->selectRaw('COALESCE(AVG(ratings.rating), 0) as ratings_avg')
            ->groupBy('recipes.id');
            
        // Фильтрация по категории, если параметр заполнен
        if ($request->filled('category')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('slug', $request->category);
            });
            
            $category = Category::where('slug', $request->category)->first();
        }
        
        // Фильтрация по идентификатору категории
        if ($request->filled('category_id')) {
            $categoryId = $request->category_id;
            $query->whereHas('categories', function($q) use ($categoryId) {
                $q->where('categories.id', $categoryId);
            });
            
            // Получаем объект категории для отображения заголовка
            if (!$category) {
                $category = Category::find($categoryId);
            }
        }
        
        // Фильтрация по времени приготовления, если параметр заполнен
        if ($request->filled('cooking_time')) {
            $query->where('cooking_time', '<=', $request->cooking_time);
        }
        
        // Унифицированная обработка поиска: используем LOWER() для процентного сравнения
        if (!empty($search)) {
            $lowerSearchTerm = strtolower($search);
            $query->where(function($q) use ($lowerSearchTerm) {
                $q->whereRaw('LOWER(title) like ?', ['%' . $lowerSearchTerm . '%'])
                  ->orWhereRaw('LOWER(description) like ?', ['%' . $lowerSearchTerm . '%']);
            });
        }
        
        // Сортировка
        $sort = $request->sort ?? 'newest';
        
        switch ($sort) {
            case 'popular':
                $query->orderBy('views', 'desc');
                break;
            case 'rating':
                $query->orderBy('ratings_avg', 'desc');
                break;
            case 'newest':
            default:
                $query->orderBy('created_at', 'desc');
                break;
        }
        
        $recipes = $query->paginate(12);
        
        // Загружаем все категории с количеством рецептов для фильтра
        $categories = Category::withCount('recipes')->orderBy('name')->get();
        
        // Подготавливаем поисковые термины для подсветки результатов
        $searchTerms = !empty($search) ? preg_split('/\s+/', $search) : null;

        return view('recipes.index', compact(
            'recipes', 
            'categories', 
            'category',
            'categoryId',
            'search',
            'cookingTime',
            'sort',
            'searchTerms'
        ));
    }

    /**
     * Отображение конкретного рецепта
     *
     * @param string $slug
     * @return \Illuminate\Http\Response
     */
    public function show(string $slug)
    {
        $recipe = Recipe::with(['categories', 'user'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
        
        // Инкрементируем количество просмотров
        $recipe->increment('views');
        
        // Получаем похожие рецепты
        $relatedRecipes = $recipe->relatedRecipes();
        
        // Готовим данные для PWA
        $pwaData = [
            'recipe' => [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'slug' => $recipe->slug,
                'image_url' => $recipe->image_url,
                'description' => Str::limit($recipe->description, 100),
                'cooking_time' => $recipe->cooking_time,
                'servings' => $recipe->servings,
                'viewed_at' => now()->toIso8601String()
            ]
        ];
        
        // Подготовка ингредиентов для отображения
        try {
            $recipe->ingredients = $recipe->getIngredientsForDisplay();
            
            // Подготовка инструкций
            if (isset($recipe->attributes['instructions'])) {
                if (is_string($recipe->attributes['instructions'])) {
                    $instructionsArray = explode("\n", $recipe->attributes['instructions']);
                    $recipe->instructions = collect($instructionsArray)
                        ->filter(function($line) {
                            return !empty(trim($line));
                        })->values()->all();
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Error preparing recipe data: " . $e->getMessage());
            $recipe->ingredients = is_array($recipe->ingredients) ? $recipe->ingredients : [];
            $recipe->instructions = is_string($recipe->instructions) ? $recipe->instructions : 
                (is_array($recipe->instructions) ? $recipe->instructions : '');
        }
        
        return view('recipes.show', compact('recipe', 'relatedRecipes', 'pwaData'));
    }
    
    /**
     * Отображение AMP версии рецепта
     *
     * @param string $slug
     * @return \Illuminate\Http\Response
     */
    public function showAmp(string $slug)
    {
        $recipe = Recipe::with(['categories', 'user'])
            ->where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
        
        // Инкрементируем количество просмотров
        $recipe->increment('views');
        
        // Подготовка ингредиентов для отображения
        try {
            $recipe->ingredients = $recipe->getIngredientsForDisplay();
            
            // Подготовка инструкций
            if (isset($recipe->attributes['instructions'])) {
                if (is_string($recipe->attributes['instructions'])) {
                    $instructionsArray = explode("\n", $recipe->attributes['instructions']);
                    $recipe->instructions = collect($instructionsArray)
                        ->filter(function($line) {
                            return !empty(trim($line));
                        })->values()->all();
                }
            }
        } catch (\Exception $e) {
            \Log::warning("Error preparing recipe data for AMP: " . $e->getMessage());
            $recipe->ingredients = is_array($recipe->ingredients) ? $recipe->ingredients : [];
            $recipe->instructions = is_string($recipe->instructions) ? $recipe->instructions : 
                (is_array($recipe->instructions) ? $recipe->instructions : '');
        }
        
        return view('recipes.amp', compact('recipe'));
    }

    /**
     * Увеличивает счетчик просмотров рецепта
     *
     * @param Recipe $recipe
     * @return void
     */
    protected function incrementViews(Recipe $recipe)
    {
        $recipe->increment('views');
    }
}
