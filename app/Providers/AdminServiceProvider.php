<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AdminService;
use App\Services\RecipeParserService;
use App\Services\SocialMediaService;

class AdminServiceProvider extends ServiceProvider
{
    /**
     * Register services specific to admin pages.
     */
    public function register(): void
    {
        // Регистрируем основной сервис для админ-панели
        $this->app->singleton(AdminService::class, function ($app) {
            return new AdminService();
        });
        
        // Регистрация дополнительных админских сервисов
        // которые не нужны на фронтенде
        $this->app->singleton(RecipeParserService::class, function ($app) {
            return new RecipeParserService();
        });
        
        $this->app->singleton(SocialMediaService::class, function ($app) {
            return new SocialMediaService();
        });
        
        // Сервис модерации
        $this->app->singleton('admin.moderation', function ($app) {
            return new \App\Services\ModerationService();
        });
    }

    /**
     * Bootstrap services for admin pages.
     */
    public function boot(): void
    {
        // Глобальные переменные для админских шаблонов
        view()->composer('admin.*', function ($view) {
            $view->with('isAdmin', true);
            
            // Если пользователь авторизован, добавляем проверку прав
            if (auth()->check()) {
                $view->with('isAdminUser', auth()->user()->isAdmin());
                $view->with('hasModeratorAccess', auth()->user()->can('moderate-recipes'));
                $view->with('canManageCategories', auth()->user()->can('manage-categories'));
            }
            
            // Добавляем счетчики для бейджей в админке
            $view->with('pendingRecipesCount', $this->getPendingRecipesCount());
        });
    }
    
    /**
     * Получаем количество рецептов, ожидающих модерации
     */
    protected function getPendingRecipesCount()
    {
        // Если не в админке, не выполняем запрос
        if (!request()->is('admin*')) {
            return 0;
        }
        
        // Кэшируем результат на 5 минут
        return \Illuminate\Support\Facades\Cache::remember('admin_pending_recipes_count', 300, function () {
            return \App\Models\Recipe::where('is_published', false)->count();
        });
    }
}
