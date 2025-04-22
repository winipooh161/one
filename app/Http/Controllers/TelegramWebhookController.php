<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Recipe;
use App\Models\Tag;
use App\Models\Category;
use App\Models\Ingredient;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Telegram\Bot\Api as Telegram;
use Telegram\Bot\Keyboard\Keyboard;
use Telegram\Bot\FileUpload\InputFile;
use Illuminate\Support\Facades\Schema;

class TelegramWebhookController extends Controller
{
    protected $telegram;
    
    // Определяем типы блюд (холодные/горячие и т.д.)
    protected $dishTypes = [
        'hot' => [
            'name' => 'Горячие блюда',
            'keywords' => ['горячее', 'жареное', 'запеченное', 'тушеное', 'гриль', 'суп', 'борщ', 'щи', 'каша', 'рагу'],
            'categories' => ['Горячие блюда', 'Супы', 'Вторые блюда', 'Мясные блюда', 'Гриль'],
        ],
        'cold' => [
            'name' => 'Холодные блюда',
            'keywords' => ['холодное', 'салат', 'закуска', 'бутерброд', 'сэндвич', 'нарезка', 'холодец', 'заливное'],
            'categories' => ['Салаты', 'Закуски', 'Холодные закуски', 'Холодные блюда'],
        ],
        'dessert' => [
            'name' => 'Десерты',
            'keywords' => ['десерт', 'сладкое', 'торт', 'пирожное', 'мороженое', 'конфеты', 'выпечка', 'сладкая выпечка'],
            'categories' => ['Десерты', 'Сладкая выпечка', 'Торты', 'Пирожные', 'Сладости'],
        ],
        'drink' => [
            'name' => 'Напитки',
            'keywords' => ['напиток', 'коктейль', 'смузи', 'чай', 'кофе', 'сок', 'компот', 'морс'],
            'categories' => ['Напитки', 'Коктейли', 'Безалкогольные напитки'],
        ],
        'appetizer' => [
            'name' => 'Закуски',
            'keywords' => ['закуска', 'канапе', 'тапас', 'снэк', 'фингерфуд'],
            'categories' => ['Закуски', 'Быстрые закуски', 'Фуршетные блюда'],
        ],
        'soup' => [
            'name' => 'Супы',
            'keywords' => ['суп', 'бульон', 'крем-суп', 'окрошка', 'уха', 'борщ', 'солянка', 'щи'],
            'categories' => ['Супы', 'Первые блюда'],
        ],
        'main' => [
            'name' => 'Основные блюда',
            'keywords' => ['мясо', 'рыба', 'птица', 'гарнир', 'паста', 'макароны', 'картофель', 'рис'],
            'categories' => ['Основные блюда', 'Мясные блюда', 'Рыбные блюда', 'Вторые блюда'],
        ],
        'breakfast' => [
            'name' => 'Завтраки',
            'keywords' => ['завтрак', 'омлет', 'яичница', 'каша', 'бутерброд', 'тост', 'мюсли', 'гранола'],
            'categories' => ['Завтраки', 'Утренние блюда', 'Быстрые завтраки'],
        ],
        'healthy' => [
            'name' => 'Правильное питание',
            'keywords' => ['диет', 'здоров', 'постное', 'веган', 'вегетариан', 'низкокалорийн', 'пп', 'правильное питание'],
            'categories' => ['Диетические блюда', 'Здоровое питание', 'Постные блюда', 'Вегетарианские блюда'],
        ],
        'quick' => [
            'name' => 'Быстрые рецепты',
            'keywords' => ['быстро', 'за 15 минут', 'за 30 минут', 'простой', 'легкий', 'экспресс'],
            'categories' => ['Быстрые рецепты', 'Простые блюда', 'Экспресс-рецепты'],
        ],
    ];
    
    public function __construct(Telegram $telegram)
    {
        $this->telegram = $telegram;
    }
    
    public function handle(Request $request)
    {
        // Немедленно отвечаем на webhook для освобождения соединения
        $this->sendImmediateResponse();
        
        try {
            // Получаем обновление от Telegram
            $update = $this->telegram->getWebhookUpdate();
            
            // Логируем минимальную информацию для скорости
            if ($update->has('message')) {
                $message = $update->getMessage();
                $chatId = $message->getChat()->getId();
                $messageId = $message->getMessageId();
                $messageText = $message->getText();
                $chatType = $message->getChat()->getType();
                
                Log::info('Получено сообщение', [
                    'chat_id' => $chatId,
                    'message_id' => $messageId,
                    'type' => $chatType,
                    'text' => $messageText
                ]);
                
                // Быстро сохраняем чат без блокировки основного потока
                $this->saveChatInBackground($message->getChat());
                
                // Быстро сохраняем сообщение в фоне
                $this->saveMessageInBackground($message, 'incoming');
                
                // Проверяем, является ли сообщение командой и быстро отвечаем
                // ВАЖНО: Реагируем даже если команда не начинается с /
                if (!empty($messageText)) {
                    if (strpos($messageText, '/') === 0) {
                        // Это явная команда, обрабатываем её как команду
                        return $this->handleCommand($messageText, $chatId, $messageId);
                    } else {
                        // Это обычный текст - ищем рецепты
                        return $this->handleTextSearch($messageText, $chatId, $messageId);
                    }
                } else {
                    // Сообщение без текста, отправляем инструкцию
                    $this->replyToMessage($chatId, $messageId, 'Пожалуйста, укажите ключевые слова для поиска рецепта или воспользуйтесь командой /help');
                    return response()->json(['status' => 'ok']);
                }
            } elseif ($update->has('callback_query')) {
                // Обрабатываем callback запросы (нажатия на кнопки)
                return $this->handleCallbackQuery($update->getCallbackQuery());
            } else {
                // Логируем необработанные типы обновлений
                Log::info('Получено обновление неизвестного типа', [
                    'update' => json_encode($update)
                ]);
            }
        } catch (\Exception $e) {
            // Минимальное логирование ошибок
            Log::error('Ошибка бота: ' . $e->getMessage());
        }
        
        return response()->json(['status' => 'ok']);
    }
    
    /**
     * Отправляет немедленный HTTP-ответ для освобождения соединения
     */
    private function sendImmediateResponse()
    {
        // Отправляем немедленный ответ, чтобы Telegram не ждал
        if (function_exists('fastcgi_finish_request')) {
            header('Content-Type: application/json');
            echo json_encode(['status' => 'ok']);
            session_write_close();
            if (function_exists('fastcgi_finish_request')) {
                fastcgi_finish_request();
            } else {
                ob_flush();
                flush();
            }
        }
    }
    
    /**
     * Сохраняет информацию о чате в фоновом режиме 
     */
    private function saveChatInBackground($chat)
    {
        try {
            TelegramChat::updateOrCreate(
                ['chat_id' => $chat->getId()],
                [
                    'type' => $chat->getType(),
                    'username' => $chat->getUsername(),
                    'first_name' => $chat->getFirstName(),
                    'last_name' => $chat->getLastName(),
                    'last_activity_at' => now(),
                    'is_active' => true,
                ]
            );
        } catch (\Exception $e) {
            Log::error('Ошибка сохранения чата: ' . $e->getMessage());
        }
    }
    
    /**
     * Сохраняет сообщение в фоновом режиме
     */
    private function saveMessageInBackground($message, $direction = 'incoming')
    {
        try {
            // Определяем данные сообщения
            $messageId = is_object($message) ? $message->getMessageId() : ($message['message_id'] ?? null);
            $chatId = is_object($message) ? $message->getChat()->getId() : ($message['chat_id'] ?? null);
            $text = is_object($message) ? ($message->getText() ?: '[Нет текста]') : ($message['text'] ?? '[Нет текста]');
            
            if ($messageId && $chatId) {
                TelegramMessage::updateOrCreate(
                    ['message_id' => $messageId, 'chat_id' => $chatId],
                    [
                        'text' => substr($text, 0, 1000),
                        'direction' => $direction,
                    ]
                );
            }
        } catch (\Exception $e) {
            // Только логируем ошибку
            Log::error('Ошибка при сохранении сообщения: ' . $e->getMessage());
        }
    }
    
    /**
     * Обработка команд бота с немедленным ответом
     */
    private function handleCommand($text, $chatId, $messageId)
    {
        // Показываем "печатает" для улучшения UX
        $this->telegram->sendChatAction([
            'chat_id' => $chatId,
            'action' => 'typing'
        ]);
        
        try {
            // Разбираем команду
            $parts = explode(' ', trim($text));
            $command = strtolower($parts[0]);
            
            switch ($command) {
                case '/start':
                    $this->handleStartCommand($chatId, $messageId);
                    break;
                    
                case '/help':
                    $this->handleHelpCommand($chatId, $messageId);
                    break;
                    
                case '/category':
                case '/categories':
                    $this->handleCategoriesCommand($chatId, $messageId);
                    break;
                    
                case '/dish_types':
                    $this->handleDishTypesCommand($chatId, $messageId);
                    break;
                    
                case '/random':
                    $this->handleRandomCommand($chatId, $messageId);
                    break;
                    
                case '/popular':
                    $this->handlePopularCommand($chatId, $messageId);
                    break;
                    
                case '/healthy':
                    $this->handleHealthyCommand($chatId, $messageId);
                    break;
                    
                case '/history':
                    $this->handleHistoryCommand($chatId, $messageId);
                    break;
                    
                case '/clear_history':
                    $this->handleClearHistoryCommand($chatId, $messageId);
                    break;
                    
                default:
                    $this->replyToMessage($chatId, $messageId, "Неизвестная команда. Воспользуйтесь /help для получения списка доступных команд.");
            }
        } catch (\Exception $e) {
            Log::error('Ошибка обработки команды: ' . $e->getMessage());
            $this->replyToMessage($chatId, $messageId, "Произошла ошибка при обработке команды. Пожалуйста, попробуйте снова.");
        }
        
        return response()->json(['status' => 'ok']);
    }
    
    /**
     * Обработка текстового поиска
     */
    private function handleTextSearch($query, $chatId, $messageId)
    {
        // Показываем "печатает" для улучшения UX
        $this->telegram->sendChatAction([
            'chat_id' => $chatId,
            'action' => 'typing'
        ]);
        
        try {
            // Получаем или создаем запись чата для сохранения истории
            $telegramChat = TelegramChat::firstOrCreate(
                ['chat_id' => $chatId],
                ['is_active' => true, 'last_activity_at' => now()]
            );
            
            // Добавляем запрос в историю поиска
            $telegramChat->addSearchQuery($query);
            
            // Получаем список ID просмотренных рецептов
            $viewedRecipes = $telegramChat->getViewedRecipes();
            
            // Быстрый поиск рецептов с исключением просмотренных
            $recipes = $this->fastRecipeSearch($query, $viewedRecipes);
            
            if ($recipes->isEmpty()) {
                // Если не нашли, пробуем искать повторно, но без исключения просмотренных
                $recipes = $this->fastRecipeSearch($query);
                
                if ($recipes->isEmpty()) {
                    $this->replyToMessage($chatId, $messageId, "К сожалению, рецепты по запросу \"{$query}\" не найдены. Попробуйте изменить запрос или воспользуйтесь командой /categories для выбора категории.");
                    
                    // Предлагаем альтернативные варианты поиска
                    $this->suggestAlternativeSearch($chatId, $messageId);
                    return response()->json(['status' => 'ok']);
                } else {
                    // Нашли с учетом просмотренных - сообщаем об этом
                    $this->sendRecipesWithReply($chatId, $messageId, $recipes, "🔍 *Рецепты по запросу \"{$query}\"* (включая ранее просмотренные):\n\n");
                    
                    // Добавляем рецепты в список просмотренных
                    foreach ($recipes as $recipe) {
                        $telegramChat->addViewedRecipe($recipe->id);
                    }
                }
            } else {
                $this->sendRecipesWithReply($chatId, $messageId, $recipes, "🔍 *Рецепты по запросу \"{$query}\":*\n\n");
                
                // Добавляем рецепты в список просмотренных
                foreach ($recipes as $recipe) {
                    $telegramChat->addViewedRecipe($recipe->id);
                }
            }
        } catch (\Exception $e) {
            Log::error('Ошибка поиска рецептов: ' . $e->getMessage());
            $this->replyToMessage($chatId, $messageId, "Произошла ошибка при поиске. Пожалуйста, попробуйте позже.");
        }
        
        return response()->json(['status' => 'ok']);
    }
    
    /**
     * Быстрый поиск рецептов с исключением просмотренных
     */
    private function fastRecipeSearch($query, $excludeIds = [])
    {
        // Кеширование частых запросов с учетом исключаемых ID
        $excludeKey = empty($excludeIds) ? 'none' : md5(json_encode($excludeIds));
        $cacheKey = 'recipe_search_' . md5($query) . '_exclude_' . $excludeKey;
        
        return Cache::remember($cacheKey, 1800, function() use ($query, $excludeIds) {
            // Оптимизированный запрос с прямым поиском по названию (самый быстрый)
            $recipesQuery = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
                ->with(['categories:id,name'])
                ->where('is_published', 1)
                ->where('title', 'like', '%' . $query . '%');
                
            // Исключаем просмотренные рецепты, если они указаны
            if (!empty($excludeIds)) {
                $recipesQuery->whereNotIn('id', $excludeIds);
            }
            
            $recipes = $recipesQuery->limit(15)->get();
            
            // Если не нашли по названию, ищем в других полях
            if ($recipes->isEmpty()) {
                $recipesQuery = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
                    ->with(['categories:id,name'])
                    ->where('is_published', 1)
                    ->where(function($q) use ($query) {
                        $q->where('description', 'like', '%' . $query . '%')
                          ->orWhereHas('categories', function($q) use ($query) {
                              $q->where('name', 'like', '%' . $query . '%');
                          })
                          ->orWhereHas('ingredients', function($q) use ($query) {
                              $q->where('name', 'like', '%' . $query . '%');
                          });
                    });
                    
                // Исключаем просмотренные рецепты
                if (!empty($excludeIds)) {
                    $recipesQuery->whereNotIn('id', $excludeIds);
                }
                
                $recipes = $recipesQuery->limit(7)->get();
            }
            
            return $recipes;
        });
    }
    
    /**
     * Предлагает альтернативные варианты поиска
     */
    private function suggestAlternativeSearch($chatId, $messageId)
    {
        $keyboard = new Keyboard();
        $keyboard->inline();
        
        // Добавляем кнопки основных категорий
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🍲 Горячие блюда', 'callback_data' => 'dish_type:hot']),
            Keyboard::inlineButton(['text' => '🥗 Холодные блюда', 'callback_data' => 'dish_type:cold'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🍰 Десерты', 'callback_data' => 'dish_type:dessert']),
            Keyboard::inlineButton(['text' => '🍹 Напитки', 'callback_data' => 'dish_type:drink'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🥪 Закуски', 'callback_data' => 'dish_type:appetizer']),
            Keyboard::inlineButton(['text' => '🍲 Супы', 'callback_data' => 'dish_type:soup'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '⏱ Быстрые рецепты', 'callback_data' => 'dish_type:quick']),
            Keyboard::inlineButton(['text' => '📋 Все категории', 'callback_data' => 'command:categories'])
        ]);
        
        $this->replyToMessage(
            $chatId,
            $messageId,
            "Попробуйте выбрать одну из популярных категорий или посмотреть полный список категорий:",
            $keyboard
        );
    }
    
    /**
     * Отправляет ответ на сообщение, указывая original_message_id
     */
    private function replyToMessage($chatId, $messageId, $text, $keyboard = null, $parseMode = 'Markdown')
    {
        try {
            $params = [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode,
                'reply_to_message_id' => $messageId, // Важно: отвечаем на конкретное сообщение
                'allow_sending_without_reply' => true,
            ];
            
            if ($keyboard) {
                $params['reply_markup'] = $keyboard;
            }
            
            $response = $this->telegram->sendMessage($params);
            
            // Сохраняем наш ответ в фоне
            if ($response && $response->getMessageId()) {
                $this->saveMessageInBackground([
                    'message_id' => $response->getMessageId(),
                    'chat_id' => $chatId,
                    'text' => $text
                ], 'outgoing');
            }
            
            return $response;
        } catch (\Exception $e) {
            Log::error('Ошибка отправки ответа: ' . $e->getMessage());
            
            // Попытка отправить сообщение без reply_to_message_id в случае ошибки
            try {
                unset($params['reply_to_message_id']);
                $response = $this->telegram->sendMessage($params);
                return $response;
            } catch (\Exception $e2) {
                Log::error('Повторная ошибка отправки: ' . $e2->getMessage());
                return null;
            }
        }
    }
    
    /**
     * Обработка команды /start
     */
    private function handleStartCommand($chatId, $messageId)
    {
        $welcomeMessage = "*Добро пожаловать в бот рецептов!* 🍽\n\n"
            . "Здесь вы можете найти различные рецепты блюд по ключевым словам или категориям.\n\n"
            . "Что я умею:\n"
            . "• Искать рецепты по названию, ингредиентам или типу блюда\n"
            . "• Показывать список категорий и типов блюд\n"
            . "• Предлагать случайные рецепты\n"
            . "• Показывать популярные рецепты\n"
            . "• Запоминать историю поиска и просмотров\n\n"
            . "Для поиска просто напишите то, что хотите приготовить, например: \"Борщ\" или \"Десерт с клубникой\".";
        
        // Создаем клавиатуру с основными командами
        $keyboard = new Keyboard();
        $keyboard->inline();
        $keyboard->row([
            Keyboard::inlineButton(['text' => '📋 Категории', 'callback_data' => 'command:categories']),
            Keyboard::inlineButton(['text' => '🍽 Типы блюд', 'callback_data' => 'command:dish_types'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🎲 Случайный', 'callback_data' => 'command:random']),
            Keyboard::inlineButton(['text' => '🔝 Популярные', 'callback_data' => 'command:popular'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '📜 История', 'callback_data' => 'command:history']),
            Keyboard::inlineButton(['text' => '❓ Помощь', 'callback_data' => 'command:help'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🥗 Правильное питание', 'callback_data' => 'command:healthy'])
        ]);
        
        $this->replyToMessage($chatId, $messageId, $welcomeMessage, $keyboard);
    }
    
    /**
     * Обработка команды /help
     */
    private function handleHelpCommand($chatId, $messageId)
    {
        $helpMessage = "*Доступные команды:*\n\n"
            . "📌 */start* - Начать работу с ботом\n"
            . "📌 */help* - Показать список команд\n"
            . "📌 */categories* - Выбрать категорию рецептов\n"
            . "📌 */dish_types* - Выбрать тип блюда (горячее/холодное)\n"
            . "📌 */random* - Получить случайный рецепт\n"
            . "📌 */popular* - Показать популярные рецепты\n"
            . "📌 */history* - Показать историю поиска\n"
            . "📌 */clear_history* - Очистить историю просмотренных рецептов\n\n"
            . "Для поиска рецепта просто отправьте сообщение с ключевыми словами, например: \"Паста с грибами\"";
        
        // Клавиатура с основными командами для удобства
        $keyboard = new Keyboard();
        $keyboard->inline();
        $keyboard->row([
            Keyboard::inlineButton(['text' => '📋 Категории', 'callback_data' => 'command:categories']),
            Keyboard::inlineButton(['text' => '🍽 Типы блюд', 'callback_data' => 'command:dish_types'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🎲 Случайный', 'callback_data' => 'command:random']),
            Keyboard::inlineButton(['text' => '🔝 Популярные', 'callback_data' => 'command:popular'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '📜 История', 'callback_data' => 'command:history']),
            Keyboard::inlineButton(['text' => '🧹 Очистить историю', 'callback_data' => 'command:clear_history'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🥗 Правильное питание', 'callback_data' => 'command:healthy']),
            Keyboard::inlineButton(['text' => '🏠 Главная', 'callback_data' => 'command:start'])
        ]);
        
        $this->replyToMessage($chatId, $messageId, $helpMessage, $keyboard);
    }
    
    /**
     * Обработка команды /category - перенаправляем на новую команду categories
     */
    private function handleCategoryCommand($chatId, $messageId)
    {
        $this->handleCategoriesCommand($chatId, $messageId);
    }
    
    /**
     * Обработка команды /categories
     */
    private function handleCategoriesCommand($chatId, $messageId)
    {
        // Кэшируем категории для быстрого доступа
        $categories = Cache::remember('telegram_categories', 3600, function() {
            return Category::select('id', 'name')
                ->when(Schema::hasColumn('categories', 'parent_id'), function($query) {
                    return $query->whereNull('parent_id');
                })
                ->orderBy('name')
                ->limit(40)
                ->get();
        });
        
        if ($categories->isEmpty()) {
            $this->replyToMessage($chatId, $messageId, "К сожалению, категории не найдены.");
            return;
        }
        
        // Создаем клавиатуру с категориями
        $keyboard = new Keyboard();
        $keyboard->inline();
        
        // Добавляем кнопки категорий по 2 в ряд
        $rowButtons = [];
        foreach ($categories as $index => $category) {
            $rowButtons[] = Keyboard::inlineButton([
                'text' => $category->name, 
                'callback_data' => 'category:' . $category->id
            ]);
            
            // Добавляем по 2 кнопки в ряд
            if (count($rowButtons) === 2 || $index === $categories->count() - 1) {
                $keyboard->row($rowButtons);
                $rowButtons = [];
            }
        }
        
        // Добавляем кнопку возврата и дополнительные опции
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🔍 Типы блюд', 'callback_data' => 'command:dish_types']),
            Keyboard::inlineButton(['text' => '🏠 Главная', 'callback_data' => 'command:start'])
        ]);
        
        $this->replyToMessage($chatId, $messageId, "*Выберите категорию рецептов:*", $keyboard);
    }
    
    /**
     * Обработка команды /dish_types - выбор типа блюда
     */
    private function handleDishTypesCommand($chatId, $messageId)
    {
        $keyboard = new Keyboard();
        $keyboard->inline();
        
        // Добавляем кнопки для типов блюд
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🍲 Горячие блюда', 'callback_data' => 'dish_type:hot']),
            Keyboard::inlineButton(['text' => '🥗 Холодные блюда', 'callback_data' => 'dish_type:cold'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🍰 Десерты', 'callback_data' => 'dish_type:dessert']),
            Keyboard::inlineButton(['text' => '🍹 Напитки', 'callback_data' => 'dish_type:drink'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🥪 Закуски', 'callback_data' => 'dish_type:appetizer']),
            Keyboard::inlineButton(['text' => '🍲 Супы', 'callback_data' => 'dish_type:soup'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🍱 Основные блюда', 'callback_data' => 'dish_type:main']),
            Keyboard::inlineButton(['text' => '🍳 Завтраки', 'callback_data' => 'dish_type:breakfast'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '🥗 Правильное питание', 'callback_data' => 'dish_type:healthy']),
            Keyboard::inlineButton(['text' => '⏱ Быстрые рецепты', 'callback_data' => 'dish_type:quick'])
        ]);
        $keyboard->row([
            Keyboard::inlineButton(['text' => '📋 Категории', 'callback_data' => 'command:categories']),
            Keyboard::inlineButton(['text' => '🏠 Главная', 'callback_data' => 'command:start'])
        ]);
        
        $this->replyToMessage($chatId, $messageId, "*Выберите тип блюда:*", $keyboard);
    }
    
    /**
     * Обработка команды /random
     */
    private function handleRandomCommand($chatId, $messageId)
    {
        try {
            // Получаем чат для исключения просмотренных рецептов
            $telegramChat = TelegramChat::firstOrCreate(
                ['chat_id' => $chatId],
                ['is_active' => true, 'last_activity_at' => now()]
            );
            
            // Получаем список ID просмотренных рецептов
            $viewedRecipes = $telegramChat->getViewedRecipes();
            
            // Строим запрос с исключением просмотренных рецептов - запрашиваем только существующие столбцы!
            $query = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
                ->with(['categories:id,name', 'ingredients']) // Загружаем связанные данные
                ->where('is_published', 1);
                
            // Исключаем просмотренные рецепты, если они есть
            if (!empty($viewedRecipes)) {
                $query->whereNotIn('id', $viewedRecipes);
            }
            
            // Получаем случайный рецепт
            $recipe = $query->inRandomOrder()->first();
            
            // Если нет рецептов без просмотров, выбираем любой
            if (!$recipe) {
                $recipe = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
                    ->with(['categories:id,name', 'ingredients']) 
                    ->where('is_published', 1)
                    ->inRandomOrder()
                    ->first();
            }
            
            if (!$recipe) {
                $this->replyToMessage($chatId, $messageId, "К сожалению, не удалось найти рецепт. Пожалуйста, попробуйте позже.");
                return;
            }
            
            // Добавляем рецепт в просмотренные
            $telegramChat->addViewedRecipe($recipe->id);
            
            // Отправляем рецепт с фотографией и подробной информацией
            $this->sendRecipeWithPhoto($chatId, $messageId, $recipe, "🎲 *Случайный рецепт:*\n\n");
        } catch (\Exception $e) {
            Log::error('Ошибка при получении случайного рецепта: ' . $e->getMessage());
            $this->replyToMessage($chatId, $messageId, "Произошла ошибка при получении рецепта. Пожалуйста, попробуйте позже.");
        }
    }

    /**
     * Обработка команды /history - показать историю поиска
     */
    private function handleHistoryCommand($chatId, $messageId)
    {
        try {
            // Получаем чат для доступа к истории
            $telegramChat = TelegramChat::firstOrCreate(
                ['chat_id' => $chatId],
                ['is_active' => true, 'last_activity_at' => now()]
            );
            
            // Получаем историю поиска
            $searchHistory = $telegramChat->getSearchHistory(10);
            
            if (empty($searchHistory)) {
                $this->replyToMessage($chatId, $messageId, "У вас пока нет истории поиска. Попробуйте найти что-нибудь!");
                return;
            }
            
            // Формируем сообщение с историей
            $message = "*Ваша история поиска:*\n\n";
            
            foreach ($searchHistory as $index => $item) {
                $message .= ($index + 1) . ". \"{$item['query']}\"";
                
                // Добавляем дату, если она есть
                if (isset($item['date'])) {
                    $date = \Carbon\Carbon::parse($item['date'])->format('d.m.Y H:i');
                    $message .= " - {$date}";
                }
                
                $message .= "\n";
            }
            
            // Создаем клавиатуру для повторного поиска
            $keyboard = new Keyboard();
            $keyboard->inline();
            
            // Добавляем первые 5 запросов как кнопки для повторного поиска
            for ($i = 0; $i < min(5, count($searchHistory)); $i++) {
                $keyboard->row([
                    Keyboard::inlineButton([
                        'text' => '🔍 ' . mb_substr($searchHistory[$i]['query'], 0, 30),
                        'callback_data' => 'search:' . mb_substr($searchHistory[$i]['query'], 0, 30)
                    ])
                ]);
            }
            
            // Добавляем кнопки навигации
            $keyboard->row([
                Keyboard::inlineButton(['text' => '🧹 Очистить историю', 'callback_data' => 'command:clear_history']),
                Keyboard::inlineButton(['text' => '🏠 Главная', 'callback_data' => 'command:start'])
            ]);
            
            $this->replyToMessage($chatId, $messageId, $message, $keyboard);
        } catch (\Exception $e) {
            Log::error('Ошибка при получении истории поиска: ' . $е->getMessage());
            $this->replyToMessage($chatId, $messageId, "Произошла ошибка при получении истории. Пожалуйста, попробуйте позже.");
        }
    }
    
    /**
     * Обработка команды /clear_history - очистить историю просмотренных рецептов
     */
    private function handleClearHistoryCommand($chatId, $messageId)
    {
        try {
            // Получаем чат для очистки истории
            $telegramChat = TelegramChat::firstOrCreate(
                ['chat_id' => $chatId],
                ['is_active' => true, 'last_activity_at' => now()]
            );
            
            // Очищаем историю просмотренных рецептов
            $telegramChat->clearViewedRecipes();
            
            // Создаем клавиатуру
            $keyboard = new Keyboard();
            $keyboard->inline();
            $keyboard->row([
                Keyboard::inlineButton(['text' => '🎲 Случайный рецепт', 'callback_data' => 'command:random']),
                Keyboard::inlineButton(['text' => '📋 Категории', 'callback_data' => 'command:categories'])
            ]);
            
            $this->replyToMessage(
                $chatId, 
                $messageId, 
                "✅ История просмотренных рецептов очищена. Теперь вы будете получать новые рецепты при поиске и при использовании команды /random.", 
                $keyboard
            );
        } catch (\Exception $e) {
            Log::error('Ошибка при очистке истории: ' . $e->getMessage());
            $this->replyToMessage($chatId, $messageId, "Произошла ошибка при очистке истории. Пожалуйста, попробуйте позже.");
        }
    }
    
    /**
     * Обработка нажатий на кнопки (callback query)
     */
    private function handleCallbackQuery($callbackQuery)
    {
        try {
            $chatId = $callbackQuery->getMessage()->getChat()->getId();
            $messageId = $callbackQuery->getMessage()->getMessageId();
            $data = $callbackQuery->getData();
            $queryId = $callbackQuery->getId();
            
            // Отвечаем на callback, чтобы убрать часы загрузки
            $this->telegram->answerCallbackQuery([
                'callback_query_id' => $queryId
            ]);
            
            // Разбираем данные callback
            $parts = explode(':', $data);
            $type = $parts[0];
            $value = $parts[1] ?? null;
            $page = isset($parts[2]) ? (int)$parts[2] : 0;
            
            // Показываем действие "печатает"
            $this->telegram->sendChatAction([
                'chat_id' => $chatId,
                'action' => 'typing'
            ]);
            
            switch ($type) {
                case 'command':
                    // Обрабатываем команды из кнопок
                    switch ($value) {
                        case 'start':
                            $this->handleStartCommand($chatId, $messageId);
                            break;
                        case 'categories':
                            $this->handleCategoriesCommand($chatId, $messageId);
                            break;
                        case 'dish_types':
                            $this->handleDishTypesCommand($chatId, $messageId);
                            break;
                        case 'random':
                            $this->handleRandomCommand($chatId, $messageId);
                            break;
                        case 'popular':
                            $this->handlePopularCommand($chatId, $messageId);
                            break;
                        case 'more_popular':
                            // Обработка кнопки "Подобрать еще"
                            $this->handlePopularCommand($chatId, $messageId, $page);
                            break;
                        case 'help':
                            $this->handleHelpCommand($chatId, $messageId);
                            break;
                        case 'healthy':
                            $this->handleHealthyCommand($chatId, $messageId);
                            break;
                        case 'history':
                            $this->handleHistoryCommand($chatId, $messageId);
                            break;
                        case 'clear_history':
                            $this->handleClearHistoryCommand($chatId, $messageId);
                            break;
                    }
                    break;
                    
                case 'category':
                    // Показываем рецепты из выбранной категории
                    $this->handleCategorySelection($chatId, $messageId, $value);
                    break;
                    
                case 'dish_type':
                    // Показываем рецепты выбранного типа блюда
                    $this->handleDishTypeSelection($chatId, $messageId, $value);
                    break;
                    
                case 'search':
                    // Выполняем поиск по запросу из истории
                    $this->handleTextSearch($value, $chatId, $messageId);
                    break;
                    
                case 'recipe':
                    // Новый тип - отображение конкретного рецепта
                    $this->handleRecipeSelection($chatId, $messageId, $value);
                    break;
            }
        } catch (\Exception $e) {
            Log::error('Ошибка при обработке callback query: ' . $e->getMessage());
        }
        
        return response()->json(['status' => 'ok']);
    }
    
    /**
     * Обработка выбора категории
     */
    private function handleCategorySelection($chatId, $messageId, $categoryId)
    {
        $category = Category::find($categoryId);
        
        if (!$category) {
            $this->replyToMessage($chatId, $messageId, "Категория не найдена.");
            return;
        }
        
        // Быстрый запрос рецептов из категории с дополнительными полями
        $recipes = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
            ->with(['categories:id,name'])
            ->whereHas('categories', function ($query) use ($categoryId) {
                $query->where('categories.id', $categoryId);
            })
            ->where('is_published', 1)
            ->limit(7)
            ->get();
        
        if ($recipes->isEmpty()) {
            $this->replyToMessage($chatId, $messageId, "В категории \"{$category->name}\" пока нет рецептов.");
            return;
        }
        
        // Отправляем рецепты с указанием категории
        $this->sendRecipesWithReply($chatId, $messageId, $recipes, "🍴 *Рецепты из категории \"{$category->name}\":*\n\n");
    }
    
    /**
     * Обработка выбора типа блюда
     */
    private function handleDishTypeSelection($chatId, $messageId, $dishTypeCode)
    {
        try {
            // Получаем чат для сохранения просмотренных рецептов
            $telegramChat = TelegramChat::firstOrCreate(
                ['chat_id' => $chatId],
                ['is_active' => true, 'last_activity_at' => now()]
            );
            
            // Получаем информацию о типе блюда
            if (!isset($this->dishTypes[$dishTypeCode])) {
                $this->replyToMessage($chatId, $messageId, "Тип блюда не найден.");
                return;
            }
            
            $dishType = $this->dishTypes[$dishTypeCode];
            
            // Получаем список просмотренных рецептов
            $viewedRecipes = $telegramChat->getViewedRecipes();
            
            // Создаем запрос для поиска рецептов этого типа
            $query = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
                ->with(['categories:id,name'])
                ->where('is_published', 1);
                
            // Исключаем просмотренные рецепты
            if (!empty($viewedRecipes)) {
                $query->whereNotIn('id', $viewedRecipes);
            }
            
            // Поиск по категориям
            if (!empty($dishType['categories'])) {
                $query->whereHas('categories', function($q) use ($dishType) {
                    $q->where(function($subq) use ($dishType) {
                        foreach ($dishType['categories'] as $category) {
                            $subq->orWhere('name', 'like', '%' . $category . '%');
                        }
                    });
                });
            }
            
            // Поиск по ключевым словам в названии и описании
            if (!empty($dishType['keywords'])) {
                $query->orWhere(function($q) use ($dishType) {
                    foreach ($dishType['keywords'] as $keyword) {
                        $q->orWhere('title', 'like', '%' . $keyword . '%')
                          ->orWhere('description', 'like', '%' . $keyword . '%');
                    }
                });
            }
            
            // Получаем рецепты
            $recipes = $query->limit(7)->get();
            
            // Если нет результатов, повторяем запрос без исключения просмотренных
            if ($recipes->isEmpty()) {
                $query = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
                    ->with(['categories:id,name'])
                    ->where('is_published', 1);
                    
                // Поиск по категориям
                if (!empty($dishType['categories'])) {
                    $query->whereHas('categories', function($q) use ($dishType) {
                        $q->where(function($subq) use ($dishType) {
                            foreach ($dishType['categories'] as $category) {
                                $subq->orWhere('name', 'like', '%' . $category . '%');
                            }
                        });
                    });
                }
                
                // Поиск по ключевым словам в названии и описании
                if (!empty($dishType['keywords'])) {
                    $query->orWhere(function($q) use ($dishType) {
                        foreach ($dishType['keywords'] as $keyword) {
                            $q->orWhere('title', 'like', '%' . $keyword . '%')
                              ->orWhere('description', 'like', '%' . $keyword . '%');
                        }
                    });
                }
                
                $recipes = $query->limit(7)->get();
            }
            
            if ($recipes->isEmpty()) {
                $this->replyToMessage($chatId, $messageId, "К сожалению, рецепты типа \"{$dishType['name']}\" не найдены.");
                return;
            }
            
            // Добавляем рецепты в список просмотренных
            foreach ($recipes as $recipe) {
                $telegramChat->addViewedRecipe($recipe->id);
            }
            
            // Отправляем результаты
            $this->sendRecipesWithReply($chatId, $messageId, $recipes, "🍽 *{$dishType['name']}:*\n\n");
            
        } catch (\Exception $e) {
            Log::error('Ошибка при поиске рецептов по типу блюда: ' . $e->getMessage());
            $this->replyToMessage($chatId, $messageId, "Произошла ошибка при поиске рецептов. Пожалуйста, попробуйте позже.");
        }
    }
    
    /**
     * Обработка команды /popular
     */
    private function handlePopularCommand($chatId, $messageId, $page = 0)
    {
        // Кэшируем популярные рецепты с дополнительной информацией
        $cacheKey = 'telegram_popular_recipes_' . $page;
        $limit = 5; // Показываем по 5 рецептов на странице
        $offset = $page * $limit;
        
        $recipes = Cache::remember($cacheKey, 1800, function() use ($limit, $offset) {
            return Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'views', 'servings')
                ->with(['categories:id,name', 'ingredients']) // Загружаем только существующие связи
                ->where('is_published', 1)
                ->where('views', '>', 0) // Только рецепты с просмотрами
                ->orderBy('views', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
        });
        
        if ($recipes->isEmpty()) {
            // Если страница пуста, но это не первая страница, начинаем сначала
            if ($page > 0) {
                return $this->handlePopularCommand($chatId, $messageId, 0);
            }
            $this->replyToMessage($chatId, $messageId, "К сожалению, популярные рецепты не найдены.");
            return;
        }
        
        // Отправляем рецепты с ответом на сообщение, указывая номер страницы
        $headerText = "🔝 *Популярные рецепты" . ($page > 0 ? " (стр. " . ($page + 1) . ")" : "") . ":*\n\n";
        $this->sendRecipesWithReply($chatId, $messageId, $recipes, $headerText, true, $page);
    }
    
    /**
     * Отправка рецептов с ответом на исходное сообщение
     */
    private function sendRecipesWithReply($chatId, $messageId, $recipes, $headerText = "🍳 *Найденные рецепты:*\n\n", $showMoreButton = false, $currentPage = 0)
    {
        // Если у нас всего один рецепт, используем sendRecipeWithPhoto для улучшенного вида
        if ($recipes->count() === 1) {
            $this->sendRecipeWithPhoto($chatId, $messageId, $recipes->first(), $headerText);
            return;
        }
        
        // Для нескольких рецептов формируем список с кнопками
        $message = $headerText;
        
        // Краткий список рецептов
        foreach ($recipes as $index => $recipe) {
            $message .= ($index + 1) . ". *{$recipe->title}*\n";
        }
        
        $message .= "\nВыберите рецепт из списка, чтобы увидеть подробную информацию.";
        
        // Создаем клавиатуру с кнопками для каждого рецепта
        $keyboard = new Keyboard();
        $keyboard->inline();
        
        // Добавляем кнопки для каждого рецепта
        foreach ($recipes as $index => $recipe) {
            $buttonText = ($index + 1) . ". " . mb_substr($recipe->title, 0, 30);
            if(mb_strlen($recipe->title) > 30) {
                $buttonText .= '...';
            }
            
            $keyboard->row([
                Keyboard::inlineButton(['text' => $buttonText, 'callback_data' => 'recipe:' . $recipe->id])
            ]);
        }
        
        // Добавляем кнопку "Подобрать еще" для популярных рецептов
        if ($showMoreButton) {
            $keyboard->row([
                Keyboard::inlineButton(['text' => '📋 Категории', 'callback_data' => 'command:categories']),
                Keyboard::inlineButton(['text' => '🔄 Подобрать еще', 'callback_data' => 'command:more_popular:' . ($currentPage + 1)])
            ]);
        } else {
            // Стандартные дополнительные кнопки навигации
            $keyboard->row([
                Keyboard::inlineButton(['text' => '📋 Категории', 'callback_data' => 'command:categories']),
                Keyboard::inlineButton(['text' => '🎲 Случайный', 'callback_data' => 'command:random'])
            ]);
        }
        
        // Отправляем сообщение как ответ на исходное сообщение пользователя
        $this->replyToMessage($chatId, $messageId, $message, $keyboard);
    }
    
    /**
     * Отправка рецепта с фотографией
     */
    private function sendRecipeWithPhoto($chatId, $messageId, $recipe, $headerText = "🍳 *Найденный рецепт:*\n\n")
    {
        try {
            // Улучшаем внешний вид заголовка
            $caption = "┏━━━━━━━━━━━━━━━━━━━━━┓\n";
            $caption .= "┃      " . trim(str_replace("*", "", $headerText)) . "      ┃\n";
            $caption .= "┗━━━━━━━━━━━━━━━━━━━━━┛\n\n";
            
            // Название рецепта красиво выделяем
            $caption .= "🍽️ *{$recipe->title}*\n\n";
            
            // Добавляем порции, если указаны
            if (!empty($recipe->servings)) {
                $caption .= "👥 *Порций:* {$recipe->servings}\n";
            }
            
            // Добавляем время приготовления, если есть
            if (!empty($recipe->cooking_time)) {
                $caption .= "⏱ *Время приготовления:* {$recipe->cooking_time} мин.\n\n";
            } else {
                $caption .= "\n";
            }
            
            // Добавляем ингредиенты - исправлена обработка для разных типов данных
            if ($recipe->ingredients) {
                // Обработчик ингредиентов, защищенный от разных форматов данных
                $ingredients = [];
                
                // Проверяем, является ли ingredients коллекцией Laravel или массивом
                if (is_object($recipe->ingredients) && method_exists($recipe->ingredients, 'isNotEmpty')) {
                    // Это коллекция Laravel
                    if ($recipe->ingredients->isNotEmpty()) {
                        foreach ($recipe->ingredients as $ingredient) {
                            $ingredients = $this->extractIngredientData($ingredients, $ingredient);
                        }
                    }
                } elseif (is_array($recipe->ingredients)) {
                    // Это обычный массив
                    foreach ($recipe->ingredients as $ingredient) {
                        $ingredients = $this->extractIngredientData($ingredients, $ingredient);
                    }
                }
                
                // Добавляем список ингредиентов, если они есть
                if (!empty($ingredients)) {
                    $caption .= "🧂 *Ингредиенты:*\n";
                    foreach ($ingredients as $ingredientText) {
                        $caption .= "• {$ingredientText}\n";
                    }
                    $caption .= "\n";
                }
            }
            
            // Получаем описание рецепта вместо шагов приготовления, если шаги недоступны
            if (!empty($recipe->description)) {
                $caption .= "👨‍🍳 *Приготовление:*\n";
                $caption .= $recipe->description . "\n\n";
            }
            
            // Добавляем ссылку на полный рецепт с красивым форматированием
            $recipeUrl = url('/recipes/' . $recipe->slug);
            $caption .= "✨ *Также вы можете перейти на удобный сайт и посмотреть, как готовить:* [открыть рецепт]({$recipeUrl})";
            
            // Создаем клавиатуру с основными командами
            $keyboard = new Keyboard();
            $keyboard->inline();
            $keyboard->row([
                Keyboard::inlineButton(['text' => '📋 Категории', 'callback_data' => 'command:categories']),
                Keyboard::inlineButton(['text' => '🎲 Еще случайный', 'callback_data' => 'command:random'])
            ]);
            $keyboard->row([
                Keyboard::inlineButton(['text' => '🔝 Популярные', 'callback_data' => 'command:popular']),
                Keyboard::inlineButton(['text' => '❓ Помощь', 'callback_data' => 'command:help'])
            ]);
            $keyboard->row([
                Keyboard::inlineButton(['text' => '🥗 Правильное питание', 'callback_data' => 'command:healthy'])
            ]);
            
            // Отправляем фото с подписью
            $imageUrl = $recipe->image_url;
            // Проверяем, содержит ли image_url полный URL или относительный путь
            if (!empty($imageUrl) && !filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                // Если это не полный URL, формируем его
                $imageUrl = url($imageUrl);
            } else if (empty($imageUrl)) {
                // Если изображение отсутствует, используем заглушку
                $imageUrl = url('/images/placeholder.jpg');
            }
            
            // Используем InputFile для отправки фото
            $imageFile = InputFile::create($imageUrl, basename($imageUrl));
            
            // Проверяем длину подписи
            if (mb_strlen($caption) > 1024) {
                // Если подпись слишком длинная, разделяем её на две части
                $shortCaption = mb_substr($caption, 0, 1000) . "...";
                
                // Отправляем фото с короткой подписью
                $this->telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => $imageFile,
                    'caption' => $shortCaption,
                    'parse_mode' => 'Markdown',
                    'reply_to_message_id' => $messageId
                ]);
                
                // Отправляем вторую часть как текст
                $this->telegram->sendMessage([
                    'chat_id' => $chatId,
                    'text' => "...продолжение:\n\n" . mb_substr($caption, 1000),
                    'parse_mode' => 'Markdown',
                    'reply_markup' => $keyboard
                ]);
            } else {
                // Отправляем фото с полной подписью
                $this->telegram->sendPhoto([
                    'chat_id' => $chatId,
                    'photo' => $imageFile,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown',
                    'reply_to_message_id' => $messageId,
                    'reply_markup' => $keyboard
                ]);
            }
            
            // Сохраняем отправленное сообщение
            $this->saveMessageInBackground([
                'chat_id' => $chatId,
                'text' => "Фото рецепта: {$recipe->title}"
            ], 'outgoing');
            
        } catch (\Exception $e) {
            Log::error('Ошибка отправки рецепта с фото: ' . $е->getMessage());
            // Если не получилось отправить фото, отправляем обычное сообщение
            $this->replyToMessage($chatId, $messageId, $caption, $keyboard);
        }
    }
    
    /**
     * Вспомогательный метод для извлечения данных об ингредиентах из различных форматов
     */
    private function extractIngredientData($ingredients, $ingredient)
    {
        // Если ингредиент - массив с ключами
        if (is_array($ingredient)) {
            if (!empty($ingredient['name'])) {
                $ingredientText = $ingredient['name'];
                
                if (!empty($ingredient['quantity'])) {
                    $ingredientText .= " — {$ingredient['quantity']}";
                    if (!empty($ingredient['unit'])) {
                        $ingredientText .= " {$ingredient['unit']}";
                    }
                }
                $ingredients[] = $ingredientText;
            }
        } 
        // Если ингредиент - объект
        elseif (is_object($ingredient)) {
            $name = null;
            
            // Попытаемся получить имя ингредиента разными способами
            if (!empty($ingredient->name)) {
                $name = $ingredient->name;
            } elseif (isset($ingredient->ingredient) && is_object($ingredient->ingredient) && !empty($ingredient->ingredient->name)) {
                $name = $ingredient->ingredient->name;
            } elseif (isset($ingredient->ingredient) && is_array($ingredient->ingredient) && !empty($ingredient->ingredient['name'])) {
                $name = $ingredient->ingredient['name'];
            }
            
            if ($name) {
                $ingredientText = $name;
                
                // Добавляем количество если есть
                if (!empty($ingredient->quantity)) {
                    $ingredientText .= " — {$ingredient->quantity}";
                    if (!empty($ingredient->unit)) {
                        $ingredientText .= " {$ingredient->unit}";
                    }
                }
                $ingredients[] = $ingredientText;
            }
        } 
        // Если ингредиент - просто строка
        elseif (is_string($ingredient)) {
            $ingredients[] = $ingredient;
        }
        
        return $ingredients;
    }
    
    /**
     * Метод для обработки команды "Правильное питание"
     */
    private function handleHealthyCommand($chatId, $messageId)
    {
        // Показываем "печатает" для улучшения UX
        $this->telegram->sendChatAction([
            'chat_id' => $chatId,
            'action' => 'typing'
        ]);
        
        try {
            // Список категорий, относящихся к правильному питанию
            $healthyCategories = [
                'Здоровье',
                'Постная еда',
                'Диетические салаты',
                'Вегетарианская еда',
                'Веганская еда',
                'Низкокалорийная еда',
                'Безглютеновая диета',
                'Кето-диета',
                'Меню при диабете',
                'Низкокалорийные десерты',
                'Салаты без майонеза',
                'Овощные салаты',
                'Фруктовые салаты',
                'Средиземноморская кухня',
                'Японская кухня',
                'Рататуй',
                'Овощное рагу',
                'Фруктовые десерты',
                'Каши',
                'Омлет',
                'Шакшука',
                'Греческий салат',
                'Кабачковая икра',
                'Гречневая каша',
                'Блюда из морепродуктов'
            ];
            
            // Поиск рецепта из здоровых категорий
            $recipe = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
                ->with(['categories:id,name'])
                ->where('is_published', 1)
                ->whereHas('categories', function($q) use ($healthyCategories) {
                    $q->where(function($query) use ($healthyCategories) {
                        foreach ($healthyCategories as $category) {
                            $query->orWhere('name', 'like', '%' . $category . '%');
                        }
                    });
                })
                ->inRandomOrder()
                ->first();
                
            // Если по категориям ничего не нашли, ищем по ключевым словам в названии и описании
            if (!$recipe) {
                $healthyKeywords = [
                    'диет', 'здоров', 'правильн', 'постн', 'вегетариан', 'веган',
                    'низкокалорийн', 'салат', 'овощ', 'фрукт', 'каша', 'без сахара'
                ];
                
                $query = Recipe::select('id', 'title', 'slug', 'description', 'cooking_time', 'image_url', 'servings')
                    ->with(['categories:id,name'])
                    ->where('is_published', 1)
                    ->where(function($q) use ($healthyKeywords) {
                        foreach ($healthyKeywords as $keyword) {
                            $q->orWhere('title', 'like', '%' . $keyword . '%')
                              ->orWhere('description', 'like', '%' . $keyword . '%');
                        }
                    });
                    
                $recipe = $query->inRandomOrder()->first();
            }
            
            if (!$recipe) {
                $this->replyToMessage($chatId, $messageId, "К сожалению, рецептов для правильного питания не найдено.");
                return;
            }
            
            // Отправляем рецепт с фото
            $this->sendRecipeWithPhoto($chatId, $messageId, $recipe, "🥗 *Рецепт для правильного питания:*\n\n");
        } catch (\Exception $e) {
            Log::error('Ошибка при поиске рецептов правильного питания: ' . $e->getMessage());
            $this->replyToMessage($chatId, $messageId, "Произошла ошибка при поиске рецептов. Пожалуйста, попробуйте позже.");
        }
    }
    
    /**
     * Обработка выбора конкретного рецепта из списка
     */
    private function handleRecipeSelection($chatId, $messageId, $recipeId)
    {
        try {
            // Получаем рецепт из базы данных
            $recipe = Recipe::with(['categories', 'ingredients'])
                ->where('id', $recipeId)
                ->first();
                
            if (!$recipe) {
                $this->replyToMessage($chatId, $messageId, "К сожалению, рецепт не найден.");
                return;
            }
            
            // Отправляем подробный рецепт с красивым заголовком
            $this->sendRecipeWithPhoto($chatId, $messageId, $recipe, "✨ *Выбранный рецепт* ✨");
            
            // Сохраняем рецепт в просмотренные
            $telegramChat = TelegramChat::firstOrCreate(
                ['chat_id' => $chatId],
                ['is_active' => true, 'last_activity_at' => now()]
            );
            $telegramChat->addViewedRecipe($recipe->id);
            
        } catch (\Exception $e) {
            Log::error('Ошибка при отображении рецепта: ' . $е->getMessage());
            $this->replyToMessage($chatId, $messageId, "Произошла ошибка при получении рецепта. Пожалуйста, попробуйте позже.");
        }
    }
}
