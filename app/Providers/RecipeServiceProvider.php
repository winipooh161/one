<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\RecipeService;
use App\Models\Recipe;
use Illuminate\Support\Facades\Schema;

class RecipeServiceProvider extends ServiceProvider
{
    /**
     * Register services specific to recipe pages.
     */
    public function register(): void
    {
        // Регистрируем сервис для работы с рецептами
        $this->app->singleton(RecipeService::class, function ($app) {
            return new RecipeService();
        });
        
        // Алиас для удобства использования
        $this->app->alias(RecipeService::class, 'recipe.service');
        
        // Сервис для подготовки ингредиентов
        $this->app->singleton('recipe.ingredients_formatter', function ($app) {
            return new \App\Services\IngredientsFormatter();
        });
    }

    /**
     * Bootstrap services for recipe pages.
     */
    public function boot(): void
    {
        // Скоупы для модели Recipe
        Recipe::addGlobalScope('published', function ($builder) {
            $builder->where('is_published', true);
        });
        
        // Добавляем переменные для всех шаблонов с рецептами
        view()->composer(['recipes.*', 'recipe.*'], function ($view) {
            $view->with('isRecipe', true);
            
            // Добавляем structured data для микроразметки
            // в шаблоны страниц рецептов
            if ($view->recipe ?? false) {
                $recipe = $view->recipe;
                $structuredData = $this->getRecipeStructuredData($recipe);
                $view->with('structuredData', $structuredData);
            }
        });
    }
    
    /**
     * Создает микроразметку для рецепта
     */
    protected function getRecipeStructuredData($recipe)
    {
        return [
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $recipe->title,
            'description' => $recipe->description,
            'keywords' => $recipe->categories->pluck('name')->join(', '),
            'author' => [
                '@type' => 'Person',
                'name' => $recipe->user->name ?? config('app.name')
            ],
            'datePublished' => $recipe->created_at->toIso8601String(),
            'image' => asset($recipe->image_url),
            'recipeYield' => $recipe->servings . ' ' . trans_choice('порция|порции|порций', $recipe->servings),
            'recipeCategory' => $recipe->categories->first()->name ?? '',
            'recipeCuisine' => $recipe->cuisine ?? 'Русская кухня',
            'prepTime' => 'PT' . $recipe->prep_time . 'M',
            'cookTime' => 'PT' . $recipe->cooking_time . 'M',
            'totalTime' => 'PT' . ($recipe->prep_time + $recipe->cooking_time) . 'M',
            'nutrition' => [
                '@type' => 'NutritionInformation',
                'calories' => $recipe->calories . ' ккал'
            ]
        ];
    }
}
