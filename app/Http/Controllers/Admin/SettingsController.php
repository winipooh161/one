<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Log;

class SettingsController extends Controller
{
    protected $vkService;
    
    public function __construct(VkService $vkService)
    {
        $this->vkService = $vkService;
        $this->middleware('auth');
        $this->middleware('admin');
    }
    
    /**
     * Отображает страницу настроек ВКонтакте
     */
    public function vk()
    {
        $settings = [
            'token' => env('VK_ACCESS_TOKEN'),
            'owner_id' => env('VK_OWNER_ID'),
            'client_id' => env('VK_CLIENT_ID'),
            'client_secret' => env('VK_CLIENT_SECRET')
        ];
        
        // Проверяем настройки подключения к ВК
        $connectionStatus = $this->vkService->checkSettings();
        
        // Проверяем доступность сервиса
        $serviceAvailable = $this->vkService->isServiceAvailable();
        
        return view('admin.settings.vk', compact('settings', 'connectionStatus', 'serviceAvailable'));
    }
    
    /**
     * Сохраняет настройки ВКонтакте
     */
    public function updateVk(Request $request)
    {
        $validated = $request->validate([
            'vk_access_token' => 'nullable|string',
            'vk_owner_id' => 'nullable|string',
            'vk_client_id' => 'nullable|string',
            'vk_client_secret' => 'nullable|string',
        ]);
        
        // Сохраняем настройки в .env файл
        $this->updateEnvFile([
            'VK_ACCESS_TOKEN' => $validated['vk_access_token'],
            'VK_OWNER_ID' => $validated['vk_owner_id'],
            'VK_CLIENT_ID' => $validated['vk_client_id'],
            'VK_CLIENT_SECRET' => $validated['vk_client_secret'],
        ]);
        
        // Очищаем кэш конфигурации
        try {
            Artisan::call('config:clear');
        } catch (\Exception $e) {
            Log::error('Ошибка при очистке кэша: ' . $e->getMessage());
        }
        
        return redirect()->route('admin.settings.vk')
            ->with('success', 'Настройки ВКонтакте успешно сохранены');
    }
    
    /**
     * Показывает страницу с помощью по получению токена ВКонтакте
     */
    public function vkTokenHelp()
    {
        return view('admin.settings.vk.token_help', [
            'client_id' => env('VK_CLIENT_ID')
        ]);
    }

    /**
     * Показывает страницу для быстрого добавления токена ВКонтакте
     */
    public function quickToken()
    {
        return view('admin.settings.vk.quick_token');
    }
    
    /**
     * Обновляет содержимое .env файла
     */
    private function updateEnvFile(array $values)
    {
        $envPath = app()->environmentFilePath();
        $envContents = file_get_contents($envPath);
        
        foreach ($values as $key => $value) {
            if (empty($value)) continue;
            
            // Проверяем, существует ли уже переменная
            if (strpos($envContents, "{$key}=") !== false) {
                // Заменяем значение существующей переменной
                $envContents = preg_replace("/{$key}=.*/", "{$key}={$value}", $envContents);
            } else {
                // Добавляем новую переменную
                $envContents .= "\n{$key}={$value}";
            }
        }
        
        file_put_contents($envPath, $envContents);
    }
}
