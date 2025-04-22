<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SeoService;
use Illuminate\Support\Facades\View;
use App\Models\Recipe;

class PwaController extends Controller
{
    protected $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Отображает оффлайн страницу для PWA
     *
     * @return \Illuminate\Http\Response
     */
    public function offline()
    {
        return view('pwa.offline');
    }
    
    /**
     * Страница установки PWA с инструкциями для разных устройств
     *
     * @return \Illuminate\Http\Response
     */
    public function install()
    {
        $this->seoService->setTitle('Установить приложение рецептов')
            ->setDescription('Установите наше приложение на свое устройство и получите доступ к рецептам даже без интернета.')
            ->setKeywords('установить приложение, PWA, рецепты, мобильное приложение');

        return view('pwa.install');
    }

    /**
     * API-метод для получения информации о том, как установить PWA на разных устройствах
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function installInfo(Request $request)
    {
        $device = $this->detectDevice($request->userAgent() ?? '');
        
        $instructions = [
            'device' => $device,
            'steps' => $this->getInstallSteps($device)
        ];
        
        return response()->json($instructions);
    }
    
    /**
     * Определяет тип устройства по User-Agent
     *
     * @param string $userAgent
     * @return string
     */
    private function detectDevice($userAgent) 
    {
        if (preg_match('/iPad|iPhone|iPod/i', $userAgent) && !preg_match('/Windows Phone/i', $userAgent)) {
            return 'ios';
        } elseif (preg_match('/Android/i', $userAgent)) {
            return 'android';
        } elseif (preg_match('/Windows NT/i', $userAgent)) {
            return 'windows';
        } elseif (preg_match('/Macintosh|Mac OS X/i', $userAgent)) {
            return 'mac';
        } else {
            return 'other';
        }
    }
    
    /**
     * Получает инструкции по установке для определенного устройства
     *
     * @param string $device
     * @return array
     */
    private function getInstallSteps($device)
    {
        switch ($device) {
            case 'ios':
                return [
                    'Откройте сайт в браузере Safari',
                    'Нажмите на кнопку "Поделиться" <i class="fas fa-share-square"></i> внизу экрана',
                    'Прокрутите вниз и нажмите "Добавить на экран "Домой"',
                    'Нажмите "Добавить" в верхнем правом углу'
                ];
            case 'android':
                return [
                    'Откройте сайт в браузере Chrome',
                    'Нажмите на меню (три точки) в правом верхнем углу',
                    'Выберите "Установить приложение" или "Добавить на главный экран"',
                    'Нажмите "Установить" в появившемся окне'
                ];
            case 'windows':
                return [
                    'Откройте сайт в браузере Edge или Chrome',
                    'Нажмите на значок установки <i class="fas fa-plus-square"></i> в адресной строке',
                    'Или нажмите на кнопку "Установить приложение" ниже',
                    'Подтвердите установку в появившемся диалоговом окне'
                ];
            case 'mac':
                return [
                    'Откройте сайт в браузере Safari',
                    'В меню выберите "Safari" > "Настройки" > "Веб-сайты"',
                    'Включите "Добавление в Dock" для этого сайта',
                    'Перезагрузите страницу и нажмите "Установить"'
                ];
            default:
                return [
                    'Откройте сайт в современном браузере (Chrome, Edge, Firefox)',
                    'Нажмите на меню браузера (обычно в правом верхнем углу)',
                    'Найдите и выберите пункт "Установить приложение" или "Добавить на главный экран"',
                    'Следуйте инструкциям на экране'
                ];
        }
    }

    /**
     * Записывает информацию об установке PWA в аналитику
     *
     * @param  Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function trackInstall(Request $request)
    {
        $device = $request->input('device');
        $platform = $request->input('platform');
        
        // Здесь можно добавить код для записи в аналитику или базу данных
        // например: DB::table('pwa_installs')->insert(['device' => $device, 'platform' => $platform]);
        
        return response()->json(['success' => true]);
    }

    /**
     * Вернуть манифест приложения
     */
    public function manifest()
    {
        $manifest = [
            'name' => 'Яедок - Кулинарные рецепты',
            'short_name' => 'Яедок',
            'description' => 'Лучшие кулинарные рецепты для вас',
            'start_url' => '/?source=pwa',
            'display' => 'standalone',
            'orientation' => 'portrait',
            'background_color' => '#ffffff',
            'theme_color' => '#0d6efd',
            'categories' => ['food', 'cooking', 'recipes'],
            'lang' => 'ru-RU',
            'dir' => 'ltr',
            'prefer_related_applications' => false,
            'scope' => '/',
            'icons' => [
                [
                    'src' => '/android-icon-72x72.png',
                    'sizes' => '72x72',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/android-icon-96x96.png',
                    'sizes' => '96x96',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/android-icon-144x144.png',
                    'sizes' => '144x144',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/android-icon-192x192.png',
                    'sizes' => '192x192',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/android-icon-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'any'
                ],
                [
                    'src' => '/android-icon-maskable-512x512.png',
                    'sizes' => '512x512',
                    'type' => 'image/png',
                    'purpose' => 'maskable'
                ]
            ],
            'shortcuts' => [
                [
                    'name' => 'Поиск рецептов',
                    'short_name' => 'Поиск',
                    'description' => 'Быстрый поиск рецептов',
                    'url' => '/search',
                    'icons' => [
                        [
                            'src' => '/shortcut-search.png',
                            'sizes' => '96x96',
                            'type' => 'image/png'
                        ]
                    ]
                ],
                [
                    'name' => 'Категории рецептов',
                    'short_name' => 'Категории',
                    'description' => 'Просмотр категорий рецептов',
                    'url' => '/categories',
                    'icons' => [
                        [
                            'src' => '/shortcut-categories.png',
                            'sizes' => '96x96',
                            'type' => 'image/png'
                        ]
                    ]
                ]
            ]
        ];
        
        return response()->json($manifest);
    }
    
    /**
     * Вернуть список рецептов для сохранения в офлайн-режиме (API)
     */
    public function offlineRecipes()
    {
        $recipes = Recipe::with('category')
            ->select(['id', 'title', 'slug', 'description', 'image', 'cooking_time', 'category_id'])
            ->limit(20)
            ->latest()
            ->get();
            
        return response()->json($recipes);
    }
}
