<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class CategoryService
{
    /**
     * Получить популярные категории
     *
     * @param int $limit
     * @return Collection
     */
    public function getPopularCategories(int $limit = 12): Collection
    {
        return Cache::remember('popular_categories_'.$limit, 60 * 60 * 24, function () use ($limit) {
            return Category::withCount('recipes')
                ->orderByDesc('recipes_count')
                ->limit($limit)
                ->get();
        });
    }

    /**
     * Получить категории с рецептами для главной страницы
     *
     * @param int $limit
     * @return Collection
     */
    public function getFeaturedCategories(int $limit = 4): Collection
    {
        return Cache::remember('featured_categories_'.$limit, 60 * 60, function () use ($limit) {
            return Category::withCount(['recipes' => function($query) {
                    $query->where('is_published', true);
                }])
                ->having('recipes_count', '>', 3)
                ->orderByDesc('recipes_count')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Получить рецепты для определенной категории
     *
     * @param Category $category
     * @param int $limit
     * @return Collection
     */
    public function getCategoryRecipes(Category $category, int $limit = 12): Collection
    {
        return Cache::remember('category_recipes_'.$category->id.'_'.$limit, 60 * 10, function () use ($category, $limit) {
            return $category->recipes()
                ->where('is_published', true)
                ->orderByDesc('created_at')
                ->limit($limit)
                ->get();
        });
    }
    
    /**
     * Создание дерева категорий для навигации
     *
     * @return array
     */
    public function getCategoryTree(): array
    {
        return Cache::remember('category_tree', 60 * 60 * 24, function () {
            $categories = Category::withCount('recipes')
                ->orderBy('name')
                ->get();
                
            $tree = [];
            
            // Группировка категорий по первой букве для алфавитной навигации
            foreach ($categories as $category) {
                $firstLetter = mb_strtoupper(mb_substr($category->name, 0, 1));
                if (!isset($tree[$firstLetter])) {
                    $tree[$firstLetter] = [];
                }
                $tree[$firstLetter][] = $category;
            }
            
            ksort($tree);
            
            return $tree;
        });
    }

    /**
     * Получает категории, сгруппированные по первой букве
     *
     * @return \Illuminate\Support\Collection
     */
    public function getCategoriesByLetter()
    {
        return Cache::remember('categories_by_letter', 60 * 60, function() {
            $categories = Category::withCount('recipes')
                ->orderBy('name')
                ->get();
            
            return $categories->groupBy(function($category) {
                return mb_strtoupper(mb_substr($category->name, 0, 1));
            });
        });
    }

    /**
     * Получает список рецептов для конкретной категории с пагинацией
     *
     * @param Category $category
     * @param array $filters
     * @param int $perPage
     * @return \Illuminate\Contracts\Pagination\LengthAwarePaginator
     */
    public function getRecipesForCategory(Category $category, $filters = [], $perPage = 12)
    {
        $query = Recipe::whereHas('categories', function($q) use ($category) {
            $q->where('categories.id', $category->id);
        })->with('categories', 'user')->where('is_published', true);
        
        // Фильтрация по времени приготовления
        if (!empty($filters['cooking_time'])) {
            $query->where('cooking_time', '<=', $filters['cooking_time']);
        }
        
        // Сортировка
        if (!empty($filters['sort'])) {
            switch ($filters['sort']) {
                case 'popular':
                    $query->orderBy('views', 'desc');
                    break;
                case 'rating':
                    $query->leftJoin('ratings', 'recipes.id', '=', 'ratings.recipe_id')
                        ->selectRaw('recipes.*, COALESCE(AVG(ratings.rating), 0) as avg_rating')
                        ->groupBy('recipes.id')
                        ->orderBy('avg_rating', 'desc');
                    break;
                case 'cooking_time_asc':
                    $query->orderBy('cooking_time', 'asc');
                    break;
                case 'cooking_time_desc':
                    $query->orderBy('cooking_time', 'desc');
                    break;
                default:
                    $query->orderBy('created_at', 'desc');
            }
        } else {
            $query->orderBy('created_at', 'desc');
        }

        return $query->paginate($perPage);
    }

    /**
     * Получает популярные рецепты для категории
     *
     * @param Category $category
     * @param int $limit
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getPopularRecipesForCategory(Category $category, $limit = 5)
    {
        return Cache::remember('popular_recipes_category_' . $category->id . '_' . $limit, 60, function() use ($category, $limit) {
            return Recipe::whereHas('categories', function($q) use ($category) {
                $q->where('categories.id', $category->id);
            })->where('is_published', true)
              ->orderBy('views', 'desc')
              ->limit($limit)
              ->get();
        });
    }
    
    /**
     * Получает советы для категории
     *
     * @param Category $category
     * @return array
     */
    public function getTipsForCategory(Category $category)
    {
        // Здесь можно реализовать логику получения советов,
        // например, из базы данных или на основе ключевых слов категории
        return [
            'Выбирайте свежие ингредиенты для лучшего вкуса',
            'Следуйте рецепту, но не бойтесь экспериментировать',
            'Готовьте с любовью и хорошим настроением!',
            'Используйте правильную температуру для приготовления',
            'Соблюдайте пропорции и время приготовления для идеального результата'
        ];
    }
    
    /**
     * Генерирует метаданные для категории
     *
     * @param Category $category
     * @return array
     */
    public function generateMetaForCategory(Category $category)
    {
        $title = $category->meta_title ?? $category->name . ' - рецепты на ' . config('app.name');
        $description = $category->meta_description ?? 'Рецепты в категории ' . $category->name . '. Подробные пошаговые инструкции с фото.';
        $keywords = $category->meta_keywords ?? $category->name . ', рецепты, кулинария, готовка';
        
        return [
            'title' => $title,
            'description' => $description,
            'keywords' => $keywords,
            'og_image' => $category->image_path ? asset($category->image_path) : asset('images/categories-cover.jpg')
        ];
    }
}
