<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Recipe;
use App\Models\Tag;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;
use Exception;
use Illuminate\Support\Facades\DB;

class RecipeParserController extends Controller
{
    /**
     * Отображение формы для парсинга рецепта
     */
    public function index()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.parser.index', compact('categories'));
    }

    /**
     * Обработка запроса на парсинг рецепта
     */
    public function parse(Request $request)
    {
        $request->validate([
            'url' => 'required|url',
            'categories' => 'nullable|array',
        ]);

        $url = $request->input('url');
    
        // Проверяем, существует ли уже рецепт с таким URL
        $existingRecipe = Recipe::where('source_url', $url)->first();
        
        if ($existingRecipe) {
            return redirect()->back()->with('error', 'Рецепт с данным URL уже добавлен в базу данных ранее.');
        }

        try {
            // Получаем HTML-содержимое по указанному URL
            $response = Http::get($request->url);
            
            if (!$response->successful()) {
                return back()->with('error', 'Не удалось получить доступ к указанной странице. Код ответа: ' . $response->status());
            }
            
            $html = $response->body();
            
            // Парсим HTML для извлечения данных рецепта
            $parseResult = $this->parseRecipeData($html, $request->url);
            
            if (empty($parseResult['title'])) {
                return back()->with('error', 'Не удалось извлечь название рецепта с указанной страницы.');
            }
            
            // Получаем исходные выбранные категории
            $selectedCategories = $request->categories ?? [];
            
            // Проверяем существующие и добавляем обнаруженные категории
            $categories = Category::orderBy('name')->get();
            $categoryMap = [];
            $newCategories = [];
            
            if (!empty($parseResult['detected_categories'])) {
                // Создаем мапу существующих категорий для быстрого поиска
                foreach ($categories as $category) {
                    $categoryMap[mb_strtolower($category->name)] = $category->id;
                }
                
                // Проверяем каждую обнаруженную категорию
                foreach ($parseResult['detected_categories'] as $detectedCategory) {
                    $categoryName = trim($detectedCategory);
                    $categoryKey = mb_strtolower($categoryName);
                    
                    if (isset($categoryMap[$categoryKey])) {
                        // Если категория существует, добавляем её ID в список выбранных
                        $selectedCategories[] = $categoryMap[$categoryKey];
                    } else {
                        // Если категории нет, создаем новую
                        try {
                            $newCategory = new Category();
                            $newCategory->name = $categoryName;
                            $newCategory->slug = Str::slug($categoryName);
                            $newCategory->save();
                            
                            // Добавляем новую категорию в список выбранных
                            $selectedCategories[] = $newCategory->id;
                            $newCategories[] = $newCategory;
                            
                            // Обновляем карту категорий
                            $categoryMap[$categoryKey] = $newCategory->id;
                        } catch (Exception $e) {
                            \Log::warning("Не удалось создать категорию: $categoryName. Ошибка: " . $e->getMessage());
                        }
                    }
                }
                
                // Удаляем дубликаты
                $selectedCategories = array_unique($selectedCategories);
            }
            
            // Добавляем теги, если они были обнаружены
            $tagMap = [];
            $newTags = [];
            
            if (!empty($parseResult['detected_tags'])) {
                // Получаем существующие теги
                $existingTags = Tag::whereIn('name', $parseResult['detected_tags'])->get();
                
                foreach ($existingTags as $tag) {
                    $tagMap[strtolower($tag->name)] = $tag->id;
                }
                
                // Добавляем новые теги, которых нет в базе
                foreach ($parseResult['detected_tags'] as $tagName) {
                    if (!isset($tagMap[strtolower($tagName)])) {
                        $tag = new Tag();
                        $tag->name = $tagName;
                        $tag->slug = Str::slug($tagName);
                        $tag->save();
                        
                        $tagMap[strtolower($tagName)] = $tag->id;
                        $newTags[] = $tag;
                    }
                }
            }
            
            // Добавляем информацию о количестве порций, если она была извлечена
            if (isset($parseResult['servings'])) {
                $parseResult['servings'] = $parseResult['servings'];
            } else {
                $parseResult['servings'] = null;
            }
            
            // Получаем обновленный список всех категорий
            $categories = Category::orderBy('name')->get();
            $tags = Tag::orderBy('name')->get();
            
            // Отображаем форму предпросмотра с полученными данными
            return view('admin.parser.preview', compact('parseResult', 'categories', 'selectedCategories', 'newCategories', 'tags', 'tagMap', 'newTags'));
            
        } catch (Exception $e) {
            return back()->with('error', 'Произошла ошибка при обработке запроса: ' . $e->getMessage());
        }
    }
    
    /**
     * Сохранение импортированного рецепта
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'ingredients' => 'required|string',
            'instructions' => 'required|string',
            'cooking_time' => 'nullable|integer|min:1',
            'calories' => 'nullable|integer|min:0',
            'proteins' => 'nullable|integer|min:0',
            'fats' => 'nullable|integer|min:0',
            'carbs' => 'nullable|integer|min:0',
            'source_url' => 'required|url',
            'image_urls' => 'nullable|array',
            'categories' => 'nullable|array',
            'structured_ingredients' => 'nullable|json',
            'servings' => 'nullable|integer|min:1',
            'tags' => 'nullable|array', // Добавляем теги
        ]);
        
        try {
            // Создаем новый рецепт
            $recipe = new Recipe();
            $recipe->title = $request->title;
            $recipe->slug = Str::slug($request->title);
            $recipe->description = $request->description;
            $recipe->ingredients = $request->ingredients;
            $recipe->instructions = $request->instructions;
            $recipe->cooking_time = $request->cooking_time;
            $recipe->calories = $request->calories;
            $recipe->proteins = $request->proteins;
            $recipe->fats = $request->fats;
            $recipe->carbs = $request->carbs;
            $recipe->source_url = $request->source_url;
            $recipe->is_published = $request->has('is_published');
            $recipe->servings = $request->servings;
            
            // Массив для хранения путей к сохраненным изображениям
            $savedImages = [];
            
            // Сохраняем все выбранные изображения
            if ($request->has('image_urls') && is_array($request->image_urls)) {
                // Первое изображение используем как главное для рецепта
                if (!empty($request->image_urls[0])) {
                    $imageContent = file_get_contents($request->image_urls[0]);
                    $imageName = 'recipes/' . Str::random(40) . '.jpg';
                    
                    Storage::disk('public')->put($imageName, $imageContent);
                    $recipe->image_url = Storage::url($imageName);
                }
                
                // Сохраняем все остальные изображения для использования в шагах
                foreach ($request->image_urls as $index => $imageUrl) {
                    if ($index === 0) continue; // Пропускаем первое изображение, так как оно уже сохранено
                    
                    try {
                        $imageContent = file_get_contents($imageUrl);
                        $imageName = 'recipe_steps/' . Str::random(40) . '.jpg';
                        
                        Storage::disk('public')->put($imageName, $imageContent);
                        $savedImages[] = [
                            'original_url' => $imageUrl,
                            'saved_path' => Storage::url($imageName)
                        ];
                    } catch (Exception $e) {
                        // Логирование ошибки, но продолжение выполнения
                        \Log::warning("Не удалось сохранить изображение: {$imageUrl}. Ошибка: {$e->getMessage()}");
                    }
                }
            }
            
            // Сохраняем структурированные ингредиенты в additional_data
            $additionalData = [];
            
            if ($request->structured_ingredients) {
                $additionalData['structured_ingredients'] = json_decode($request->structured_ingredients, true);
            }

            // Сохраняем обнаруженные теги, если они есть
            if (!empty($request->detected_tags)) {
                $additionalData['detected_tags'] = $request->detected_tags;
            }
            
            // Сохраняем информацию о дополнительных изображениях и прочих данных в JSON поле
            if (!empty($savedImages) || !empty($additionalData)) {
                $existingData = [];
                
                if (!empty($savedImages)) {
                    $existingData['step_images'] = $savedImages;
                }
                
                if (!empty($additionalData)) {
                    $existingData = array_merge($existingData, $additionalData);
                }
                
                $recipe->additional_data = json_encode($existingData);
            }
            
            $recipe->save();
            
            // Привязываем категории, если они выбраны
            if ($request->has('categories') && is_array($request->categories) && !empty($request->categories)) {
                $recipe->categories()->attach($request->categories);
            }
            
            // Привязываем теги, если они выбраны
            if ($request->has('tags') && is_array($request->tags) && !empty($request->tags)) {
                $recipe->tags()->sync($request->tags);
            }
            
            return redirect()->route('admin.recipesedit', $recipe->id)
                ->with('success', 'Рецепт успешно сохранен!');
                
        } catch (Exception $e) {
            return back()->with('error', 'Произошла ошибка при сохранении рецепта: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Парсинг данных рецепта из HTML
     */
    private function parseRecipeData($html, $sourceUrl)
    {
        $result = [
            'title' => '',
            'description' => '',
            'ingredients' => '',
            'instructions' => '',
            'cooking_time' => null,
            'servings' => null,
            'calories' => null,
            'proteins' => null,
            'fats' => null,
            'carbs' => null,
            'image_urls' => [],
            'recipe_image_urls' => [], // Изображения, точно относящиеся к рецепту
            'source_url' => $sourceUrl,
            'detected_tags' => [], // Добавляем поле для хранения обнаруженных тегов
        ];
        
        // Создаем DOM документ
        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        $xpath = new DOMXPath($dom);
        
        // Пытаемся найти микроразметку JSON-LD (schema.org)
        $jsonLdScripts = $xpath->query('//script[@type="application/ld+json"]');
        foreach ($jsonLdScripts as $script) {
            $jsonData = json_decode($script->textContent, true);
            
            if (isset($jsonData['@type']) && ($jsonData['@type'] === 'Recipe' || (is_array($jsonData['@type']) && in_array('Recipe', $jsonData['@type'])))) {
                $result = $this->extractFromJsonLd($jsonData, $result);
                
                // Извлекаем теги из ключевых слов JSON-LD
                if (isset($jsonData['keywords'])) {
                    $keywords = explode(',', $jsonData['keywords']);
                    foreach ($keywords as $keyword) {
                        $keyword = trim($keyword);
                        if (strlen($keyword) > 2 && strlen($keyword) < 50) {
                            $result['detected_tags'][] = $keyword;
                        }
                    }
                }
            }
            
            // Проверяем случай, когда JSON-LD находится в массиве @graph
            if (isset($jsonData['@graph']) && is_array($jsonData['@graph'])) {
                foreach ($jsonData['@graph'] as $graphItem) {
                    if (isset($graphItem['@type']) && ($graphItem['@type'] === 'Recipe' || (is_array($graphItem['@type']) && in_array('Recipe', $graphItem['@type'])))) {
                        $result = $this->extractFromJsonLd($graphItem, $result);
                        if (!empty($result['title'])) {
                            return $result;
                        }
                    }
                }
            }
        }
        
        // Если JSON-LD не найден, пробуем парсить через DOM для eda.ru
        if (strpos($sourceUrl, 'eda.ru') !== false) {
            $result = $this->parseEdaRu($xpath, $result);
        } else {
            // Обобщенный парсинг для других сайтов
            $result = $this->parseGeneric($xpath, $result);
        }
        
        // Очистка и дедупликация тегов
        if (!empty($result['detected_tags'])) {
            $result['detected_tags'] = array_unique($result['detected_tags']);
            $result['detected_tags'] = array_filter($result['detected_tags'], function($tag) {
                return mb_strlen(trim($tag)) > 2 && mb_strlen(trim($tag)) < 50;
            });
            $result['detected_tags'] = array_values($result['detected_tags']);
        }
        
        return $result;
    }
    
    /**
     * Извлечение данных рецепта из JSON-LD
     */
    private function extractFromJsonLd($jsonData, $result)
    {
        if (isset($jsonData['name'])) {
            $result['title'] = $jsonData['name'];
        }
        
        if (isset($jsonData['description'])) {
            $result['description'] = $jsonData['description'];
        }
        
        // Ингредиенты
        if (isset($jsonData['recipeIngredient']) && is_array($jsonData['recipeIngredient'])) {
            $result['ingredients'] = implode("\n", $jsonData['recipeIngredient']);
        }
        
        // Инструкции
        if (isset($jsonData['recipeInstructions'])) {
            if (is_array($jsonData['recipeInstructions'])) {
                $instructions = [];
                foreach ($jsonData['recipeInstructions'] as $step) {
                    if (is_string($step)) {
                        $instructions[] = $step;
                    } elseif (is_array($step) && isset($step['text'])) {
                        $instructions[] = $step['text'];
                    }
                }
                $result['instructions'] = implode("\n", $instructions);
            } elseif (is_string($jsonData['recipeInstructions'])) {
                $result['instructions'] = $jsonData['recipeInstructions'];
            }
        }
        
        // Время приготовления
        if (isset($jsonData['cookTime'])) {
            $result['cooking_time'] = $this->parseDuration($jsonData['cookTime']);
        } elseif (isset($jsonData['totalTime'])) {
            $result['cooking_time'] = $this->parseDuration($jsonData['totalTime']);
        }
        
        // Изображения
        if (isset($jsonData['image'])) {
            if (is_string($jsonData['image'])) {
                $result['image_urls'][] = $jsonData['image'];
            } elseif (is_array($jsonData['image'])) {
                if (isset($jsonData['image']['url'])) {
                    $result['image_urls'][] = $jsonData['image']['url'];
                } else {
                    foreach ($jsonData['image'] as $image) {
                        if (is_string($image)) {
                            $result['image_urls'][] = $image;
                        } elseif (is_array($image) && isset($image['url'])) {
                            $result['image_urls'][] = $image['url'];
                        }
                    }
                }
            }
        }

        // Количество порций (servings)
        if (isset($jsonData['recipeYield'])) {
            // Может быть как строкой, так и числом
            $servings = $jsonData['recipeYield'];
            if (is_array($servings)) {
                $servings = $servings[0] ?? '';
            }
            $result['servings'] = $this->parseNumber($servings);
            \Log::info("Извлечено порций из JSON-LD: {$result['servings']}");
        }

        // Калорийность и нутриенты - улучшенное извлечение
        if (isset($jsonData['nutrition']) && is_array($jsonData['nutrition'])) {
            // Калории могут быть записаны в разных форматах
            if (isset($jsonData['nutrition']['calories'])) {
                $result['calories'] = $this->parseNumber($jsonData['nutrition']['calories']);
                \Log::info("Извлечены калории из JSON-LD: {$result['calories']}");
            }
            
            // Белки
            if (isset($jsonData['nutrition']['proteinContent'])) {
                $result['proteins'] = $this->parseNumber($jsonData['nutrition']['proteinContent']);
                \Log::info("Извлечены белки из JSON-LD: {$result['proteins']}");
            }
            
            // Жиры
            if (isset($jsonData['nutrition']['fatContent'])) {
                $result['fats'] = $this->parseNumber($jsonData['nutrition']['fatContent']);
                \Log::info("Извлечены жиры из JSON-LD: {$result['fats']}");
            }
            
            // Углеводы
            if (isset($jsonData['nutrition']['carbohydrateContent'])) {
                $result['carbs'] = $this->parseNumber($jsonData['nutrition']['carbohydrateContent']);
                \Log::info("Извлечены углеводы из JSON-LD: {$result['carbs']}");
            }
        } 
        // Проверяем альтернативные форматы для калорий
        elseif (isset($jsonData['calories'])) {
            $result['calories'] = $this->parseNumber($jsonData['calories']);
            \Log::info("Извлечены калории из прямого поля JSON-LD: {$result['calories']}");
        }
        
        // Дополнительная проверка для нутриентов в корне объекта JSON-LD
        if (isset($jsonData['proteinContent'])) {
            $result['proteins'] = $this->parseNumber($jsonData['proteinContent']);
            \Log::info("Извлечены белки из прямого поля JSON-LD: {$result['proteins']}");
        }
        
        if (isset($jsonData['fatContent'])) {
            $result['fats'] = $this->parseNumber($jsonData['fatContent']);
            \Log::info("Извлечены жиры из прямого поля JSON-LD: {$result['fats']}");
        }
        
        if (isset($jsonData['carbohydrateContent'])) {
            $result['carbs'] = $this->parseNumber($jsonData['carbohydrateContent']);
            \Log::info("Извлечены углеводы из прямого поля JSON-LD: {$result['carbs']}");
        }

        // Извлекаем теги из ключевых слов
        if (isset($jsonData['keywords'])) {
            if (is_string($jsonData['keywords'])) {
                $keywords = explode(',', $jsonData['keywords']);
                foreach ($keywords as $keyword) {
                    $keyword = trim($keyword);
                    if (strlen($keyword) > 2 && strlen($keyword) < 50) {
                        $result['detected_tags'][] = $keyword;
                    }
                }
            } elseif (is_array($jsonData['keywords'])) {
                foreach ($jsonData['keywords'] as $keyword) {
                    $keyword = trim($keyword);
                    if (strlen($keyword) > 2 && strlen($keyword) < 50) {
                        $result['detected_tags'][] = $keyword;
                    }
                }
            }
        }
        
        // Если в JSON-LD есть рецептКатегори, добавляем их как теги
        if (isset($jsonData['recipeCategory'])) {
            if (is_string($jsonData['recipeCategory'])) {
                $result['detected_tags'][] = trim($jsonData['recipeCategory']);
            } elseif (is_array($jsonData['recipeCategory'])) {
                foreach ($jsonData['recipeCategory'] as $category) {
                    $result['detected_tags'][] = trim($category);
                }
            }
        }

        return $result;
    }
    
    /**
     * Парсинг данных с сайта eda.ru
     */
    private function parseEdaRu($xpath, $result)
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
        
        // --- Новая часть: Извлечение питательной ценности для eda.eu ---
        $nutritionNode = $xpath->query('//*[@itemprop="nutrition"]')->item(0);
        if ($nutritionNode) {
            $caloriesNode = $xpath->query('.//*[@itemprop="calories"]', $nutritionNode)->item(0);
            $proteinNode = $xpath->query('.//*[@itemprop="proteinContent"]', $nutritionNode)->item(0);
            $fatNode = $xpath->query('.//*[@itemprop="fatContent"]', $nutritionNode)->item(0);
            $carbNode = $xpath->query('.//*[@itemprop="carbohydrateContent"]', $nutritionNode)->item(0);
            
            if ($caloriesNode) {
                $result['calories'] = $this->parseNumber($caloriesNode->textContent);
                \Log::info("Извлечены калории (eda.eu): {$result['calories']}");
            }
            if ($proteinNode) {
                $result['proteins'] = $this->parseNumber($proteinNode->textContent);
                \Log::info("Извлечены белки (eda.eu): {$result['proteins']}");
            }
            if ($fatNode) {
                $result['fats'] = $this->parseNumber($fatNode->textContent);
                \Log::info("Извлечены жиры (eda.eu): {$result['fats']}");
            }
            if ($carbNode) {
                $result['carbs'] = $this->parseNumber($carbNode->textContent);
                \Log::info("Извлечены углеводы (eda.eu): {$result['carbs']}");
            }
        }
        // ---------------------------------------------------------------------
        
        // Извлекаем теги с eda.ru - ищем их в различных местах
        // 1. Ищем в мета-тегах keywords
        $metaKeywords = $xpath->query('//meta[@name="keywords"]')->item(0);
        if ($metaKeywords && $metaKeywords->hasAttribute('content')) {
            $keywords = explode(',', $metaKeywords->getAttribute('content'));
            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (mb_strlen($keyword) > 2 && mb_strlen($keyword) < 50) {
                    $result['detected_tags'][] = $keyword;
                }
            }
        }
        
        // 2. Ищем теги в специальных блоках тегов
        $tagNodes = $xpath->query('//a[contains(@href, "/tag/") or contains(@href, "/tag=")]');
        foreach ($tagNodes as $node) {
            $tagText = trim($node->textContent);
            if (mb_strlen($tagText) > 2 && mb_strlen($tagText) < 50) {
                $result['detected_tags'][] = $tagText;
            }
        }
        
        // 3. Ищем связанные рецепты как источник тегов
        $relatedRecipes = $xpath->query('//div[contains(@class, "emotion-")]/a[contains(@href, "/recepty/")]');
        foreach ($relatedRecipes as $node) {
            $recipeTitle = trim($node->textContent);
            // Преобразуем название рецепта в теги (разбиваем по словам)
            $words = preg_split('/\s+/', $recipeTitle);
            foreach ($words as $word) {
                $word = trim($word);
                // Берем только значимые слова для тегов
                if (mb_strlen($word) > 3 && mb_strlen($word) < 20) {
                    $result['detected_tags'][] = $word;
                }
            }
        }
        
        // 4. Основные ингредиенты как теги
        $ingredientNodes = $xpath->query('//span[@itemprop="recipeIngredient"]');
        $mainIngredients = [];
        foreach ($ingredientNodes as $node) {
            $ingredient = trim($node->textContent);
            $parts = explode(' ', $ingredient);
            if (count($parts) > 0) {
                $mainIngredient = trim(end($parts));
                if (mb_strlen($mainIngredient) > 3 && mb_strlen($mainIngredient) < 20) {
                    $mainIngredients[] = $mainIngredient;
                }
            }
        }
        
        // Добавляем основные ингредиенты как теги
        if (!empty($mainIngredients)) {
            $result['detected_tags'] = array_merge($result['detected_tags'], $mainIngredients);
        }
        
        return $result;
    }
    
    /**
     * Парсинг данных с общих сайтов
     */
    private function parseGeneric($xpath, $result)
    {
        // Ищем заголовок рецепта в разных местах
        $titleCandidates = [
            '//h1',
            '//meta[@property="og:title"]/@content',
            '//meta[@name="title"]/@content',
            '//h1[contains(@class, "title")]',
            '//h1[contains(@class, "recipe")]',
            '//article//h1',
            '//div[contains(@class, "recipe")]//h1'
        ];
        
        foreach ($titleCandidates as $query) {
            $titleNode = $xpath->query($query)->item(0);
            if ($titleNode) {
                $result['title'] = trim($titleNode->textContent);
                break;
            }
        }
        
        // Ищем описание
        $descriptionCandidates = [
            '//meta[@name="description"]/@content',
            '//div[contains(@class, "description")]',
            '//div[contains(@class, "summary")]',
            '//div[contains(@class, "excerpt")]',
            '//p[contains(@class, "lead")]'
        ];
        
        foreach ($descriptionCandidates as $query) {
            $descNode = $xpath->query($query)->item(0);
            if ($descNode) {
                $result['description'] = trim($descNode->textContent ?? $descNode->value);
                break;
            }
        }
        
        // Ищем ингредиенты
        $ingredientsCandidates = [
            '//ul[contains(@class, "ingredient")]//li',
            '//div[contains(@class, "ingredient")]//li',
            '//div[contains(@id, "ingredient")]//li',
            '//ul[contains(@class, "ingredients")]//li',
            '//div[contains(@class, "ingredients")]//li'
        ];
        
        $ingredients = [];
        foreach ($ingredientsCandidates as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length > 0) {
                foreach ($nodes as $node) {
                    $ingredients[] = trim($node->textContent);
                }
                break;
            }
        }
        
        if (!empty($ingredients)) {
            $result['ingredients'] = implode("\n", $ingredients);
        }
        
        // Ищем инструкции
        $instructionsCandidates = [
            '//div[contains(@class, "instruction")]//li',
            '//div[contains(@id, "instruction")]//li',
            '//div[contains(@class, "direction")]//li',
            '//div[contains(@class, "method")]//li',
            '//ol[contains(@class, "instructions")]//li',
            '//div[contains(@class, "steps")]//p'
        ];
        
        $instructions = [];
        foreach ($instructionsCandidates as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length > 0) {
                foreach ($nodes as $node) {
                    $instructions[] = trim($node->textContent);
                }
                break;
            }
        }
        
        if (!empty($instructions)) {
            $result['instructions'] = implode("\n", $instructions);
        }
        
        // Ищем изображения
        $imageCandidates = [
            '//meta[@property="og:image"]/@content',
            '//div[contains(@class, "recipe")]//img/@src',
            '//article//img/@src',
            '//figure//img/@src'
        ];
        
        foreach ($imageCandidates as $query) {
            $nodes = $xpath->query($query);
            foreach ($nodes as $node) {
                $url = $node->value ?? $node->textContent;
                if (!empty($url) && filter_var($url, FILTER_VALIDATE_URL)) {
                    $result['image_urls'][] = $url;
                } elseif (!empty($url) && substr($url, 0, 2) === '//') {
                    // Для URL без протокола
                    $result['image_urls'][] = 'https:' . $url;
                } elseif (!empty($url) && substr($url, 0, 1) === '/') {
                    // Для относительных URL
                    $urlParts = parse_url($result['source_url']);
                    $result['image_urls'][] = $urlParts['scheme'] . '://' . $urlParts['host'] . $url;
                }
            }
            if (!empty($result['image_urls'])) {
                break;
            }
        }
        
        // Поиск категорий
        $categories = [];
        
        // Поиск категорий в хлебных крошках
        $breadcrumbCandidates = [
            '//nav[contains(@class, "breadcrumb")]//li',
            '//div[contains(@class, "breadcrumb")]//a',
            '//ul[contains(@class, "breadcrumb")]//li',
            '//div[contains(@class, "breadcrumb")]//span'
        ];
        
        foreach ($breadcrumbCandidates as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length > 0) {
                foreach ($nodes as $node) {
                    $categoryName = trim($node->textContent);
                    if (!empty($categoryName) && !in_array(mb_strtolower($categoryName), ['главная', 'home', 'рецепты', 'recipes'])) {
                        $categories[] = $categoryName;
                    }
                }
                break;
            }
        }
        
        // Поиск категорий в метатегах
        $metaKeywords = $xpath->query('//meta[@name="keywords"]')->item(0);
        if ($metaKeywords && $metaKeywords->hasAttribute('content')) {
            $keywords = explode(',', $metaKeywords->getAttribute('content'));
            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (strlen($keyword) > 3 && strlen($keyword) < 30) {
                    $categories[] = ucfirst($keyword);
                }
            }
        }
        
        // Поиск категорий по тегам или меткам
        $tagCandidates = [
            '//div[contains(@class, "tag")]//a',
            '//div[contains(@class, "category")]//a',
            '//span[contains(@class, "category")]',
            '//a[contains(@href, "category")]'
        ];
        
        foreach ($tagCandidates as $query) {
            $nodes = $xpath->query($query);
            if ($nodes->length > 0) {
                foreach ($nodes as $node) {
                    $categoryName = trim($node->textContent);
                    if (!empty($categoryName) && strlen($categoryName) > 3 && strlen($categoryName) < 30) {
                        $categories[] = $categoryName;
                    }
                }
            }
        }
        
        // Удаляем дубликаты и добавляем в результат
        if (!empty($categories)) {
            $result['detected_categories'] = array_unique($categories);
        }

        // Попытка извлечь данные о питательной ценности
        $nutritionCandidates = [
            // Калории
            'calories' => [
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "Калорийность")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "калории")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "ккал")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "Калорийность")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "калории")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "ккал")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "Калорийность")]/following-sibling::*',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "калории")]/following-sibling::*',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "ккал")]/following-sibling::*',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "калорийность")]//td[last()]',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "калории")]//td[last()]',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "ккал")]//td[last()]',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "калорийность")]/following-sibling::*',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "калории")]/following-sibling::*',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "ккал")]/following-sibling::*',
                '//*[contains(text(), "Пищевая ценность")]/following::*[contains(text(), "ккал") or contains(text(), "калории")]',
                '//meta[@name="caloricity" or @property="caloricity"]/@content',
                '//span[@data-name="calories"]',
                '//*[contains(@class, "calories") or contains(@id, "calories")]'
            ],
            
            // Белки
            'proteins' => [
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "Белки")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "белков")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "Белки")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "белков")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "Белки")]/following-sibling::*',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "белков")]/following-sibling::*',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "белки")]//td[last()]',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "белков")]//td[last()]',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "белки")]/following-sibling::*',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "белков")]/following-sibling::*',
                '//*[contains(text(), "Пищевая ценность")]/following::*[contains(text(), "белки") or contains(text(), "белков")]',
                '//meta[@name="proteins" or @property="proteins"]/@content',
                '//span[@data-name="proteins"]',
                '//*[contains(@class, "proteins") or contains(@id, "proteins")]'
            ],
            
            // Жиры
            'fats' => [
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "Жиры")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "жиров")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "Жиры")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "жиров")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "Жиры")]/following-sibling::*',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "жиров")]/following-sibling::*',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "жиры")]//td[last()]',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "жиров")]//td[last()]',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "жиры")]/following-sibling::*',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "жиров")]/following-sibling::*',
                '//*[contains(text(), "Пищевая ценность")]/following::*[contains(text(), "жиры") or contains(text(), "жиров")]',
                '//meta[@name="fats" or @property="fats"]/@content',
                '//span[@data-name="fats"]',
                '//*[contains(@class, "fats") or contains(@id, "fats")]'
            ],
            
            // Углеводы
            'carbs' => [
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "Углеводы")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//span[contains(text(), "углеводов")]/following-sibling::span',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "Углеводы")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//dt[contains(text(), "углеводов")]/following-sibling::dd',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "Углеводы")]/following-sibling::*',
                '//div[contains(@class, "nutrition") or contains(@class, "nutrients")]//*[contains(text(), "углеводов")]/following-sibling::*',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "углеводы")]//td[last()]',
                '//table[contains(@class, "nutrition") or contains(@class, "nutrients")]//tr[contains(., "углеводов")]//td[last()]',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "углеводы")]/following-sibling::*',
                '//div[contains(@itemprop, "nutrition")]//*[contains(text(), "углеводов")]/following-sibling::*',
                '//*[contains(text(), "Пищевая ценность")]/following::*[contains(text(), "углеводы") or contains(text(), "углеводов")]',
                '//meta[@name="carbohydrates" or @property="carbohydrates"]/@content',
                '//span[@data-name="carbohydrates"]',
                '//*[contains(@class, "carbs") or contains(@id, "carbs") or contains(@class, "carbohydrates") or contains(@id, "carbohydrates")]'
            ]
        ];

        // Перебираем все селекторы для каждого типа питательного вещества
        foreach ($nutritionSelectors as $nutrient => $selectors) {
            foreach ($selectors as $selector) {
                if (isset($result[$nutrient]) && $result[$nutrient] !== null) {
                    // Если значение уже найдено, прекращаем поиск
                    break;
                }
                
                $nodes = $xpath->query($selector);
                if ($nodes && $nodes->length > 0) {
                    \Log::info("Найден селектор для {$nutrient}: {$selector}, количество узлов: {$nodes->length}");
                    
                    foreach ($nodes as $node) {
                        if ($node->nodeType === XML_ATTRIBUTE_NODE) {
                            $text = $node->value;
                        } else {
                            $text = $node->textContent;
                        }
                        
                        $value = $this->parseNumber($text);
                        if ($value !== null) {
                            $result[$nutrient] = $value;
                            \Log::info("Извлечено значение для {$nutrient}: {$value} из текста '{$text}'");
                            break;
                        }
                    }
                }
            }
        }

        // Проверяем наличие микроданных с атрибутами itemprop
        $nutritionProps = [
            'calories' => 'calories',
            'proteins' => 'proteinContent',
            'fats' => 'fatContent',
            'carbs' => 'carbohydrateContent'
        ];
        
        foreach ($nutritionProps as $nutrient => $prop) {
            if (isset($result[$nutrient]) && $result[$nutrient] !== null) {
                continue;
            }
            
            $propNodes = $xpath->query("//*[@itemprop='{$prop}']");
            if ($propNodes && $propNodes->length > 0) {
                \Log::info("Найден микроданные itemprop для {$nutrient}: {$prop}");
                
                foreach ($propNodes as $node) {
                    if ($node->hasAttribute('content')) {
                        $value = $this->parseNumber($node->getAttribute('content'));
                        if ($value !== null) {
                            $result[$nutrient] = $value;
                            \Log::info("Извлечено значение для {$nutrient} из атрибута content: {$value}");
                            break;
                        }
                    }
                    
                    $value = $this->parseNumber($node->textContent);
                    if ($value !== null) {
                        $result[$nutrient] = $value;
                        \Log::info("Извлечено значение для {$nutrient} из текста: {$value}");
                        break;
                    }
                }
            }
        }

        // Поиск питательной ценности в отдельных блоках данных (часто в таблицах)
        $tableRows = $xpath->query('//table//tr');
        if ($tableRows && $tableRows->length > 0) {
            foreach ($tableRows as $row) {
                $cells = $xpath->query('.//td|.//th', $row);
                if ($cells->length < 2) continue;
                
                $headerCell = $cells->item(0)->textContent;
                $valueCell = $cells->item($cells->length - 1)->textContent;
                
                if (preg_match('/(калорийность|калории|ккал)/ui', $headerCell) && !isset($result['calories'])) {
                    $result['calories'] = $this->parseNumber($valueCell);
                    \Log::info("Извлечено значение для calories из таблицы: {$result['calories']}");
                } elseif (preg_match('/(белки|белков)/ui', $headerCell) && !isset($result['proteins'])) {
                    $result['proteins'] = $this->parseNumber($valueCell);
                    \Log::info("Извлечено значение для proteins из таблицы: {$result['proteins']}");
                } elseif (preg_match('/(жиры|жиров)/ui', $headerCell) && !isset($result['fats'])) {
                    $result['fats'] = $this->parseNumber($valueCell);
                    \Log::info("Извлечено значение для fats из таблицы: {$result['fats']}");
                } elseif (preg_match('/(углеводы|углеводов)/ui', $headerCell) && !isset($result['carbs'])) {
                    $result['carbs'] = $this->parseNumber($valueCell);
                    \Log::info("Извлечено значение для carbs из таблицы: {$result['carbs']}");
                }
            }
        }

        // Извлекаем теги из разных мест на странице
        
        // 1. Ищем в мета-тегах keywords
        $metaKeywords = $xpath->query('//meta[@name="keywords"]')->item(0);
        if ($metaKeywords && $metaKeywords->hasAttribute('content')) {
            $keywords = explode(',', $metaKeywords->getAttribute('content'));
            foreach ($keywords as $keyword) {
                $keyword = trim($keyword);
                if (mb_strlen($keyword) > 2 && mb_strlen($keyword) < 50) {
                    $result['detected_tags'][] = $keyword;
                }
            }
        }
        
        // 2. Ищем в специальных блоках тегов
        $tagSelectors = [
            '//div[contains(@class, "tag")]/a',
            '//span[contains(@class, "tag")]',
            '//a[contains(@href, "tag/")]',
            '//a[contains(@class, "tag")]',
            '//div[contains(@class, "keywords")]//a',
            '//div[contains(@class, "tags")]//a'
        ];
        
        foreach ($tagSelectors as $selector) {
            $tagNodes = $xpath->query($selector);
            foreach ($tagNodes as $node) {
                $tagText = trim($node->textContent);
                if (mb_strlen($tagText) > 2 && mb_strlen($tagText) < 50) {
                    $result['detected_tags'][] = $tagText;
                }
            }
        }
        
        // 3. Извлекаем ключевые слова из заголовка
        if (!empty($result['title'])) {
            $titleWords = preg_split('/\s+/', $result['title']);
            foreach ($titleWords as $word) {
                $word = trim($word);
                // Отбираем только значимые слова как теги
                if (mb_strlen($word) > 3 && mb_strlen($word) < 20) {
                    $result['detected_tags'][] = $word;
                }
            }
        }

        return $result;
    }
    
    /**
     * Парсинг времени в формате ISO 8601 Duration (PT1H30M)
     */
    private function parseDuration($duration)
    {
        $minutes = 0;
        
        if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches)) {
            $hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $mins = isset($matches[2]) ? (int)$matches[2] : 0;
            $secs = isset($matches[3]) ? (int)$matches[3] : 0;
            
            $minutes = $hours * 60 + $mins + ceil($secs / 60);
        } elseif (is_numeric($duration)) {
            $minutes = (int)$duration;
        } elseif (preg_match('/(\d+)\s*(?:h|ч|час|hours?)\s*(?:и|and)?\s*(\d+)?\s*(?:m|мин|min|минут)?/i', $duration, $matches)) {
            $hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $mins = isset($matches[2]) ? (int)$matches[2] : 0;
            $minutes = $hours * 60 + $mins;
        }
        
        return $minutes > 0 ? $minutes : null;
    }

    /**
     * Парсинг количества ингредиента и единицы измерения
     */
    private function parseQuantity($quantityString)
    {
        $result = [
            'value' => null,
            'unit' => ''
        ];
        
        if (empty($quantityString) || preg_match('/по\s+(вкусу|желанию)/i', $quantityString)) {
            $result['unit'] = 'по вкусу';
            return $result;
        }
        
        if (preg_match('/(\d+)\s*[\/]\s*(\d+)/', $quantityString, $matches)) {
            $result['value'] = (float)$matches[1] / (float)$matches[2];
        } elseif (preg_match('/(\d+[.,]?\d*)/', $quantityString, $matches)) {
            $result['value'] = (float)str_replace(',', '.', $matches[1]);
        }
        
        $unitPatterns = [
            'г|грамм|гр\.?' => 'г',
            'кг|килограмм' => 'кг',
            'мл|миллилитр' => 'мл',
            'л|литр' => 'л',
            'ч\.?\s*л\.?|чайн(ая|ой|ую)?\s+лож(ка|ки|ку)' => 'ч.л.',
            'ст\.?\s*л\.?|столов(ая|ой|ую)?\s+лож(ка|ки|ку)' => 'ст.л.',
            'шт\.?|штук[аи]?|штук' => 'шт.',
            'стакан[а-я]*' => 'стакан',
            'пуч[ое]?к|пучок' => 'пучок',
            'зуб[а-я]+|зубчик[а-я]*' => 'зубчик',
            'долька|долек|дольки' => 'долька',
            'банк[аи]|банок' => 'банка',
            'упаковк[аи]|упаковок' => 'упаковка',
            'пачк[аи]|пачек' => 'пачка',
            'по\s+вкусу' => 'по вкусу',
            'щепотк[аи]|щепоток' => 'щепотка'
        ];
        
        foreach ($unitPatterns as $pattern => $standardUnit) {
            if (preg_match('/' . $pattern . '/iu', $quantityString)) {
                $result['unit'] = $standardUnit;
                break;
            }
        }
        
        if (empty($result['unit']) && !is_null($result['value'])) {
            $result['unit'] = 'шт.';
        }
        
        return $result;
    }

    /**
     * Вспомогательная функция для парсинга чисел
     */
    private function parseNumber($value)
    {
        if (empty($value)) {
            return null;
        }
        
        \Log::debug("Парсинг числового значения из строки: '{$value}'");
        
        if (preg_match('/(\d+[.,]?\d*)/', $value, $matches)) {
            $numericValue = str_replace(',', '.', $matches[1]);
            if (is_numeric($numericValue)) {
                $result = (float)$numericValue;
                \Log::debug("Успешно распарсено число: {$result}");
                return $result;
            }
        }
        
        if (preg_match('/^(\d+[.,]?\d*)\s*(?:g|г|ккал|kkal|kcal)/i', $value, $matches)) {
            $numericValue = str_replace(',', '.', $matches[1]);
            if (is_numeric($numericValue)) {
                $result = (float)$numericValue;
                \Log::debug("Успешно распарсено число из единиц измерения: {$result}");
                return $result;
            }
        }
        
        \Log::debug("Не удалось распарсить число из строки: '{$value}'");
        return null;
    }

    /**
     * Отображение формы для пакетного парсинга рецептов
     */
    public function batchIndex()
    {
        return view('admin.parser.batch');
    }
    
    /**
     * Обработка списка URL для пакетного парсинга
     */
    public function batchParse(Request $request)
    {
        $request->validate([
            'urls' => 'required|string',
        ]);
        
        // Увеличиваем время выполнения скрипта для обработки большого количества URL
        set_time_limit(3600); // 1 час
        
        $urls = preg_split('/\r\n|\r|\n/', $request->urls);
        $urls = array_filter($urls, function($url) {
            return filter_var(trim($url), FILTER_VALIDATE_URL);
        });
        
        if (empty($urls)) {
            return redirect()->route('admin.parser.batch')
                            ->with('error', 'Не найдено корректных URL');
        }
        
        $validUrls = [];
        $duplicateUrls = [];
    
        foreach ($urls as $url) {
            $url = trim($url);
            if (empty($url)) continue;
            
            $existingRecipe = Recipe::where('source_url', $url)->first();
            
            if ($existingRecipe) {
                $duplicateUrls[] = $url;
            } else {
                $validUrls[] = $url;
            }
        }
        
        session(['batch_urls' => $validUrls]);
        
        if (count($duplicateUrls) > 0) {
            $message = 'Найдено ' . count($duplicateUrls) . ' URL-адресов, которые уже обработаны ранее. Они будут пропущены.';
            return view('admin.parser.batch_result', [
                'total_urls' => count($validUrls),
                'duplicate_urls' => $duplicateUrls,
                'message' => $message
            ]);
        }
        
        session(['batch_urls' => $urls]);
        session(['current_url_index' => 0]);
        session(['processed_urls' => []]);
        session(['failed_urls' => []]);
        
        return redirect()->route('admin.parser.processBatch');
    }
    
    /**
     * Обработка следующего URL из пакета
     */
    public function processBatch()
    {
        // Получаем URL из сессии
        $urls = session('batch_urls', []);
        $currentIndex = session('current_url_index', 0);
        $processedUrls = session('processed_urls', []);
        $failedUrls = session('failed_urls', []);
        
        // Если нет URL в сессии, проверяем, есть ли они в request
        if (empty($urls) && request()->has('urls')) {
            $urls = request()->urls;
            session(['batch_urls' => $urls]);
        }
        
        // Проверяем, есть ли URL для обработки
        if (empty($urls) || $currentIndex >= count($urls)) {
            return view('admin.parser.batch_result', [
                'message' => 'Нет URL для обработки или все URL уже обработаны.',
                'failed' => $failedUrls
            ]);
        }
        
        $url = $urls[$currentIndex];
        
        try {
            // Получаем HTML-содержимое по указанному URL
            $response = Http::withOptions([
                'allow_redirects' => true,
            ])->get($url);
            
            if (!$response->successful()) {
                $failedUrls[] = [
                    'url' => $url,
                    'error' => 'Не удалось получить содержимое страницы. HTTP статус: ' . $response->status()
                ];
            } else {
                $html = $response->body();
                
                // Парсим HTML для извлечения данных рецепта
                $parseResult = $this->parseRecipeData($html, $url);
                
                if (empty($parseResult['title'])) {
                    $failedUrls[] = [
                        'url' => $url,
                        'error' => 'Не удалось извлечь название рецепта'
                    ];
                } else {
                    // Создаем рецепт из полученных данных
                    $recipe = $this->createRecipeFromParsedData($parseResult);
                    
                    if ($recipe) {
                        $processedUrls[] = [
                            'url' => $url,
                            'recipe_id' => $recipe->id,
                            'title' => $recipe->title
                        ];
                    } else {
                        $failedUrls[] = [
                            'url' => $url,
                            'error' => 'Не удалось создать рецепт'
                        ];
                    }
                }
            }
        } catch (\Exception $e) {
            $failedUrls[] = [
                'url' => $url,
                'error' => 'Ошибка при обработке: ' . $e->getMessage()
            ];
        }
        
        // Обновляем индекс текущего URL
        session([
            'current_url_index' => $currentIndex + 1,
            'processed_urls' => $processedUrls,
            'failed_urls' => $failedUrls
        ]);
        
        // Если все URL обработаны, показываем результат
        if ($currentIndex + 1 >= count($urls)) {
            return view('admin.parser.batch_result', [
                'message' => 'Обработка завершена. Обработано: ' . count($processedUrls) . ' URL. С ошибками: ' . count($failedUrls),
                'failed' => $failedUrls
            ]);
        }
        
        // Иначе переходим к следующему URL
        return redirect()->route('admin.parser.processBatch');
    }
    
    /**
     * Парсинг URL для получения данных о рецепте
     */
    private function parseUrl($url)
    {
        try {
            $response = Http::withOptions([
                'verify' => false,
                'timeout' => 30,
                'connect_timeout' => 15,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
                    'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,image/webp,*/*;q=0.8',
                    'Accept-Language' => 'ru-RU,ru;q=0.8,en-US;q=0.5,en;q=0.3',
                ],
                'allow_redirects' => false,
            ])->get($url);
            
            if ($response->status() === 301 || $response->status() === 302) {
                $redirectUrl = $response->header('Location');
                \Log::info("Обнаружен редирект с $url на $redirectUrl");
                
                if (!empty($redirectUrl)) {
                    $response = Http::withOptions([
                        'verify' => false,
                        'timeout' => 30,
                        'connect_timeout' => 15,
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
                        ],
                        'allow_redirects' => false,
                    ])->get($redirectUrl);
                }
            }
            
            if (!$response->successful() && $response->status() !== 302 && $response->status() !== 301) {
                throw new \Exception("Не удалось получить доступ к странице. Код ответа: " . $response->status());
            }
            
            $html = $response->body();
            
            $parseResult = $this->parseRecipeData($html, $url);
            
            if (empty($parseResult['title'])) {
                throw new \Exception("Не удалось извлечь название рецепта с указанной страницы.");
            }
            
            return $parseResult;
            
        } catch (\Exception $e) {
            throw new \Exception("Ошибка при парсинге URL: " . $e->getMessage());
        }
    }
    
    /**
     * Создание рецепта из спарсенных данных
     */
    private function createRecipeFromParsedData($data)
    {
        try {
            $request = new \Illuminate\Http\Request();
            $request->replace([
                'title' => $data['title'] ?? null,
                'description' => $data['description'] ?? null,
                'ingredients' => $data['ingredients'] ?? null,
                'instructions' => $data['instructions'] ?? null,
                'cooking_time' => $data['cooking_time'] ?? null,
                'servings' => $data['servings'] ?? null,
                'calories' => $data['calories'] ?? null,
                'proteins' => $data['proteins'] ?? null,
                'fats' => $data['fats'] ?? null,
                'carbs' => $data['carbs'] ?? null,
                'source_url' => $data['source_url'] ?? null,
                'is_published' => true,
                'image_urls' => $data['recipe_image_urls'] ?? $data['image_urls'] ?? null,
                'structured_ingredients' => isset($data['structured_ingredients']) ? json_encode($data['structured_ingredients']) : null,
                'additional_data' => $data['additional_data'] ?? null,
                'detected_tags' => $data['detected_tags'] ?? [] // Добавляем обнаруженные теги
            ]);
            
            \Log::info("Создание рецепта: {$data['title']}");
            \Log::info("Питательная ценность: калории = " . ($data['calories'] ?? 'NULL'));
        
            $recipe = $this->storeRecipe($request);
            
            // Добавляем теги, если они были обнаружены
            if (!empty($data['detected_tags']) && $recipe) {
                $this->addTagsToRecipe($recipe, $data['detected_tags']);
            }
            
            if (!empty($data['detected_categories']) && $recipe) {
                $this->addCategoriesToRecipe($recipe, $data['detected_categories']);
            }
            
            return $recipe;
        } catch (\Exception $e) {
            \Log::error('Error creating recipe from parsed data: ' . $e->getMessage());
            throw new \Exception("Не удалось создать рецепт: " . $e->getMessage());
        }
    }
    
    /**
     * Добавляет теги к рецепту
     */
    private function addTagsToRecipe($recipe, $tagNames)
    {
        try {
            $tagIds = [];
            $tagMap = [];
            
            // Получаем существующие теги
            $existingTags = Tag::all();
            foreach ($existingTags as $tag) {
                $tagMap[strtolower($tag->name)] = $tag->id;
            }
            
            // Проходим по всем именам тегов
            foreach ($tagNames as $tagName) {
                $tagName = trim($tagName);
                if (strlen($tagName) < 2) continue; // Пропускаем слишком короткие теги
                
                $key = strtolower($tagName);
                
                // Проверяем, существует ли уже такой тег
                if (isset($tagMap[$key])) {
                    $tagIds[] = $tagMap[$key];
                } else {
                    // Создаем новый тег
                    $tag = new Tag();
                    $tag->name = $tagName;
                    $tag->slug = Str::slug($tagName);
                    $tag->save();
                    
                    $tagIds[] = $tag->id;
                    $tagMap[$key] = $tag->id;
                }
            }
            
            // Удаляем дубликаты тегов
            $tagIds = array_unique($tagIds);
            
            // Связываем теги с рецептом
            if (!empty($tagIds)) {
                $recipe->tags()->sync($tagIds);
                \Log::info("Добавлено " . count($tagIds) . " тегов к рецепту ID " . $recipe->id);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error adding tags to recipe: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Добавление категорий к рецепту
     */
    private function addCategoriesToRecipe($recipe, $categoryNames)
    {
        try {
            $categoryIds = [];
            $categoryMap = [];
            
            $existingCategories = Category::all();
            foreach ($existingCategories as $category) {
                $categoryMap[mb_strtolower($category->name)] = $category->id;
            }
            
            foreach ($categoryNames as $categoryName) {
                $categoryName = trim($categoryName);
                $categoryKey = mb_strtolower($categoryName);
                
                if (isset($categoryMap[$categoryKey])) {
                    $categoryIds[] = $categoryMap[$categoryKey];
                } else {
                    try {
                        $newCategory = new Category();
                        $newCategory->name = $categoryName;
                        $newCategory->slug = Str::slug($categoryName);
                        $newCategory->save();
                        
                        $categoryIds[] = $newCategory->id;
                        $categoryMap[$categoryKey] = $newCategory->id;
                    } catch (\Exception $e) {
                        \Log::warning("Не удалось создать категорию: $categoryName. Ошибка: " . $e->getMessage());
                    }
                }
            }
            
            $categoryIds = array_unique($categoryIds);
            
            if (!empty($categoryIds)) {
                $recipe->categories()->attach($categoryIds);
            }
            
            return true;
        } catch (\Exception $e) {
            \Log::error('Error adding categories to recipe: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создание рецепта на основе данных запроса
     */
    private function storeRecipe($request)
    {
        try {
            $recipe = new \App\Models\Recipe();
            $recipe->title = $request->title;
            
            $baseSlug = \Illuminate\Support\Str::slug($request->title);
            $slug = $this->generateUniqueSlug($baseSlug);
            $recipe->slug = $slug;
            
            \Log::info("Создаем рецепт с заголовком: {$request->title}, слаг: {$slug}");
            
            $recipe->description = $request->description;
            $recipe->cooking_time = $request->cooking_time;
            $recipe->servings = $request->servings;
            
            $recipe->calories = $request->calories;
            $recipe->proteins = $request->proteins;
            $recipe->fats = $request->fats;
            $recipe->carbs = $request->carbs;
            
            \Log::info("Сохраняемые значения питательной ценности: calories={$recipe->calories}, proteins={$recipe->proteins}, fats={$recipe->fats}, carbs={$recipe->carbs}");
            
            $recipe->ingredients = is_array($request->ingredients) ? json_encode($request->ingredients) : $request->ingredients;
            $recipe->instructions = is_array($request->instructions) ? json_encode($request->instructions) : $request->instructions;
            
            $recipe->source_url = $request->source_url;
            $recipe->is_published = $request->has('is_published');
            
            $recipe->user_id = \App\Models\User::where('role', 'admin')->first()->id ?? 1;
            
            $additionalData = [];
            
            if ($request->structured_ingredients) {
                $structuredIngredients = $request->structured_ingredients;
                if (is_string($structuredIngredients)) {
                    $structuredIngredients = json_decode($structuredIngredients, true);
                }
                $additionalData['structured_ingredients'] = $structuredIngredients;
            }
            
            if ($request->step_images && !empty($request->step_images)) {
                $additionalData['step_images'] = $request->step_images;
            }
            
            if ($request->calories || $request->proteins || $request->fats || $request->carbs) {
                $additionalData['nutrition'] = [
                    'calories' => $request->calories,
                    'proteins' => $request->proteins,
                    'fats' => $request->fats,
                    'carbs' => $request->carbs
                ];
            }
            
            if ($request->additional_data) {
                $otherData = is_string($request->additional_data) ? 
                                json_decode($request->additional_data, true) : 
                                $request->additional_data;
                
                if (is_array($otherData)) {
                    $additionalData = array_merge($additionalData, $otherData);
                }
            }
            
            if (!empty($additionalData)) {
                $recipe->additional_data = json_encode($additionalData);
            }
            
            $recipe->save();
            
            \Log::info("После сохранения в БД: calories={$recipe->calories}, proteins={$recipe->proteins}, fats={$recipe->fats}, carbs={$recipe->carbs}");
            
            if ($request->has('image_urls') && is_array($request->image_urls) && !empty($request->image_urls)) {
                $this->saveRecipeImages($recipe, $request->image_urls);
            }
            
            return $recipe;
        } catch (\Exception $e) {
            \Log::error('Error creating recipe: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Генерирует уникальный слаг для рецепта
     */
    private function generateUniqueSlug($baseSlug)
    {
        $slug = $baseSlug;
        $counter = 1;
        
        while (\App\Models\Recipe::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            
            if ($counter > 100) {
                $slug = $baseSlug . '-' . uniqid();
                break;
            }
        }
        
        return $slug;
    }
    
    /**
     * Сохранение изображений для рецепта
     */
    private function saveRecipeImages($recipe, $imageUrls)
    {
        try {
            $mainImageSaved = false;
            $savedImages = [];
            $sliderImages = [];
            $errors = [];
            
            $basePath = public_path('images/recipes/');
            if (!file_exists($basePath)) {
                mkdir($basePath, 0755, true);
            }
            
            \Log::info("Начинаем сохранение изображений для рецепта ID: {$recipe->id}. Количество URL: " . count($imageUrls));
            
            foreach ($imageUrls as $index => $imageUrl) {
                try {
                    if (empty($imageUrl)) {
                        \Log::warning("Пустой URL изображения на индексе $index для рецепта ID: {$recipe->id}");
                        continue;
                    }
                    
                    $imageUrl = trim($imageUrl);
                    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        if (substr($imageUrl, 0, 2) === '//') {
                            $imageUrl = 'https:' . $imageUrl;
                        } else if (substr($imageUrl, 0, 1) === '/') {
                            $urlParts = parse_url($recipe->source_url);
                            $baseUrl = $urlParts['scheme'] . '://' . $urlParts['host'];
                            $imageUrl = $baseUrl . $imageUrl;
                        } else {
                            \Log::warning("Невалидный URL изображения: $imageUrl для рецепта ID: {$recipe->id}");
                            continue;
                        }
                    }
                    
                    \Log::info("Пытаемся загрузить изображение с URL: $imageUrl для рецепта ID: {$recipe->id}");
                    
                    $response = Http::withOptions([
                        'verify' => false,
                        'timeout' => 15,
                        'allow_redirects' => true,
                        'headers' => [
                            'User-Agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/96.0.4664.110 Safari/537.36',
                        ],
                    ])->get($imageUrl);
                    
                    if (!$response->successful()) {
                        $errors[] = "HTTP-ошибка {$response->status()} при загрузке $imageUrl";
                        \Log::warning("HTTP-ошибка {$response->status()} при загрузке изображения: $imageUrl");
                        continue;
                    }
                    
                    $imageContents = $response->body();
                    if (empty($imageContents)) {
                        $errors[] = "Получен пустой ответ с $imageUrl";
                        \Log::warning("Получены пустые данные изображения с: $imageUrl");
                        continue;
                    }
                    
                    $contentType = $response->header('Content-Type');
                    if (!$contentType || !strstr($contentType, 'image/')) {
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $detectedType = $finfo->buffer($imageContents);
                        
                        if (!strstr($detectedType, 'image/')) {
                            $errors[] = "Невалидное изображение (тип: $detectedType) с $imageUrl";
                            \Log::warning("Невалидный тип содержимого: $detectedType для $imageUrl");
                            continue;
                        }
                    }
                    
                    $extension = $this->getImageExtensionFromContent($imageContents, $imageUrl);
                    $filename = 'recipe_' . $recipe->id . '_' . ($index + 1) . '.' . $extension;
                    $filepath = $basePath . $filename;
                    
                    $result = file_put_contents($filepath, $imageContents);
                    if ($result === false) {
                        $errors[] = "Ошибка записи файла $filename";
                        \Log::error("Не удалось записать изображение в файл: $filepath");
                        continue;
                    }
                    
                    \Log::info("Изображение успешно сохранено: $filepath");
                    
                    $imagePath = 'images/recipes/' . $filename;
                    
                    if (!$mainImageSaved) {
                        $recipe->image_url = $imagePath;
                        $recipe->save();
                        $mainImageSaved = true;
                        \Log::info("Установлено главное изображение для рецепта ID: {$recipe->id}: $imagePath");
                    } else {
                        $sliderImages[] = $imagePath;
                    }
                    
                    $savedImages[] = [
                        'original_url' => $imageUrl,
                        'saved_path' => $imagePath
                    ];
                    
                } catch (\Exception $e) {
                    $errors[] = "Ошибка обработки изображения ($imageUrl): " . $e->getMessage();
                    \Log::error("Исключение при обработке изображения {$imageUrl}: " . $e->getMessage());
                    continue;
                }
            }
            
            \Log::info("Сохранение изображений завершено для рецепта ID: {$recipe->id}. Сохранено: " . 
                  count($savedImages) . ", Для слайдера: " . count($sliderImages) . ", Ошибок: " . count($errors));
            
            if (!empty($savedImages) || !empty($errors) || !empty($sliderImages)) {
                $additionalData = [];
                
                if ($recipe->additional_data) {
                    $additionalData = json_decode($recipe->additional_data, true) ?: [];
                }
                
                $additionalData['saved_images'] = $savedImages;
                
                if (!empty($sliderImages)) {
                    $additionalData['slider_images'] = $sliderImages;
                }
                
                if (!empty($errors)) {
                    $additionalData['image_errors'] = $errors;
                }
                
                $recipe->additional_data = json_encode($additionalData);
                $recipe->save();
            }
            
            return !empty($savedImages);
        } catch (\Exception $e) {
            \Log::error("Критическая ошибка при сохранении изображений для рецепта ID: {$recipe->id}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Определение расширения изображения из содержимого и URL
     */
    private function getImageExtensionFromContent($imageContents, $imageUrl)
    {
        $parts = parse_url($imageUrl);
        $path = $parts['path'] ?? '';
        
        if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $path, $matches)) {
            $ext = strtolower($matches[1]);
            return $ext == 'jpeg' ? 'jpg' : $ext;
        }
        
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageContents);
        
        switch ($mimeType) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
            default:
                return 'jpg';
        }
    }

    /**
     * Отображение формы для сбора ссылок с категории
     */
    public function collectLinksForm()
    {
        return view('admin.parser.collect_links');
    }
    
    /**
     * Сбор ссылок на рецепты с категории
     */
    public function collectLinks(Request $request)
    {
        // Увеличиваем максимальное время выполнения скрипта для больших объемов данных
        set_time_limit(3600); // 60 минут

        $validator = \Illuminate\Support\Facades\Validator::make($request->all(), [
            'category_url' => 'required|url',
            'max_links' => 'required|integer|min:1',
            'collect_images' => 'nullable|boolean',
        ]);
        
        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }
        
        $categoryUrl = $request->input('category_url');
        $selector = $request->input('link_selector');
        $maxLinks = (int) $request->input('max_links', 100000);
        $scrollMode = $request->input('scroll_mode', 'none');
        $saveImages = $request->input('collect_images', false);
        
        try {
            // Создаем директорию для файла с ссылками
            $linksDir = public_path('recipe_links');
            if (!file_exists($linksDir)) {
                mkdir($linksDir, 0755, true);
            }
            
            // Определяем файл для сохранения ссылок
            $linkFile = public_path('recipe_links/collected_links.txt');
            
            Log::info("Начало сбора ссылок с URL: {$categoryUrl}");
            Log::info("Желаемое количество ссылок: {$maxLinks}");
            Log::info("Файл для сохранения ссылок: {$linkFile}");
            
            // Получаем существующие URL из базы данных
            $existingUrlsInDB = Recipe::where('source_url', '<>', '')->pluck('source_url')->toArray();
            Log::info("Найдено уже существующих ссылок в базе данных: " . count($existingUrlsInDB));
            
            // Вызываем коллектор ссылок
            $linkCollector = app()->make('App\Http\Controllers\Admin\RecipeLinkCollectorController');
            $isEdaRu = stripos($categoryUrl, 'eda.ru') !== false;
            
            if ($isEdaRu) {
                $links = $linkCollector->collectEdaRuLinks($categoryUrl, $maxLinks, $scrollMode);
            } else {
                $links = $linkCollector->collectGenericLinks($categoryUrl, $selector, $maxLinks);
            }
            
            return redirect()->route('admin.parser.collectedLinks')
                ->with('success', 'Собрано ' . count($links) . ' ссылок. Ссылки были сохранены в процессе сбора.');
        } catch (\Exception $e) {
            Log::error("Ошибка при сборе ссылок: " . $e->getMessage());
            return redirect()->back()
                ->with('error', 'Ошибка при сборе ссылок: ' . $e->getMessage())
                ->withInput();
        }
    }

    /**
     * Улучшенная функция для имитации прокрутки и сбора ссылок с динамических сайтов
     */
    private function extractLinksWithImprovedScroll($url, $linkSelector, $desiredLinks = 100000, $collectImages = false, $maxPages = 1000, $existingUrlsInDB = [])
    {
        set_time_limit(1800); // 30 минут

        $images = [];
        $processedUrls = [];
        $failedUrls = [];
        $isEdaRu = stripos($url, 'eda.ru') !== false;
        $baseUrl = parse_url($url, PHP_URL_SCHEME) . '://' . parse_url($url, PHP_URL_HOST);

        \Log::info("extractLinksWithImprovedScroll: начало извлечения для URL: $url");
        \Log::info("isEdaRu: " . ($isEdaRu ? 'true' : 'false') . ", baseUrl: $baseUrl");
        \Log::info("Целевое количество ссылок: $desiredLinks. В базе существует: " . count($existingUrlsInDB) . " URL");

        try {
            if ($isEdaRu) {
                // Расширенная стратегия для сайта eda.ru
                return $this->extractLinksFromEdaRuEnhanced($url, $desiredLinks, $collectImages, $maxPages, $existingUrlsInDB);
            } else {
                // Для других сайтов используем стандартную логику
                $pagesToCheck = $this->generatePaginationUrls($url, $maxPages);
                
                foreach ($pagesToCheck as $pageUrl) {
                    // Проверяем количество уже собранных ссылок в файле
                    $collectedCount = $this->getLinksCount();
                    if ($collectedCount >= $desiredLinks) {
                        \Log::info("Достигнуто желаемое количество ссылок: $collectedCount. Прекращаем сбор.");
                        break;
                    }
                    
                    try {
                        $response = Http::timeout(30)
                                       ->connectTimeout(10)
                                       ->retry(3, 1000)
                                       ->get($pageUrl);
                                       
                        if ($response->successful()) {
                            $processedUrls[] = $pageUrl;
                            $html = $response->body();
                            
                            // Непосредственная обработка HTML для извлечения ссылок
                            $this->processLinksFromHtml($html, $baseUrl, $linkSelector, $existingUrlsInDB);
                            
                            if ($collectImages) {
                                $extractedImages = $this->extractImagesFromHtml($html, $baseUrl);
                                $images = array_merge($images, $extractedImages);
                            }
                        } else {
                            $failedUrls[] = [
                                'url' => $pageUrl,
                                'error' => 'HTTP error: ' . $response->status()
                            ];
                        }
                    } catch (\Exception $e) {
                        $failedUrls[] = [
                            'url' => $pageUrl,
                            'error' => $e->getMessage()
                        ];
                    }
                    
                    // Добавляем случайную задержку между запросами
                    $delay = mt_rand(1000, 2000); // 1-2 секунды
                    usleep($delay * 1000); // usleep принимает микросекунды
                }
            }
            
            // Получаем все собранные ссылки из файла для статистики
            $collectedLinks = $this->getLinksFromFile();
            \Log::info("extractLinksWithImprovedScroll: завершено. Найдено ссылок: " . count($collectedLinks) . ", изображений: " . count($images));
            
            return [
                'links' => $collectedLinks,
                'images' => array_values(array_unique($images)),
                'processed_urls' => $processedUrls,
                'failed_urls' => $failedUrls
            ];
            
        } catch (\Exception $e) {
            \Log::error("Критическая ошибка в extractLinksWithImprovedScroll: " . $e->getMessage());
            return [
                'links' => $this->getLinksFromFile(),
                'images' => array_values(array_unique($images)),
                'processed_urls' => $processedUrls,
                'failed_urls' => array_merge($failedUrls, [
                    [
                        'url' => $url,
                        'error' => $e->getMessage()
                    ]
                ])
            ];
        }
    }

    /**
     * Расширенный метод для извлечения ссылок с eda.ru 
     * с максимальным покрытием категорий и подкатегорий
     */
    private function extractLinksFromEdaRuEnhanced($baseUrl, $desiredLinks = 100000, $collectImages = false, $maxPages = 1000, $existingUrlsInDB = [])
    {
        set_time_limit(3600); // 60 минут, так как будет большой объем работы
        
        $images = [];
        $processedUrls = [];
        $failedUrls = [];
        $visitedUrls = [];
        $mainDomain = 'https://eda.ru';
        
        // Базовые категории для обхода
        $baseCategories = [
            '/recepty',
            '/recepty/zavtraki',
            '/recepty/osnovnye-blyuda',
            '/recepty/supy',
            '/recepty/salaty',
            '/recepty/vypechka-deserty',
            '/recepty/napitki',
            '/recepty/sousy-marinady',
            '/recepty/zagotovki',
        ];
        
        // Параметры для расширенного поиска
        $sortParams = ['popular', 'new', 'rating', 'cooking_time'];
        $pageParams = range(1, min($maxPages, 100)); // Ограничиваем максимальное число страниц для одной категории
        
        \Log::info("Начинаем расширенное извлечение ссылок с eda.ru. Целевое количество: $desiredLinks");
        
        // 1. Сначала собираем все ссылки с главной страницы рецептов
        try {
            $response = Http::timeout(30)->get($mainDomain . '/recepty');
            if ($response->successful()) {
                // Извлекаем все ссылки на категории
                $html = $response->body();
                $dom = new DOMDocument();
                @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                $xpath = new DOMXPath($dom);
                
                // Находим ссылки на категории и подкатегории
                $categoryNodes = $xpath->query('//a[contains(@href, "/recepty/")]');
                $categoryUrls = [];
                
                foreach ($categoryNodes as $node) {
                    $href = $node->getAttribute('href');
                    if (strpos($href, 'http') !== 0) {
                        $href = $mainDomain . $href;
                    }
                    
                    // Отфильтровываем ссылки на рецепты (они содержат дефис)
                    if (strpos($href, '-') === false && !in_array($href, $categoryUrls)) {
                        $categoryUrls[] = $href;
                    }
                }
                
                // Добавляем базовые категории, если их нет в списке
                foreach ($baseCategories as $category) {
                    $fullUrl = $mainDomain . $category;
                    if (!in_array($fullUrl, $categoryUrls)) {
                        $categoryUrls[] = $fullUrl;
                    }
                }
                
                \Log::info("Найдено категорий на eda.ru: " . count($categoryUrls));
                
                // 2. Теперь для каждой категории создаем множество вариаций URL (с сортировками, страницами и т.д.)
                $urlsToCheck = [];
                foreach ($categoryUrls as $categoryUrl) {
                    // Базовый URL категории
                    $urlsToCheck[] = $categoryUrl;
                    
                    // URL с параметрами сортировки
                    foreach ($sortParams as $sort) {
                        $urlsToCheck[] = $categoryUrl . '?sort=' . $sort;
                        
                        // URL с параметрами сортировки и страницами
                        foreach ($pageParams as $page) {
                            $urlsToCheck[] = $categoryUrl . '?sort=' . $sort . '&page=' . $page;
                        }
                    }
                    
                    // URL только с параметрами страниц
                    foreach ($pageParams as $page) {
                        $urlsToCheck[] = $categoryUrl . '?page=' . $page;
                    }
                }
                
                // Удаляем дубликаты URL
                $urlsToCheck = array_values(array_unique($urlsToCheck));
                \Log::info("Создано вариаций URL для проверки: " . count($urlsToCheck));
                
                // 3. Проходим по всем URL и собираем ссылки на рецепты
                $processed = 0;
                foreach ($urlsToCheck as $urlToCheck) {
                    // Проверяем, не превысили ли мы лимит
                    $collectedCount = $this->getLinksCount();
                    if ($collectedCount >= $desiredLinks) {
                        \Log::info("Достигнут лимит количества ссылок: $desiredLinks. Прекращаем сбор.");
                        break;
                    }
                    
                    // Не проверяем URL, которые уже обрабатывали
                    if (in_array($urlToCheck, $visitedUrls)) {
                        continue;
                    }
                    
                    $visitedUrls[] = $urlToCheck;
                    $processed++;
                    
                    try {
                        \Log::info("Обработка URL {$processed} из " . count($urlsToCheck) . ": $urlToCheck");
                        $response = Http::timeout(30)->get($urlToCheck);
                        
                        if ($response->successful()) {
                            $processedUrls[] = $urlToCheck;
                            $html = $response->body();
                            
                            // Извлекаем и сразу сохраняем ссылки на рецепты
                            $this->processEdaRuRecipeLinks($html, $mainDomain, $existingUrlsInDB);
                            
                            // Собираем изображения, если нужно
                            if ($collectImages) {
                                $extractedImages = $this->extractEdaRuImages($html, $mainDomain);
                                $images = array_merge($images, $extractedImages);
                            }
                            
                            // Находим дополнительные ссылки на категории, которые еще не обрабатывали
                            $dom = new DOMDocument();
                            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
                            $xpath = new DOMXPath($dom);
                            
                            $newCategoryNodes = $xpath->query('//a[contains(@href, "/recepty/") and not(contains(@href, "-"))]');
                            $newCategories = [];
                            
                            foreach ($newCategoryNodes as $node) {
                                $href = $node->getAttribute('href');
                                if (strpos($href, 'http') !== 0) {
                                    $href = $mainDomain . $href;
                                }
                                
                                if (!in_array($href, $categoryUrls) && !in_array($href, $urlsToCheck) && !in_array($href, $newCategories)) {
                                    $newCategories[] = $href;
                                }
                            }
                            
                            // Добавляем новые найденные категории в список для проверки
                            foreach ($newCategories as $newCategory) {
                                if (!in_array($newCategory, $urlsToCheck)) {
                                    $urlsToCheck[] = $newCategory;
                                    
                                    // Добавляем также вариации с первой страницей для быстрого охвата
                                    $urlsToCheck[] = $newCategory . '?page=1';
                                    foreach ($sortParams as $sort) {
                                        $urlsToCheck[] = $newCategory . '?sort=' . $sort;
                                    }
                                }
                            }
                            
                            if (!empty($newCategories)) {
                                \Log::info("Найдено новых категорий: " . count($newCategories) . ". Добавлено в очередь.");
                            }
                        } else {
                            $failedUrls[] = [
                                'url' => $urlToCheck,
                                'error' => 'HTTP error: ' . $response->status()
                            ];
                            \Log::warning("Ошибка HTTP при обработке URL: $urlToCheck. Код: " . $response->status());
                        }
                    } catch (\Exception $e) {
                        $failedUrls[] = [
                            'url' => $urlToCheck,
                            'error' => $e->getMessage()
                        ];
                        \Log::warning("Исключение при обработке URL: $urlToCheck. Ошибка: " . $e->getMessage());
                    }
                    
                    // Добавляем случайную задержку между запросами
                    $delay = mt_rand(1000, 2000); // 1-2 секунды
                    usleep($delay * 1000); // usleep принимает микросекунды
                }
            } else {
                \Log::error("Не удалось получить доступ к главной странице eda.ru. HTTP-код: " . $response->status());
                $failedUrls[] = [
                    'url' => $mainDomain . '/recepty',
                    'error' => 'HTTP error: ' . $response->status()
                ];
            }
        } catch (\Exception $e) {
            \Log::error("Критическая ошибка при извлечении ссылок с eda.ru: " . $e->getMessage());
            $failedUrls[] = [
                'url' => $mainDomain . '/recepty',
                'error' => $e->getMessage()
            ];
        }
        
        // Получаем все собранные ссылки из файла
        $collectedLinks = $this->getLinksFromFile();
        \Log::info("Завершено расширенное извлечение ссылок с eda.ru. Найдено: " . count($collectedLinks) . " уникальных ссылок на рецепты.");
        
        return [
            'links' => $collectedLinks,
            'images' => array_values(array_unique($images)),
            'processed_urls' => $processedUrls,
            'failed_urls' => $failedUrls
        ];
    }

    /**
     * Обработка и сохранение ссылок из HTML
     */
    private function processLinksFromHtml($html, $baseUrl, $linkSelector, $existingUrlsInDB = [])
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            try {
                if ($linkSelector && $linkSelector !== 'auto') {
                    $nodes = $xpath->query($this->cssToXPath($linkSelector));
                } else {
                    $nodes = $xpath->query('//a[@href]');
                }
                
                if ($nodes && $nodes->length > 0) {
                    foreach ($nodes as $node) {
                        if ($node instanceof \DOMElement && $node->nodeName === 'a' && $node->hasAttribute('href')) {
                            $href = $node->getAttribute('href');
                            
                            if (strpos($href, 'http') !== 0) {
                                if (strpos($href, '/') === 0) {
                                    $href = $baseUrl . $href;
                                } else {
                                    $baseDir = rtrim(dirname($baseUrl), '/');
                                    $href = $baseDir . '/' . $href;
                                }
                            }
                            
                            if (filter_var($href, FILTER_VALIDATE_URL)) {
                                // Проверяем, нет ли ссылки в базе данных
                                if (!in_array($href, $existingUrlsInDB)) {
                                    // Проверяем, нет ли ссылки в файле
                                    if (!$this->linkExistsInFile($href)) {
                                        // Сохраняем ссылку в файл
                                        $this->saveLinkToFile($href);
                                    }
                                }
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                \Log::warning("Ошибка при извлечении ссылок: " . $e->getMessage());
            }
        } catch (\Exception $e) {
            \Log::warning("Ошибка при обработке HTML: " . $e->getMessage());
        }
    }

    /**
     * Извлекает и сохраняет ссылки на рецепты eda.ru
     */
    private function processEdaRuRecipeLinks($html, $baseUrl, $existingUrlsInDB = [])
    {
        try {
            $dom = new DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            $xpath = new DOMXPath($dom);
            
            // Ищем все ссылки, содержащие "/recepty/" и имеющие дефис в пути (типичные для рецептов на eda.ru)
            $recipeNodes = $xpath->query('//a[contains(@href, "/recepty/") and contains(@href, "-")]');
            
            foreach ($recipeNodes as $node) {
                $href = $node->getAttribute('href');
                
                // Пропускаем ссылки на категории и другие нерецептные страницы
                if (preg_match('/\/recepty\/[^\/]+\/[^\/]+-\d+$/', $href) || 
                    preg_match('/\/recepty\/[^\/]+-\d+$/', $href)) {
                    
                    // Преобразуем относительные ссылки в абсолютные
                    if (strpos($href, 'http') !== 0) {
                        $href = $baseUrl . $href;
                    }
                    
                    // Проверяем формат URL, характерный для рецептов
                    if (filter_var($href, FILTER_VALIDATE_URL)) {
                        // Проверяем, нет ли ссылки в базе данных
                        if (!in_array($href, $existingUrlsInDB)) {
                            // Проверяем, нет ли ссылки в файле
                            if (!$this->linkExistsInFile($href)) {
                                // Сохраняем ссылку в файл
                                $this->saveLinkToFile($href);
                            }
                        }
                    }
                }
            }
        } catch (\Exception $e) {
            \Log::error("Ошибка в processEdaRuRecipeLinks: " . $e->getMessage());
        }
    }

    /**
     * Получает путь к файлу ссылок
     */
    private function getLinksFilePath()
    {
        $directory = public_path('recipe_links');
        if (!file_exists($directory)) {
            mkdir($directory, 0755, true);
        }
        return $directory . '/collected_links.txt';
    }

    /**
     * Инициализирует файл ссылок, если он не существует
     */
    private function initializeLinksFile()
    {
        $file = $this->getLinksFilePath();
        if (!file_exists($file)) {
            file_put_contents($file, '');
        }
        return $file;
    }

    /**
     * Сохраняет массив ссылок в файл (добавляет к существующим)
     */
    private function saveLinksToFile($links)
    {
        $file = $this->getLinksFilePath();
        $existingLinks = $this->getLinksFromFile();
        $allLinks = array_unique(array_merge($existingLinks, $links));
        file_put_contents($file, implode(PHP_EOL, $allLinks));
    }

    /**
     * Сохраняет одну ссылку в файл (добавляет, если отсутствует)
     */
    private function saveLinkToFile($link)
    {
        $file = $this->getLinksFilePath();
        
        // Строгая проверка на существование ссылки в файле
        if (!$this->linkExistsInFile($link)) {
            // Проверяем, не существует ли уже этот рецепт в базе данных
            $existsInDB = Recipe::where('source_url', $link)->exists();
            
            if (!$existsInDB) {
                file_put_contents($file, $link . PHP_EOL, FILE_APPEND);
                return true;
            }
        }
        
        return false;
    }

    /**
     * Возвращает массив ссылок из файла
     */
    private function getLinksFromFile()
    {
        $file = $this->getLinksFilePath();
        if (!file_exists($file)) {
            return [];
        }
        
        $content = file_get_contents($file);
        if (empty($content)) {
            return [];
        }
        
        $links = explode(PHP_EOL, $content);
        return array_filter($links); // Удаляем пустые строки
    }

    /**
     * Возвращает количество ссылок в файле
     */
    private function getLinksCount()
    {
        return count($this->getLinksFromFile());
    }

    /**
     * Проверяет, существует ли ссылка в файле
     */
    private function linkExistsInFile($link)
    {
        $file = $this->getLinksFilePath();
        if (!file_exists($file)) {
            return false;
        }
        
        // Используем более точный метод поиска строки в файле
        $links = $this->getLinksFromFile();
        return in_array($link, $links);
    }

    /**
     * Удаляет дубликаты ссылок из файла
     */
    public function removeDuplicateLinks()
    {
        $file = $this->getLinksFilePath();
        if (!file_exists($file)) {
            return [
                'status' => 'error', 
                'message' => 'Файл со ссылками не найден'
            ];
        }
        
        // Получаем все ссылки
        $links = $this->getLinksFromFile();
        
        // Считаем количество до дедупликации
        $countBefore = count($links);
        
        // Удаляем дубликаты
        $uniqueLinks = array_unique($links);
        
        // Считаем количество после дедупликации
        $countAfter = count($uniqueLinks);
        
        // Сохраняем обратно только уникальные ссылки
        file_put_contents($file, implode(PHP_EOL, $uniqueLinks));
        
        // Количество удаленных дубликатов
        $removedCount = $countBefore - $countAfter;
        
        return [
            'status' => 'success',
            'message' => "Удалено $removedCount дубликатов ссылок",
            'before' => $countBefore,
            'after' => $countAfter
        ];
    }

    /**
     * Удаляет ссылки, которые уже есть в базе данных
     */
    public function removeExistingLinks()
    {
        $file = $this->getLinksFilePath();
        if (!file_exists($file)) {
            return [
                'status' => 'error', 
                'message' => 'Файл со ссылками не найден'
            ];
        }
        
        // Получаем все ссылки
        $links = $this->getLinksFromFile();
        
        // Считаем количество до фильтрации
        $countBefore = count($links);
        
        // Получаем все существующие URL из базы данных
        $existingUrls = Recipe::pluck('source_url')->toArray();
        
        // Фильтруем ссылки, оставляя только те, которых нет в базе данных
        $filteredLinks = array_filter($links, function($link) use ($existingUrls) {
            return !in_array($link, $existingUrls);
        });
        
        // Считаем количество после фильтрации
        $countAfter = count($filteredLinks);
        
        // Сохраняем обратно только отфильтрованные ссылки
        file_put_contents($file, implode(PHP_EOL, $filteredLinks));
        
        // Количество удаленных существующих ссылок
        $removedCount = $countBefore - $countAfter;
        
        return [
            'status' => 'success',
            'message' => "Удалено $removedCount ссылок, которые уже есть в базе данных",
            'before' => $countBefore,
            'after' => $countAfter
        ];
    }

    /**
     * Очистить файл ссылок
     */
    public function clearLinksFile()
    {
        $file = $this->getLinksFilePath();
        file_put_contents($file, '');
        
        return [
            'status' => 'success',
            'message' => 'Файл ссылок очищен'
        ];
    }

    /**
     * Страница управления ссылками
     */
    public function manageLinks()
    {
        $links = $this->getLinksFromFile();
        $totalLinks = count($links);
        
        $existingUrls = Recipe::pluck('source_url')->toArray();
        
        // Подсчет дубликатов
        $uniqueLinks = array_unique($links);
        $duplicatesCount = $totalLinks - count($uniqueLinks);
        
        // Подсчет ссылок, которые уже есть в базе данных
        $existingCount = 0;
        foreach ($links as $link) {
            if (in_array($link, $existingUrls)) {
                $existingCount++;
            }
        }
        
        return view('admin.parser.manage_links', [
            'totalLinks' => $totalLinks,
            'duplicatesCount' => $duplicatesCount,
            'existingCount' => $existingCount,
            'links' => array_slice($links, 0, 1000000) // Первые 100 ссылок для отображения
        ]);
    }

    /**
     * API метод для обработки файла ссылок
     */
    public function processLinksFile(Request $request)
    {
        $action = $request->input('action', '');
        
        switch ($action) {
            case 'remove_duplicates':
                $result = $this->removeDuplicateLinks();
                break;
            case 'remove_existing':
                $result = $this->removeExistingLinks();
                break;
            case 'clear':
                $result = $this->clearLinksFile();
                break;
            default:
                $result = [
                    'status' => 'error',
                    'message' => 'Неизвестное действие'
                ];
        }
        
        if ($request->ajax()) {
            return response()->json($result);
        }
        
        return redirect()->route('admin.parser.manage_links')
            ->with($result['status'], $result['message']);
    }

    /**
     * Извлекает URL изображений с сайта eda.ru из HTML-документа
     *
     * @param string $html HTML-содержимое страницы
     * @return array Массив URL-адресов изображений
     */
    protected function extractEdaRuImages($html)
    {
        $images = [];
        
        if (empty($html)) {
            Log::warning("extractEdaRuImages: получен пустой HTML");
            return $images;
        }
        
        try {
            // Создаём объект DOMDocument
            $dom = new \DOMDocument();
            @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
            
            // Создаём объект DOMXPath для запросов
            $xpath = new \DOMXPath($dom);
            
            // Находим карточки рецептов и изображения
            $recipeCards = $xpath->query('//div[contains(@class, "recipe-item")]');
            foreach ($recipeCards as $card) {
                $imgNodes = $xpath->query('.//img', $card);
                foreach ($imgNodes as $img) {
                    if ($img instanceof \DOMElement && $img->hasAttribute('src')) {
                        $src = $img->getAttribute('src');
                        if (!empty($src)) {
                            $images[] = $src;
                        }
                    }
                    // Также проверяем data-src для ленивой загрузки
                    if ($img instanceof \DOMElement && $img->hasAttribute('data-src')) {
                        $src = $img->getAttribute('data-src');
                        if (!empty($src)) {
                            $images[] = $src;
                        }
                    }
                }
            }
            
            // Ищем дополнительные изображения рецептов
            $otherImages = $xpath->query('//div[contains(@class, "recipe-img") or contains(@class, "recipe-photo")]//img');
            foreach ($otherImages as $img) {
                if ($img instanceof \DOMElement && $img->hasAttribute('src')) {
                    $src = $img->getAttribute('src');
                    if (!empty($src)) {
                        $images[] = $src;
                    }
                }
                if ($img instanceof \DOMElement && $img->hasAttribute('data-src')) {
                    $src = $img->getAttribute('data-src');
                    if (!empty($src)) {
                        $images[] = $src;
                    }
                }
            }
            
            // Убедимся, что все URL абсолютные
            foreach ($images as &$img) {
                if (strpos($img, 'http') !== 0) {
                    $img = 'https://eda.ru' . $img;
                }
            }
            
            Log::info("Извлечено " . count(array_unique($images)) . " изображений с сайта eda.ru");
            
            return array_unique($images);
        } catch (\Exception $e) {
            Log::error("Ошибка при извлечении изображений: " . $e->getMessage());
            return $images;
        }
    }
}
