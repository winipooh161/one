<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Services\YmlGenerator;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use App\Models\Recipe;
use App\Models\Category;

class GenerateYmlFeedCommand extends Command
{
    /**
     * Имя и сигнатура консольной команды.
     *
     * @var string
     */
    protected $signature = 'feed:generate-yml {--save : Сохраняет фид в файл}';

    /**
     * Описание консольной команды.
     *
     * @var string
     */
    protected $description = 'Генерирует YML-фид для Яндекса';

    /**
     * Выполнение консольной команды.
     */
    public function handle()
    {
        $this->info('Начинаем генерацию YML-фида для Яндекса...');

        try {
            // Очищаем кэш фида
            Cache::forget('yandex_yml_feed');
            $this->info('Кэш YML-фида очищен');

            // Используем YmlGenerator вместо шаблона Blade
            $generator = new YmlGenerator();
            $xml = $generator->generate();
            
            // Выводим информацию о количестве данных после генерации
            // (ранее эта информация была доступна непосредственно, теперь берем из сервиса)
            $recipeCount = Recipe::where('is_published', true)->count();
            $categoryCount = Category::withCount('recipes')
                              ->having('recipes_count', '>', 0)
                              ->count();
            $this->info("Получено {$recipeCount} рецептов и {$categoryCount} категорий");

            // Опционально сохраняем в файл
            if ($this->option('save')) {
                $filePath = public_path('feeds/yandex-yml.xml');
                
                // Создаем директорию, если она не существует
                $directory = dirname($filePath);
                if (!File::exists($directory)) {
                    File::makeDirectory($directory, 0755, true);
                }
                
                // Сохраняем файл
                File::put($filePath, $xml);
                $this->info("YML-фид сохранен в файл: {$filePath}");
            }

            $this->info('Генерация YML-фида успешно завершена!');
            return 0;
        } catch (\Exception $e) {
            $this->error("Ошибка при генерации YML-фида: {$e->getMessage()}");
            Log::error("Ошибка при генерации YML-фида: {$e->getMessage()}", ['exception' => $e]);
            return 1;
        }
    }
}
