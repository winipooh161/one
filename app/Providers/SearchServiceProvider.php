<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\SearchService;

class SearchServiceProvider extends ServiceProvider
{
    /**
     * Register services specific to search pages.
     */
    public function register(): void
    {
        // Регистрируем сервис поиска
        $this->app->singleton(SearchService::class, function ($app) {
            return new SearchService();
        });
        
        // Алиас для удобного доступа
        $this->app->alias(SearchService::class, 'search.service');
        
        // Специфический для поиска сервис автодополнения
        $this->app->singleton('search.autocomplete', function ($app) {
            return new \App\Services\AutocompleteService();
        });
    }

    /**
     * Bootstrap services for search pages.
     */
    public function boot(): void
    {
        // Добавляем переменные в шаблоны поиска
        view()->composer(['search.*'], function ($view) {
            $view->with('isSearch', true);
            
            // Добавляем популярные поисковые запросы
            if (!isset($view->popularQueries)) {
                $view->with('popularQueries', $this->getPopularQueries());
            }
        });
    }
    
    /**
     * Получает популярные поисковые запросы
     */
    protected function getPopularQueries()
    {
        return \Illuminate\Support\Facades\Cache::remember('popular_search_queries', 60 * 60, function () {
            return \App\Models\SearchQuery::orderByDesc('count')
                ->limit(10)
                ->pluck('query');
        });
    }
}
