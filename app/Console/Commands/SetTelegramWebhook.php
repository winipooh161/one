<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Config;

class SetTelegramWebhook extends Command
{
    protected $signature = 'telegram:set-webhook';
    protected $description = 'Установка webhook URL для Telegram бота рецептов';

    public function handle()
    {
        $botToken = Config::get('services.telegram.recipe.bot_token');
        $webhookUrl = Config::get('services.telegram.recipe.webhook_url');
        
        if (empty($botToken)) {
            $this->error('Токен бота не задан в конфигурации services.telegram.recipe.bot_token');
            return 1;
        }
        
        if (empty($webhookUrl)) {
            $this->error('URL вебхука не задан в конфигурации services.telegram.recipe.webhook_url');
            return 1;
        }
        
        $url = "https://api.telegram.org/bot{$botToken}/setWebhook";
        
        try {
            $response = Http::post($url, [
                'url' => $webhookUrl
            ]);
            
            if ($response->successful() && $response->json('ok')) {
                $this->info('Вебхук успешно установлен!');
                $this->info('Ответ: ' . json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return 0;
            } else {
                $this->error('Не удалось установить вебхук.');
                $this->error('Ответ: ' . json_encode($response->json(), JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                return 1;
            }
        } catch (\Exception $e) {
            $this->error('Произошла ошибка: ' . $e->getMessage());
            return 1;
        }
    }
}
