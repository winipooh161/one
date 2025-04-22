<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class RSSController extends Controller
{
    /**
     * Генерирует RSS-фид рецептов
     *
     * @return \Illuminate\Http\Response
     */
    public function recipes()
    {
        $recipes = Cache::remember('feed_recipes', 60, function() {
            return Recipe::where('is_published', true)
                ->with('categories', 'user')
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        });

        $content = view('feeds.recipes', compact('recipes'));

        return response($content, 200)
            ->header('Content-Type', 'application/rss+xml');
    }

    /**
     * Генерирует RSS-фид категорий
     *
     * @return \Illuminate\Http\Response
     */
    public function categories()
    {
        $categories = Cache::remember('feed_categories', 60 * 60, function() {
            return Category::withCount('recipes')
                ->orderBy('recipes_count', 'desc')
                ->get();
        });

        $content = view('feeds.categories', compact('categories'));

        return response($content, 200)
            ->header('Content-Type', 'application/rss+xml');
    }
}
