<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use GuzzleHttp\Exception\ConnectException;

class RecipeLinkCollectorController extends Controller
{
    protected $baseUrl = 'https://eda.ru';
    protected $linksFilePath;
    protected $statusFilePath;
    protected $collectionActive = false;
    protected $maxAttemptsPerURL = 3;
    protected $delayBetweenRequests = 1; // секунды
    protected $maxTimePerBatch = 25; // секунды

    public function __construct()
    {
        $this->linksFilePath = public_path('recipe_links/collected_links.txt');
        $this->statusFilePath = storage_path('app/link_collection_status.json');

        // Создаем директорию для хранения ссылок, если она не существует
        if (!file_exists(dirname($this->linksFilePath))) {
            mkdir(dirname($this->linksFilePath), 0755, true);
        }

        // Инициализируем статус сбора, если файл не существует
        if (!file_exists($this->statusFilePath)) {
            $this->updateCollectionStatus([
                'active'            => false,
                'total_collected'   => 0,
                'current_url'       => '',
                'start_time'        => null,
                'last_activity'     => null,
                'urls_queue'        => [],
                'scroll_offset'     => 0, // для infinite scroll
                'errors'            => 0,
                'duplicate_skipped' => 0
            ]);
        }
    }

    public function collectLinksForm()
    {
        $status = $this->getCollectionStatusData();
        $totalLinks = $this->countTotalLinks();
        return view('admin.parser.collect_links', compact('status', 'totalLinks'));
    }

    public function collectLinks(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'max_pages' => 'nullable|integer|min:1',
        ]);

        $url = $request->input('url');
        $maxPages = $request->input('max_pages', 5);

        try {
            $this->initializeLinksFile();
            $collected = $this->processUrl($url, $maxPages);
            return redirect()->route('admin.parser.collect_links')
                ->with('success', "Успешно собрано {$collected} ссылок на рецепты.");
        } catch (\Exception $e) {
            Log::error('Ошибка при сборе ссылок: ' . $e->getMessage());
            return redirect()->route('admin.parser.collect_links')
                ->with('error', 'Произошла ошибка при сборе ссылок: ' . $e->getMessage());
        }
    }

    /**
     * Запуск непрерывного сбора ссылок с поддержкой infinite scroll
     */
    public function startContinuousCollection(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'strategy' => 'required|in:breadth,depth,combined',
        ]);

        $url = $request->input('url');
        $strategy = $request->input('strategy', 'combined');

        $initialUrls = [$url];
        if ($request->has('additional_urls')) {
            $additionalUrls = array_filter(explode("\n", $request->input('additional_urls')));
            $initialUrls = array_merge($initialUrls, $additionalUrls);
        }

        $this->updateCollectionStatus([
            'active'            => true,
            'total_collected'   => $this->countTotalLinks(),
            'current_url'       => $url,
            'start_time'        => now()->timestamp,
            'last_activity'     => now()->timestamp,
            'urls_queue'        => $initialUrls,
            'scroll_offset'     => 0, // инициализируем смещение для infinite scroll
            'strategy'          => $strategy,
            'errors'            => 0,
            'duplicate_skipped' => 0
        ]);

        // Запускаем первый пакет обработки классического обхода
        $this->processBatch();

        return response()->json([
            'success' => true,
            'message' => 'Непрерывный сбор ссылок запущен',
            'status'  => $this->getCollectionStatusData()
        ]);
    }

    /**
     * Обработка пакета URL в режиме непрерывного сбора (классический обход)
     */
    public function processBatch()
    {
        $status = $this->getCollectionStatusData();

        if (!$status['active']) {
            return response()->json([
                'success' => false,
                'message' => 'Сбор ссылок не активен'
            ]);
        }

        $startTime = microtime(true);
        $linksCollected = 0;
        $processedUrls = [];

        try {
            $client = new Client([
                'timeout' => 30,
                'verify'  => false,
                'headers' => [
                    'User-Agent'      => 'Mozilla/5.0',
                    'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                ]
            ]);

            while (!empty($status['urls_queue']) && (microtime(true) - $startTime) < $this->maxTimePerBatch) {
                $currentUrl = array_shift($status['urls_queue']);
                $status['current_url'] = $currentUrl;

                Log::info("Обработка URL: {$currentUrl}");

                try {
                    $response = $client->get($currentUrl);
                    $html = $response->getBody()->getContents();

                    // Извлечение рецептов и категорий
                    $recipeLinks = $this->extractRecipeLinks($html, $currentUrl);
                    $categoryLinks = $this->extractCategoryLinks($html, $currentUrl);

                    foreach ($recipeLinks as $link) {
                        if (!$this->linkExistsInFile($link)) {
                            $this->saveLinkToFile($link);
                            $linksCollected++;
                            $status['total_collected']++;
                        } else {
                            $status['duplicate_skipped']++;
                        }
                    }

                    if (!empty($categoryLinks)) {
                        if ($status['strategy'] === 'depth') {
                            $status['urls_queue'] = array_merge($categoryLinks, $status['urls_queue']);
                        } elseif ($status['strategy'] === 'breadth') {
                            $status['urls_queue'] = array_merge($status['urls_queue'], $categoryLinks);
                        } else {
                            if (count($status['urls_queue']) % 2 === 0) {
                                $status['urls_queue'] = array_merge($categoryLinks, $status['urls_queue']);
                            } else {
                                $status['urls_queue'] = array_merge($status['urls_queue'], $categoryLinks);
                            }
                        }
                        Log::info("Найдено новых категорий: " . count($categoryLinks));
                    }

                    // Пагинация для страниц-списков
                    if (strpos($currentUrl, '?page=') === false && $this->isListingPage($currentUrl)) {
                        $paginationLinks = $this->generatePaginationLinks($currentUrl, 1, 50);
                        $status['urls_queue'] = array_merge($status['urls_queue'], $paginationLinks);
                    }

                    // Если страница поддерживает AJAX-подгрузку
                    // Убираем генерацию scroll-ссылок здесь, чтобы не ограничивать сбор бесконечным скроллом.
                    // Бесконечный скролл обрабатывается отдельно в методе processInfiniteScrollBatch.

                    $processedUrls[] = $currentUrl;
                    usleep($this->delayBetweenRequests * 1000000);
                } catch (RequestException $e) {
                    $status['errors']++;
                    if ($e->hasResponse()) {
                        $errorCode = $e->getResponse()->getStatusCode();
                        Log::warning("Ошибка HTTP при обработке URL: {$currentUrl}. Код: {$errorCode}");
                    } else {
                        Log::warning("Исключение при обработке URL: {$currentUrl}. Ошибка: " . $e->getMessage());
                    }
                } catch (ConnectException $e) {
                    $status['errors']++;
                    Log::warning("Ошибка соединения при обработке URL: {$currentUrl}. Ошибка: " . $e->getMessage());
                    $status['urls_queue'][] = $currentUrl;
                    usleep($this->delayBetweenRequests * 3 * 1000000);
                }

                $status['last_activity'] = now()->timestamp;
                $this->updateCollectionStatus($status);
            }

            $status['last_activity'] = now()->timestamp;
            $this->updateCollectionStatus($status);

            return response()->json([
                'success' => true,
                'message' => "Обработано {$linksCollected} ссылок за этот пакет. Осталось в очереди: " . count($status['urls_queue']),
                'status'  => $this->getCollectionStatusData()
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при обработке пакета: ' . $e->getMessage());
            $status['errors']++;
            $status['last_activity'] = now()->timestamp;
            $this->updateCollectionStatus($status);

            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка при обработке пакета: ' . $e->getMessage(),
                'status'  => $this->getCollectionStatusData()
            ]);
        }
    }

    /**
     * Метод для обработки порции данных для бесконечного скролла
     * (с инкрементальным переходом по страницам)
     */
    public function processInfiniteScrollBatch(Request $request)
    {
        $status = $this->getCollectionStatusData();

        if (!$status['active']) {
            return response()->json([
                'success' => false,
                'message' => 'Сбор ссылок не активен'
            ]);
        }

        // Если у нас нет текущей страницы или это первый запуск
        if (!isset($status['current_page'])) {
            $status['current_page'] = 1;
        }

        // Увеличиваем номер страницы
        $nextPage = $status['current_page'] + 1;
        $status['current_page'] = $nextPage;

        // Формируем базовый URL
        $baseUrl = $request->input('base_url', $this->baseUrl . '/recepty');
        
        // Добавляем параметр page
        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
        $url = $baseUrl . $separator . 'page=' . $nextPage;
        
        $status['current_url'] = $url;

        Log::info("Infinite scroll: обработка страницы {$nextPage}, URL: {$url}");

        try {
            $client = new Client([
                'timeout' => 30,
                'verify'  => false,
                'headers' => [
                    'User-Agent'      => 'Mozilla/5.0',
                    'Accept'          => 'application/json, text/html, */*;q=0.8',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                ]
            ]);

            $response = $client->get($url);
            $html = $response->getBody()->getContents();

            // Извлекаем ссылки на рецепты из HTML-ответа
            $recipeLinks = $this->extractRecipeLinks($html, $url);
            $linksCollected = 0;
            foreach ($recipeLinks as $link) {
                if (!$this->linkExistsInFile($link)) {
                    $this->saveLinkToFile($link);
                    $linksCollected++;
                    $status['total_collected']++;
                } else {
                    $status['duplicate_skipped']++;
                }
            }

            $status['last_activity'] = now()->timestamp;
            $this->updateCollectionStatus($status);

            return response()->json([
                'success' => true,
                'message' => "Infinite scroll: обработано {$linksCollected} ссылок. Загружена страница: " . $nextPage,
                'status'  => $this->getCollectionStatusData(),
                'page'    => $nextPage
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при обработке infinite scroll: ' . $e->getMessage());
            $status['errors']++;
            $status['last_activity'] = now()->timestamp;
            $this->updateCollectionStatus($status);
            return response()->json([
                'success' => false,
                'message' => 'Ошибка при обработке infinite scroll: ' . $e->getMessage(),
                'status'  => $this->getCollectionStatusData()
            ]);
        }
    }
    
    public function getCollectionStatus()
    {
        $status = $this->getCollectionStatusData();

        // Если прошло более 5 минут с последней активности, считаем сбор прерванным
        if ($status['active'] && $status['last_activity']) {
            $lastActivity = $status['last_activity'];
            $currentTime = now()->timestamp;
            if ($currentTime - $lastActivity > 300) {
                $status['active'] = false;
                $this->updateCollectionStatus($status);
            }
        }

        return response()->json([
            'success' => true,
            'status'  => $status
        ]);
    }

    public function stopCollection()
    {
        $status = $this->getCollectionStatusData();
        $status['active'] = false;
        $this->updateCollectionStatus($status);

        return response()->json([
            'success' => true,
            'message' => 'Сбор ссылок остановлен',
            'status'  => $this->getCollectionStatusData()
        ]);
    }

    public function clearLinksFile()
    {
        if (file_exists($this->linksFilePath)) {
            file_put_contents($this->linksFilePath, '');
        }

        $status = $this->getCollectionStatusData();
        $status['total_collected'] = 0;
        $this->updateCollectionStatus($status);

        return redirect()->route('admin.parser.collect_links')
            ->with('success', 'Файл ссылок успешно очищен.');
    }

    public function removeDuplicateLinks()
    {
        if (!file_exists($this->linksFilePath)) {
            return redirect()->route('admin.parser.collect_links')
                ->with('error', 'Файл ссылок не найден.');
        }

        $links = file($this->linksFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $uniqueLinks = array_unique($links);
        $removedCount = count($links) - count($uniqueLinks);
        file_put_contents($this->linksFilePath, implode("\n", $uniqueLinks));

        $status = $this->getCollectionStatusData();
        $status['total_collected'] = count($uniqueLinks);
        $this->updateCollectionStatus($status);

        return redirect()->route('admin.parser.collect_links')
            ->with('success', "Удалено {$removedCount} дубликатов ссылок.");
    }

    protected function processUrl($url, $maxPages = 5)
    {
        $client = new Client([
            'timeout' => 30,
            'verify'  => false,
            'headers' => [
                'User-Agent'      => 'Mozilla/5.0',
                'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
            ]
        ]);

        $totalCollected = 0;
        $processedUrls = [];
        $urlsQueue = [$url];

        while (!empty($urlsQueue) && count($processedUrls) < $maxPages) {
            $currentUrl = array_shift($urlsQueue);
            if (in_array($currentUrl, $processedUrls)) {
                continue;
            }

            Log::info("Обработка URL: {$currentUrl}");

            try {
                $response = $client->get($currentUrl);
                $html = $response->getBody()->getContents();

                $recipeLinks = $this->extractRecipeLinks($html, $currentUrl);
                foreach ($recipeLinks as $link) {
                    if (!$this->linkExistsInFile($link)) {
                        $this->saveLinkToFile($link);
                        $totalCollected++;
                    }
                }

                if ($this->isListingPage($currentUrl)) {
                    $nextPageUrl = $this->getNextPageUrl($currentUrl, $html);
                    if ($nextPageUrl && !in_array($nextPageUrl, $processedUrls) && !in_array($nextPageUrl, $urlsQueue)) {
                        $urlsQueue[] = $nextPageUrl;
                    }
                }

                $processedUrls[] = $currentUrl;
                usleep(1000000);
            } catch (\Exception $e) {
                Log::error("Ошибка при обработке URL {$currentUrl}: " . $e->getMessage());
            }
        }

        return $totalCollected;
    }

    protected function extractRecipeLinks($html, $baseUrl)
    {
        $recipeLinks = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if ($this->isRecipeLink($href)) {
                $fullUrl = $this->getAbsoluteUrl($href, $baseUrl);
                if (!in_array($fullUrl, $recipeLinks)) {
                    $recipeLinks[] = $fullUrl;
                }
            }
        }

        // Поиск ссылок в JSON-данных (ajax-контент)
        $jsonDataMatches = [];
        preg_match_all('/"url":"([^"]+\/recepty\/[^"]+\-\d{5})"/i', $html, $jsonDataMatches);
        if (!empty($jsonDataMatches[1])) {
            foreach ($jsonDataMatches[1] as $match) {
                $match = str_replace('\/', '/', $match);
                if ($this->isRecipeLink($match)) {
                    $fullUrl = $this->getAbsoluteUrl($match, $baseUrl);
                    if (!in_array($fullUrl, $recipeLinks)) {
                        $recipeLinks[] = $fullUrl;
                    }
                }
            }
        }

        $pattern = '/href=["\']((?:https?:\/\/[^\/]+)?\/recepty\/[^"\']+\-\d{5})["\']/i';
        preg_match_all($pattern, $html, $htmlMatches);
        if (!empty($htmlMatches[1])) {
            foreach ($htmlMatches[1] as $match) {
                if ($this->isRecipeLink($match)) {
                    $fullUrl = $this->getAbsoluteUrl($match, $baseUrl);
                    if (!in_array($fullUrl, $recipeLinks)) {
                        $recipeLinks[] = $fullUrl;
                    }
                }
            }
        }

        Log::info("Найдено ссылок на рецепты: " . count($recipeLinks));
        return array_unique($recipeLinks);
    }

    protected function extractCategoryLinks($html, $baseUrl)
    {
        $categoryLinks = [];
        $dom = new \DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        $links = $dom->getElementsByTagName('a');

        foreach ($links as $link) {
            $href = $link->getAttribute('href');
            if ($this->isCategoryLink($href)) {
                $fullUrl = $this->getAbsoluteUrl($href, $baseUrl);
                if (!in_array($fullUrl, $categoryLinks)) {
                    $categoryLinks[] = $fullUrl;
                }
            }
        }

        return array_unique($categoryLinks);
    }

    protected function isRecipeLink($href)
    {
        // Проверка на шаблон "-5цифр"
        if (preg_match('/-\d{5}$/i', $href)) {
            return true;
        }
        if (preg_match('/-\d{5}(\?|\#|\&|$)/i', $href)) {
            return true;
        }
        return false;
    }

    protected function isCategoryLink($href)
    {
        if (preg_match('/-\d{5}(\?|\#|\&|$)/i', $href)) {
            return false;
        }

        $patterns = [
            '/\/recepty\/[\w-]+$/',
            '/\/recepty\/[\w-]+\/$/',
            '/\/recepty\/[\w-]+\/[\w-]+$/',
            '/\/categories\/[\w-]+$/',
            '/\/ingredienty\/[\w-]+$/',
            '/\/tags\/[\w-]+$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $href)) {
                return true;
            }
        }

        $excludePatterns = [
            '/\/users\/[\w-]+$/',
            '/\/login$/',
            '/\/register$/',
            '/\/auth$/',
            '/\.(jpg|jpeg|png|gif|svg|webp)$/',
            '/\.(css|js)$/',
            '/\#[a-z0-9-_]+$/',
        ];

        foreach ($excludePatterns as $pattern) {
            if (preg_match($pattern, $href)) {
                return false;
            }
        }

        if (strpos($href, '/recepty/') !== false) {
            return true;
        }

        return false;
    }

    protected function isListingPage($url)
    {
        $patterns = [
            '/\/recepty\/[\w-]+$/',
            '/\/recepty$/',
            '/\/recepty\/[\w-]+\/[\w-]+$/',
            '/\/categories\/[\w-]+$/',
            '/\/ingredienty\/[\w-]+$/',
            '/\/tags\/[\w-]+$/',
        ];

        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $url)) {
                return true;
            }
        }

        return false;
    }

    protected function getAbsoluteUrl($href, $baseUrl)
    {
        if (strpos($href, 'http') === 0) {
            return $href;
        }
        if (strpos($href, '/') === 0) {
            return $this->baseUrl . $href;
        }
        return $baseUrl . '/' . $href;
    }

    protected function getNextPageUrl($currentUrl, $html)
    {
        if (preg_match('/page=(\d+)/', $currentUrl, $matches)) {
            $currentPage = (int)$matches[1];
            $nextPage = $currentPage + 1;
            return str_replace("page={$currentPage}", "page={$nextPage}", $currentUrl);
        }
        if (strpos($currentUrl, '?') !== false) {
            return $currentUrl . '&page=2';
        } else {
            return $currentUrl . '?page=2';
        }
    }

    protected function generatePaginationLinks($baseUrl, $startPage, $maxPages)
    {
        $links = [];
        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
        for ($i = $startPage; $i <= $maxPages; $i++) {
            $links[] = $baseUrl . $separator . "page={$i}";
        }
        return $links;
    }

    protected function generateScrollLinks($baseUrl, $startPage, $maxPages)
    {
        $links = [];
        $separator = (strpos($baseUrl, '?') !== false) ? '&' : '?';
        $scrollParams = [
            'page',
            'offset',
            'start',
            'limit',
            'cursor',
        ];
        foreach ($scrollParams as $param) {
            for ($i = $startPage; $i <= $maxPages; $i++) {
                $links[] = $baseUrl . $separator . "{$param}={$i}";
            }
        }
        return $links;
    }

    protected function hasInfiniteScroll($html)
    {
        $infiniteScrollIndicators = [
            'infinite-scroll',
            'data-page=',
            'data-offset=',
            'loadMore',
            'load-more',
            'pagination__more',
            'ajax-pagination',
        ];

        foreach ($infiniteScrollIndicators as $indicator) {
            if (strpos($html, $indicator) !== false) {
                return true;
            }
        }

        return false;
    }

    protected function initializeLinksFile()
    {
        if (!file_exists($this->linksFilePath)) {
            file_put_contents($this->linksFilePath, '');
        }
    }

    protected function saveLinkToFile($link)
    {
        $link = trim($link);
        file_put_contents($this->linksFilePath, $link . PHP_EOL, FILE_APPEND);
    }

    protected function linkExistsInFile($link)
    {
        if (!file_exists($this->linksFilePath)) {
            return false;
        }
        $links = file($this->linksFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return in_array(trim($link), $links);
    }

    protected function countTotalLinks()
    {
        if (!file_exists($this->linksFilePath)) {
            return 0;
        }
        $links = file($this->linksFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        return count($links);
    }

    protected function updateCollectionStatus($statusData)
    {
        file_put_contents($this->statusFilePath, json_encode($statusData, JSON_PRETTY_PRINT));
    }

    protected function getCollectionStatusData()
    {
        if (!file_exists($this->statusFilePath)) {
            return [
                'active'            => false,
                'total_collected'   => 0,
                'current_url'       => '',
                'start_time'        => null,
                'last_activity'     => null,
                'urls_queue'        => [],
                'scroll_offset'     => 0,
                'errors'            => 0,
                'duplicate_skipped' => 0
            ];
        }
        return json_decode(file_get_contents($this->statusFilePath), true);
    }

    protected function hasRecipePattern($url)
    {
        return preg_match('/-\d{5}(\?|\#|\&|$)/i', $url);
    }

    public function showCollectedLinks()
    {
        if (!file_exists($this->linksFilePath)) {
            return view('admin.parser.collected_links', ['links' => []]);
        }

        $links = file($this->linksFilePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        // Фильтруем только ссылки с шаблоном "-5цифр"
        $links = array_filter($links, function($link) {
            return $this->hasRecipePattern($link);
        });

        return view('admin.parser.collected_links', ['links' => $links]);
    }

    /**
     * Отображает страницу управления ссылками с аналитикой
     */
    public function manageLinks()
    {
        $linksFile = public_path('recipe_links/collected_links.txt');
        $links = [];
        $analytics = [
            'total' => 0,
            'unique' => 0,
            'domains' => [],
            'processed' => 0,
            'dublicates' => 0,
            'by_status' => [
                'pending' => 0,
                'processed' => 0,
                'failed' => 0
            ]
        ];
        
        if (file_exists($linksFile)) {
            $content = file_get_contents($linksFile);
            $allLinks = explode("\n", $content);
            
            // Убираем пустые строки
            $allLinks = array_filter($allLinks, function($link) {
                return !empty(trim($link));
            });
            
            $analytics['total'] = count($allLinks);
            $analytics['unique'] = count(array_unique($allLinks));
            $analytics['dublicates'] = $analytics['total'] - $analytics['unique'];
            
            // Получаем статистику по доменам
            foreach ($allLinks as $link) {
                $domain = parse_url($link, PHP_URL_HOST);
                if ($domain) {
                    if (!isset($analytics['domains'][$domain])) {
                        $analytics['domains'][$domain] = 0;
                    }
                    $analytics['domains'][$domain]++;
                }
            }
            
            // Сортируем домены по количеству ссылок
            arsort($analytics['domains']);
            
            // Проверяем, какие ссылки уже обработаны (существуют как рецепты)
            $processedLinks = [];
            foreach (array_slice($allLinks, 0, 1000) as $link) { // Обрабатываем только первые 1000 для производительности
                $existingRecipe = \App\Models\Recipe::where('source_url', $link)->first();
                if ($existingRecipe) {
                    $processedLinks[$link] = [
                        'id' => $existingRecipe->id,
                        'title' => $existingRecipe->title,
                        'status' => 'processed'
                    ];
                    $analytics['by_status']['processed']++;
                    $analytics['processed']++;
                } else {
                    $analytics['by_status']['pending']++;
                }
            }
            
            // Получаем ссылки для отображения (с пагинацией)
            $page = request()->get('page', 1);
            $perPage = 100;
            $offset = ($page - 1) * $perPage;
            
            $displayLinks = array_slice($allLinks, $offset, $perPage);
            
            foreach ($displayLinks as $link) {
                $status = isset($processedLinks[$link]) ? 'processed' : 'pending';
                $recipe = isset($processedLinks[$link]) ? $processedLinks[$link] : null;
                
                $links[] = [
                    'url' => $link,
                    'domain' => parse_url($link, PHP_URL_HOST),
                    'status' => $status,
                    'recipe' => $recipe
                ];
            }
        }
        
        $totalPages = ceil($analytics['total'] / $perPage);
        
        return view('admin.parser.manage_links', [
            'links' => $links,
            'analytics' => $analytics,
            'currentPage' => $page,
            'totalPages' => $totalPages,
            'perPage' => $perPage
        ]);
    }

    /**
     * Обработать выбранные ссылки
     */
    public function processSelectedLinks(Request $request)
    {
        $links = $request->input('links', []);
        $processedUrls = [];
        $failedUrls = [];

        foreach ($links as $link) {
            // Здесь можно использовать существующую логику парсинга
            try {
                $result = $this->parseUrl($link);
                
                if (!empty($result) && !empty($result['title'])) {
                    $recipe = $this->createRecipeFromParsedData($result);
                    
                    if ($recipe) {
                        $processedUrls[] = [
                            'url' => $link,
                            'title' => $recipe->title,
                            'id' => $recipe->id
                        ];
                    } else {
                        $failedUrls[] = [
                            'url' => $link,
                            'error' => 'Не удалось создать рецепт из полученных данных'
                        ];
                    }
                } else {
                    $failedUrls[] = [
                        'url' => $link,
                        'error' => 'Не удалось получить данные рецепта (отсутствует название)'
                    ];
                }
            } catch (\Exception $e) {
                $failedUrls[] = [
                    'url' => $link,
                    'error' => 'Ошибка: ' . $e->getMessage()
                ];
            }
        }
        
        return redirect()->route('admin.parser.manage_links')
            ->with('processedUrls', $processedUrls)
            ->with('failedUrls', $failedUrls);
    }

    /**
     * Удалить обработанные ссылки из файла
     */
    public function removeProcessedLinks()
    {
        $linksFile = public_path('recipe_links/collected_links.txt');
        
        if (file_exists($linksFile)) {
            $content = file_get_contents($linksFile);
            $allLinks = explode("\n", $content);
            
            // Убираем пустые строки
            $allLinks = array_filter($allLinks, function($link) {
                return !empty(trim($link));
            });
            
            $newLinks = [];
            foreach ($allLinks as $link) {
                $existingRecipe = \App\Models\Recipe::where('source_url', $link)->first();
                if (!$existingRecipe) {
                    $newLinks[] = $link;
                }
            }
            
            $removedCount = count($allLinks) - count($newLinks);
            
            // Сохраняем обновленный список ссылок
            file_put_contents($linksFile, implode("\n", $newLinks));
            
            return redirect()->route('admin.parser.manage_links')
                ->with('success', "Удалено {$removedCount} обработанных ссылок");
        }
        
        return redirect()->route('admin.parser.manage_links')
            ->with('error', 'Файл со ссылками не найден');
    }

    /**
     * Check links against database and keep only valid ones
     */
    public function checkValidLinks(Request $request)
    {
        $request->validate([
            'links_file' => 'required|string',
        ]);
        
        $filename = $request->input('links_file');
        $filePath = public_path('recipe_links/' . $filename);
        
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Файл не найден');
        }
        
        $links = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $originalCount = count($links);
        
        // Check each link against your database
        $validLinks = [];
        foreach ($links as $link) {
            // Extract the recipe ID or slug from the link
            preg_match('/\/recepty\/.*\/(.+?)-(\d+)$/', $link, $matches);
            if (isset($matches[2])) {
                $recipeId = $matches[2];
                
                // Check if recipe exists in your database
                $exists = \DB::table('recipes')->where('external_id', $recipeId)->exists();
                
                // If it doesn't exist, it's a valid link to parse
                if (!$exists) {
                    $validLinks[] = $link;
                }
            } else {
                // If we can't extract an ID, keep it for manual review
                $validLinks[] = $link;
            }
        }
        
        // Write valid links back to file
        file_put_contents($filePath, implode(PHP_EOL, $validLinks));
        
        // Сохраняем валидные ссылки в сессии для последующей обработки
        session(['batch_urls' => $validLinks]);
        session(['current_url_index' => 0]);
        session(['processed_urls' => []]);
        session(['failed_urls' => []]);
        
        $removedCount = $originalCount - count($validLinks);
        
        // Передаем количество URL для отображения на странице
        return redirect()->route('admin.parser.batch_result', [
            'total_urls' => count($validLinks),
            'removed' => $removedCount
        ])->with('success', "Проверка завершена. Удалено {$removedCount} уже обработанных ссылок. Осталось " . count($validLinks) . " ссылок для обработки.");
    }

    /**
     * Удаляет ссылки, которые уже существуют в базе данных
     */
    public function removeExistingLinks(Request $request)
    {
        $request->validate([
            'links_file' => 'required|string',
        ]);
        
        $filename = $request->input('links_file');
        $filePath = public_path('recipe_links/' . $filename);
        
        if (!file_exists($filePath)) {
            return redirect()->back()->with('error', 'Файл не найден');
        }
        
        $links = file($filePath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $originalCount = count($links);
        
        // Получаем все source_url из базы данных
        $existingUrls = \DB::table('recipes')->pluck('source_url')->toArray();
        
        // Фильтруем ссылки, оставляя только те, которых нет в базе данных
        $filteredLinks = array_filter($links, function($link) use ($existingUrls) {
            return !in_array($link, $existingUrls);
        });
        
        // Записываем отфильтрованные ссылки обратно в файл
        file_put_contents($filePath, implode(PHP_EOL, $filteredLinks));
        
        $removedCount = $originalCount - count($filteredLinks);
        
        return redirect()->back()->with('success', "Удалено {$removedCount} ссылок, которые уже существуют в базе данных. Осталось " . count($filteredLinks) . " ссылок для обработки.");
    }
}
