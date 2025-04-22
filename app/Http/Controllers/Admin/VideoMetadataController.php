<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use DOMDocument;
use DOMXPath;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;

class VideoMetadataController extends Controller
{
    /**
     * Извлекает метаданные видео из URL
     */
    public function extract(Request $request)
    {
        $url = $request->input('url');

        if (!$url) {
            return response()->json(['error' => 'URL не указан'], 400);
        }

        if (stripos($url, 'vk.com') !== false || stripos($url, 'vkvideo.ru') !== false) {
            return $this->extractVkVideoMetadata($url);
        } else {
            return response()->json(['error' => 'Поддерживаются только видео с VK'], 400);
        }
    }

    /**
     * Извлекает метаданные видео ВКонтакте
     */
    private function extractVkVideoMetadata($url)
    {
        try {
            if (stripos($url, 'vkvideo.ru') !== false) {
                if (preg_match('/video-?(\d+)_(\d+)/', $url, $matches)) {
                    $ownerId = $matches[1];
                    $videoId = $matches[2];
                    $url = "https://vk.com/video-{$ownerId}_{$videoId}";
                }
            }

            Log::info("Начинаем запрос к VK видео: {$url}");

            try {
                $client = new Client([
                    'timeout'         => 60,
                    'connect_timeout' => 30,
                    'verify'          => false,
                    'http_errors'     => false,
                    'cookies'         => true,
                ]);

                $response = $client->request('GET', $url, [
                    'headers' => [
                        'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, как Gecko) Chrome/121.0.0.0 Safari/537.36',
                        'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                        'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                        'Accept-Encoding' => 'gzip, deflate, br',
                        'Cache-Control' => 'max-age=0',
                        'Connection' => 'keep-alive',
                        'Referer' => 'https://vk.com/',
                    ],
                ]);

                $statusCode = $response->getStatusCode();
                $html       = (string)$response->getBody();

                Log::info("Получен ответ от VK: HTTP {$statusCode}, длина ответа: " . strlen($html));

                if ($statusCode < 200 || $statusCode >= 300) {
                    throw new \Exception("Неуспешный HTTP статус: {$statusCode}");
                }
            } catch (GuzzleException $e) {
                Log::warning("Ошибка Guzzle при запросе к VK: " . $e->getMessage() . ". Пробуем Http фасад Laravel.");

                $response = Http::withOptions([
                    'timeout'         => 60,
                    'connect_timeout' => 30,
                    'verify'          => false,
                ])
                ->withHeaders([
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, как Gecko) Chrome/121.0.0.0 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/avif,image/webp,image/apng,*/*;q=0.8',
                    'Accept-Language' => 'ru-RU,ru;q=0.9,en-US;q=0.8,en;q=0.7',
                    'Accept-Encoding' => 'gzip, deflate, br',
                    'Cache-Control' => 'no-cache',
                    'Connection' => 'keep-alive',
                    'Pragma' => 'no-cache',
                    'Referer' => 'https://vk.com/',
                ])
                ->retry(5, 3000)
                ->get($url);

                if (!$response->successful()) {
                    Log::error("Ответ от VK не успешен: {$response->status()} {$response->body()}");
                    throw new \Exception("Не удалось получить данные видео: {$response->status()}");
                }

                $html = $response->body();
            }

            file_put_contents(storage_path('logs/vk_video_html.log'), $html);

            // Исправление кодировки HTML перед парсингом
            $html = $this->normalizeHtmlEncoding($html);
            
            $dom = new DOMDocument('1.0', 'UTF-8');
            $dom->preserveWhiteSpace = false;
            $dom->formatOutput = true;
            @$dom->loadHTML($html, LIBXML_NOERROR | LIBXML_NOWARNING);
            $xpath = new DOMXPath($dom);

            $title = $this->extractMetaContent($xpath, 'og:title');
            $description = $this->extractMetaContent($xpath, 'og:description');

            // Исправляем кодировку если необходимо
            $title = $this->normalizeEncoding($title);
            $description = $this->normalizeEncoding($description);

            if (!$title) {
                $titleSelectors = [
                    "//div[@data-testid='video_modal_title']",
                    "//div[contains(@class, 'vkitTextClamp__root') and @data-testid='video_modal_title']",
                    "//span[contains(@class, 'vv_title')]",
                    "//div[contains(@class, 'VideoPageInfoRowWithPlayer__titleRow')]",
                    "//div[contains(@class, 'VideoPageInfoRow__title')]//span",
                ];

                foreach ($titleSelectors as $selector) {
                    $node = $xpath->query($selector)->item(0);
                    if ($node) {
                        $title = $this->normalizeEncoding(trim($node->textContent));
                        break;
                    }
                }
            }

            if (!$description) {
                $descriptionSelectors = [
                    "//span[contains(@class, 'Description__textWrapper')]",
                    "//span[contains(@class, 'Description__textWrapper--PmEMO')]",
                    "//div[contains(@class, 'vkuiDiv__host')]//span[contains(@class, 'Description__textWrapper')]",
                    "//div[contains(@class, 'VideoDescriptionText')]",
                    "//div[contains(@class, 'video_desc')]",
                    "//div[contains(@class, 'VideoPageInfoRow__description')]",
                ];

                foreach ($descriptionSelectors as $selector) {
                    $node = $xpath->query($selector)->item(0);
                    if ($node) {
                        $description = $this->normalizeEncoding($this->extractFormattedText($node, $dom));
                        break;
                    }
                }
            }

            preg_match('/video(-?\d+)_(\d+)/', $url, $matches);
            $ownerId = $matches[1] ?? '';
            $videoId = $matches[2] ?? '';

            $iframe = '';
            if ($ownerId && $videoId) {
                $iframe = '<iframe src="https://vk.com/video_ext.php?oid=' . $ownerId . '&id=' . $videoId . '&hd=2" width="100%"  frameborder="0" allowfullscreen></iframe>';
            } else {
                $node = $xpath->query("//iframe[contains(@src, 'vk.com/video_ext')]")->item(0);
                if ($node) {
                    $iframe = $dom->saveHTML($node);
                }
            }

            list($authorName, $authorLink) = $this->extractVkVideoAuthor($xpath);
            $authorName = $this->normalizeEncoding($authorName);

            if (!$authorName) {
                $authorName = 'ВКонтакте';
                $authorLink = 'https://vk.com';
            }

            $tags = $this->extractVkVideoTags($xpath);
            // Нормализуем кодировку каждого тега
            $tags = array_map([$this, 'normalizeEncoding'], $tags);
            
            if (empty($tags)) {
                $tags = $this->extractPossibleTags($title . ' ' . $description);
            } else {
                $tags = array_slice(array_unique($tags), 0, 5);
            }

            // Извлечение URL обложки видео
            $thumbnailUrl = '';
            
            // Попытка извлечь URL обложки из meta-тегов
            $thumbnailUrl = $this->extractMetaContent($xpath, 'og:image');
            
            if (!$thumbnailUrl) {
                // Попытка найти URL обложки в JSON данных страницы
                if (preg_match('/"preview_url":"([^"]+)"/', $html, $matches)) {
                    $thumbnailUrl = str_replace('\/', '/', $matches[1]);
                } elseif (preg_match('/"thumb":"([^"]+)"/', $html, $matches)) {
                    $thumbnailUrl = str_replace('\/', '/', $matches[1]);
                }
            }
            
            // Если все еще нет URL, попробуем найти в других атрибутах страницы
            if (!$thumbnailUrl) {
                $imgNodes = $xpath->query("//img[contains(@src, 'vk') and contains(@src, 'video') and contains(@src, '.jpg')]");
                if ($imgNodes->length > 0) {
                    $thumbnailUrl = $imgNodes->item(0)->getAttribute('src');
                }
            }
            
            Log::info("VK Video Metadata успешно извлечены: URL={$url}, Title={$title}, Thumbnail={$thumbnailUrl}");

            return response()->json([
                'success' => true,
                'data'    => [
                    'iframe'      => $iframe,
                    'title'       => $title,
                    'description' => $description,
                    'author_name' => $authorName,
                    'author_link' => $authorLink,
                    'tags'        => implode(', ', $tags),
                    'platform'    => 'vk',
                    'url'         => $url,
                    'thumbnail'   => $thumbnailUrl,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error("Исключение при извлечении данных VK: {$e->getMessage()} в {$e->getFile()} строка {$e->getLine()}");
            Log::debug($e->getTraceAsString());

            if (strpos($e->getMessage(), 'cURL error 28') !== false) {
                return response()->json(['error' => 'Истекло время ожидания при загрузке данных с VK.'], 504);
            }

            return response()->json(['error' => 'Ошибка при извлечении метаданных VK: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Извлекает информацию об авторе из VKVideo
     * 
     * @param DOMXPath $xpath
     * @return array [authorName, authorLink]
     */
    private function extractVkVideoAuthor(DOMXPath $xpath)
    {
        $authorName = '';
        $authorLink = '';
        $baseDomain = 'https://vkvideo.ru'; // Используем vkvideo.ru как основной домен
        
        // Определяем источник видео по метаданным или HTML
        $html = $xpath->document->saveHTML();
        $sourceIsVkVideo = preg_match('/vkvideo\.ru/', $html);
        
        // Устанавливаем базовый домен в зависимости от источника
        if (!$sourceIsVkVideo && preg_match('/vk\.com/', $html)) {
            $baseDomain = 'https://vk.com';
        }
        
        Log::info("Определен базовый домен для видео: " . $baseDomain);
        
        // 1. Метод: поиск в данных API и переменных JS (самый надежный метод)
        if (preg_match('/ownerId\s*:\s*(-?\d+)/', $html, $ownerMatches) && 
            preg_match('/ownerName\s*:\s*[\'"]([^\'"]+)[\'"]/', $html, $nameMatches)) {
            
            $ownerId = $ownerMatches[1];
            $ownerName = $this->normalizeEncoding($nameMatches[1]);
            
            Log::debug("Найдены данные владельца из API: ID={$ownerId}, Name={$ownerName}");
            
            if ($ownerName) {
                $authorName = $ownerName;
                if (is_numeric($ownerId)) {
                    if ($ownerId < 0) {
                        // Группа/сообщество (отрицательный ID)
                        $groupId = abs($ownerId);
                        $authorLink = "https://vk.com/public{$groupId}";
                    } else {
                        // Пользователь (положительный ID)
                        $authorLink = "https://vk.com/id{$ownerId}";
                    }
                    return [$authorName, $authorLink];
                }
            }
        }
        
        // 2. Метод: Извлечение из данных API различных форматов
        if (preg_match('/data-author-id=[\'"]([-\d]+)[\'"]/', $html, $authorIdMatches) &&
            preg_match('/data-author-name=[\'"](.*?)[\'"]/', $html, $authorNameMatches)) {
            
            $authorId = $authorIdMatches[1];
            $authorNameFromData = $this->normalizeEncoding($authorNameMatches[1]);
            
            if ($authorNameFromData) {
                $authorName = $authorNameFromData;
                if (is_numeric($authorId)) {
                    if ($authorId < 0) {
                        $groupId = abs($authorId);
                        $authorLink = "https://vk.com/public{$groupId}";
                    } else {
                        $authorLink = "https://vk.com/id{$authorId}";
                    }
                    return [$authorName, $authorLink];
                }
            }
        }
        
        // 3. Метод: Поиск в JSON-LD данных (более подробный поиск)
        if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $jsonMatches)) {
            foreach ($jsonMatches[1] as $jsonString) {
                $jsonString = $this->normalizeEncoding($jsonString);
                $json = json_decode($jsonString, true);
                
                if (isset($json['author'])) {
                    $author = $json['author'];
                    if (is_array($author) && isset($author['name'])) {
                        $authorName = $author['name'];
                        $authorLink = $author['url'] ?? '';
                        
                        if ($authorName) {
                            Log::debug("Найден автор в JSON-LD: {$authorName}");
                            
                            // Если URL не указан, но есть ID, формируем ссылку
                            if (!$authorLink && isset($author['identifier'])) {
                                $id = $author['identifier'];
                                if (is_numeric($id)) {
                                    if ($id < 0) {
                                        $authorLink = "https://vk.com/public" . abs($id);
                                    } else {
                                        $authorLink = "https://vk.com/id{$id}";
                                    }
                                } else if (is_string($id) && !empty($id)) {
                                    $authorLink = "https://vk.com/{$id}";
                                }
                            }
                            
                            if ($authorName && (!$authorLink || strpos($authorLink, 'http') !== 0)) {
                                // Создаем slug из имени для ссылки
                                $slug = $this->createSlugFromName($authorName);
                                $authorLink = "{$baseDomain}/channel/{$slug}";
                            }
                            
                            return [$authorName, $authorLink];
                        }
                    } elseif (is_string($author) && !empty($author)) {
                        $authorName = $author;
                        $slug = $this->createSlugFromName($authorName);
                        $authorLink = "{$baseDomain}/channel/{$slug}";
                        return [$authorName, $authorLink];
                    }
                }
            }
        }
        
        // 4. Метод: поиск в vk_shared_data объекте
        if (preg_match('/vk_shared_data\s*=\s*({.*?});/s', $html, $matches)) {
            $jsonData = $this->normalizeEncoding($matches[1]);
            
            // Заменяем одинарные кавычки на двойные и неэкранированные кавычки
            $jsonData = str_replace("'", '"', $jsonData);
            $jsonData = preg_replace('/([{,:])\s*([a-zA-Z0-9_]+)\s*:/', '$1"$2":', $jsonData);
            
            // Попытка декодировать исправленные данные
            $data = @json_decode($jsonData, true);
            
            if ($data && isset($data['page']['authorData'])) {
                $authorData = $data['page']['authorData'];
                $authorName = $authorData['name'] ?? '';
                $authorLink = $authorData['url'] ?? '';
                
                if ($authorName) {
                    Log::debug("Найден автор через vk_shared_data: {$authorName}");
                    return [$authorName, !empty($authorLink) ? $authorLink : "{$baseDomain}/channel/{$this->createSlugFromName($authorName)}"];
                }
            }
        }

        // 5. Расширенный набор селекторов CSS
        $authorSelectors = [
            // Общие селекторы
            "//a[contains(@class, 'VideoOwner__user')]",
            "//a[contains(@class, 'VideoOwner__info')]//a",
            "//div[contains(@class, 'VideoOwner__name')]",
            "//a[contains(@class, 'VideoOwner__author')]//span",
            
            // Специфичные для VK.com
            "//div[contains(@class, 'ui_owner_name')]",
            "//div[contains(@class, 'VideoCard__owner')]//a",
            "//a[contains(@class, 'PostAuthor')]",
            "//a[contains(@class, 'PostHeaderSubtitle__link')]",
            
            // Новые форматы видеоплеера
            "//div[contains(@class, 'vkuiSimpleCell__content')]//a",
            "//div[contains(@class, 'VideoPagePlace__info')]//a[contains(@href, '/')]",
            
            // Бэкап селекторы
            "//a[contains(@href, '/video/@')]",
            "//a[starts-with(@href, '/') and not(contains(@href, 'video'))]",
        ];
        
        foreach ($authorSelectors as $selector) {
            $nodes = $xpath->query($selector);
            
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $href = $node->getAttribute('href');
                    $name = trim($node->textContent);
                    
                    if (!empty($name)) {
                        Log::debug("Найден потенциальный автор через селектор '{$selector}': {$name}");
                        $authorName = $name;
                        
                        if (!empty($href)) {
                            if (strpos($href, '/') === 0) {
                                $authorLink = $baseDomain . $href;
                            } elseif (strpos($href, 'http') === 0) {
                                $authorLink = $href;
                            }
                            return [$this->normalizeEncoding($authorName), $authorLink];
                        }
                    }
                }
            }
        }

        // 6. Поиск в мета-тегах
        $metaSelectors = [
            "//meta[@property='og:site_name']",
            "//meta[@property='article:author']",
            "//meta[@name='author']",
        ];
        
        foreach ($metaSelectors as $selector) {
            $meta = $xpath->query($selector)->item(0);
            if ($meta) {
                $content = $meta->getAttribute('content');
                if (!empty($content) && $content !== 'VK' && $content !== 'ВКонтакте') {
                    Log::debug("Найден автор через мета-тег {$selector}: {$content}");
                    $authorName = $content;
                    $slug = $this->createSlugFromName($authorName);
                    $authorLink = "{$baseDomain}/channel/{$slug}";
                    return [$this->normalizeEncoding($authorName), $authorLink];
                }
            }
        }
        
        // 7. Извлечение из данных группы VK
        if (preg_match('/group_id\s*:\s*(-?\d+)/', $html, $groupMatches) && 
            preg_match('/group_name\s*:\s*[\'"]([^\'"]+)[\'"]/', $html, $groupNameMatches)) {
            
            $groupId = abs($groupMatches[1]);  // Используем abs, так как группы имеют отрицательный ID
            $groupName = $this->normalizeEncoding($groupNameMatches[1]);
            
            if ($groupName) {
                Log::debug("Найдена группа: {$groupName}, ID: {$groupId}");
                return [$groupName, "https://vk.com/public{$groupId}"];
            }
        }
        
        // Если определить автора не удалось, используем информацию из URL видео
        if (preg_match('/video(-?\d+)_\d+/', $html, $matches)) {
            $ownerId = $matches[1];
            if (is_numeric($ownerId)) {
                if ($ownerId < 0) {
                    // Это группа/сообщество
                    $authorName = "Сообщество ВКонтакте";
                    $authorLink = "https://vk.com/public" . abs($ownerId);
                } else {
                    // Это пользователь
                    $authorName = "Пользователь ВКонтакте";
                    $authorLink = "https://vk.com/id{$ownerId}";
                }
                
                Log::debug("Определен автор из ID в URL видео: {$authorName}, ссылка: {$authorLink}");
                return [$authorName, $authorLink];
            }
        }

        // Если все методы поиска не сработали, используем запасной вариант
        if (!$authorName) {
            if ($baseDomain === 'https://vk.com') {
                $authorName = 'Канал ВКонтакте';
                $authorLink = 'https://vk.com/video';
            } else {
                $authorName = 'Видео-канал';
                $authorLink = 'https://vkvideo.ru';
            }
            
            Log::warning("Не удалось определить автора канала, используем запасной вариант: {$authorName}");
        }
        
        return [$authorName, $authorLink];
    }

    /**
     * Скачивает обложку видео по URL
     *
     * @param string $url URL обложки
     * @return string|null Относительный путь к сохраненной обложке или null при ошибке
     */
    public function downloadThumbnail($url)
    {
        if (empty($url)) {
            return null;
        }
        
        try {
            // Создаем директорию для обложек, если она не существует
            $uploadPath = public_path('uploads/thumbnails');
            if (!file_exists($uploadPath)) {
                mkdir($uploadPath, 0755, true);
            }
            
            // Генерируем уникальное имя файла
            $filename = 'thumbnail_' . time() . '_' . \Illuminate\Support\Str::random(10) . '.jpg';
            $fullPath = $uploadPath . '/' . $filename;
            
            // Устанавливаем контекст запроса с User-Agent для избежания блокировок
            $context = stream_context_create([
                'http' => [
                    'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, как Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]
            ]);
            
            // Скачиваем изображение
            $imageContent = file_get_contents($url, false, $context);
            if ($imageContent === false) {
                return null;
            }
            
            // Сохраняем изображение
            file_put_contents($fullPath, $imageContent);
            
            // Проверяем, что файл действительно является изображением
            if (exif_imagetype($fullPath) === false) {
                unlink($fullPath);
                return null;
            }
            
            // Ресайз изображения, если доступна библиотека Intervention Image
            if (class_exists('\Intervention\Image\Facades\Image')) {
                $img = \Intervention\Image\Facades\Image::make($fullPath);
                // Ограничиваем размер изображения
                $img->resize(1200, null, function ($constraint) {
                    $constraint->aspectRatio();
                    $constraint->upsize();
                });
                $img->save($fullPath, 80); // Сохраняем с качеством 80%
                
                // Создаем миниатюру
                $thumbPath = $uploadPath . '/thumb_' . $filename;
                $img->fit(400, 300);
                $img->save($thumbPath);
            }
            
            // Возвращаем относительный путь
            return 'thumbnails/' . $filename;
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при скачивании обложки видео: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Извлекает содержимое метатега
     */
    private function extractMetaContent(DOMXPath $xpath, $propertyName)
    {
        $node = $xpath->query("//meta[@property='{$propertyName}' or @name='{$propertyName}']")->item(0);
        $content = $node ? $node->getAttribute('content') : '';
        return $this->normalizeEncoding($content);
    }

    /**
     * Создает slug из имени
     */
    private function createSlugFromName($name)
    {
        $slug = mb_strtolower($name);
        $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', $slug);
        return trim($slug, '-');
    }

    /**
     * Извлекает возможные теги из текста
     */
    private function extractPossibleTags($text)
    {
        $text = mb_strtolower($text);
        $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text); // Исправлено: добавлен пропущенный параметр - пустая строка
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);
        $words = array_filter($words, fn($w) => mb_strlen($w) >= 3 && mb_strlen($w) <= 15);
        $stopWords = ['этот','того','этого','этом','этим','как','для','что','или','при','его','все','так','был','они','она','оно','еще','когда','также','если','уже','чем','быть','нас','вас','вам','нам','были','было','будет','наш','ваш','мой','твой','это','вот','где','там','тут','кто','такой','который','сейчас','здесь'];
        $words = array_diff($words, $stopWords);
        return array_slice(array_unique($words), 0, 5);
    }

    /**
     * Извлекает теги из VK Video
     */
    private function extractVkVideoTags(DOMXPath $xpath)
    {
        $tags = [];
        $selectors = [
            "//span[contains(@class, 'Description__textWrapper')]//a[contains(@href, '/video?q=%23')]",
            "//span[contains(@class, 'Description__textWrapper--PmEMO')]//a[contains(@href, '/video?q=%23')]",
            "//div[contains(@class, 'VideoPageInfoRow__tagsContainer')]//a",
        ];
        foreach ($selectors as $sel) {
            $nodes = $xpath->query($sel);
            if ($nodes->length) {
                foreach ($nodes as $node) {
                    $txt = trim($node->textContent);
                    if ($txt) {
                        $tags[] = ltrim($txt, '#');
                    }
                }
                if (!empty($tags)) break;
            }
        }
        return $tags;
    }

    /**
     * Извлекает форматированный текст из DOM-узла, сохраняя переносы строк
     */
    private function extractFormattedText($node, $dom)
    {
        $text = '';
        foreach ($node->childNodes as $child) {
            if ($child->nodeType === XML_TEXT_NODE) {
                $text += $child->wholeText;
            } elseif ($child->nodeType === XML_ELEMENT_NODE) {
                if ($child->nodeName === 'br') {
                    $text += "\n";
                } elseif ($child->nodeName === 'a') {
                    $text += $child->textContent + ' ';
                } else {
                    $text += $this->extractFormattedText($child, $dom);
                }
            }
        }
        return trim($text);
    }
    
    /**
     * Нормализует кодировку HTML перед парсингом
     * 
     * @param string $html
     * @return string
     */
    private function normalizeHtmlEncoding($html)
    {
        // Проверяем кодировку из заголовка Content-Type
        if (preg_match('/<meta[^>]+charset=[\'"]*([a-zA-Z0-9\-_]+)/i', $html, $matches)) {
            $charset = strtoupper($matches[1]);
            Log::debug("Обнаружена кодировка в HTML: " . $charset);
            
            // Если не UTF-8, преобразуем в UTF-8
            if ($charset != 'UTF-8' && $charset != 'UTF8') {
                $html = mb_convert_encoding($html, 'UTF-8', $charset);
                Log::debug("HTML преобразован из {$charset} в UTF-8");
            }
        }
        
        // Обязательно добавляем декларацию UTF-8 для DOMDocument
        if (!preg_match('/<meta[^>]+charset=[\'"]*utf-8/i', $html)) {
            $html = preg_replace('/(<head[^>]*>)/i', '$1<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>', $html);
        }
        
        return $html;
    }
    
    /**
     * Нормализует кодировку текста
     * 
     * @param string $text
     * @return string
     */
    private function normalizeEncoding($text)
    {
        if (empty($text)) {
            return '';
        }

        // декодируем HTML-сущности
        $text = html_entity_decode($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');

        // если видим «ГђВ»—идем по спец‑алг.
        if (preg_match('/Г.{1,2}В.{1,2}/u', $text)) {
            return $this->fixBrokenCyrillicEncoding($text);
        }

        // стандартный детект и конвертация
        $order = ['UTF-8','Windows-1251','KOI8-R','ISO-8859-5','CP866'];
        $enc   = mb_detect_encoding($text,$order,true);
        if (!$enc) {
            return $this->cleanInvalidUtf8Characters($this->forcedRussianCharsetConversion($text));
        }
        if ($enc!=='UTF-8') {
            $text = mb_convert_encoding($text,'UTF-8',$enc);
        }
        return $this->cleanInvalidUtf8Characters($text);
    }

    // Новый метод для «сломанных» кириллиц
    private function fixBrokenCyrillicEncoding($text)
    {
        // пробуем «UTF8→CP1251→UTF8»
        $t = mb_convert_encoding($text,'Windows-1251','UTF-8');
        $r = mb_convert_encoding($t,'UTF-8','Windows-1251');
        if (preg_match('/[а-яА-ЯёЁ]/u',$r)) {
            return $this->cleanInvalidUtf8Characters($r);
        }
        // иначе выбираем лучший вариант
        $variants = [
            $text,
            mb_convert_encoding($text,'UTF-8','Windows-1251'),
            mb_convert_encoding($text,'UTF-8','KOI8-R'),
        ];
        $best = $text; $max=0;
        foreach($variants as $v){
            $c = preg_match_all('/[а-яА-ЯёЁ]/u',$v);
            if($c>$max){ $max=$c; $best=$v; }
        }
        return $this->cleanInvalidUtf8Characters($best);
    }

    /**
     * Принудительное преобразование кодировки для русского текста
     * 
     * @param string $text
     * @return string
     */
    private function forcedRussianCharsetConversion($text)
    {
        // Пробуем различные варианты преобразования кодировок
        $variants = [
            mb_convert_encoding($text, 'UTF-8', 'Windows-1251'),
            mb_convert_encoding($text, 'UTF-8', 'KOI8-R'),
            mb_convert_encoding($text, 'UTF-8', 'ISO-8859-5'),
            mb_convert_encoding($text, 'UTF-8', 'CP866'),
        ];
        
        // Выбираем вариант с наибольшим количеством кириллических символов
        $bestVariant = $text;
        $maxCyrillicCount = $this->countCyrillicChars($text);
        
        foreach ($variants as $variant) {
            $cyrillicCount = $this->countCyrillicChars($variant);
            if ($cyrillicCount > $maxCyrillicCount) {
                $maxCyrillicCount = $cyrillicCount;
                $bestVariant = $variant;
            }
        }
        
        return $bestVariant;
    }
    
    /**
     * Принудительное преобразование кодировки HTML
     * 
     * @param string $html
     * @return string
     */
    private function forcedCharsetConversion($html)
    {
        // Проверяем объявленную кодировку в документе
        $declaredCharset = null;
        if (preg_match('/<meta[^>]+charset=[\'"]*([a-zA-Z0-9\-_]+)/i', $html, $matches)) {
            $declaredCharset = strtoupper($matches[1]);
            Log::debug("Объявленная кодировка в HTML: " . $declaredCharset);
        }
        
        // Если объявлена не UTF-8 кодировка, пробуем преобразовать
        if ($declaredCharset && $declaredCharset !== 'UTF-8' && $declaredCharset !== 'UTF8') {
            try {
                $html = mb_convert_encoding($html, 'UTF-8', $declaredCharset);
                // Заменяем объявление кодировки на UTF-8
                $html = preg_replace('/(charset=)[\'"]?[a-zA-Z0-9\-_]+[\'"]?/i', '$1"UTF-8"', $html);
                Log::debug("HTML преобразован из {$declaredCharset} в UTF-8");
            } catch (\Exception $e) {
                Log::warning("Ошибка при преобразовании кодировки: " . $e->getMessage());
            }
        }
        
        return $html;
    }
    
    /**
     * Очищает строку от некорректных UTF-8 символов
     * 
     * @param string $text
     * @return string
     */
    private function cleanInvalidUtf8Characters($text) 
    {
        // удаляем некорректные байты, BOM, декодируем сущности
        $text = html_entity_decode($text, ENT_QUOTES|ENT_HTML5,'UTF-8');
        $text = preg_replace('/[\x00-\x08\x0B-\x0C\x0E-\x1F\x7F-\x9F]+/u','',$text);
        $text = preg_replace('/^\xEF\xBB\xBF/','',$text);
        if (function_exists('iconv')) {
            $text = @iconv('UTF-8','UTF-8//IGNORE',$text);
        }
        return $text;
    }
    
    /**
     * Подсчитывает количество кириллических символов в строке
     * 
     * @param string $text
     * @return int
     */
    private function countCyrillicChars($text) 
    {
        return preg_match_all('/[\p{Cyrillic}]/u', $text);
    }
    
    /**
     * Извлекает метаданные из JSON-LD на странице
     */
    private function extractJsonLdData($html)
    {
        $data = ['title' => '', 'description' => '', 'authorName' => '', 'authorUrl' => '', 'thumbnailUrl' => ''];
        if (preg_match_all('/<script type="application\/ld\+json">(.*?)<\/script>/s', $html, $matches)) {
            foreach ($matches[1] as $jsonString) {
                $jsonString = $this->normalizeEncoding($jsonString);
                $json = json_decode($jsonString, true);
                if (!$json) continue;
                if (isset($json['@type']) && in_array($json['@type'], ['VideoObject', 'Movie'])) {
                    $data['title']       = $json['name'] ?? $data['title'];
                    $data['description'] = $json['description'] ?? $data['description'];
                    $data['thumbnailUrl'] = $json['thumbnailUrl'] ?? ($json['image'] ?? $data['thumbnailUrl']);
                    $author = $json['author'] ?? $json['creator'] ?? null;
                    if (is_array($author)) {
                        $data['authorName'] = $author['name'] ?? $data['authorName'];
                        $data['authorUrl']  = $author['url'] ?? $data['authorUrl'];
                    } elseif (is_string($author)) {
                        $data['authorName'] = $author;
                    }
                }
            }
        }
        
        // Проверяем кодировку всех извлеченных данных
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = $this->normalizeEncoding($value);
            }
        }
        
        return $data;
    }
}