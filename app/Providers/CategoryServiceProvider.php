<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\CategoryService;
use App\Models\Category;
use Illuminate\Support\Facades\Cache;

class CategoryServiceProvider extends ServiceProvider
{
    /**
     * Register services specific to category pages.
     */
    public function register(): void
    {
        // Регистрируем сервис для работы с категориями
        $this->app->singleton(CategoryService::class, function ($app) {
            return new CategoryService();
        });
        
        // Алиас для удобства использования
        $this->app->alias(CategoryService::class, 'category.service');
        
        // Кэшированный список популярных категорий
        $this->app->singleton('category.popular', function ($app) {
            return Cache::remember('popular_categories', 60 * 60, function () {
                return Category::withCount('recipes')
                    ->orderByDesc('recipes_count')
                    ->limit(10)
                    ->get();
            });
        });
    }

    /**
     * Bootstrap services for category pages.
     */
    public function boot(): void
    {
        // Добавляем переменные для всех шаблонов с категориями
        view()->composer(['categories.*', 'category.*'], function ($view) {
            $view->with('isCategory', true);
            
            // Добавляем популярные категории для боковой панели
            if (!isset($view->popularCategories)) {
                $view->with('popularCategories', app('category.popular'));
            }
            
            // Добавляем микроданные для категории
            if ($view->category ?? false) {
                $category = $view->category;
                $structuredData = $this->getCategoryStructuredData($category);
                $view->with('structuredData', $structuredData);
            }
        });
    }
    
    /**
     * Создает микроразметку для категории
     */
    protected function getCategoryStructuredData($category)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name,
            'description' => $category->description ?? 'Рецепты категории ' . $category->name,
            'url' => route('categories.show', $category->slug)
        ];
    }
}
