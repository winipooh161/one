<?php

namespace App\Services;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class TelegramService
{
    protected $botToken;
    protected $channelId;
    protected $apiUrl = 'https://api.telegram.org/bot';

    public function __construct()
    {
        // Напрямую получаем токены из переменных окружения
        $this->botToken = env('TELEGRAM_BOT_TOKEN');
        $this->channelId = env('TELEGRAM_CHANNEL_ID');
        
        // Запись в лог для отладки (без вывода самого токена)
        Log::debug('TelegramService constructor', [
            'bot_token_exists' => !empty($this->botToken),
            'channel_id_exists' => !empty($this->channelId),
            'bot_token_length' => $this->botToken ? strlen($this->botToken) : 0
        ]);
    }

    /**
     * Проверка настроек Telegram
     * 
     * @return array Результат проверки
     */
    public function checkSettings()
    {
        $result = [
            'success' => true,
            'errors' => []
        ];
        
        if (empty($this->botToken)) {
            $result['success'] = false;
            $result['errors'][] = 'Не задан токен бота Telegram';
        }
        
        if (empty($this->channelId)) {
            $result['success'] = false;
            $result['errors'][] = 'Не задан ID канала Telegram';
        }
        
        // Если токен и ID канала заданы, проверяем связь с API
        if ($result['success']) {
            try {
                $response = Http::timeout(10)->get("{$this->apiUrl}{$this->botToken}/getMe");
                
                if (!$response->successful()) {
                    $result['success'] = false;
                    $result['errors'][] = 'Ошибка соединения с API Telegram: ' . $response->body();
                    Log::error('Ошибка проверки API Telegram', [
                        'status' => $response->status(),
                        'response' => $response->body()
                    ]);
                }
            } catch (\Exception $e) {
                $result['success'] = false;
                $result['errors'][] = 'Исключение при проверке API Telegram: ' . $e->getMessage();
                Log::error('Исключение при проверке API Telegram: ' . $e->getMessage());
            }
        }
        
        return $result;
    }

    /**
     * Отправляет сообщение в Telegram канал
     *
     * @param string $content Текст сообщения
     * @param string|null $imageUrl URL изображения для отправки с текстом
     * @return bool Результат отправки сообщения
     */
    public function sendMessage($content, $imageUrl = null)
    {
        try {
            if (empty($this->botToken) || empty($this->channelId)) {
                Log::error('TelegramService: Не заданы токен бота или ID канала', [
                    'bot_token_exists' => !empty($this->botToken),
                    'channel_id_exists' => !empty($this->channelId)
                ]);
                return false;
            }

            // Логируем детали отправки
            Log::info('Попытка отправки сообщения в Telegram', [
                'content_length' => strlen($content),
                'has_image' => !empty($imageUrl),
                'image_url' => $imageUrl ? substr($imageUrl, 0, 100) . '...' : null,
                'channel_id' => $this->channelId
            ]);

            // Если есть изображение, отправляем фото с подписью
            if ($imageUrl) {
                return $this->sendPhoto($imageUrl, $content);
            }

            // Иначе отправляем просто текст
            $response = Http::timeout(20)->post("{$this->apiUrl}{$this->botToken}/sendMessage", [
                'chat_id' => $this->channelId,
                'text' => $content,
                'parse_mode' => 'Markdown',
                'disable_web_page_preview' => false
            ]);

            if ($response->successful()) {
                Log::info('Сообщение успешно отправлено в Telegram', [
                    'message_id' => $response->json('result.message_id') ?? 'unknown'
                ]);
                return true;
            } else {
                Log::error('Ошибка отправки сообщения в Telegram', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                return false;
            }
        } catch (\Exception $e) {
            Log::error('Исключение при отправке сообщения в Telegram: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Отправляет фото с подписью в Telegram канал
     *
     * @param string $imageUrl URL изображения
     * @param string $caption Подпись к фото (текст сообщения)
     * @return bool Результат отправки фото
     */
    protected function sendPhoto($imageUrl, $caption)
    {
        try {
            // Попытка отправить фото напрямую по URL
            Log::info('Отправка фото в Telegram по URL', ['image_url' => $imageUrl]);
            
            $response = Http::timeout(30)->post("{$this->apiUrl}{$this->botToken}/sendPhoto", [
                'chat_id' => $this->channelId,
                'photo' => $imageUrl,
                'caption' => $caption,
                'parse_mode' => 'Markdown'
            ]);

            // Если удалось отправить по URL - возвращаем результат
            if ($response->successful()) {
                Log::info('Фото успешно отправлено в Telegram по URL', [
                    'message_id' => $response->json('result.message_id') ?? 'unknown'
                ]);
                return true;
            }
            
            // Если не удалось, пробуем скачать и отправить как файл
            Log::warning('Не удалось отправить фото по URL, пробуем скачать', [
                'image_url' => $imageUrl,
                'error' => $response->body()
            ]);
            
            // Скачиваем изображение во временный файл
            $tempFile = tempnam(sys_get_temp_dir(), 'telegram_img_');
            if (file_put_contents($tempFile, file_get_contents($imageUrl))) {
                Log::info('Изображение успешно скачано во временный файл', ['temp_file' => $tempFile]);
                
                // Отправляем как мультипарт-форму
                $response = Http::attach(
                    'photo', 
                    file_get_contents($tempFile), 
                    'image.jpg'
                )->post("{$this->apiUrl}{$this->botToken}/sendPhoto", [
                    'chat_id' => $this->channelId,
                    'caption' => $caption,
                    'parse_mode' => 'Markdown'
                ]);
                
                // Удаляем временный файл
                @unlink($tempFile);
                
                if ($response->successful()) {
                    Log::info('Фото успешно отправлено в Telegram как файл');
                    return true;
                } else {
                    Log::error('Ошибка отправки фото в Telegram как файл', [
                        'status' => $response->status(),
                        'error' => $response->body()
                    ]);
                }
            } else {
                Log::error('Не удалось скачать изображение', ['image_url' => $imageUrl]);
            }
            
            // Если ничего не помогло, отправляем текст без фото
            Log::warning('Отправляем только текст без фото');
            return $this->sendMessage($caption);
            
        } catch (\Exception $e) {
            Log::error('Исключение при отправке фото в Telegram: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
