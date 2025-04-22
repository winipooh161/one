<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * Отображает индекс карты сайта
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $content = view('sitemaps.index', [
            'sitemaps' => [
                [
                    'loc' => route('sitemap.recipes'),
                    'lastmod' => $this->getLastModifiedDate('recipes')
                ],
                [
                    'loc' => route('sitemap.categories'),
                    'lastmod' => $this->getLastModifiedDate('categories')
                ]
            ]
        ]);

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Карта сайта для рецептов
     *
     * @return \Illuminate\Http\Response
     */
    public function recipes()
    {
        $recipes = Cache::remember('sitemap_recipes', 60 * 24, function() {
            return Recipe::where('is_published', true)
                ->orderBy('updated_at', 'desc')
                ->select('id', 'slug', 'updated_at')
                ->get();
        });

        $content = view('sitemaps.recipes', compact('recipes'));

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Карта сайта для категорий
     *
     * @return \Illuminate\Http\Response
     */
    public function categories()
    {
        $categories = Cache::remember('sitemap_categories', 60 * 24, function() {
            return Category::orderBy('updated_at', 'desc')
                ->select('id', 'slug', 'updated_at')
                ->get();
        });

        $content = view('sitemaps.categories', compact('categories'));

        return response($content, 200)
            ->header('Content-Type', 'text/xml');
    }

    /**
     * Получает дату последнего изменения для определенного типа данных
     *
     * @param string $type
     * @return string
     */
    private function getLastModifiedDate($type)
    {
        if ($type === 'recipes') {
            $lastUpdated = Recipe::where('is_published', true)
                ->latest('updated_at')
                ->value('updated_at');

            return $lastUpdated ? $lastUpdated->toAtomString() : now()->toAtomString();
        }

        if ($type === 'categories') {
            $lastUpdated = Category::latest('updated_at')->value('updated_at');
            return $lastUpdated ? $lastUpdated->toAtomString() : now()->toAtomString();
        }

        return now()->toAtomString();
    }
}
