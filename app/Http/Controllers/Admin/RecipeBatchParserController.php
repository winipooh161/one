<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Symfony\Component\DomCrawler\Crawler;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;

class RecipeBatchParserController extends Controller
{
    protected $client;
    protected $parserController;
    
    protected $defaultOptions = [
        'timeout' => 30,
        'connect_timeout' => 30,
        'headers' => [
            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36',
            'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
            'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
        ]
    ];

    public function __construct(RecipeParserController $parserController)
    {
        $this->client = new Client($this->defaultOptions);
        $this->parserController = $parserController;
    }

    /**
     * Показывает форму для пакетного парсинга
     */
    public function batchIndex()
    {
        $directory = public_path('recipe_links');
        $files = [];
        
        if (is_dir($directory)) {
            $files = array_diff(scandir($directory), ['.', '..']);
        }
        
        return view('admin.parser.batch', compact('files'));
    }

    /**
     * Начинает пакетный парсинг
     */
    public function batchParse(Request $request)
    {
        $request->validate([
            'links_file' => 'required',
            'max_recipes' => 'required|integer|min:1|max:1000',
            'delay' => 'required|integer|min:1|max:60',
            'batch_size' => 'required|integer|min:1|max:100', // Новое поле для размера пакета
            'batch_interval' => 'required|integer|min:1|max:60', // Новое поле для интервала в минутах
        ]);

        $linksFile = $request->input('links_file');
        $maxRecipes = $request->input('max_recipes', 10);
        $delay = $request->input('delay', 3);
        $batchSize = $request->input('batch_size', 15);
        $batchInterval = $request->input('batch_interval', 1);

        $filePath = public_path('recipe_links/' . $linksFile);

        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Файл со ссылками не найден');
        }

        $links = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $links = array_slice($links, 0, $maxRecipes);

        // Сохраняем параметры пакетной обработки в сессии
        session([
            'batch_links' => $links,
            'batch_delay' => $delay,
            'batch_total' => count($links),
            'batch_processed' => 0,
            'batch_success' => 0,
            'batch_errors' => 0,
            'batch_size' => $batchSize, // Сохраняем размер пакета
            'batch_interval' => $batchInterval, // Сохраняем интервал
        ]);

        return redirect()->route('admin.parser.processBatch');
    }

    /**
     * Обрабатывает пакетный парсинг
     */
    public function processBatch()
    {
        $links = session('batch_links', []);
        $delay = session('batch_delay', 1);
        $processed = session('batch_processed', 0);
        $success = session('batch_success', 0);
        $errors = session('batch_errors', 0);
        $total = session('batch_total', 0);
        $batchSize = session('batch_size', 15);
        $batchInterval = session('batch_interval', 1);

        if (empty($links) || $processed >= count($links)) {
            return view('admin.parser.batch_result', [
                'total' => $total,
                'processed' => $processed,
                'success' => $success,
                'errors' => $errors,
            ]);
        }

        $batchLinks = array_slice($links, $processed, $batchSize);

        foreach ($batchLinks as $url) {
            try {
                // Логика обработки конкретной ссылки
                // Здесь должен быть код для парсинга и сохранения рецепта

                session([
                    'batch_processed' => session('batch_processed') + 1,
                    'batch_success' => session('batch_success') + 1,
                ]);
            } catch (\Exception $e) {
                \Log::error('Ошибка обработки URL: ' . $url . ' - ' . $e->getMessage());

                session([
                    'batch_processed' => session('batch_processed') + 1,
                    'batch_errors' => session('batch_errors') + 1,
                ]);
            }

            sleep($delay);
        }

        // Задержка между пакетами
        sleep($batchInterval * 60);

        return redirect()->route('admin.parser.processBatch');
    }

    /**
     * Вспомогательный метод для сохранения рецепта
     */
    protected function storeRecipe($recipeData)
    {
        // Делегируем сохранение основному контроллеру парсера
        return $this->parserController->storeRecipe($recipeData);
    }
}
