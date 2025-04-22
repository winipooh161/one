<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Cache;
use App\Models\Recipe;
use App\Models\Category;

class HomeServiceProvider extends ServiceProvider
{
    /**
     * Register services specific to the home page.
     */
    public function register(): void
    {
        // Регистрируем только сервисы, необходимые для главной страницы
        $this->app->singleton('home.featured_recipes', function ($app) {
            return Cache::remember('home_featured_recipes', 60 * 30, function () {
                return Recipe::where('is_published', true)
                    ->orderBy('created_at', 'desc')
                    ->limit(6)
                    ->get();
            });
        });
    }

    /**
     * Bootstrap services for the home page.
     */
    public function boot(): void
    {
        // Инъекция переменных только для шаблонов главной страницы
        view()->composer('home', function ($view) {
            // Если сезонные рецепты нужны только на главной странице, загружаем их здесь
            $season = $this->getCurrentSeason();
            $seasonalRecipes = Cache::remember('home_seasonal_recipes_'.$season, 60*60, function () use ($season) {
                return Recipe::where('title', 'like', '%'.$season.'%')
                    ->orWhere('description', 'like', '%'.$season.'%')
                    ->where('is_published', true)
                    ->limit(4)
                    ->get();
            });
            
            $view->with('isHome', true)
                 ->with('season', $season)
                 ->with('seasonalRecipes', $seasonalRecipes);
        });
    }
    
    /**
     * Определить текущий сезон
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
}
