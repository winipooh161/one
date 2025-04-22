<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Recipe;

class ReindexRecipes extends Command
{
    protected $signature = 'recipes:reindex';
    protected $description = 'Reindex all recipes to update SEO data';

    public function handle()
    {
        $this->info('Starting recipe reindexing...');
        
        // Получаем все опубликованные рецепты
        $recipes = Recipe::where('is_published', true)->get();
        $count = $recipes->count();
        
        $this->info("Found $count published recipes to reindex");
        
        $bar = $this->output->createProgressBar($count);
        $bar->start();
        
        foreach ($recipes as $recipe) {
            // Обновляем структурированные ингредиенты
            $recipe->updateStructuredIngredients();
            
            // Обновляем JSON данные
            $additionalData = $recipe->additional_data ? 
                (is_array($recipe->additional_data) ? $recipe->additional_data : json_decode($recipe->additional_data, true)) : [];
            
            // Добавляем или обновляем время последней индексации
            $additionalData['last_indexed'] = now()->toIso8601String();
            
            // Добавляем SEO информацию
            $seoData = [
                'meta_keywords' => $this->generateKeywords($recipe),
                'meta_description' => $this->generateDescription($recipe),
                'focus_keywords' => $this->extractFocusKeywords($recipe),
            ];
            
            $additionalData['seo'] = $seoData;
            
            // Сохраняем обновления
            $recipe->additional_data = json_encode($additionalData, JSON_UNESCAPED_UNICODE);
            $recipe->save();
            
            $bar->advance();
        }
        
        $bar->finish();
        $this->newLine();
        $this->info("Reindexing completed for $count recipes!");
        
        return 0;
    }
    
    /**
     * Генерирует ключевые слова для рецепта
     */
    private function generateKeywords($recipe)
    {
        $keywords = [];
        
        // Добавляем название рецепта
        $keywords[] = $recipe->title;
        
        // Добавляем категории
        foreach ($recipe->categories as $category) {
            $keywords[] = $category->name;
        }
        
        // Добавляем основные ингредиенты (первые 5)
        $ingredients = explode("\n", $recipe->ingredients);
        $mainIngredients = array_slice($ingredients, 0, 5);
        foreach ($mainIngredients as $ingredient) {
            // Извлекаем только название ингредиента (без количества)
            preg_match('/(?:\d+\s*\w+\s*)?(.+)/i', $ingredient, $matches);
            if (isset($matches[1])) {
                $keywords[] = trim($matches[1]);
            }
        }
        
        // Добавляем общие ключевые слова
        $keywords[] = 'рецепт';
        $keywords[] = 'приготовление';
        
        // Если есть время приготовления, добавляем соответствующее ключевое слово
        if ($recipe->cooking_time) {
            if ($recipe->cooking_time <= 30) {
                $keywords[] = 'быстрый рецепт';
            }
        }
        
        // Убираем дубликаты и объединяем
        return implode(', ', array_unique($keywords));
    }
    
    /**
     * Генерирует мета-описание для рецепта
     */
    private function generateDescription($recipe)
    {
        if (!empty($recipe->description) && strlen($recipe->description) > 50) {
            return \Illuminate\Support\Str::limit($recipe->description, 160, '');
        }
        
        $ingredients = explode("\n", $recipe->ingredients);
        $ingredientsSample = array_slice($ingredients, 0, 3);
        $ingredientsCount = count($ingredients);
        
        $description = "Рецепт {$recipe->title}. ";
        
        if ($recipe->cooking_time) {
            $description .= "Время приготовления: {$recipe->cooking_time} мин. ";
        }
        
        $description .= "Используется $ingredientsCount ингредиентов";
        
        if (!empty($ingredientsSample)) {
            $description .= ", включая " . implode(', ', $ingredientsSample) . ". ";
        } else {
            $description .= ". ";
        }
        
        if ($recipe->calories) {
            $description .= "Калорийность: {$recipe->calories} ккал.";
        }
        
        return \Illuminate\Support\Str::limit($description, 160, '');
    }
    
    /**
     * Извлекает основные ключевые слова для фокусировки
     */
    private function extractFocusKeywords($recipe)
    {
        $words = explode(' ', strtolower($recipe->title));
        $ingredients = explode("\n", $recipe->ingredients);
        
        $focusKeywords = [];
        
        // Ищем значимые слова из названия (исключаем предлоги, местоимения и т.д.)
        foreach ($words as $word) {
            if (strlen($word) > 3 && !in_array($word, ['для', 'как', 'что', 'это', 'все', 'рецепт'])) {
                $focusKeywords[] = $word;
            }
        }
        
        // Добавляем основной ингредиент, если можем его определить
        if (!empty($ingredients)) {
            $mainIngredient = trim($ingredients[0]);
            preg_match('/(?:\d+\s*\w+\s*)?(.+)/i', $mainIngredient, $matches);
            if (isset($matches[1])) {
                $focusKeywords[] = trim($matches[1]);
            }
        }
        
        // Берем первую категорию
        if ($recipe->categories->isNotEmpty()) {
            $focusKeywords[] = $recipe->categories->first()->name;
        }
        
        // Ограничиваем до 3-5 фокусных ключевых слов
        return array_slice(array_unique($focusKeywords), 0, 5);
    }
}
