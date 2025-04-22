<?php

namespace App\Services;

use App\Models\Category;
use App\Models\Recipe;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;
use Exception;

class RecipeParserService
{
    /**
     * Добавление категорий к рецепту
     */
    public function addCategoriesToRecipe($recipe, $categoryNames)
    {
        try {
            $categoryIds = [];
            $categoryMap = [];
            
            // Создаем мапу существующих категорий для быстрого поиска
            $existingCategories = Category::all();
            foreach ($existingCategories as $category) {
                $categoryMap[mb_strtolower($category->name)] = $category->id;
            }
            
            foreach ($categoryNames as $categoryName) {
                $categoryName = trim($categoryName);
                $categoryKey = mb_strtolower($categoryName);
                
                if (isset($categoryMap[$categoryKey])) {
                    // Если категория существует, добавляем её ID в список
                    $categoryIds[] = $categoryMap[$categoryKey];
                } else {
                    // Если категории нет, создаем новую
                    try {
                        $newCategory = new Category();
                        $newCategory->name = $categoryName;
                        $newCategory->slug = Str::slug($categoryName);
                        $newCategory->save();
                        
                        // Добавляем новую категорию в список
                        $categoryIds[] = $newCategory->id;
                        
                        // Обновляем карту категорий
                        $categoryMap[$categoryKey] = $newCategory->id;
                    } catch (Exception $e) {
                        \Log::warning("Не удалось создать категорию: $categoryName. Ошибка: " . $e->getMessage());
                    }
                }
            }
            
            // Удаляем дубликаты
            $categoryIds = array_unique($categoryIds);
            
            // Привязываем категории к рецепту, если они найдены
            if (!empty($categoryIds)) {
                $recipe->categories()->attach($categoryIds);
            }
            
            return true;
        } catch (Exception $e) {
            \Log::error('Error adding categories to recipe: ' . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Создание рецепта на основе данных запроса
     */
    public function storeRecipe(Request $request)
    {
        try {
            // Создаем рецепт с базовыми полями
            $recipe = new Recipe();
            $recipe->title = $request->title;
            
            // Создаем базовый slug
            $baseSlug = Str::slug($request->title);
            
            // Проверяем существование слага и генерируем уникальный слаг при необходимости
            $slug = $this->generateUniqueSlug($baseSlug);
            $recipe->slug = $slug;
            
            \Log::info("Создаем рецепт с заголовком: {$request->title}, слаг: {$slug}");
            
            $recipe->description = $request->description;
            $recipe->cooking_time = $request->cooking_time;
            $recipe->servings = $request->servings;
            
            // Обработка данных о питательной ценности
            $recipe->calories = $request->calories;
            $recipe->proteins = $request->proteins;
            $recipe->fats = $request->fats;
            $recipe->carbs = $request->carbs;
            
            // Логируем все значения питательной ценности для отладки
            \Log::info("Сохраняемые значения питательной ценности: calories={$recipe->calories}, proteins={$recipe->proteins}, fats={$recipe->fats}, carbs={$recipe->carbs}");
            
            // Преобразуем строки в JSON для хранения в базе данных
            $recipe->ingredients = is_array($request->ingredients) ? json_encode($request->ingredients) : $request->ingredients;
            $recipe->instructions = is_array($request->instructions) ? json_encode($request->instructions) : $request->instructions;
            
            $recipe->source_url = $request->source_url;
            $recipe->is_published = $request->has('is_published');
            
            // Устанавливаем пользователя - добавляем администратора в качестве создателя
            $recipe->user_id = User::where('role', 'admin')->first()->id ?? 1;
            
            // Создаем структуру для хранения дополнительных данных
            $additionalData = [];
            
            // Сохраняем структурированные ингредиенты, если они есть
            if ($request->structured_ingredients) {
                $structuredIngredients = $request->structured_ingredients;
                if (is_string($structuredIngredients)) {
                    $structuredIngredients = json_decode($structuredIngredients, true);
                }
                $additionalData['structured_ingredients'] = $structuredIngredients;
            }
            
            // Сохраняем изображения шагов, если они есть
            if ($request->step_images && !empty($request->step_images)) {
                $additionalData['step_images'] = $request->step_images;
            }
            
            // Сохраняем данные о питательной ценности в дополнительные данные для сохранения исходных значений
            if ($request->calories || $request->proteins || $request->fats || $request->carbs) {
                $additionalData['nutrition'] = [
                    'calories' => $request->calories,
                    'proteins' => $request->proteins,
                    'fats' => $request->fats,
                    'carbs' => $request->carbs
                ];
            }
            
            // Сохраняем другие дополнительные данные
            if ($request->additional_data) {
                $otherData = is_string($request->additional_data) ? 
                                json_decode($request->additional_data, true) : 
                                $request->additional_data;
                
                if (is_array($otherData)) {
                    $additionalData = array_merge($additionalData, $otherData);
                }
            }
            
            // Сохраняем объединенные дополнительные данные в JSON-поле
            if (!empty($additionalData)) {
                $recipe->additional_data = json_encode($additionalData);
            }
            
            // Сохраняем рецепт
            $recipe->save();
            
            // Проверяем, что данные питательной ценности действительно сохранены
            \Log::info("После сохранения в БД: calories={$recipe->calories}, proteins={$recipe->proteins}, fats={$recipe->fats}, carbs={$recipe->carbs}");
            
            // Сохраняем изображения
            if ($request->has('image_urls') && is_array($request->image_urls) && !empty($request->image_urls)) {
                $this->saveRecipeImages($recipe, $request->image_urls);
            }
            
            return $recipe;
        } catch (Exception $e) {
            \Log::error('Error creating recipe: ' . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Генерирует уникальный слаг для рецепта
     */
    protected function generateUniqueSlug($baseSlug)
    {
        $slug = $baseSlug;
        $counter = 1;
        
        // Проверяем существование слага
        while (Recipe::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            
            // Предотвращаем бесконечный цикл
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
    public function saveRecipeImages($recipe, $imageUrls)
    {
        try {
            $mainImageSaved = false;
            $savedImages = [];
            $sliderImages = []; // Для хранения путей к изображениям слайдера
            $errors = [];
            
            // Создаем директорию, если она не существует
            $basePath = public_path('images/recipes/');
            if (!file_exists($basePath)) {
                mkdir($basePath, 0755, true);
            }
            
            // Логируем начало процесса сохранения изображений
            \Log::info("Начинаем сохранение изображений для рецепта ID: {$recipe->id}. Количество URL: " . count($imageUrls));
            
            foreach ($imageUrls as $index => $imageUrl) {
                try {
                    if (empty($imageUrl)) {
                        \Log::warning("Пустой URL изображения на индексе $index для рецепта ID: {$recipe->id}");
                        continue;
                    }
                    
                    // Проверяем URL изображения
                    $imageUrl = trim($imageUrl);
                    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        // Пробуем исправить некорректные URL
                        if (substr($imageUrl, 0, 2) === '//') {
                            $imageUrl = 'https:' . $imageUrl;
                        } else if (substr($imageUrl, 0, 1) === '/') {
                            // Для относительных URL получаем домен из source_url рецепта
                            $urlParts = parse_url($recipe->source_url);
                            $baseUrl = $urlParts['scheme'] . '://' . $urlParts['host'];
                            $imageUrl = $baseUrl . $imageUrl;
                        } else {
                            \Log::warning("Невалидный URL изображения: $imageUrl для рецепта ID: {$recipe->id}");
                            continue;
                        }
                    }
                    
                    \Log::info("Пытаемся загрузить изображение с URL: $imageUrl для рецепта ID: {$recipe->id}");
                    
                    // Используем HTTP-клиент вместо file_get_contents для лучшей обработки ошибок
                    $response = Http::withOptions([
                        'verify' => false, // Отключаем проверку SSL для некоторых сайтов
                        'timeout' => 15,   // Увеличиваем таймаут для больших изображений
                        'allow_redirects' => true, // Разрешаем редиректы для изображений
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
                    
                    // Проверяем тип полученных данных
                    $contentType = $response->header('Content-Type');
                    if (!$contentType || !strstr($contentType, 'image/')) {
                        // Дополнительная проверка содержимого
                        $finfo = new \finfo(FILEINFO_MIME_TYPE);
                        $detectedType = $finfo->buffer($imageContents);
                        
                        if (!strstr($detectedType, 'image/')) {
                            $errors[] = "Невалидное изображение (тип: $detectedType) с $imageUrl";
                            \Log::warning("Невалидный тип содержимого: $detectedType для $imageUrl");
                            continue;
                        }
                    }
                    
                    // Определяем расширение изображения
                    $extension = $this->getImageExtensionFromContent($imageContents, $imageUrl);
                    $filename = 'recipe_' . $recipe->id . '_' . ($index + 1) . '.' . $extension;
                    $filepath = $basePath . $filename;
                    
                    // Сохраняем изображение
                    $result = file_put_contents($filepath, $imageContents);
                    if ($result === false) {
                        $errors[] = "Ошибка записи файла $filename";
                        \Log::error("Не удалось записать изображение в файл: $filepath");
                        continue;
                    }
                    
                    \Log::info("Изображение успешно сохранено: $filepath");
                    
                    // Путь к изображению для сохранения в базе данных
                    $imagePath = 'images/recipes/' . $filename;
                    
                    // Устанавливаем первое изображение как главное
                    if (!$mainImageSaved) {
                        $recipe->image_url = $imagePath;
                        $recipe->save();
                        $mainImageSaved = true;
                        \Log::info("Установлено главное изображение для рецепта ID: {$recipe->id}: $imagePath");
                    } else {
                        // Добавляем в массив слайдера
                        $sliderImages[] = $imagePath;
                    }
                    
                    // Добавляем информацию о сохраненном изображении
                    $savedImages[] = [
                        'original_url' => $imageUrl,
                        'saved_path' => $imagePath
                    ];
                    
                } catch (Exception $e) {
                    $errors[] = "Ошибка обработки изображения ($imageUrl): " . $e->getMessage();
                    \Log::error("Исключение при обработке изображения {$imageUrl}: " . $e->getMessage());
                    continue;
                }
            }
            
            // Выводим статистику в лог
            \Log::info("Сохранение изображений завершено для рецепта ID: {$recipe->id}. Сохранено: " . 
                  count($savedImages) . ", Для слайдера: " . count($sliderImages) . ", Ошибок: " . count($errors));
            
            // Обновляем дополнительные данные рецепта, включая изображения и ошибки
            if (!empty($savedImages) || !empty($errors) || !empty($sliderImages)) {
                $additionalData = [];
                
                if ($recipe->additional_data) {
                    $additionalData = json_decode($recipe->additional_data, true) ?: [];
                }
                
                $additionalData['saved_images'] = $savedImages;
                
                // Добавляем изображения для слайдера
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
        } catch (Exception $e) {
            \Log::error("Критическая ошибка при сохранении изображений для рецепта ID: {$recipe->id}: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Определение расширения изображения из содержимого и URL
     */
    protected function getImageExtensionFromContent($imageContents, $imageUrl)
    {
        // Сначала пробуем определить из URL
        $parts = parse_url($imageUrl);
        $path = $parts['path'] ?? '';
        
        if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $path, $matches)) {
            $ext = strtolower($matches[1]);
            return $ext == 'jpeg' ? 'jpg' : $ext;
        }
        
        // Если не удалось определить из URL, используем finfo для определения типа
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
                // Если тип не определен, используем jpg по умолчанию
                return 'jpg';
        }
    }
}
