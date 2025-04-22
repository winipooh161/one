<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\User; // Исправлен синтаксис импорта здесь (было App\Models.User)
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class CollectAllUrls extends Command
{
    protected $signature = 'site:collect-urls {--output=urls.txt : Имя выходного файла}';
    protected $description = 'Собирает все URL-адреса сайта и сохраняет их в текстовый файл';

    public function handle()
    {
        $this->info('Начинаем сбор всех URL-адресов сайта...');
        
        $outputFile = $this->option('output');
        $urls = [];
        
        // 1. Собираем статические страницы
        $this->info('Собираем статические страницы...');
        $urls = array_merge($urls, $this->collectStaticPages());
        $this->info('Собрано ' . count($this->collectStaticPages()) . ' статических страниц');
        
        // 2. Собираем страницы рецептов
        $this->info('Собираем страницы рецептов...');
        $urls = array_merge($urls, $this->collectRecipePages());
        $this->info('Собрано ' . count($this->collectRecipePages()) . ' страниц рецептов');
        
        // 3. Собираем страницы категорий
        $this->info('Собираем страницы категорий...');
        $urls = array_merge($urls, $this->collectCategoryPages());
        $this->info('Собрано ' . count($this->collectCategoryPages()) . ' страниц категорий');
        
        // 4. Собираем страницы пользователей
        $this->info('Собираем страницы пользователей...');
        $urls = array_merge($urls, $this->collectUserPages());
        $this->info('Собрано ' . count($this->collectUserPages()) . ' страниц пользователей');
        
        // 5. Собираем страницы поиска
        $this->info('Собираем страницы поиска...');
        $urls = array_merge($urls, $this->collectSearchPages());
        $this->info('Собрано ' . count($this->collectSearchPages()) . ' страниц поиска');
        
        // Записываем все URL в файл
        Storage::disk('local')->put($outputFile, implode("\n", $urls));
        $this->info('Все URL-адреса сохранены в файл: ' . storage_path('app/' . $outputFile));
        $this->info('Всего собрано: ' . count($urls) . ' URL-адресов');
        
        return 0;
    }
    
    /**
     * Собирает URL-адреса статических страниц
     */
    private function collectStaticPages()
    {
        $staticUrls = [
            url('/'),
            route('recipes.index'),
            route('categories.index'),
            route('search'),
            route('legal.terms'),
            route('legal.privacy'),
            route('legal.disclaimer'),
            route('legal.dmca'),
            route('sitemap'),
        ];
        
        return $staticUrls;
    }
    
    /**
     * Собирает URL-адреса страниц рецептов
     */
    private function collectRecipePages()
    {
        $recipeUrls = [];
        
        Recipe::where('is_published', true)
            ->select('slug')
            ->chunk(100, function($recipes) use (&$recipeUrls) {
                foreach ($recipes as $recipe) {
                    $recipeUrls[] = route('recipes.show', $recipe->slug);
                }
            });
            
        // Добавляем страницы пагинации для списка рецептов
        $totalRecipes = Recipe::where('is_published', true)->count();
        $perPage = 12; // Количество рецептов на странице
        $totalPages = ceil($totalRecipes / $perPage);
        
        for ($page = 2; $page <= $totalPages; $page++) {
            $recipeUrls[] = route('recipes.index', ['page' => $page]);
        }
        
        return $recipeUrls;
    }
    
    /**
     * Собирает URL-адреса страниц категорий
     */
    private function collectCategoryPages()
    {
        $categoryUrls = [];
        
        Category::select('slug')
            ->chunk(100, function($categories) use (&$categoryUrls) {
                foreach ($categories as $category) {
                    $categoryUrls[] = route('categories.show', $category->slug);
                    
                    // Добавляем страницы пагинации для каждой категории
                    $recipeCount = DB::table('category_recipe')
                        ->join('recipes', 'category_recipe.recipe_id', '=', 'recipes.id')
                        ->where('category_recipe.category_id', $category->id)
                        ->where('recipes.is_published', true)
                        ->count();
                    
                    $perPage = 12; // Количество рецептов на странице категории
                    $totalPages = ceil($recipeCount / $perPage);
                    
                    for ($page = 2; $page <= $totalPages; $page++) {
                        $categoryUrls[] = route('categories.show', [
                            'slug' => $category->slug,
                            'page' => $page
                        ]);
                    }
                }
            });
            
        return $categoryUrls;
    }
    
    /**
     * Собирает URL-адреса страниц пользователей
     */
    private function collectUserPages()
    {
        $userUrls = [];
        
        User::whereHas('recipes', function($query) {
                $query->where('is_published', true);
            })
            ->select('id')
            ->chunk(100, function($users) use (&$userUrls) {
                foreach ($users as $user) {
                    $userUrls[] = route('user.profile', $user->id);
                }
            });
            
        return $userUrls;
    }
    
    /**
     * Собирает URL-адреса страниц поиска
     */
    private function collectSearchPages()
    {
        $searchUrls = [];
        
        // Добавляем базовый URL поиска
        $searchUrls[] = route('search');
        
        // Добавляем популярные поисковые запросы
        $popularSearchTerms = [
            'завтрак', 'обед', 'ужин', 'десерт', 'салат', 'суп',
            'мясо', 'рыба', 'вегетарианский', 'быстрый завтрак',
            'праздничный стол', 'на скорую руку', 'низкокалорийный',
            'для детей', 'выпечка', 'пицца', 'паста'
        ];
        
        foreach ($popularSearchTerms as $term) {
            $searchUrls[] = route('search', ['query' => $term]);
        }
        
        // Собираем популярные запросы из базы данных (если есть такая таблица)
        if (Schema::hasTable('search_queries')) {
            DB::table('search_queries')
                ->select('query')
                ->orderBy('count', 'desc')
                ->limit(100)
                ->get()
                ->each(function($item) use (&$searchUrls) {
                    $searchUrls[] = route('search', ['query' => $item->query]);
                });
        }
        
        return $searchUrls;
    }
}
