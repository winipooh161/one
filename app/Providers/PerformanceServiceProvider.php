<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Log;

class PerformanceServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Кэшируем маршруты в production
        if ($this->app->environment('production')) {
            // Если кеш маршрутов не создан, создаем его
            if (!file_exists($this->app->getCachedRoutesPath())) {
                $this->app->booted(function() {
                    try {
                        \Artisan::call('route:cache');
                    } catch (\Exception $e) {
                        Log::warning('Failed to cache routes: ' . $e->getMessage());
                    }
                });
            }

            // Если кеш конфигураций не создан, создаем его
            if (!file_exists($this->app->getCachedConfigPath())) {
                $this->app->booted(function() {
                    try {
                        \Artisan::call('config:cache');
                    } catch (\Exception $e) {
                        Log::warning('Failed to cache config: ' . $e->getMessage());
                    }
                });
            }

            // Отключаем логирование запросов в production
            DB::disableQueryLog();
        }

        // Включаем логирование SQL только если включена соответствующая настройка
        if (config('app.debug') && config('app.log_queries', false)) {
            DB::listen(function ($query) {
                if ($query->time > 100) { // Логируем только запросы длительнее 100ms
                    Log::channel('sql')->info('Slow query', [
                        'sql' => $query->sql,
                        'bindings' => $query->bindings,
                        'time' => $query->time . 'ms'
                    ]);
                }
            });
        }
    }
}
