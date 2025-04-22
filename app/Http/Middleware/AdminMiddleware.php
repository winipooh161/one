<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем, что пользователь авторизован и имеет роль админа
        if (!auth()->check() || !auth()->user()->hasRole('admin')) {
            return redirect()->route('login')->with('error', 'У вас нет доступа к этой странице.');
        }

        // Обработка запросов к разделу Telegram, если таблицы не существуют
        if ($request->is('admin/telegram*') && !$request->is('admin/telegram/settings*')) {
            try {
                // Проверяем существование необходимых таблиц для Telegram
                $telegramTablesExist = Schema::hasTable('telegram_chats');
                
                if (!$telegramTablesExist && !$request->is('admin/telegram/setup')) {
                    // Перенаправляем на страницу настройки, если таблиц нет
                    return redirect()->route('admin.telegram.setup')
                        ->with('warning', 'Необходимо выполнить миграции для Telegram бота.');
                }
            } catch (\Exception $e) {
                Log::error('Ошибка при проверке таблиц Telegram: ' . $e->getMessage());
                // Продолжаем выполнение запроса, контроллер должен обработать ошибку
            }
        }

        return $next($request);
    }
}
