<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class RecommendationController extends Controller
{
    /**
     * Получить персонализированные рекомендации рецептов для пользователя
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRecommendations(Request $request)
    {
        // Проверяем, авторизован ли пользователь
        if (!Auth::check()) {
            return response()->json([], 401);
        }
        
        $user = Auth::user();
        
        // Получаем ID просмотренных пользователем рецептов
        $viewedRecipeIds = DB::table('recipe_views')
            ->where('user_id', $user->id)
            ->pluck('recipe_id');
            
        // Получаем ID рецептов, добавленных в избранное
        $favoriteRecipeIds = DB::table('favorites')
            ->where('user_id', $user->id)
            ->pluck('recipe_id');
            
        // Объединяем взаимодействия пользователя
        $userInteractionIds = $viewedRecipeIds->merge($favoriteRecipeIds)->unique();
        
        // Если у пользователя нет взаимодействий, показываем популярные рецепты
        if ($userInteractionIds->isEmpty()) {
            $recommendedRecipes = Recipe::where('is_published', true)
                ->with(['categories'])
                ->orderBy('views', 'desc')
                ->limit(4)
                ->get();
                
            return response()->json($recommendedRecipes);
        }
        
        // Получаем категории, которые интересуют пользователя
        $userCategoryIds = DB::table('category_recipe')
            ->whereIn('recipe_id', $userInteractionIds)
            ->pluck('category_id')
            ->unique();
            
        // Находим похожие рецепты из тех же категорий, но которые пользователь еще не видел
        $recommendedRecipes = Recipe::where('is_published', true)
            ->whereNotIn('id', $userInteractionIds)
            ->whereHas('categories', function ($query) use ($userCategoryIds) {
                $query->whereIn('categories.id', $userCategoryIds);
            })
            ->with(['categories'])
            ->orderBy('views', 'desc')
            ->limit(4)
            ->get();
            
        // Если рекомендаций недостаточно, добавляем популярные рецепты
        if ($recommendedRecipes->count() < 4) {
            $additionalCount = 4 - $recommendedRecipes->count();
            
            $additionalRecipes = Recipe::where('is_published', true)
                ->whereNotIn('id', $userInteractionIds)
                ->whereNotIn('id', $recommendedRecipes->pluck('id'))
                ->with(['categories'])
                ->orderBy('views', 'desc')
                ->limit($additionalCount)
                ->get();
                
            $recommendedRecipes = $recommendedRecipes->merge($additionalRecipes);
        }
        
        return response()->json($recommendedRecipes);
    }
}
