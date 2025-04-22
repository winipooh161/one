<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\IngredientParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class RepairIngredients extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:repair-ingredients {--all : Process all recipes, even if they already have structured ingredients} {--id= : Process a specific recipe by ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Парсит и структурирует ингредиенты для рецептов';

    /**
     * @var IngredientParser
     */
    protected $ingredientParser;

    /**
     * Create a new command instance.
     *
     * @param IngredientParser $ingredientParser
     */
    public function __construct(IngredientParser $ingredientParser)
    {
        parent::__construct();
        $this->ingredientParser = $ingredientParser;
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $processAll = $this->option('all');
        $specificId = $this->option('id');
        
        Log::info('Запуск команды recipes:repair-ingredients', [
            'process_all' => $processAll,
            'specific_id' => $specificId
        ]);
        
        $query = Recipe::query();
        
        if ($specificId) {
            $query->where('id', $specificId);
            Log::info("Обработка конкретного рецепта с ID: {$specificId}");
        } elseif (!$processAll) {
            Log::info("Обработка только рецептов без структурированных ингредиентов");
            $query->whereNull('additional_data')
                ->orWhere(function($q) {
                    $q->whereNotNull('additional_data')
                      ->whereRaw("JSON_EXTRACT(additional_data, '$.structured_ingredients') IS NULL");
                });
        } else {
            Log::info("Обработка всех рецептов, включая те, у которых уже есть структурированные ингредиенты");
        }
        
        $recipesCount = $query->count();
        
        if ($recipesCount === 0) {
            Log::info('Нет рецептов для обработки.');
            $this->info('Нет рецептов для обработки.');
            return 0;
        }
        
        Log::info("Найдено {$recipesCount} рецептов для обработки.");
        $this->info("Найдено {$recipesCount} рецептов для обработки.");
        
        $bar = $this->output->createProgressBar($recipesCount);
        $bar->start();
        
        $processedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        $query->chunk(100, function ($recipes) use (&$processedCount, &$skippedCount, &$errorCount, $bar, $processAll) {
            foreach ($recipes as $recipe) {
                Log::info("Обработка рецепта ID: {$recipe->id}, название: {$recipe->title}");
                
                if (!$processAll && $this->hasStructuredIngredients($recipe)) {
                    Log::info("Рецепт ID: {$recipe->id} пропущен, так как уже имеет структурированные ингредиенты");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }
                
                try {
                    $this->processRecipe($recipe);
                    $processedCount++;
                    Log::info("Рецепт ID: {$recipe->id} успешно обработан");
                } catch (\Exception $e) {
                    $errorCount++;
                    $errorMessage = "Ошибка при обработке рецепта #{$recipe->id}: {$e->getMessage()}";
                    Log::error($errorMessage);
                    $this->error("\n" . $errorMessage);
                }
                
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        
        $summary = "Обработка завершена: Обработано: {$processedCount}, Пропущено: {$skippedCount}, Ошибок: {$errorCount}";
        Log::info($summary);
        
        $this->info("Обработка завершена:");
        $this->line("- Обработано: {$processedCount}");
        $this->line("- Пропущено: {$skippedCount}");
        $this->line("- Ошибок: {$errorCount}");
        
        return 0;
    }
    
    /**
     * Проверяет наличие структурированных ингредиентов
     */
    private function hasStructuredIngredients(Recipe $recipe): bool
    {
        if (!$recipe->additional_data) {
            Log::info("Рецепт ID: {$recipe->id} не имеет additional_data");
            return false;
        }
        
        // Декодируем JSON данные
        $additionalData = json_decode($recipe->additional_data, true);
        
        // Проверяем наличие и непустоту структурированных ингредиентов
        $hasIngredients = isset($additionalData['structured_ingredients']) && 
                           is_array($additionalData['structured_ingredients']) && 
                           count($additionalData['structured_ingredients']) > 0;
                           
        if ($hasIngredients) {
            Log::info("Рецепт ID: {$recipe->id} уже имеет " . count($additionalData['structured_ingredients']) . " структурированных ингредиентов");
        } else {
            Log::info("Рецепт ID: {$recipe->id} не имеет структурированных ингредиентов");
        }
        
        return $hasIngredients;
    }
    
    /**
     * Обрабатывает рецепт - структурирует ингредиенты
     */
    private function processRecipe(Recipe $recipe): void
    {
        if (empty($recipe->ingredients)) {
            Log::warning("Рецепт ID: {$recipe->id} имеет пустое поле ingredients, пропускаем");
            return;
        }
        
        // Парсим ингредиенты
        $ingredientsLines = explode("\n", $recipe->ingredients);
        Log::info("Рецепт ID: {$recipe->id} содержит " . count($ingredientsLines) . " строк ингредиентов");
        
        $structuredIngredients = $this->ingredientParser->parseIngredients($recipe->ingredients);
        Log::info("Рецепт ID: {$recipe->id} - успешно распарсено " . count($structuredIngredients) . " ингредиентов");
        
        // Обновляем additional_data
        $additionalData = [];
        if ($recipe->additional_data) {
            $additionalData = json_decode($recipe->additional_data, true) ?: [];
            Log::info("Рецепт ID: {$recipe->id} - обновляем существующие additional_data");
        } else {
            Log::info("Рецепт ID: {$recipe->id} - создаем новые additional_data");
        }
        
        $additionalData['structured_ingredients'] = $structuredIngredients;
        
        // Сохраняем обновленные данные
        $recipe->additional_data = json_encode($additionalData);
        $recipe->save();
        
        Log::info("Рецепт ID: {$recipe->id} - структурированные ингредиенты успешно сохранены");
    }
}
