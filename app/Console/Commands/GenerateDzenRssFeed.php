<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class GenerateDzenRssFeed extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'generate:dzen-rss {--limit=30 : Максимальное количество рецептов в ленте} {--days=3 : За какой период брать рецепты (в днях)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерирует RSS-ленту для Яндекс.Дзен';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $limit = $this->option('limit');
        $days = $this->option('days');
        
        $this->info("Начинаем генерацию RSS для Яндекс.Дзен (максимум {$limit} рецептов за последние {$days} дней)");

        try {
            // Проверяем наличие колонок в таблице recipes
            $hasStatusColumn = Schema::hasColumn('recipes', 'status');
            $hasIsActiveColumn = Schema::hasColumn('recipes', 'is_active');
            
            // Получаем рецепты из базы данных
            $query = Recipe::query()
                ->where('created_at', '>=', now()->subDays($days));
            
            // Добавляем условие публикации в зависимости от имеющихся колонок
            if ($hasStatusColumn) {
                $query->where('status', 'published'); // Предполагаем, что 'published' - одно из возможных значений статуса
            } elseif ($hasIsActiveColumn) {
                $query->where('is_active', true);
            }
            
            // Получаем финальный набор рецептов
            $recipes = $query->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();

            $this->info("Получено {$recipes->count()} рецептов");

            if ($recipes->count() < 10) {
                $this->warn("Внимание: в ленте менее 10 рецептов. По требованиям Дзена необходимо минимум 10 материалов.");
                
                // Дополняем более старыми рецептами, если не хватает
                if ($recipes->count() < 10) {
                    $additionalQuery = Recipe::query()
                        ->where('created_at', '<', now()->subDays($days));
                    
                    // Добавляем условие публикации в зависимости от имеющихся колонок
                    if ($hasStatusColumn) {
                        $additionalQuery->where('status', 'published');
                    } elseif ($hasIsActiveColumn) {
                        $additionalQuery->where('is_active', true);
                    }
                    
                    $additionalRecipes = $additionalQuery
                        ->orderBy('created_at', 'desc')
                        ->limit(10 - $recipes->count())
                        ->get();
                    
                    $recipes = $recipes->merge($additionalRecipes);
                    $this->info("Добавлено {$additionalRecipes->count()} более старых рецептов. Всего: {$recipes->count()}");
                }
            }

            // Генерация XML
            $xml = $this->generateRssXml($recipes);

            // Сохранение XML в файл
            $filePath = public_path('dzen-rss.xml');
            file_put_contents($filePath, $xml);

            $this->info("RSS-лента для Яндекс.Дзен успешно сгенерирована и сохранена в: {$filePath}");
            $this->info("URL для добавления в Дзен: " . config('app.url') . "/dzen-rss.xml");
        } catch (\Exception $e) {
            $this->error("Ошибка при генерации RSS: " . $e->getMessage());
            Log::error("Ошибка генерации RSS для Дзена: " . $e->getMessage(), [
                'exception' => $e,
            ]);
        }
    }

    /**
     * Генерация XML
     */
    private function generateRssXml($recipes)
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:dc="http://purl.org/dc/elements/1.1/" xmlns:media="http://search.yahoo.com/mrss/" xmlns:atom="http://www.w3.org/2005/Atom" xmlns:georss="http://www.georss.org/georss">';
        $xml .= '<channel>';
        $xml .= '<title>Яедок - Рецепты и кулинария</title>';
        $xml .= '<link>' . config('app.url') . '</link>';
        $xml .= '<language>ru</language>';

        foreach ($recipes as $recipe) {
            $xml .= $this->generateItemXml($recipe);
        }

        $xml .= '</channel>';
        $xml .= '</rss>';

        return $xml;
    }

    /**
     * Генерация элемента item для каждого рецепта
     */
    private function generateItemXml($recipe)
    {
        $recipeUrl = config('app.url') . '/recipes/' . $recipe->slug;
        $imageUrl = config('app.url') . '/images/recipes/recipe_' . $recipe->id . '_1.jpg';

        $item = '<item>';
        $item .= '<title>' . htmlspecialchars($recipe->title) . '</title>';
        $item .= '<link>' . $recipeUrl . '</link>';
        $item .= '<pdalink>' . $recipeUrl . '</pdalink>';
        $item .= '<guid>' . md5($recipe->id . $recipe->slug) . '</guid>';
        $item .= '<pubDate>' . $recipe->created_at->format('D, d M Y H:i:s O') . '</pubDate>';
        $item .= '<media:rating scheme="urn:simple">nonadult</media:rating>';
        
        // Способ публикации и тип контента
        $item .= '<category>format-article</category>'; // Формат статьи
        $item .= '<category>index</category>'; // Индексация
        $item .= '<category>comment-all</category>'; // Комментирование
        
        // Обложка для статьи
        $item .= '<enclosure url="' . $imageUrl . '" type="image/jpeg"/>';
        
        // Краткое описание
        $description = $recipe->description ?? mb_substr(strip_tags($recipe->instructions), 0, 200) . '...';
        $item .= '<description>' . htmlspecialchars($description) . '</description>';
        
        // Содержимое статьи
        $item .= '<content:encoded><![CDATA[';
        $item .= '<h1>' . htmlspecialchars($recipe->title) . '</h1>';
        $item .= '<figure><img src="' . $imageUrl . '"><figcaption>' . htmlspecialchars($recipe->title) . '</figcaption></figure>';
        
        // Описание блюда, если есть
        if (!empty($recipe->description)) {
            $item .= '<p>' . nl2br(htmlspecialchars($recipe->description)) . '</p>';
        }
        
        // Ингредиенты
        if (!empty($recipe->ingredients)) {
            $item .= '<h2 id="ingredients">Ингредиенты</h2>';
            $item .= '<ul>';
            
            // Проверяем тип ingredients и обрабатываем соответственно
            $ingredients = $recipe->ingredients;
            
            // Если ingredients уже массив, используем его напрямую
            if (is_array($ingredients)) {
                // Массив ингредиентов уже готов к использованию
            } 
            // Если ingredients - строка, пытаемся декодировать JSON
            elseif (is_string($ingredients)) {
                $decodedIngredients = json_decode($ingredients, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    $ingredients = $decodedIngredients;
                }
            }
            
            // Обработка массива ингредиентов
            if (is_array($ingredients)) {
                foreach ($ingredients as $ingredient) {
                    if (is_array($ingredient)) {
                        $name = $ingredient['name'] ?? '';
                        $amount = $ingredient['amount'] ?? '';
                        
                        if (!empty($name)) {
                            $item .= '<li>' . htmlspecialchars($name);
                            if (!empty($amount)) {
                                $item .= ' - ' . htmlspecialchars($amount);
                            }
                            $item .= '</li>';
                        }
                    } else {
                        $item .= '<li>' . htmlspecialchars($ingredient) . '</li>';
                    }
                }
            } else {
                // Если не удалось обработать как массив, разбиваем текст по строкам
                $ingredientLines = explode("\n", (string)$recipe->ingredients);
                foreach ($ingredientLines as $line) {
                    if (!empty(trim($line))) {
                        $item .= '<li>' . htmlspecialchars(trim($line)) . '</li>';
                    }
                }
            }
            $item .= '</ul>';
        }
        
        // Инструкции по приготовлению
        if (!empty($recipe->instructions)) {
            $item .= '<h2 id="cooking">Приготовление</h2>';
            
            // Обработка текста инструкций - разбиваем на параграфы
            $paragraphs = preg_split('/\r\n|\r|\n/', $recipe->instructions);
            foreach ($paragraphs as $paragraph) {
                if (!empty(trim($paragraph))) {
                    $item .= '<p>' . htmlspecialchars(trim($paragraph)) . '</p>';
                }
            }
        }
        
        // Дополнительная информация
        $item .= '<p>Время приготовления: ' . ($recipe->cooking_time ?? 'Не указано') . '</p>';
        
        // Ссылка на оригинал
        $item .= '<p>Оригинал рецепта на сайте <a href="' . $recipeUrl . '">Яедок</a></p>';
        
        $item .= ']]></content:encoded>';
        $item .= '</item>';

        return $item;
    }
}
