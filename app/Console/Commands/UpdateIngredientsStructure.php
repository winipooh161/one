<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use App\Services\IngredientParser;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class UpdateIngredientsStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recipes:update-ingredients {--id= : Обновить ингредиенты для конкретного рецепта}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Обновляет структурированное представление ингредиентов для рецептов';

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
     *
     * @return int
     */
    public function handle()
    {
        $recipeId = $this->option('id');
        
        // Формируем запрос
        $query = Recipe::query();
        
        if ($recipeId) {
            $query->where('id', $recipeId);
            $this->info("Обновление ингредиентов для рецепта с ID: {$recipeId}");
        } else {
            $this->info("Обновление ингредиентов для всех рецептов");
        }
        
        $recipesCount = $query->count();
        
        if ($recipesCount === 0) {
            $this->info('Нет рецептов для обновления.');
            return 0;
        }
        
        $this->info("Найдено {$recipesCount} рецептов для обновления.");
        
        $bar = $this->output->createProgressBar($recipesCount);
        $bar->start();
        
        $updated = 0;
        $skipped = 0;
        $errors = 0;
        
        $query->chunk(100, function ($recipes) use (&$updated, &$skipped, &$errors, $bar) {
            foreach ($recipes as $recipe) {
                if (empty($recipe->ingredients)) {
                    $this->warn("Рецепт ID: {$recipe->id} не имеет ингредиентов, пропускаем");
                    $skipped++;
                    $bar->advance();
                    continue;
                }
                
                try {
                    // Парсим ингредиенты
                    $structuredIngredients = $this->ingredientParser->parseIngredients($recipe->ingredients);
                    
                    // Обновляем additional_data
                    $additionalData = [];
                    if ($recipe->additional_data) {
                        $additionalData = is_array($recipe->additional_data) ? 
                                          $recipe->additional_data : 
                                          json_decode($recipe->additional_data, true);
                    }
                    
                    $additionalData['structured_ingredients'] = $structuredIngredients;
                    
                    // Сохраняем обновленные данные
                    $recipe->additional_data = json_encode($additionalData, JSON_UNESCAPED_UNICODE);
                    $recipe->save();
                    
                    $updated++;
                } catch (\Exception $e) {
                    $errors++;
                    Log::error("Ошибка при обновлении ингредиентов рецепта #{$recipe->id}: {$e->getMessage()}");
                }
                
                $bar->advance();
            }
        });
        
        $bar->finish();
        $this->newLine(2);
        
        $this->info("Обновление завершено:");
        $this->line("- Обновлено: {$updated}");
        $this->line("- Пропущено: {$skipped}");
        $this->line("- Ошибок: {$errors}");
        
        return 0;
    }
}
