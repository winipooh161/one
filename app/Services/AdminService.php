<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Category;
use App\Models\User;
use App\Models\Comment;
use Illuminate\Support\Facades\Cache;

class AdminService
{
    /**
     * Получить статистику для административной панели
     *
     * @return array
     */
    public function getDashboardStats(): array
    {
        $cacheKey = 'admin_dashboard_stats';
        
        return Cache::remember($cacheKey, 60 * 15, function () {
            return [
                'total_recipes' => Recipe::count(),
                'published_recipes' => Recipe::where('is_published', true)->count(),
                'pending_recipes' => Recipe::where('is_published', false)->count(),
                'total_categories' => Category::count(),
                'total_users' => User::count(),
                'total_comments' => Comment::count(),
                'most_viewed_recipe' => $this->getMostViewedRecipe(),
                'most_active_user' => $this->getMostActiveUser(),
                'recent_users' => User::orderByDesc('created_at')->limit(5)->get(),
                'recent_comments' => Comment::with('user', 'recipe')->orderByDesc('created_at')->limit(5)->get()
            ];
        });
    }

    /**
     * Получить рецепты для модерации
     *
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRecipesForModeration(int $limit = 15)
    {
        return Recipe::where('is_published', false)
            ->with('user', 'categories')
            ->orderByDesc('created_at')
            ->paginate($limit);
    }
    
    /**
     * Получить самый просматриваемый рецепт
     *
     * @return Recipe|null
     */
    protected function getMostViewedRecipe()
    {
        return Recipe::where('is_published', true)
            ->orderByDesc('views')
            ->first();
    }
    
    /**
     * Получить самого активного пользователя по количеству рецептов
     *
     * @return User|null
     */
    protected function getMostActiveUser()
    {
        return User::withCount('recipes')
            ->orderByDesc('recipes_count')
            ->first();
    }
    
    /**
     * Очистить все кэши приложения
     *
     * @return void
     */
    public function clearAllCaches(): void
    {
        Cache::flush();
        
        // Сбрасываем кэш маршрутов
        if (function_exists('artisan')) {
            \Artisan::call('route:cache');
            \Artisan::call('view:clear');
            \Artisan::call('config:clear');
        }
    }
}
