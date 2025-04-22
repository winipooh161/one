<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ProfileRequests
{
    /**
     * Профилирует запросы для анализа производительности.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle(Request $request, Closure $next)
    {
        // Время начала выполнения запроса
        $startTime = microtime(true);
        
        // Счётчик запросов к базе данных
        $queryCount = 0;
        $queryLog = [];
        
        // Включаем логирование запросов (только в режиме отладки)
        if (config('app.debug')) {
            DB::enableQueryLog();
        }
        
        // Обрабатываем запрос
        $response = $next($request);
        
        // Профилируем запросы к БД
        if (config('app.debug')) {
            $queries = DB::getQueryLog();
            $queryCount = count($queries);
            
            // Ограничиваем логирование только запросами, которые выполняются дольше 100мс
            foreach ($queries as $query) {
                if ($query['time'] > 100) { // время в мс
                    $queryLog[] = [
                        'sql' => $query['query'],
                        'bindings' => $query['bindings'],
                        'time' => $query['time'] . 'ms'
                    ];
                }
            }
        }
        
        // Время окончания выполнения запроса
        $endTime = microtime(true);
        $executionTime = ($endTime - $startTime) * 1000; // в миллисекундах
        
        // Логируем только "медленные" запросы (более 1 секунды)
        if ($executionTime > 1000) {
            Log::warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time' => round($executionTime, 2) . 'ms',
                'query_count' => $queryCount,
                'slow_queries' => $queryLog,
                'memory_usage' => round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB'
            ]);
        }
        
        // Добавляем заголовки с информацией о производительности (только в режиме отладки)
        if (config('app.debug') && !$request->ajax() && $response->headers) {
            $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
            $response->headers->set('X-Query-Count', $queryCount);
            $response->headers->set('X-Memory-Usage', round(memory_get_peak_usage(true) / 1024 / 1024, 2) . 'MB');
        }
        
        return $response;
    }
}
