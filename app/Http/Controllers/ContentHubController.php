<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\Article;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;

class ContentHubController extends Controller
{
    /**
     * Отображение многофункциональной страницы контента.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        try {
            // Рекомендуемые рецепты
            $featuredRecipes = Recipe::where('status', 'published')
                ->orderBy('views', 'desc')
                ->take(8)
                ->get();

            // Последние новости
            $news = Article::where('status', 'published')
                ->where('type', 'news')
                ->orderBy('published_at', 'desc')
                ->take(3)
                ->get();

            // Категории новостей
            $newsCategories = Category::withCount(['articles' => function($query) {
                $query->where('status', 'published')
                      ->where('type', 'news');
            }])
            ->having('articles_count', '>', 0)
            ->orderBy('articles_count', 'desc')
            ->take(4)
            ->get();

            // Тематические сборки рецептов
            $collections = Article::where('status', 'published')
                ->where('type', 'guide')
                ->orderBy('views', 'desc')
                ->take(2)
                ->get();

            // Популярные категории рецептов
            $categories = Category::withCount(['recipes' => function($query) {
                $query->where('status', 'published');
            }])
            ->having('recipes_count', '>', 0)
            ->orderBy('recipes_count', 'desc')
            ->take(6)
            ->get();

            // Идеи для вдохновения (рецепты с тегом "идея")
            $ideas = Recipe::where('status', 'published')
                ->where('tags', 'like', '%идея%')
                ->orderBy('created_at', 'desc')
                ->take(3)
                ->get();

            return view('content-hub.index', compact(
                'featuredRecipes', 
                'news', 
                'newsCategories',
                'collections',
                'categories',
                'ideas'
            ));
        } catch (\Exception $e) {
            Log::error('Error in ContentHub index: ' . $e->getMessage());
            abort(500, 'Произошла ошибка при загрузке страницы. Пожалуйста, попробуйте позже.');
        }
    }
}
