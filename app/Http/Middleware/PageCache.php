<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

class PageCache
{
    /**
     * Обрабатывает входящий запрос.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Не используем кэш для авторизованных пользователей
        if (Auth::check() || $request->isMethod('post')) {
            return $next($request);
        }

        // Формируем ключ кэша на основе URL и query-параметров
        $cacheKey = 'page_cache_' . md5($request->fullUrl());

        // Проверяем наличие данных в кэше
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Если кэша нет, выполняем запрос и сохраняем результат
        $response = $next($request);

        // Не кэшируем ошибки и редиректы
        if ($response instanceof Response && $response->isSuccessful() && !$response->isRedirection()) {
            Cache::put($cacheKey, $response, now()->addHours(1)); // Кэшируем на 1 час
        }

        return $response;
    }
}
