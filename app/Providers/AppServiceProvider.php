<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Schema;
use App\Services\SeoService;
use Illuminate\Support\Facades\Request;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Регистрируем SeoService как синглтон
        $this->app->singleton(SeoService::class, function ($app) {
            return new SeoService();
        });
        
        // Добавляем алиас для обратной совместимости
        $this->app->alias(SeoService::class, 'SeoService');
        
        // Регистрируем провайдеры для конкретных разделов сайта
        $this->registerPageSpecificProviders();
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Используем Bootstrap для пагинации
        Paginator::useBootstrap();
        
        // Устанавливаем длину строки по умолчанию для MySQL
        Schema::defaultStringLength(191);
        
        // Сохраняем параметры фильтрации и сортировки при пагинации
        Paginator::defaultView('pagination::bootstrap-5');
        
        // Явно указываем метод withQueryString для включения всех существующих параметров в пагинацию
        // Это предотвращает потерю фильтров при переходе между страницами

        if (\DB::getDriverName() === 'mysql') {
            \DB::statement('SET SQL_BIG_SELECTS=1');
        }
    }
    
    /**
     * Регистрирует провайдеры в зависимости от текущего маршрута
     */
    protected function registerPageSpecificProviders()
    {
        $path = Request::path();
        
        // Регистрируем провайдер главной страницы
        if ($path === '/' || $path === 'home') {
            $this->app->register(HomeServiceProvider::class);
        }
        
        // Провайдер для страниц рецептов
        if (str_starts_with($path, 'recipes') || str_contains($path, '/recipes/')) {
            $this->app->register(RecipeServiceProvider::class);
        }
        
        // Провайдер для страниц категорий
        if (str_starts_with($path, 'categories') || str_contains($path, '/categories/')) {
            $this->app->register(CategoryServiceProvider::class);
        }
        
        // Провайдер для админ-панели
        if (str_starts_with($path, 'admin') || str_contains($path, '/admin/')) {
            $this->app->register(AdminServiceProvider::class);
        }
        
        // Провайдер для поиска
        if (str_starts_with($path, 'search') || str_contains($path, '/search/')) {
            $this->app->register(SearchServiceProvider::class);
        }
        
        // Провайдер для профиля пользователя
        if (str_starts_with($path, 'profile') || str_contains($path, '/profile/') ||
            str_starts_with($path, 'user') || str_contains($path, '/user/')) {
            $this->app->register(ProfileServiceProvider::class);
        }
    }
}
