<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;

class FeedController extends Controller
{
    /**
     * Конструктор с проверкой прав доступа
     */
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Отображает страницу управления фидами
     */
    public function index()
    {
        $feeds = [
            [
                'name' => 'YML-фид для Яндекса',
                'description' => 'Основной фид для Яндекс.Маркета в формате YML',
                'url' => route('feeds.yandex.yml'),
                'refresh_route' => route('admin.feeds.refresh', ['type' => 'yml']),
                'last_updated' => Cache::get('yml_feed_last_updated', 'Никогда'),
                'file_exists' => file_exists(public_path('feeds/yandex-yml.xml')),
                'file_size' => file_exists(public_path('feeds/yandex-yml.xml')) ? 
                    $this->formatBytes(filesize(public_path('feeds/yandex-yml.xml'))) : 'N/A',
            ],
            [
                'name' => 'XML-фид рецептов',
                'description' => 'Стандартный XML-фид всех рецептов',
                'url' => route('feeds.yandex.index'),
                'refresh_route' => route('admin.feeds.refresh', ['type' => 'recipes']),
                'last_updated' => Cache::get('recipes_feed_last_updated', 'Никогда'),
                'file_exists' => false,
                'file_size' => 'N/A',
            ],
        ];

        return view('admin.feeds.index', compact('feeds'));
    }

    /**
     * Обновляет выбранный фид
     */
    public function refresh(Request $request)
    {
        $type = $request->input('type');
        
        try {
            switch ($type) {
                case 'yml':
                    Artisan::call('feed:generate-yml', ['--save' => true]);
                    Cache::put('yml_feed_last_updated', now()->format('d.m.Y H:i:s'), 60*24*7);
                    $message = 'YML-фид успешно обновлен';
                    break;
                case 'recipes':
                    Cache::forget('yandex_feed_recipes');
                    Cache::put('recipes_feed_last_updated', now()->format('d.m.Y H:i:s'), 60*24*7);
                    $message = 'XML-фид рецептов успешно обновлен';
                    break;
                default:
                    return redirect()->route('admin.feeds.index')
                        ->with('error', 'Неизвестный тип фида');
            }
            
            return redirect()->route('admin.feeds.index')
                ->with('success', $message);
                
        } catch (\Exception $e) {
            return redirect()->route('admin.feeds.index')
                ->with('error', 'Ошибка при обновлении фида: ' . $e->getMessage());
        }
    }

    /**
     * Форматирует размер файла в человекочитаемый вид
     */
    private function formatBytes($bytes, $precision = 2)
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];
        
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        
        $bytes /= pow(1024, $pow);
        
        return round($bytes, $precision) . ' ' . $units[$pow];
    }
}
