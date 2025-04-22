<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ZenService
{
    protected $apiToken;
    protected $channelId;
    protected $apiUrl = 'https://zen.yandex.ru/api/v1';

    public function __construct()
    {
        $this->apiToken = config('services.zen.token');
        $this->channelId = config('services.zen.channel_id');
    }

    /**
     * Публикация контента в Дзене
     * 
     * @param string $title Заголовок публикации
     * @param string $content HTML-контент публикации
     * @param string $imageUrl URL изображения для обложки
     * @return array|null Результат публикации или null в случае ошибки
     */
    public function publish($title, $content, $imageUrl = null)
    {
        try {
            // Подготовка данных для запроса
            $data = [
                'title' => $title,
                'text' => $content,
                'channel_id' => $this->channelId
            ];

            // Добавляем обложку, если передана
            if ($imageUrl) {
                $data['cover_url'] = $imageUrl;
            }

            // Выполняем запрос к API
            $response = Http::withToken($this->apiToken)
                ->post("{$this->apiUrl}/publications", $data);

            // Проверка успешности запроса
            if ($response->successful()) {
                Log::info('Публикация в Дзен успешно создана', [
                    'title' => $title,
                    'response' => $response->json()
                ]);
                
                return $response->json();
            } else {
                Log::error('Ошибка при публикации в Дзен', [
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Исключение при публикации в Дзен: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return null;
        }
    }

    /**
     * Получение информации о публикации
     * 
     * @param string $publicationId ID публикации
     * @return array|null Информация о публикации или null в случае ошибки
     */
    public function getPublicationStatus($publicationId)
    {
        try {
            $response = Http::withToken($this->apiToken)
                ->get("{$this->apiUrl}/publications/{$publicationId}");

            if ($response->successful()) {
                return $response->json();
            } else {
                Log::error('Ошибка при получении статуса публикации в Дзене', [
                    'publication_id' => $publicationId,
                    'status' => $response->status(),
                    'error' => $response->body()
                ]);
                
                return null;
            }
        } catch (\Exception $e) {
            Log::error('Исключение при получении статуса публикации в Дзене: ' . $e->getMessage());
            return null;
        }
    }
}
