<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\VkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class VkOAuthController extends Controller
{
    protected $vkService;
    
    public function __construct(VkService $vkService)
    {
        $this->vkService = $vkService;
        $this->middleware('auth');
        $this->middleware('admin');
    }
    
    /**
     * Инициирует процесс OAuth авторизации в ВКонтакте
     */
    public function redirect()
    {
        $authUrl = $this->vkService->getOAuthUrl();
        return redirect($authUrl);
    }
    
    /**
     * Обрабатывает ответ от сервера авторизации ВКонтакте
     */
    public function callback(Request $request)
    {
        $code = $request->input('code');
        $state = $request->input('state');
        $error = $request->input('error');
        $errorDescription = $request->input('error_description');
        
        if ($error) {
            Log::error('Ошибка в OAuth ВКонтакте', [
                'error' => $error,
                'description' => $errorDescription
            ]);
            
            return redirect()->route('admin.settings.vk')
                ->with('error', 'Ошибка авторизации ВКонтакте: ' . $errorDescription);
        }
        
        if (!$code) {
            return redirect()->route('admin.settings.vk')
                ->with('error', 'Отсутствует код авторизации');
        }
        
        $result = $this->vkService->exchangeCodeForToken($code, $state);
        
        if (!$result['success']) {
            return redirect()->route('admin.settings.vk')
                ->with('error', 'Ошибка получения токена: ' . ($result['error'] ?? 'Неизвестная ошибка'));
        }
        
        // В результате мы получили токены для групп
        // Теперь нужно сохранить их в настройках или .env
        
        // Для демонстрации просто отобразим токены
        return view('admin.settings.vk.tokens', [
            'tokens' => $result['tokens'],
            'groups' => $result['groups']
        ]);
    }
}
