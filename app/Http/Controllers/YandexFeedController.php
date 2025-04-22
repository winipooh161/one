<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use App\Models\Recipe;
use App\Models\Category;
use App\Services\YmlGenerator;

class YandexFeedController extends Controller
{
    /**
     * Отображение XML-ленты рецептов для Яндекса
     */
    public function index()
    {
        $recipes = Recipe::where('is_published', true)
            ->with(['categories', 'user'])
            ->latest()
            ->take(1000)
            ->get();
            
        return response()->view('feeds.yandex.recipes', [
            'recipes' => $recipes
        ])->header('Content-Type', 'text/xml');
    }
    
    /**
     * Отображение комбинированной ленты для Яндекса
     */
    public function combined()
    {
        $recipes = Recipe::where('is_published', true)
            ->with(['categories', 'user'])
            ->latest()
            ->take(1000)
            ->get();
            
        return response()->view('feeds.yandex.combined', [
            'recipes' => $recipes
        ])->header('Content-Type', 'text/xml');
    }
    
    /**
     * Отображение ленты мастер-классов для Яндекса
     */
    public function masterclasses()
    {
        $recipes = Recipe::where('is_published', true)
            ->whereHas('categories', function($query) {
                $query->where('name', 'like', '%мастер%класс%')
                      ->orWhere('name', 'like', '%мастер-класс%');
            })
            ->with(['categories', 'user'])
            ->latest()
            ->take(500)
            ->get();
            
        return response()->view('feeds.yandex.masterclasses', [
            'recipes' => $recipes
        ])->header('Content-Type', 'text/xml');
    }
    
    /**
     * Отображение ленты образовательных материалов для Яндекса
     */
    public function education()
    {
        // Реализация для образовательных материалов
        return response()->view('feeds.yandex.education')->header('Content-Type', 'text/xml');
    }
    
    /**
     * Отображение ленты услуг для Яндекса
     */
    public function services()
    {
        // Реализация для услуг
        return response()->view('feeds.yandex.services')->header('Content-Type', 'text/xml');
    }
    
    /**
     * Отображение ленты активностей для Яндекса
     */
    public function activities()
    {
        // Реализация для активностей
        return response()->view('feeds.yandex.activities')->header('Content-Type', 'text/xml');
    }
    
    /**
     * Обновление кэша фидов для Яндекса
     */
    public function refresh()
    {
        try {
            // Очищаем кэш для всех фидов
            Cache::forget('yandex_feed_recipes');
            Cache::forget('yandex_feed_combined');
            Cache::forget('yandex_feed_masterclasses');
            Cache::forget('yandex_feed_education');
            Cache::forget('yandex_feed_services');
            Cache::forget('yandex_feed_activities');
            
            // Можно также принудительно сгенерировать новые версии фидов
            $this->generateRecipesFeed();
            $this->generateCombinedFeed();
            
            return response()->json([
                'success' => true,
                'message' => 'Кэш фидов Яндекса успешно обновлен'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении кэша: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Генерация фида рецептов
     */
    private function generateRecipesFeed()
    {
        $recipes = Recipe::where('is_published', true)
            ->with(['categories', 'user'])
            ->latest()
            ->take(1000)
            ->get();
            
        // Сохраняем результат в кэш
        Cache::put('yandex_feed_recipes', $recipes, now()->addHours(6));
        
        return $recipes;
    }
    
    /**
     * Генерация комбинированного фида
     */
    private function generateCombinedFeed()
    {
        $recipes = Recipe::where('is_published', true)
            ->with(['categories', 'user'])
            ->latest()
            ->take(1000)
            ->get();
            
        // Сохраняем результат в кэш
        Cache::put('yandex_feed_combined', $recipes, now()->addHours(6));
        
        return $recipes;
    }

    /**
     * Генерация YML-фида для Яндекса
     */
    public function yml()
    {
        // Пытаемся получить данные из кэша или генерируем заново
        return Cache::remember('yandex_yml_feed', 60 * 12, function () {
            $generator = new YmlGenerator();
            $xml = $generator->generate();
            
            return response($xml)->header('Content-Type', 'text/xml');
        });
    }
    
    /**
     * Обновление кэша YML-фида
     */
    public function refreshYml()
    {
        try {
            Cache::forget('yandex_yml_feed');
            return response()->json([
                'success' => true,
                'message' => 'Кэш YML-фида успешно обновлен'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обновлении кэша: ' . $e->getMessage()
            ]);
        }
    }

    /**
     * Генерирует RSS-фид для Яндекс Дзен
     */
    public function zen()
    {
        // Получаем последние 30 опубликованных рецептов
        $recipes = Recipe::where('is_published', true)
                    ->orderBy('published_at', 'desc')
                    ->orOrderBy('created_at', 'desc')
                    ->with('categories')
                    ->limit(30)
                    ->get();
        
        // Устанавливаем правильный заголовок для XML
        return response()
            ->view('feeds.yandex.zen', compact('recipes'))
            ->header('Content-Type', 'application/rss+xml; charset=utf-8');
    }
}
