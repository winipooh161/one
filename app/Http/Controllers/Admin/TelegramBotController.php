<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\TelegramChat;
use App\Models\TelegramMessage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schema;

class TelegramBotController extends Controller
{
    /**
     * Страница настройки Telegram бота
     */
    public function setup()
    {
        $botToken = Config::get('services.telegram.recipe.bot_token');
        $webhookUrl = Config::get('services.telegram.recipe.webhook_url');
        
        $needSetup = !Schema::hasTable('telegram_chats') || !Schema::hasTable('telegram_messages');
        
        return view('admin.telegram.setup', [
            'needSetup' => $needSetup,
            'botToken' => $botToken,
            'webhookUrl' => $webhookUrl
        ]);
    }
    
    /**
     * Главная страница управления ботом
     */
    public function index()
    {
        try {
            // Проверяем, существуют ли необходимые таблицы
            if (!Schema::hasTable('telegram_chats') || !Schema::hasTable('telegram_messages')) {
                return redirect()->route('admin.telegram.setup')
                    ->with('warning', 'Необходимо выполнить миграции для Telegram бота.');
            }
            
            // Получаем статистику
            $totalChats = TelegramChat::count();
            $activeChats = TelegramChat::where('is_active', true)->count();
            $totalMessages = TelegramMessage::count();
            $lastMessages = TelegramMessage::latest()->take(10)->get();
            
            // Статистика по дням (последние 7 дней)
            $dailyStats = TelegramMessage::selectRaw('DATE(created_at) as date, COUNT(*) as count')
                ->where('created_at', '>=', now()->subDays(7))
                ->groupBy('date')
                ->orderBy('date')
                ->get()
                ->pluck('count', 'date')
                ->toArray();
            
            // Получаем информацию о боте
            $botInfo = $this->getBotInfo();
            
            return view('admin.telegram.index', [
                'totalChats' => $totalChats,
                'activeChats' => $activeChats,
                'totalMessages' => $totalMessages,
                'lastMessages' => $lastMessages,
                'dailyStats' => $dailyStats,
                'botInfo' => $botInfo
            ]);
        } catch (\Exception $e) {
            Log::error('Ошибка при загрузке панели управления Telegram: ' . $e->getMessage());
            return back()->with('error', 'Ошибка при загрузке данных: ' . $e->getMessage());
        }
    }
    
    /**
     * Страница списка пользователей
     */
    public function users()
    {
        $chats = TelegramChat::orderBy('last_activity_at', 'desc')->paginate(20);
        return view('admin.telegram.users', ['chats' => $chats]);
    }
    
    /**
     * Просмотр информации о конкретном чате
     */
    public function show(TelegramChat $chat)
    {
        $messages = $chat->messages()->orderBy('created_at', 'desc')->paginate(20);
        return view('admin.telegram.chat', [
            'chat' => $chat,
            'messages' => $messages
        ]);
    }
    
    /**
     * Отправка сообщения в конкретный чат
     */
    public function sendMessage(Request $request, $chatId)
    {
        $request->validate([
            'message' => 'required|string|max:4096'
        ]);
        
        $botToken = Config::get('services.telegram.recipe.bot_token');
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        try {
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $request->message,
                'parse_mode' => 'Markdown'
            ]);
            
            if ($response->successful() && $response->json('ok')) {
                $result = $response->json('result');
                
                // Сохраняем отправленное сообщение в базу
                TelegramMessage::create([
                    'message_id' => $result['message_id'],
                    'chat_id' => $chatId,
                    'text' => $request->message,
                    'direction' => 'outgoing',
                    'additional_data' => $result
                ]);
                
                return back()->with('success', 'Сообщение успешно отправлено');
            } else {
                Log::error('Ошибка при отправке сообщения', [
                    'response' => $response->json(),
                    'chat_id' => $chatId
                ]);
                return back()->with('error', 'Ошибка при отправке сообщения: ' . ($response->json('description') ?? 'Неизвестная ошибка'));
            }
        } catch (\Exception $e) {
            Log::error('Исключение при отправке сообщения: ' . $e->getMessage());
            return back()->with('error', 'Ошибка при отправке сообщения: ' . $e->getMessage());
        }
    }
    
    /**
     * Страница массовой рассылки
     */
    public function broadcast(Request $request)
    {
        if ($request->isMethod('post')) {
            $request->validate([
                'message' => 'required|string|max:4096',
                'recipients' => 'required|string'
            ]);
            
            // Получаем IDs выбранных чатов
            $recipientType = $request->recipients;
            $chatIds = [];
            
            if ($recipientType === 'all') {
                $chatIds = TelegramChat::pluck('chat_id')->toArray();
            } elseif ($recipientType === 'active') {
                $chatIds = TelegramChat::where('is_active', true)->pluck('chat_id')->toArray();
            } elseif ($recipientType === 'selected') {
                $chatIds = $request->input('selected_chats', []);
            }
            
            if (empty($chatIds)) {
                return back()->with('error', 'Не выбраны получатели для рассылки');
            }
            
            // Запускаем задачу рассылки (в реальном приложении лучше использовать очереди)
            $sent = 0;
            $errors = 0;
            
            foreach ($chatIds as $chatId) {
                try {
                    $result = $this->sendMessageToChat($chatId, $request->message);
                    if ($result) {
                        $sent++;
                    } else {
                        $errors++;
                    }
                    // Небольшая пауза между отправками, чтобы не превысить лимиты API
                    usleep(200000); // 200 ms
                } catch (\Exception $e) {
                    $errors++;
                    Log::error('Ошибка при массовой рассылке', [
                        'chat_id' => $chatId,
                        'error' => $e->getMessage()
                    ]);
                }
            }
            
            return back()->with('success', "Рассылка завершена. Отправлено: {$sent}, ошибок: {$errors}");
        }
        
        // GET-запрос - отображаем форму
        $allChats = TelegramChat::orderBy('last_activity_at', 'desc')->get();
        $activeChats = TelegramChat::where('is_active', true)->count();
        
        return view('admin.telegram.broadcast', [
            'allChats' => $allChats,
            'activeChatsCount' => $activeChats
        ]);
    }
    
    /**
     * Страница управления командами бота
     */
    public function commands(Request $request)
    {
        if ($request->isMethod('post')) {
            $botToken = Config::get('services.telegram.recipe.bot_token');
            $url = "https://api.telegram.org/bot{$botToken}/setMyCommands";
            
            // Формируем список команд из запроса
            $commands = [];
            $commandPairs = $request->input('commands', []);
            
            foreach ($commandPairs as $index => $pair) {
                if (!empty($pair['command']) && !empty($pair['description'])) {
                    $commands[] = [
                        'command' => $pair['command'],
                        'description' => $pair['description']
                    ];
                }
            }
            
            try {
                $response = Http::post($url, [
                    'commands' => $commands
                ]);
                
                if ($response->successful() && $response->json('ok')) {
                    // Сохраняем команды в кэш
                    Cache::put('telegram_bot_commands', $commands, 3600);
                    return back()->with('success', 'Команды бота успешно обновлены');
                } else {
                    return back()->with('error', 'Ошибка при обновлении команд: ' . ($response->json('description') ?? 'Неизвестная ошибка'));
                }
            } catch (\Exception $e) {
                return back()->with('error', 'Ошибка при обновлении команд: ' . $e->getMessage());
            }
        }
        
        // GET-запрос - получаем текущие команды
        $commands = $this->getBotCommands();
        
        // Если команд нет, устанавливаем значения по умолчанию
        if (empty($commands)) {
            $commands = [
                ['command' => 'start', 'description' => 'Начать работу с ботом'],
                ['command' => 'help', 'description' => 'Получить справку по использованию бота'],
                ['command' => 'random', 'description' => 'Получить случайный рецепт']
            ];
        }
        
        return view('admin.telegram.commands', ['commands' => $commands]);
    }
    
    /**
     * Страница настроек бота
     */
    public function settings(Request $request)
    {
        return view('admin.telegram.settings', [
            'botToken' => Config::get('services.telegram.recipe.bot_token'),
            'webhookUrl' => Config::get('services.telegram.recipe.webhook_url'),
            'webhookInfo' => $this->getWebhookInfo()
        ]);
    }
    
    /**
     * Обновление настроек бота
     */
    public function updateSettings(Request $request)
    {
        // TODO: Реализовать обновление настроек в .env файле
        return back()->with('success', 'Настройки бота обновлены');
    }
    
    /**
     * Установка вебхука
     */
    public function setWebhook(Request $request)
    {
        $botToken = Config::get('services.telegram.recipe.bot_token');
        $webhookUrl = Config::get('services.telegram.recipe.webhook_url');
        
        if (empty($botToken) || empty($webhookUrl)) {
            return back()->with('error', 'Не указан токен бота или URL вебхука');
        }
        
        try {
            $result = Artisan::call('telegram:set-webhook');
            $output = Artisan::output();
            
            if (strpos($output, 'Вебхук успешно установлен') !== false) {
                return back()->with('success', 'Вебхук успешно установлен');
            } else {
                return back()->with('error', 'Ошибка при установке вебхука: ' . $output);
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при установке вебхука: ' . $e->getMessage());
        }
    }
    
    /**
     * Удаление вебхука
     */
    public function deleteWebhook()
    {
        $botToken = Config::get('services.telegram.recipe.bot_token');
        $url = "https://api.telegram.org/bot{$botToken}/deleteWebhook";
        
        try {
            $response = Http::post($url);
            
            if ($response->successful() && $response->json('ok')) {
                return back()->with('success', 'Вебхук успешно удален');
            } else {
                return back()->with('error', 'Ошибка при удалении вебхука: ' . ($response->json('description') ?? 'Неизвестная ошибка'));
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при удалении вебхука: ' . $e->getMessage());
        }
    }
    
    /**
     * Просмотр логов бота
     */
    public function logs()
    {
        // В простом варианте просто показываем последние сообщения
        $messages = TelegramMessage::orderBy('created_at', 'desc')->paginate(50);
        return view('admin.telegram.logs', ['messages' => $messages]);
    }
    
    /**
     * Проверка статуса бота
     */
    public function checkStatus()
    {
        $botToken = Config::get('services.telegram.recipe.bot_token');
        $url = "https://api.telegram.org/bot{$botToken}/getMe";
        
        try {
            $response = Http::get($url);
            
            if ($response->successful() && $response->json('ok')) {
                $botInfo = $response->json('result');
                return response()->json([
                    'status' => 'ok',
                    'bot' => $botInfo
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Не удалось получить информацию о боте: ' . ($response->json('description') ?? 'Неизвестная ошибка')
                ], 500);
            }
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ошибка при проверке статуса бота: ' . $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * Очистка кэша бота
     */
    public function clearCache()
    {
        Cache::forget('telegram_bot_info');
        Cache::forget('telegram_bot_commands');
        Cache::forget('telegram_webhook_info');
        
        return back()->with('success', 'Кэш бота успешно очищен');
    }
    
    /**
     * Получение информации о боте
     */
    private function getBotInfo()
    {
        return Cache::remember('telegram_bot_info', 3600, function () {
            $botToken = Config::get('services.telegram.recipe.bot_token');
            $url = "https://api.telegram.org/bot{$botToken}/getMe";
            
            try {
                $response = Http::get($url);
                
                if ($response->successful() && $response->json('ok')) {
                    return $response->json('result');
                }
            } catch (\Exception $e) {
                Log::error('Ошибка при получении информации о боте: ' . $e->getMessage());
            }
            
            return null;
        });
    }
    
    /**
     * Получение команд бота
     */
    private function getBotCommands()
    {
        return Cache::remember('telegram_bot_commands', 3600, function () {
            $botToken = Config::get('services.telegram.recipe.bot_token');
            $url = "https://api.telegram.org/bot{$botToken}/getMyCommands";
            
            try {
                $response = Http::get($url);
                
                if ($response->successful() && $response->json('ok')) {
                    return $response->json('result');
                }
            } catch (\Exception $e) {
                Log::error('Ошибка при получении команд бота: ' . $e->getMessage());
            }
            
            return [];
        });
    }
    
    /**
     * Получение информации о вебхуке
     */
    private function getWebhookInfo()
    {
        return Cache::remember('telegram_webhook_info', 3600, function () {
            $botToken = Config::get('services.telegram.recipe.bot_token');
            $url = "https://api.telegram.org/bot{$botToken}/getWebhookInfo";
            
            try {
                $response = Http::get($url);
                
                if ($response->successful() && $response->json('ok')) {
                    return $response->json('result');
                }
            } catch (\Exception $e) {
                Log::error('Ошибка при получении информации о вебхуке: ' . $e->getMessage());
            }
            
            return null;
        });
    }
    
    /**
     * Отправка сообщения в чат
     */
    private function sendMessageToChat($chatId, $text, $parseMode = 'Markdown')
    {
        $botToken = Config::get('services.telegram.recipe.bot_token');
        $url = "https://api.telegram.org/bot{$botToken}/sendMessage";
        
        try {
            $response = Http::post($url, [
                'chat_id' => $chatId,
                'text' => $text,
                'parse_mode' => $parseMode
            ]);
            
            if ($response->successful() && $response->json('ok')) {
                $result = $response->json('result');
                
                // Сохраняем отправленное сообщение в базу
                TelegramMessage::create([
                    'message_id' => $result['message_id'],
                    'chat_id' => $chatId,
                    'text' => $text,
                    'direction' => 'outgoing',
                    'additional_data' => $result
                ]);
                
                return true;
            } else {
                Log::error('Ошибка при отправке сообщения', [
                    'response' => $response->json(),
                    'chat_id' => $chatId
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Исключение при отправке сообщения: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Выполнить миграции для Telegram бота
     */
    public function migrate()
    {
        try {
            // Выполняем миграции для таблиц Telegram
            $output = [];
            $result = 0;
            
            exec('php artisan migrate --path=/database/migrations/2023_11_10_create_telegram_chats_table.php', $output, $result);
            exec('php artisan migrate --path=/database/migrations/2023_11_10_create_telegram_messages_table.php', $output, $result);
            
            if ($result === 0) {
                return back()->with('success', 'Миграции успешно выполнены. Таблицы Telegram бота созданы.');
            } else {
                return back()->with('error', 'Ошибка при выполнении миграций: ' . implode("\n", $output));
            }
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при выполнении миграций: ' . $e->getMessage());
        }
    }
}
