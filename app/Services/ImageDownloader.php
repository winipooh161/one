<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ImageDownloader
{
    /**
     * Скачивает изображение по URL и сохраняет его во временную директорию
     * 
     * @param string $url URL изображения
     * @return string|null Путь к скачанному файлу или null в случае ошибки
     */
    public static function download($url)
    {
        try {
            Log::info('Downloading image', ['url' => $url]);
            
            // Генерируем временное имя файла
            $tempFileName = 'temp_' . Str::random(10) . '.jpg';
            $tempDir = 'temp';
            $fullPath = storage_path('app/public/' . $tempDir . '/' . $tempFileName);
            
            // Создаем директорию если не существует
            if (!Storage::exists('public/' . $tempDir)) {
                Storage::makeDirectory('public/' . $tempDir);
            }
            
            // Методы скачивания изображения
            $downloaded = false;
            
            // Метод 1: Использование file_get_contents
            try {
                $imageContent = @file_get_contents($url);
                if ($imageContent !== false) {
                    Storage::put('public/' . $tempDir . '/' . $tempFileName, $imageContent);
                    $downloaded = true;
                }
            } catch (\Exception $e) {
                Log::warning('file_get_contents failed: ' . $e->getMessage());
            }
            
            // Метод 2: Использование cURL
            if (!$downloaded) {
                try {
                    $ch = curl_init($url);
                    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
                    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
                    curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
                    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
                    $imageContent = curl_exec($ch);
                    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
                    curl_close($ch);
                    
                    if ($httpCode === 200 && !empty($imageContent)) {
                        Storage::put('public/' . $tempDir . '/' . $tempFileName, $imageContent);
                        $downloaded = true;
                    } else {
                        Log::warning('cURL download failed', ['http_code' => $httpCode]);
                    }
                } catch (\Exception $e) {
                    Log::warning('cURL failed: ' . $e->getMessage());
                }
            }
            
            // Метод 3: Использование Guzzle/Http
            if (!$downloaded) {
                try {
                    $response = Http::timeout(30)->get($url);
                    if ($response->successful()) {
                        Storage::put('public/' . $tempDir . '/' . $tempFileName, $response->body());
                        $downloaded = true;
                    } else {
                        Log::warning('Http request failed', ['status' => $response->status()]);
                    }
                } catch (\Exception $e) {
                    Log::warning('Http request exception: ' . $e->getMessage());
                }
            }
            
            // Проверяем результат
            if ($downloaded && file_exists($fullPath)) {
                Log::info('Image downloaded successfully', ['path' => $fullPath]);
                return $fullPath;
            }
            
            Log::warning('Failed to download image', ['url' => $url]);
            return null;
        } catch (\Exception $e) {
            Log::error('Error downloading image: ' . $e->getMessage());
            return null;
        }
    }
}
