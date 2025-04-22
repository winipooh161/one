<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Artisan;

class OpenAISettingsController extends Controller
{
    /**
     * Отображает форму настроек OpenAI.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        return view('admin.settings.openai');
    }
    
    /**
     * Обновляет настройки OpenAI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request)
    {
        $validated = $request->validate([
            'api_key' => 'required|string',
            'model' => 'required|string|in:gpt-3.5-turbo,gpt-4,gpt-4-turbo-preview,gpt-4-1106-preview',
        ]);
        
        try {
            // Обновляем .env файл
            $this->updateEnvironmentFile([
                'OPENAI_API_KEY' => $validated['api_key'],
                'OPENAI_MODEL' => $validated['model'],
            ]);
            
            // Очищаем кеш конфигурации
            Artisan::call('config:clear');
            
            return redirect()->route('admin.settings.openai.index')
                ->with('success', 'Настройки OpenAI успешно обновлены.');
        } catch (\Exception $e) {
            Log::error('Error updating OpenAI settings', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return redirect()->route('admin.settings.openai.index')
                ->with('error', 'Ошибка при обновлении настроек: ' . $e->getMessage());
        }
    }
    
    /**
     * Тестирует подключение к API OpenAI.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function testConnection(Request $request)
    {
        $apiKey = config('services.openai.api_key');
        $model = config('services.openai.model', 'gpt-3.5-turbo');
        
        if (!$apiKey) {
            return response()->json([
                'success' => false,
                'error' => 'API ключ не настроен. Пожалуйста, сначала сохраните API ключ.'
            ]);
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->get('https://api.openai.com/v1/models');
            
            if ($response->successful()) {
                return response()->json([
                    'success' => true,
                    'model' => $model
                ]);
            } else {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Неизвестная ошибка API';
                
                // Проверка на превышение квоты
                if (isset($errorBody['error']['type']) && $errorBody['error']['type'] === 'insufficient_quota') {
                    Log::error('OpenAI API quota exceeded', [
                        'details' => $errorBody,
                    ]);
                    
                    return response()->json([
                        'success' => false,
                        'error' => 'Превышен лимит запросов к OpenAI API. Пожалуйста, проверьте баланс вашего аккаунта OpenAI и настройки биллинга.',
                        'quota_exceeded' => true,
                        'help_url' => 'https://platform.openai.com/account/billing'
                    ]);
                }
                
                return response()->json([
                    'success' => false,
                    'error' => 'Ошибка API: ' . $errorMessage
                ]);
            }
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'error' => 'Ошибка подключения: ' . $e->getMessage()
            ]);
        }
    }
    
    /**
     * Обновляет переменные в .env файле.
     *
     * @param  array  $data
     * @return bool
     */
    protected function updateEnvironmentFile(array $data)
    {
        $path = app()->environmentFilePath();
        $content = file_get_contents($path);
        
        foreach ($data as $key => $value) {
            // Экранируем специальные символы в значениях
            $value = str_replace(['\\', '"', '$'], ['\\\\', '\\"', '\\$'], $value);
            
            if (preg_match("/^{$key}=(.*)$/m", $content)) {
                // Если переменная уже существует, заменяем её значение
                $content = preg_replace("/^{$key}=(.*)$/m", "{$key}=\"{$value}\"", $content);
            } else {
                // Если переменной нет, добавляем её в конец файла
                $content .= PHP_EOL . "{$key}=\"{$value}\"";
            }
        }
        
        file_put_contents($path, $content);
        
        return true;
    }

    /**
     * Обрабатывает и оптимизирует изображение
     * 
     * @param \Illuminate\Http\UploadedFile $image
     * @return string Путь к оптимизированному изображению
     */
    protected function handleImageUpload($image)
    {
        try {
            $imageName = time() . '.' . $image->getClientOriginalExtension();
            
            // Проверяем размер файла и сжимаем если необходимо
            if ($image->getSize() > 1.5 * 1024 * 1024) { // Если больше 1.5MB
                $img = Image::make($image->getRealPath());
                
                // Уменьшаем разрешение если размер слишком большой
                $maxDimension = 1600;
                if ($img->width() > $maxDimension || $img->height() > $maxDimension) {
                    $img->resize($maxDimension, $maxDimension, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                }
                
                // Сжимаем с качеством 80%
                $img->encode($image->getClientOriginalExtension(), 80);
                
                // Сохраняем оптимизированное изображение
                $path = 'images/' . $imageName;
                Storage::disk('public')->put($path, $img);
                
                return $path;
            } else {
                // Если размер в норме, сохраняем без изменений
                $path = $image->storeAs('images', $imageName, 'public');
                return $path;
            }
        } catch (\Exception $e) {
            Log::error('Error optimizing image', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw $e;
        }
    }
}
