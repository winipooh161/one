<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\SocialPost;
use App\Services\TelegramService;
use App\Services\VkService;
use App\Services\ZenService;
use App\Services\TelegramChannelService; // Используем сервис для канального бота
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class RecipeSocialController extends Controller
{
    protected $vkService;
    protected $telegramService;

    public function __construct(VkService $vkService, TelegramService $telegramService)
    {
        $this->vkService = $vkService;
        $this->telegramService = $telegramService;
    }

    /**
     * Отображает список рецептов, доступных для публикации в социальных сетях
     */
    public function index()
    {
        // Получаем рецепты, которые еще не были опубликованы в соцсети
        $recipes = Recipe::where('is_published', true)
            ->whereDoesntHave('socialPosts')
            ->latest()
            ->paginate(10);

        // Получаем рецепты, которые уже были опубликованы
        $publishedRecipes = Recipe::whereHas('socialPosts')
            ->with('socialPosts')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.recipe_social.index', compact('recipes', 'publishedRecipes'));
    }

    /**
     * Предпросмотр рецепта для публикации в соцсети
     */
    public function preview($id)
    {
        $recipe = Recipe::findOrFail($id);
        
        // Определяем URL изображения
        $imageUrl = $recipe->image_url; // Предполагается, что у модели Recipe есть поле image_url
        
        // Если image_url не существует или пуст, используем изображение по умолчанию
        if (empty($imageUrl)) {
            // Проверяем наличие первого изображения в галерее
            if ($recipe->images && count($recipe->images) > 0) {
                $imageUrl = $recipe->images[0];
            } else {
                // Изображение по умолчанию, если нет других изображений
                $imageUrl = asset('images/default-recipe.jpg');
            }
        }
        
        // Генерация текста для публикации
        $content = $this->generateSocialContent($recipe);
        
        // Генерация HTML-контента для Дзен
        $zenContent = $this->generateZenContent($recipe);
        
        return view('admin.recipe_social.preview', compact('recipe', 'content', 'zenContent', 'imageUrl'));
    }

    /**
     * Публикация рецепта в социальные сети
     */
    public function publish(Request $request, $id)
    {
        $recipe = Recipe::findOrFail($id);
        
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'publish_to_telegram' => 'nullable|boolean',
            'publish_to_vk' => 'nullable|boolean',
        ]);
        
        // Проверяем, выбрана ли хотя бы одна социальная сеть
        if (!$request->has('publish_to_telegram') && !$request->has('publish_to_vk')) {
            return redirect()->back()->with('error', 'Необходимо выбрать хотя бы одну социальную сеть для публикации');
        }
        
        // Получаем корректный URL изображения
        $imageUrl = $this->getValidImageUrl($recipe);
        
        // Создаем новый пост
        $socialPost = new SocialPost();
        $socialPost->title = $validated['title'];
        $socialPost->content = $validated['content'];
        $socialPost->recipe_id = $recipe->id;
        $socialPost->image_url = $imageUrl;
        $socialPost->save();
        
        \Log::info('Создан новый пост для публикации', [
            'post_id' => $socialPost->id,
            'recipe_id' => $recipe->id,
            'image_url' => $imageUrl
        ]);
        
        $errors = [];
        $success = [];
        
        // Публикуем в Телеграм
        if ($request->has('publish_to_telegram')) {
            // Проверяем настройки Telegram перед публикацией
            $telegramSettings = $this->telegramService->checkSettings();
            
            if (!$telegramSettings['success']) {
                $errors[] = 'Ошибка конфигурации Telegram: ' . implode(', ', $telegramSettings['errors']);
            } else if ($this->telegramService->sendMessage($socialPost->content, $socialPost->image_url)) {
                $socialPost->update([
                    'telegram_status' => true,
                    'telegram_posted_at' => now(),
                ]);
                $success[] = 'Рецепт успешно опубликован в Telegram';
            } else {
                $errors[] = 'Не удалось опубликовать рецепт в Telegram. Проверьте логи для деталей.';
            }
        }
        
        // Публикуем во ВКонтакте
        if ($request->has('publish_to_vk')) {
            // Проверяем настройки ВКонтакте перед публикацией
            $vkSettings = $this->vkService->checkSettings();
            
            if (!$vkSettings['success']) {
                $errors[] = 'Ошибка конфигурации ВКонтакте: ' . implode(', ', $vkSettings['errors']);
            } else if ($this->vkService->publishPost($socialPost)) {
                $success[] = 'Рецепт успешно опубликован во ВКонтакте';
            } else {
                $errors[] = 'Не удалось опубликовать рецепт во ВКонтакте. Проверьте логи для деталей.';
            }
        }
        
        if (!empty($errors)) {
            return redirect()->route('admin.recipe-social.index')
                ->with('error', implode('<br>', $errors))
                ->with('success', implode('<br>', $success));
        }
        
        return redirect()->route('admin.recipe-social.index')
            ->with('success', implode('<br>', $success));
    }

    /**
     * Получает валидный URL изображения для рецепта
     */
    private function getValidImageUrl(Recipe $recipe)
    {
        $imageUrl = $recipe->getImageUrl();
        
        // Проверяем, не пустой ли URL
        if (empty($imageUrl)) {
            return '';
        }
        
        // Проверяем, является ли URL относительным
        if (!str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
            $imageUrl = config('app.url') . '/' . ltrim($imageUrl, '/');
        }
        
        // Проверка доступности изображения и корректировка URL
        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
            try {
                $headers = get_headers($imageUrl, 1);
                if (strpos($headers[0], '200') === false) {
                    // Изображение недоступно, попробуем поискать другой путь
                    \Log::warning('Image URL is not accessible', [
                        'recipe_id' => $recipe->id, 
                        'image_url' => $imageUrl,
                        'headers' => $headers[0]
                    ]);
                       
                    // Попробуем найти локальный файл
                    $localPath = str_replace(config('app.url'), '', $imageUrl);
                    $localPath = ltrim($localPath, '/');
                    
                    if (file_exists(public_path($localPath))) {
                        $imageUrl = config('app.url') . '/' . $localPath;
                        \Log::info('Found local file, using URL', ['image_url' => $imageUrl]);
                    } else {
                        \Log::warning('Local file not found', ['path' => public_path($localPath)]);
                    }
                }
            } catch (\Exception $e) {
                \Log::warning('Error checking image availability: ' . $e->getMessage());
            }
        }
        
        \Log::info('Получен URL изображения для рецепта', [
            'recipe_id' => $recipe->id,
            'image_url' => $imageUrl
        ]);
        
        return $imageUrl;
    }

    /**
     * Генерация текста для публикации рецепта в соцсети
     */
    private function generateSocialContent(Recipe $recipe)
    {
        $content = "🍳 *{$recipe->title}*\n\n";
        
        // Добавляем описание, если оно есть
        if (!empty($recipe->description)) {
            $content .= trim($recipe->description) . "\n\n";
        }
        
        // Добавляем информацию о времени приготовления и порциях
        $content .= "⏱ *Время приготовления:* {$recipe->cooking_time} минут\n";
        if (!empty($recipe->servings)) {
            $content .= "👥 *Порций:* {$recipe->servings}\n";
        }
        
        // Добавляем энергетическую ценность, если она указана
        if ($recipe->calories || $recipe->proteins || $recipe->fats || $recipe->carbs) {
            $content .= "\n*Энергетическая ценность:*\n";
            if ($recipe->calories) $content .= "🔸 Калории: {$recipe->calories} ккал\n";
            if ($recipe->proteins) $content .= "🔸 Белки: {$recipe->proteins} г\n";
            if ($recipe->fats) $content .= "🔸 Жиры: {$recipe->fats} г\n";
            if ($recipe->carbs) $content .= "🔸 Углеводы: {$recipe->carbs} г\n";
        }
        
        // Добавляем категории рецепта
        if ($recipe->categories && $recipe->categories->count() > 0) {
            $content .= "\n*Категории:* ";
            $categories = [];
            foreach ($recipe->categories as $category) {
                $categories[] = $category->name;
            }
            $content .= implode(', ', $categories) . "\n";
        }
        
        // Проверяем, какой формат ингредиентов используется в рецепте
        if (!empty($recipe->ingredients) && is_string($recipe->ingredients)) {
            // Строковый формат ингредиентов
            $content .= "\n*Ингредиенты:*\n";
            $ingredients = explode("\n", $recipe->ingredients);
            foreach ($ingredients as $ingredient) {
                if (!empty(trim($ingredient))) {
                    $content .= "• " . trim($ingredient) . "\n";
                }
            }
        } elseif ($recipe->ingredients && is_object($recipe->ingredients) && $recipe->ingredients->count() > 0) {
            // Коллекция ингредиентов
            $content .= "\n📋 *Ингредиенты:*\n";
            foreach ($recipe->ingredients as $ingredient) {
                $content .= "• {$ingredient->name}";
                if (!empty($ingredient->quantity)) {
                    $content .= " — {$ingredient->quantity}";
                    if (!empty($ingredient->unit)) {
                        $content .= " {$ingredient->unit}";
                    }
                }
                $content .= "\n";
            }
        }
        
        // Добавляем шаги приготовления, если они есть в виде коллекции
        if ($recipe->steps && is_object($recipe->steps) && $recipe->steps->count() > 0) {
            $content .= "\n👨‍🍳 *Приготовление:*\n";
            foreach ($recipe->steps as $index => $step) {
                $content .= ($index + 1) . ". {$step->description}\n";
            }
        }
        
        // Добавляем ссылку на сайт
        $recipeUrl = route('recipes.show', $recipe->slug);
        
        // Убедимся что URL абсолютный и содержит домен
        if (!str_starts_with($recipeUrl, 'http')) {
            $recipeUrl = config('app.url') . $recipeUrl;
        }
        
        $content .= "\nПодробнее на сайте: {$recipeUrl}";
        
        // Логируем для отладки
        \Log::info('Generated social content with link', [
            'recipe_id' => $recipe->id,
            'url' => $recipeUrl,
            'content_length' => strlen($content)
        ]);
        
        return $content;
    }

    /**
     * Генерация HTML-контента для публикации в Дзене
     */
    public function generateZenContent(Recipe $recipe)
    {
        $htmlContent = "<h1>{$recipe->title}</h1>";
        
        // Добавляем описание, если есть
        if (!empty($recipe->description)) {
            $htmlContent .= "<p>{$recipe->description}</p>";
        }
        
        // Добавляем информацию о времени приготовления
        if (!empty($recipe->cooking_time)) {
            $htmlContent .= "<p><strong>Время приготовления:</strong> {$recipe->cooking_time} мин.</p>";
        }
        
        // Добавляем ингредиенты
        $htmlContent .= "<h2>Ингредиенты:</h2><ul>";
        
        // Проверяем, какой формат ингредиентов используется
        if (!empty($recipe->ingredients) && is_string($recipe->ingredients)) {
            // Строковый формат ингредиентов
            $ingredients = explode("\n", $recipe->ingredients);
            foreach ($ingredients as $ingredient) {
                if (!empty(trim($ingredient))) {
                    $htmlContent .= "<li>" . trim($ingredient) . "</li>";
                }
            }
        } elseif ($recipe->ingredients && is_object($recipe->ingredients) && $recipe->ingredients->count() > 0) {
            // Коллекция ингредиентов
            foreach ($recipe->ingredients as $ingredient) {
                $htmlContent .= "<li>{$ingredient->name}";
                if (!empty($ingredient->quantity)) {
                    $htmlContent .= " — {$ingredient->quantity}";
                    if (!empty($ingredient->unit)) {
                        $htmlContent .= " {$ingredient->unit}";
                    }
                }
                $htmlContent .= "</li>";
            }
        }
        
        $htmlContent .= "</ul>";
        
        // Добавляем шаги приготовления
        if ($recipe->steps && is_object($recipe->steps) && $recipe->steps->count() > 0) {
            $htmlContent .= "<h2>Приготовление:</h2><ol>";
            foreach ($recipe->steps as $step) {
                $htmlContent .= "<li>{$step->description}";
                if (!empty($step->image)) {
                    $htmlContent .= "<br><img src='" . asset($step->image) . "' alt='Шаг приготовления'>";
                }
                $htmlContent .= "</li>";
            }
            $htmlContent .= "</ol>";
        }
        
        // Добавляем ссылку на сайт
        $recipeUrl = route('recipes.show', $recipe->slug);
        if (!str_starts_with($recipeUrl, 'http')) {
            $recipeUrl = config('app.url') . '/' . ltrim($recipeUrl, '/');
        }
        
        $htmlContent .= "<p>Подробнее на <a href='{$recipeUrl}'>нашем сайте</a>.</p>";
        
        return $htmlContent;
    }
    
    /**
     * Публикация рецепта в Telegram
 
    
    /**
     * Публикация рецепта в Дзен
     */
    public function publishToZen(Recipe $recipe)
    {
        try {
            $zenService = app(ZenService::class);
            $title = $recipe->title;
            $htmlContent = $this->generateZenContent($recipe);
            $imageUrl = $this->getRecipeMainImage($recipe);
            
            $result = $zenService->publish($title, $htmlContent, $imageUrl);
            
            if ($result) {
                // Сохраняем информацию о публикации
                SocialPost::create([
                    'recipe_id' => $recipe->id,
                    'title' => $title, // Добавляем заголовок
                    'content' => $htmlContent, // Добавляем HTML-содержимое
                    'platform' => 'zen',
                    'post_id' => $result['publication_id'] ?? null,
                    'post_url' => $result['publication_url'] ?? null,
                    'status' => 'published',
                    'published_at' => now(),
                    'image_url' => $imageUrl, // Добавляем URL изображения для полноты данных
                ]);
                
                return redirect()->back()->with('success', 'Рецепт успешно опубликован в Дзен!');
            } else {
                return redirect()->back()->with('error', 'Ошибка при публикации в Дзен');
            }
        } catch (\Exception $e) {
            \Log::error('Ошибка при публикации в Дзен: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Произошла ошибка: ' . $e->getMessage());
        }
    }

    /**
     * Публикация рецепта во ВКонтакте
     */
    public function publishToVk(Recipe $recipe)
    {
        try {
            $vkService = app(VkService::class);
            $content = $this->generateSocialContent($recipe);
            
            // Получаем URL изображения с проверкой
            $imageUrl = $this->getRecipeMainImage($recipe);
            
            // Проверяем настройки ВКонтакте перед публикацией, но пропускаем проверку API
            $vkSettings = $vkService->checkSettings(true);
            if (!$vkSettings['success']) {
                $errors = implode(', ', $vkSettings['errors']);
                return redirect()->back()->with('error', 'Ошибка конфигурации ВКонтакте: ' . $errors);
            }
            
            Log::info('Подготовка публикации в ВКонтакте', [
                'recipe_id' => $recipe->id,
                'image_url' => $imageUrl,
                'content_length' => strlen($content)
            ]);
            
            // Создаем объект SocialPost для публикации
            $post = new SocialPost();
            $post->title = $recipe->title;
            $post->content = $content;
            $post->recipe_id = $recipe->id;
            $post->image_url = $imageUrl;
            $post->save();
            
            // Публикуем пост в форсированном режиме
            if ($vkService->publishPost($post, true)) {
                Log::info('Успешно создана запись о публикации в ВКонтакте', [
                    'post_id' => $post->id,
                    'recipe_id' => $recipe->id,
                ]);
                
                return redirect()->back()->with('success', 'Рецепт успешно опубликован во ВКонтакте!');
            } else {
                return redirect()->back()->with('error', 'Ошибка при публикации во ВКонтакте. Проверьте наличие соединения с API.');
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при публикации во ВКонтакте: ' . $e->getMessage(), [
                'recipe_id' => $recipe->id,
                'exception' => $e,
                'trace' => $e->getTraceAsString()
            ]);
            return redirect()->back()->with('error', 'Произошла ошибка: ' . $e->getMessage());
        }
    }

    /**
     * Получает основное изображение рецепта для использования в публикациях
     */
    private function getRecipeMainImage(Recipe $recipe)
    {
        // Пробуем получить изображение из разных источников
        $imageSources = [
            $recipe->image,
            $recipe->image_url,
            // Проверяем галерею изображений, если она есть
            $recipe->images && !empty($recipe->images) && is_array($recipe->images) ? reset($recipe->images) : null,
        ];
        
        // Перебираем возможные источники изображений
        foreach ($imageSources as $imageUrl) {
            if (!empty($imageUrl)) {
                // Проверяем, является ли URL относительным
                if (!str_starts_with($imageUrl, 'http://') && !str_starts_with($imageUrl, 'https://')) {
                    $imageUrl = config('app.url') . '/' . ltrim($imageUrl, '/');
                }
                
                // Логируем информацию о найденном изображении
                \Log::info('Найдено изображение для публикации', [
                    'recipe_id' => $recipe->id,
                    'image_url' => $imageUrl
                ]);
                
                return $imageUrl;
            }
        }
        
        // Если изображение не найдено, возвращаем изображение по умолчанию
        $defaultImageUrl = asset('images/default-recipe.jpg');
        
        \Log::warning('Для рецепта не найдено изображение, используем стандартное', [
            'recipe_id' => $recipe->id,
            'default_image' => $defaultImageUrl
        ]);
        
        return $defaultImageUrl;
    }

    /**
     * Публикация рецепта в Telegram канал
     */
    public function publishToTelegram(Request $request, $id)
    {
        try {
            $recipe = Recipe::findOrFail($id);
            
            // Создаем текст сообщения
            $text = "*{$recipe->title}*\n\n";
            $text .= $recipe->description ? strip_tags($recipe->description) . "\n\n" : '';
            
            if ($recipe->cooking_time) {
                $text .= "⏱ Время приготовления: {$recipe->cooking_time} мин.\n";
            }
            
            if ($recipe->servings) {
                $text .= "🍽 Порций: {$recipe->servings}\n";
            }
            
            // Добавляем ссылку на рецепт
            $text .= "\n🔗 [Посмотреть полный рецепт](" . route('recipes.show', $recipe->slug) . ")";
            
            // Используем сервис канального бота
            $telegramService = new TelegramChannelService();
            $result = $telegramService->sendPhotoToChannel($recipe->getImageUrl(), $text, 'Markdown');
            
            if ($result && isset($result['ok']) && $result['ok']) {
                // Сохраняем информацию о публикации
                // ...
                
                return response()->json([
                    'success' => true,
                    'message' => 'Рецепт успешно опубликован в Telegram'
                ]);
            } else {
                Log::error('Ошибка публикации в Telegram: ' . json_encode($result));
                return response()->json([
                    'success' => false,
                    'message' => 'Произошла ошибка при публикации в Telegram'
                ], 500);
            }
        } catch (\Exception $e) {
            Log::error('Исключение при публикации в Telegram: ' . $e->getMessage());
            return response()->json([
                'success' => false,
                'message' => 'Произошла ошибка: ' . $e->getMessage()
            ], 500);
        }
    }
}
