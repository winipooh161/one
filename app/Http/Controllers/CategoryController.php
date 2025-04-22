<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Recipe;
use App\Services\CategoryService;
use App\Services\SeoService;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    protected $categoryService;
    protected $seoService;

    public function __construct(CategoryService $categoryService, SeoService $seoService)
    {
        $this->categoryService = $categoryService;
        $this->seoService = $seoService;
    }

    /**
     * Отображает список категорий
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        // Получаем популярные категории
        $popularCategories = $this->categoryService->getPopularCategories(8);
        
        // Получаем категории по алфавиту
        $categoriesByLetter = $this->categoryService->getCategoriesByLetter();
        
        // Получаем случайные рецепты для вдохновения
        $featuredRecipes = Recipe::where('is_published', true)
            ->with('categories')
            ->inRandomOrder()
            ->limit(4)
            ->get();
        
        // Настраиваем SEO
        $seoMeta = $this->seoService->getCategoriesPageMeta();
        
        // Передаем данные в представление
        return view('categories.index', compact(
            'popularCategories', 
            'categoriesByLetter',
            'featuredRecipes'
        ))
        ->with('title', $seoMeta['title'])
        ->with('description', $seoMeta['meta_description'])
        ->with('keywords', $seoMeta['meta_keywords'])
        ->with('categoriesCount', $categoriesByLetter->sum(function ($items) {
            return $items->count();
        }));
    }

    /**
     * Отображает конкретную категорию и её рецепты
     *
     * @param Request $request
     * @param string $slug
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $slug)
    {
        // Находим категорию по slug
        $category = Category::where('slug', $slug)->firstOrFail();
        
        // Получаем рецепты для категории с фильтрами и пагинацией
        $filters = [
            'cooking_time' => $request->input('cooking_time'),
            'sort' => $request->input('sort', 'latest')
        ];
        $recipes = $this->categoryService->getRecipesForCategory($category, $filters);
        
        // Получаем все категории для сайдбара
        $categories = Category::withCount('recipes')
            ->orderBy('name')
            ->get();
        
        // Получаем популярные рецепты для этой категории
        $popularRecipes = $this->categoryService->getPopularRecipesForCategory($category);
        
        // Получаем советы для этой категории
        $categoryTips = $this->categoryService->getTipsForCategory($category);
        
        // Настраиваем SEO
        $seoMeta = $this->seoService->getCategoryMeta($category);
        
        // Подготавливаем пагинацию для SEO
        $paginationLinks = [];
        if ($recipes->hasPages()) {
            if ($recipes->currentPage() > 1) {
                $paginationLinks['prev'] = $recipes->previousPageUrl();
            }
            if ($recipes->hasMorePages()) {
                $paginationLinks['next'] = $recipes->nextPageUrl();
            }
        }
        
        // Определяем canonical URL для текущей страницы
        $canonical = route('categories.show', $category->slug);
        if ($recipes->currentPage() > 1) {
            $canonical = $recipes->url($recipes->currentPage());
        }
        
        // Передаем данные в представление
        return view('categories.show', compact(
            'category',
            'recipes',
            'categories',
            'popularRecipes',
            'categoryTips',
            'paginationLinks',
            'canonical'
        ))
        ->with('title', $seoMeta['title'])
        ->with('description', $seoMeta['meta_description'])
        ->with('keywords', $seoMeta['meta_keywords']);
    }
}
