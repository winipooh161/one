<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\IngredientParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class ConvertIngredientsToJson extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:convert-ingredients {--id= : Convert ingredients for a specific recipe}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Конвертирует ингредиенты рецептов в формат JSON 2.0';

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
        $specificId = $this->option('id');
        
        $query = Recipe::query();
        
        if ($specificId) {
            $query->where('id', $specificId);
            $this->info("Конвертация ингредиентов для рецепта с ID: {$specificId}");
        } else {
            $this->info("Конвертация ингредиентов для всех рецептов");
        }
        
        $recipesCount = $query->count();
        
        if ($recipesCount === 0) {
            $this->info('Нет рецептов для конвертации.');
            return 0;
        }
        
        $this->info("Найдено {$recipesCount} рецептов для конвертации.");
        
        $bar = $this->output->createProgressBar($recipesCount);
        $bar->start();
        
        $convertedCount = 0;
        $skippedCount = 0;
        $errorCount = 0;
        
        $query->chunk(100, function ($recipes) use (&$convertedCount, &$skippedCount, &$errorCount, $bar) {
            foreach ($recipes as $recipe) {
                if (empty($recipe->ingredients)) {
                    $this->warn("Рецепт ID: {$recipe->id} не имеет ингредиентов, пропускаем");
                    $skippedCount++;
                    $bar->advance();
                    continue;
                }
                
                try {
                    // Проверяем наличие JSON в новом формате
                    $hasJson = false;
                    
                    if ($recipe->additional_data) {
                        $additionalData = is_array($recipe->additional_data) ? 
                                         $recipe->additional_data : 
                                         json_decode($recipe->additional_data, true);
                                         
                        if (isset($additionalData['ingredients_json'])) {
                            $this->line("\nРецепт ID: {$recipe->id} уже имеет ingredients_json, пропускаем");
                            $skippedCount++;
                            $bar->advance();
                            continue;
                        }
                    }
                    
                    // Генерируем JSON в новом формате
                    $structuredData = $this->ingredientParser->parseToStructuredData($recipe->ingredients);
                    
                    // Получаем/создаем additional_data
                    $additionalData = [];
                    if ($recipe->additional_data) {
                        $additionalData = is_array($recipe->additional_data) ? 
                                         $recipe->additional_data : 
                                         json_decode($recipe->additional_data, true) ?? [];
                    }
                    
                    // Обновляем данные
                    $additionalData['structured_ingredients'] = $structuredData['ingredients'];
                    $additionalData['ingredients_json'] = json_encode($structuredData, JSON_UNESCAPED_UNICODE);
                    
                    // Сохраняем
                    $recipe->additional_data = json_encode($additionalData, JSON_UNESCAPED_UNICODE);
                    $recipe->save();
                    
                    $convertedCount++;
                    $this->line("\nРецепт ID: {$recipe->id} успешно конвертирован");
                } catch (\Exception $e) {
                    $errorCount++;
                    $this->error("\nОшибка при конвертации рецепта ID {$recipe->id}: {$e->getMessage()}");
                    Log::error("Ошибка при конвертации ингредиентов рецепта #{$recipe->id}: {$e->getMessage()}");
                }
                
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine();
        
        $this->info("Конвертация завершена:");
        $this->line("- Конвертировано: {$convertedCount}");
        $this->line("- Пропущено: {$skippedCount}");
        $this->line("- Ошибок: {$errorCount}");
        
        return 0;
    }
}
