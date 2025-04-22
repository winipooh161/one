<?php

namespace App\Services;

use App\Models\User;
use App\Models\Recipe;
use Illuminate\Support\Facades\Cache;

class ProfileService
{
    /**
     * Получить рецепты пользователя
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getUserRecipes(User $user, int $limit = 10)
    {
        return Recipe::where('user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate($limit);
    }

    /**
     * Получить избранные рецепты пользователя
     *
     * @param User $user
     * @param int $limit
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getFavoriteRecipes(User $user, int $limit = 10)
    {
        return $user->favorites()
            ->orderByDesc('created_at')
            ->paginate($limit);
    }
    
    /**
     * Получить статистику пользователя
     *
     * @param User $user
     * @return array
     */
    public function getUserStatistics(User $user): array
    {
        $cacheKey = 'user_statistics_' . $user->id;
        
        return Cache::remember($cacheKey, 60 * 10, function () use ($user) {
            $recipesCount = Recipe::where('user_id', $user->id)->count();
            $publishedCount = Recipe::where('user_id', $user->id)
                ->where('is_published', true)
                ->count();
            $totalViews = Recipe::where('user_id', $user->id)->sum('views');
            
            return [
                'recipes_count' => $recipesCount,
                'published_count' => $publishedCount,
                'total_views' => $totalViews,
                'favorites_count' => $user->favorites()->count(),
                'comments_count' => $user->comments()->count(),
                'avg_rating' => $this->getUserAverageRating($user)
            ];
        });
    }
    
    /**
     * Получить среднюю оценку рецептов пользователя
     *
     * @param User $user
     * @return float
     */
    protected function getUserAverageRating(User $user): float
    {
        $recipeIds = Recipe::where('user_id', $user->id)
            ->pluck('id')
            ->toArray();
            
        if (empty($recipeIds)) {
            return 0;
        }
        
        return \DB::table('ratings')
            ->whereIn('recipe_id', $recipeIds)
            ->avg('rating') ?? 0;
    }
}
