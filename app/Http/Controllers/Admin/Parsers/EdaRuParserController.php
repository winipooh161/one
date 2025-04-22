<?php

namespace App\Http\Controllers\Admin\Parsers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use DOMDocument;
use DOMXPath;
use Exception;

class EdaRuParserController extends BaseParserController
{
    /**
     * Извлекает ссылки на рецепты с сайта eda.ru
     */
    public function extractRecipeLinks($html, $baseUrl)
    {
        $links = [];
        
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            // Логируем информацию для отладки
            \Log::info("Извлечение ссылок на рецепты с eda.ru. baseUrl: $baseUrl");
            
            // Находим все ссылки на странице
            $allLinks = $xpath->query('//a[@href]');
            
            \Log::info("Найдено всего ссылок: " . $allLinks->length);
            $recipeLinks = 0;
            
            foreach ($allLinks as $linkNode) {
                $href = $linkNode->getAttribute('href');
                
                // Проверяем паттерн URL рецепта: должен начинаться с /recepty/ и заканчиваться на -ЦИФРЫ
                if (preg_match('~^/recepty/.*-\d+$~', $href) || preg_match('~^https?://eda\.ru/recepty/.*-\d+$~', $href)) {
                    // Нормализуем ссылку - преобразуем относительные URL в абсолютные
                    if (strpos($href, 'http') !== 0) {
                        $href = rtrim($baseUrl, '/') . $href;
                    }
                    
                    // Валидируем URL
                    if (filter_var($href, FILTER_VALIDATE_URL)) {
                        $links[] = $href;
                        $recipeLinks++;
                    }
                }
            }
            
            \Log::info("Извлечено ссылок на рецепты: $recipeLinks");
            
            return array_unique($links);
        } catch (Exception $e) {
            \Log::warning("Ошибка при извлечении ссылок eda.ru: " . $e->getMessage());
            return $links;
        }
    }

    /**
     * Извлекает изображения с сайта eda.ru
     */
    public function extractImages($html, $baseUrl)
    {
        $images = [];
        
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            // Ищем изображения в различных контейнерах
            $imgSelectors = [
                '//img[contains(@src, "/images/")]',
                '//picture//img',
                '//div[contains(@class, "emotion-")]//img'
            ];
            
            foreach ($imgSelectors as $selector) {
                $imgNodes = $xpath->query($selector);
                foreach ($imgNodes as $img) {
                    if ($img->hasAttribute('src')) {
                        $src = $img->getAttribute('src');
                        
                        // Обрабатываем относительные URL для изображений
                        if (strpos($src, 'http') !== 0) {
                            if (strpos($src, '//') === 0) {
                                $src = 'https:' . $src;
                            } else if (strpos($src, '/') === 0) {
                                $src = $baseUrl . $src;
                            }
                        }
                        
                        if (filter_var($src, FILTER_VALIDATE_URL)) {
                            $images[] = $src;
                        }
                    }
                }
            }
            
            // Также ищем изображения в мета-тегах
            $metaImages = $xpath->query('//meta[@property="og:image" or @name="og:image"]');
            foreach ($metaImages as $meta) {
                if ($meta->hasAttribute('content')) {
                    $content = $meta->getAttribute('content');
                    if (filter_var($content, FILTER_VALIDATE_URL)) {
                        $images[] = $content;
                    }
                }
            }
            
            return array_values(array_unique($images));
        } catch (Exception $e) {
            \Log::warning("Ошибка при извлечении изображений eda.ru: " . $e->getMessage());
            return $images;
        }
    }

    /**
     * Генерирует набор URL-вариаций для сайта eda.ru для имитации прокрутки
     */
    public function generateUrlVariations($baseUrl, $maxPages)
    {
        // Увеличиваем количество вариаций для более глубокого поиска
        $maxPages = min($maxPages, 25); // Увеличиваем с 8 до 25
        
        $variations = [];
        $parsedUrl = parse_url($baseUrl);
        $pathParts = explode('/', trim($parsedUrl['path'] ?? '', '/'));
        
        // Добавляем исходный URL
        $variations[] = $baseUrl;
        
        // Добавляем URL с параметрами пагинации, но не более maxPages страниц
        for ($i = 1; $i <= $maxPages; $i++) {
            // Добавляем параметр page для пагинации
            if (strpos($baseUrl, '?') !== false) {
                $variations[] = $baseUrl . '&page=' . $i;
            } else {
                $variations[] = $baseUrl . '?page=' . $i;
            }
        }
        
        // Если это страница категории, добавляем страницы сортировки
        $sortOptions = ['popular', 'new', 'views', 'rating'];
        foreach ($sortOptions as $sort) {
            if (strpos($baseUrl, '?') !== false) {
                $variations[] = $baseUrl . '&sort=' . $sort;
            } else {
                $variations[] = $baseUrl . '?sort=' . $sort;
            }
            
            // Добавляем страницы пагинации для каждого типа сортировки
            for ($i = 1; $i <= 5; $i++) {
                if (strpos($baseUrl, '?') !== false) {
                    $variations[] = $baseUrl . '&sort=' . $sort . '&page=' . $i;
                } else {
                    $variations[] = $baseUrl . '?sort=' . $sort . '&page=' . $i;
                }
            }
        }
        
        // Если это страница категории, добавляем подкатегории
        if (isset($pathParts[0]) && $pathParts[0] === 'recepty' && count($variations) < 100) {
            $popularCategories = [
                'zakuski', 'vypechka-deserty', 'supy', 'osnovnye-blyuda', 'salaty', 'zavtraki',
                'pasta-picca', 'napitki', 'sousy-marinady', 'zagotovki', 'vypechka', 'deserty'
            ];
            
            $addedCategories = 0;
            foreach ($popularCategories as $category) {
                // Добавляем только если не превышаем лимит
                if (count($variations) >= 100) {
                    break;
                }
                
                // Добавляем URL подкатегории только если она не совпадает с текущей
                if (!in_array($category, $pathParts)) {
                    $categoryUrl = $parsedUrl['scheme'] . '://' . $parsedUrl['host'] . '/recepty/' . $category;
                    $variations[] = $categoryUrl;
                    $addedCategories++;
                    
                    // Добавляем несколько страниц пагинации для подкатегории
                    for ($i = 1; $i <= 3; $i++) {
                        $variations[] = $categoryUrl . '?page=' . $i;
                    }
                }
            }
        }
        
        return array_unique($variations);
    }

    /**
     * Парсинг данных с сайта eda.ru
     */
    public function parseRecipe($xpath, $result)
    {
        // Извлекаем название рецепта
        $titleNode = $xpath->query('//h1[contains(@class, "emotion-")]')->item(0);
        if ($titleNode) {
            $result['title'] = trim($titleNode->textContent);
        }
        
        // Извлекаем описание - улучшенный метод
        $descriptionContent = '';
        
        // Пробуем найти описание в различных местах
        $descriptionNodes = [
            $xpath->query('//span[@itemprop="author"]/span[contains(@class, "emotion-aiknw3")]/span')->item(0),
            $xpath->query('//span[contains(@class, "emotion-aiknw3")]/span')->item(0),
            $xpath->query('//div[contains(@class, "emotion-")]/span')->item(0)
        ];
        
        foreach ($descriptionNodes as $node) {
            if ($node && trim($node->textContent)) {
                $descriptionContent = trim($node->textContent);
                break;
            }
        }
        
        // Если не нашли описание в основных местах, ищем в хлебных крошках
        if (empty($descriptionContent)) {
            $breadcrumbsNodes = $xpath->query('//nav/ul[contains(@class, "emotion-")]/li/a/span');
            $breadcrumbs = [];
            
            foreach ($breadcrumbsNodes as $node) {
                $text = trim($node->textContent);
                if ($text && !in_array($text, ['Главная'])) {
                    $breadcrumbs[] = $text;
                }
            }
            
            if (!empty($breadcrumbs)) {
                $descriptionContent = 'Категории: ' . implode(', ', $breadcrumbs);
            }
        }
        
        $result['description'] = $descriptionContent;
        
        // Извлекаем категории из хлебных крошек - улучшенный метод
        $result['detected_categories'] = [];
        $breadcrumbsNodes = $xpath->query('//nav/ul[contains(@class, "emotion-")]/li/a/span');
        
        if ($breadcrumbsNodes->length > 0) {
            foreach ($breadcrumbsNodes as $node) {
                $categoryName = trim($node->textContent);
                if ($categoryName && strlen($categoryName) > 2 && !in_array($categoryName, ['Главная', 'Рецепты'])) {
                    $result['detected_categories'][] = $categoryName;
                }
            }
        } 
        
        // Альтернативный поиск категорий по ссылкам
        if (empty($result['detected_categories'])) {
            $keywordNodes = $xpath->query('//a[contains(@href, "/recepty/")]');
            foreach ($keywordNodes as $node) {
                $categoryName = trim($node->textContent);
                if (!empty($categoryName) && strlen($categoryName) > 2 && !in_array($categoryName, ['Главная', 'Рецепты', 'Все рецепты'])) {
                    $result['detected_categories'][] = $categoryName;
                }
            }
        }
        
        // Поиск категорий в метатегах
        $metaKeywords = $xpath->query('//meta[@name="keywords"]')->item(0);
        if ($metaKeywords && $metaKeywords->hasAttribute('content')) {
            $keywords = explode(',', $metaKeywords->getAttribute('content'));
            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (strlen($keyword) > 3 && strlen($keyword) < 30) {
                    $result['detected_categories'][] = ucfirst($keyword);
                }
            }
        }
        
        // Поиск категорий в заголовке
        if ($titleNode) {
            $title = mb_strtolower(trim($titleNode->textContent));
            // Популярные категории, которые могут быть в названии
            $popularCategories = ['торт', 'салат', 'суп', 'десерт', 'закуска', 'соус', 'выпечка', 'напиток', 'пирог', 'печенье', 'паста'];
            
            foreach ($popularCategories as $category) {
                if (strpos($title, $category) !== false) {
                    $result['detected_categories'][] = ucfirst($category);
                }
            }
        }
        
        // Удаляем дубликаты категорий
        $result['detected_categories'] = array_unique($result['detected_categories']);
        
        // Извлекаем ингредиенты с количествами - улучшенный метод
        $ingredients = [];
        $structuredIngredients = [];
        
        // Сначала пробуем найти ингредиенты в современной структуре сайта
        $ingredientRows = $xpath->query('//div[contains(@class, "emotion-1oyy8lz")]');
        
        if ($ingredientRows->length > 0) {
            foreach ($ingredientRows as $row) {
                $nameNode = $xpath->query('.//span[contains(@class, "emotion-mdupit")]//span[@itemprop="recipeIngredient"]', $row)->item(0);
                $quantityNode = $xpath->query('.//span[contains(@class, "emotion-bsdd3p")]', $row)->item(0);
                
                if ($nameNode && $quantityNode) {
                    $name = trim($nameNode->textContent);
                    $quantity = trim($quantityNode->textContent);
                    
                    // Добавляем в обычный список
                    $ingredients[] = "$name - $quantity";
                    
                    // Парсинг количества и единиц измерения
                    $parsedQuantity = $this->parseQuantity($quantity);
                    
                    // Добавляем в структурированный список
                    $structuredIngredients[] = [
                        'name' => $name,
                        'quantity' => $parsedQuantity['value'],
                        'unit' => $parsedQuantity['unit']
                    ];
                }
            }
        } else {
            // Если не нашли через новую структуру, пробуем старые методы
            $ingredientNodes = $xpath->query('//span[@itemprop="recipeIngredient"]');
            
            foreach ($ingredientNodes as $ingredientNode) {
                $quantityNode = null;
                $parentNode = $ingredientNode->parentNode;
                
                if ($parentNode) {
                    $parentNode = $parentNode->parentNode;
                    if ($parentNode) {
                        $parentNode = $parentNode->parentNode;
                        if ($parentNode && $parentNode->nextSibling) {
                            $quantityNode = $parentNode->nextSibling;
                        }
                    }
                }
                
                $ingredientName = trim($ingredientNode->textContent);
                $quantity = $quantityNode ? trim($quantityNode->textContent) : '';
                
                // Добавляем в обычный список
                $ingredients[] = $quantity ? "$ingredientName - $quantity" : $ingredientName;
                
                // Парсинг количества и единиц измерения
                $parsedQuantity = $this->parseQuantity($quantity);
                
                // Добавляем в структурированный список
                $structuredIngredients[] = [
                    'name' => $ingredientName,
                    'quantity' => $parsedQuantity['value'],
                    'unit' => $parsedQuantity['unit']
                ];
            }
        }
        
        // Проверяем, было ли успешно извлечено хоть что-то
        if (!empty($ingredients)) {
            $result['ingredients'] = implode("\n", $ingredients);
            $result['structured_ingredients'] = $structuredIngredients;
        }
        
        // Извлекаем инструкции и привязываем изображения к шагам
        $instructionSteps = [];
        $stepImages = [];
        $recipeImageUrls = []; // Только изображения, относящиеся непосредственно к рецепту
        
        // Получаем шаги инструкций
        $steps = $xpath->query('//div[@itemscope and @itemprop="recipeInstructions"]');
        
        foreach ($steps as $index => $step) {
            $textNode = $xpath->query('.//span[@itemprop="text"]', $step)->item(0);
            $imageNode = $xpath->query('.//picture//img', $step)->item(0);
            
            $stepNumber = $index + 1;
            $stepText = $textNode ? trim($textNode->textContent) : '';
            
            if ($stepText) {
                $instructionSteps[] = "Шаг $stepNumber: $stepText";
                
                // Если есть изображение шага, добавляем его как изображение, относящееся к рецепту
                if ($imageNode && $imageNode->hasAttribute('src')) {
                    $imageUrl = $imageNode->getAttribute('src');
                    $stepImages[$stepNumber] = $imageUrl;
                    
                    // Добавляем в список изображений рецепта
                    $recipeImageUrls[] = $imageUrl;
                    
                    // Также добавляем в общий список изображений
                    $result['image_urls'][] = $imageUrl;
                }
            }
        }
        
        // Если не нашли структурированные шаги, используем альтернативный метод
        if (empty($instructionSteps)) {
            $stepNodes = $xpath->query('//span[@itemprop="text"]');
            foreach ($stepNodes as $index => $node) {
                if ($node) {
                    $stepNumber = $index + 1;
                    $instructionSteps[] = "Шаг $stepNumber: " . trim($node->textContent);
                }
            }
        }
        
        // Сохраняем инструкции в структурированном виде
        $result['instructions'] = implode("\n", $instructionSteps);
        $result['step_images'] = $stepImages;
        
        // Извлекаем время приготовления
        $timeNode = $xpath->query('//span[@itemprop="cookTime"]')->item(0);
        if ($timeNode) {
            $result['cooking_time'] = (int)preg_replace('/[^0-9]/', '', $timeNode->textContent);
        }
        
        // Извлекаем главное изображение
        $mainImage = $xpath->query('//meta[@property="og:image"]')->item(0);
        if ($mainImage && $mainImage->hasAttribute('content')) {
            $mainImageUrl = $mainImage->getAttribute('content');
            $result['image_urls'][] = $mainImageUrl;
            $recipeImageUrls[] = $mainImageUrl; // Добавляем в список изображений рецепта
        }
        
        // Извлекаем все другие изображения рецепта с фотографиями шагов
        $stepImagesNodes = $xpath->query('//div[@itemscope and @itemprop="recipeInstructions"]//picture//img');
        foreach ($stepImagesNodes as $img) {
            if ($img && $img->hasAttribute('src')) {
                $src = $img->getAttribute('src');
                if (preg_match('/\.(jpg|jpeg|png|webp)/i', $src)) {
                    $result['image_urls'][] = $src;
                    $recipeImageUrls[] = $src;
                }
            }
        }
        
        // Сохраняем только уникальные изображения рецепта
        $result['recipe_image_urls'] = array_values(array_unique($recipeImageUrls));
        
        // Удаляем дубликаты всех изображений
        if (!empty($result['image_urls'])) {
            $result['image_urls'] = array_values(array_unique($result['image_urls']));
        }
        
        // Определяем количество порций - улучшенная версия
        $servingsNode = $xpath->query('//div[contains(@class, "emotion-1047m5l")]')->item(0);
        if ($servingsNode) {
            $servingsText = trim($servingsNode->textContent);
            // Используем регулярное выражение для извлечения числа
            if (preg_match('/(\d+)/', $servingsText, $matches)) {
                $result['servings'] = (int)$matches[1];
                \Log::info("Обнаружено количество порций: {$result['servings']}");
            }
        }
        
        // Если порции не найдены, ищем в других местах
        if (empty($result['servings'])) {
            // Ищем в блоке с порциями через ближайшие элементы
            $portionLabelNode = $xpath->query('//span[contains(@class, "emotion-") and contains(text(), "порции")]')->item(0);
            if ($portionLabelNode) {
                // Ищем ближайший элемент с числом
                $parentDiv = $portionLabelNode->parentNode;
                if ($parentDiv) {
                    $portionValueNode = $xpath->query('.//div[contains(@class, "emotion-")]', $parentDiv->parentNode)->item(0);
                    if ($portionValueNode && preg_match('/(\d+)/', $portionValueNode->textContent, $matches)) {
                        $result['servings'] = (int)$matches[1];
                        \Log::info("Обнаружено количество порций (запасной вариант): {$result['servings']}");
                    }
                }
            }
        }
        
        // Устанавливаем значение по умолчанию, если порции не найдены
        if (empty($result['servings'])) {
            $result['servings'] = 2; // Типичное значение по умолчанию
            \Log::info("Установлено количество порций по умолчанию: {$result['servings']}");
        }

        // Улучшенное извлечение энергетической ценности
        // Ищем данные в современном блоке питательной ценности
        $nutritionBlock = $xpath->query('//div[contains(@class, "emotion-1bpeio7")]')->item(0);
        if ($nutritionBlock) {
            \Log::info("Найден новый блок питательной ценности emotion-1bpeio7");
            
            // Извлекаем значения непосредственно из itemprop атрибутов
            $caloriesNode = $xpath->query('.//span[@itemprop="calories"]', $nutritionBlock)->item(0);
            if ($caloriesNode) {
                $result['calories'] = $this->parseNumber($caloriesNode->textContent);
                \Log::info("Извлечены калории из нового блока: {$result['calories']}");
            }
            
            $proteinsNode = $xpath->query('.//span[@itemprop="proteinContent"]', $nutritionBlock)->item(0);
            if ($proteinsNode) {
                $result['proteins'] = $this->parseNumber($proteinsNode->textContent);
                \Log::info("Извлечены белки из нового блока: {$result['proteins']}");
            }
            
            $fatsNode = $xpath->query('.//span[@itemprop="fatContent"]', $nutritionBlock)->item(0);
            if ($fatsNode) {
                $result['fats'] = $this->parseNumber($fatsNode->textContent);
                \Log::info("Извлечены жиры из нового блока: {$result['fats']}");
            }
            
            $carbsNode = $xpath->query('.//span[@itemprop="carbohydrateContent"]', $nutritionBlock)->item(0);
            if ($carbsNode) {
                $result['carbs'] = $this->parseNumber($carbsNode->textContent);
                \Log::info("Извлечены углеводы из нового блока: {$result['carbs']}");
            }
        }

        // Если не найдены данные в современном блоке, пробуем старые селекторы
        if (!isset($result['calories']) || $result['calories'] === null) {
            $nutritionSelectors = [
                'calories' => '//*[contains(text(), "калорийность") or contains(text(), "калорий") or contains(text(), "ккал")]/following::*[1]',
                'proteins' => '//*[contains(text(), "белки") or contains(text(), "белков")]/following::*[1]',
                'fats' => '//*[contains(text(), "жиры") or contains(text(), "жиров")]/following::*[1]',
                'carbs' => '//*[contains(text(), "углеводы") or contains(text(), "углеводов")]/following::*[1]',
            ];
            
            foreach ($nutritionSelectors as $nutrient => $selector) {
                $nodes = $xpath->query($selector);
                if ($nodes->length > 0) {
                    $value = $this->parseNumber($nodes->item(0)->textContent);
                    if ($value !== null) {
                        $result[$nutrient] = $value;
                        \Log::info("Извлечено значение для {$nutrient}: {$value}");
                    }
                }
            }
        }
        
        // Также проверяем блок с классом emotion-13pa6yw который содержит питательные вещества
        if (!isset($result['calories']) || $result['calories'] === null) {
            $nutritionValueBlock = $xpath->query('//div[contains(@class, "emotion-13pa6yw")]')->item(0);
            if ($nutritionValueBlock) {
                \Log::info("Найден блок питательной ценности emotion-13pa6yw");
                
                // Получаем все значения из блока
                $valueNodes = $xpath->query('.//div[contains(@class, "emotion-16si75h")]', $nutritionValueBlock);
                if ($valueNodes && $valueNodes->length >= 4) {
                    // Предполагаем порядок: калории, белки, жиры, углеводы
                    $result['calories'] = $this->parseNumber($valueNodes->item(0)->textContent);
                    $result['proteins'] = $this->parseNumber($valueNodes->item(1)->textContent);
                    $result['fats'] = $this->parseNumber($valueNodes->item(2)->textContent);
                    $result['carbs'] = $this->parseNumber($valueNodes->item(3)->textContent);
                    
                    \Log::info("Извлечена питательная ценность из блока emotion-13pa6yw: " . 
                              "калории={$result['calories']}, белки={$result['proteins']}, " . 
                              "жиры={$result['fats']}, углеводы={$result['carbs']}");
                }
            }
        }
        
        // Также ищем в специфичных для eda.ru блоках
        $nutritionBlock = $xpath->query('//div[contains(@class, "emotion-") and contains(., "калорийность")]')->item(0);
        if ($nutritionBlock) {
            // Калории
            $caloriesNode = $xpath->query('.//*[contains(text(), "калорийность") or contains(text(), "калорий") or contains(text(), "ккал")]', $nutritionBlock)->item(0);
            if ($caloriesNode) {
                $parentNode = $caloriesNode->parentNode;
                if ($parentNode) {
                    $valueNode = $xpath->query('.//*[contains(@class, "emotion-")]', $parentNode)->item(0);
                    if ($valueNode) {
                        $result['calories'] = $this->parseNumber($valueNode->textContent);
                        \Log::info("Извлечены калории из блока: {$result['calories']}");
                    }
                }
            }
            
            // Белки
            $proteinsNode = $xpath->query('.//*[contains(text(), "белки")]', $nutritionBlock)->item(0);
            if ($proteinsNode) {
                $parentNode = $proteinsNode->parentNode;
                if ($parentNode) {
                    $valueNode = $xpath->query('.//*[contains(@class, "emotion-")]', $parentNode)->item(0);
                    if ($valueNode) {
                        $result['proteins'] = $this->parseNumber($valueNode->textContent);
                        \Log::info("Извлечены белки из блока: {$result['proteins']}");
                    }
                }
            }
            
            // Жиры
            $fatsNode = $xpath->query('.//*[contains(text(), "жиры")]', $nutritionBlock)->item(0);
            if ($fatsNode) {
                $parentNode = $fatsNode->parentNode;
                if ($parentNode) {
                    $valueNode = $xpath->query('.//*[contains(@class, "emotion-")]', $parentNode)->item(0);
                    if ($valueNode) {
                        $result['fats'] = $this->parseNumber($valueNode->textContent);
                        \Log::info("Извлечены жиры из блока: {$result['fats']}");
                    }
                }
            }
            
            // Углеводы
            $carbsNode = $xpath->query('.//*[contains(text(), "углеводы")]', $nutritionBlock)->item(0);
            if ($carbsNode) {
                $parentNode = $carbsNode->parentNode;
                if ($parentNode) {
                    $valueNode = $xpath->query('.//*[contains(@class, "emotion-")]', $parentNode)->item(0);
                    if ($valueNode) {
                        $result['carbs'] = $this->parseNumber($valueNode->textContent);
                        \Log::info("Извлечены углеводы из блока: {$result['carbs']}");
                    }
                }
            }
        }
        
        return $result;
    }
}
