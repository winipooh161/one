<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\ProfileService;
use App\Services\UserRecipeService;

class ProfileServiceProvider extends ServiceProvider
{
    /**
     * Register services specific to profile pages.
     */
    public function register(): void
    {
        // Регистрируем основной сервис профиля
        $this->app->singleton(ProfileService::class, function ($app) {
            return new ProfileService();
        });
        
        // Добавляем специализированный сервис для работы с рецептами пользователя
        $this->app->singleton(UserRecipeService::class, function ($app) {
            return new UserRecipeService();
        });
        
        // Алиас для удобства
        $this->app->alias(ProfileService::class, 'profile.service');
    }

    /**
     * Bootstrap services for profile pages.
     */
    public function boot(): void
    {
        // Добавляем переменные в шаблоны профиля
        view()->composer(['profile.*', 'user.*'], function ($view) {
            $view->with('isProfile', true);
            
            // Если это просмотр собственного профиля
            if (auth()->check() && isset($view->user) && auth()->id() === $view->user->id) {
                $view->with('isOwnProfile', true);
                
                // Добавляем статистику для пользователя
                $view->with('userStats', $this->getUserStatistics(auth()->user()));
            }
        });
    }
    
    /**
     * Получает статистику для авторизованного пользователя
     */
    protected function getUserStatistics($user)
    {
        return [
            'recipes_count' => $user->recipes()->count(),
            'favorites_count' => $user->favorites()->count(),
            'comments_count' => $user->comments()->count(),
            'rating_avg' => $user->recipes()
                ->withCount(['ratings as average_rating' => function ($query) {
                    $query->select(\DB::raw('coalesce(avg(rating), 0)'));
                }])
                ->get()
                ->avg('average_rating')
        ];
    }
}
