<?php

namespace App\Services;

use App\Models\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class RecipeService
{
    /**
     * Получить похожие рецепты
     *
     * @param Recipe $recipe
     * @param int $limit
     * @return Collection
     */
    public function getSimilarRecipes(Recipe $recipe, int $limit = 4): Collection
    {
        $cacheKey = "similar_recipes_{$recipe->id}_{$limit}";
        
        return Cache::remember($cacheKey, 60 * 24, function () use ($recipe, $limit) {
            return Recipe::whereHas('categories', function($query) use ($recipe) {
                    $query->whereIn('categories.id', $recipe->categories->pluck('id'));
                })
                ->where('id', '!=', $recipe->id)
                ->where('is_published', true)
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Получить популярные рецепты
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularRecipes(int $limit = 8): Collection
    {
        $cacheKey = "popular_recipes_{$limit}";
        
        return Cache::remember($cacheKey, 60 * 60, function () use ($limit) {
            return Recipe::where('is_published', true)
                ->orderBy('views', 'desc')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Подготовка ингредиентов для отображения
     *
     * @param mixed $ingredients
     * @return array
     */
    public function prepareIngredients($ingredients): array
    {
        if (is_string($ingredients)) {
            return array_filter(explode("\n", $ingredients), function($line) {
                return !empty(trim($line));
            });
        }
        
        if (is_array($ingredients)) {
            return array_filter($ingredients, function($line) {
                return !empty(trim($line));
            });
        }
        
        return [];
    }
    
    /**
     * Найти рецепты по ключевым словам
     * 
     * @param string $query
     * @param int $limit
     * @return Collection
     */
    public function searchRecipes(string $query, int $limit = 10): Collection
    {
        $query = trim($query);
        
        if (empty($query)) {
            return collect([]);
        }
        
        return Recipe::where('is_published', true)
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhereHas('categories', function($q2) use ($query) {
                      $q2->where('name', 'like', "%{$query}%");
                  });
            })
            ->limit($limit)
            ->get();
    }
}
