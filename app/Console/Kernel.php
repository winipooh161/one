<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;
use App\Console\Commands\GenerateSitemap;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        // Добавляем нашу новую команду для генерации XML-фидов
        GenerateSitemap::class,
        
        // Добавляем команду для генерации карты сайта
        Commands\GenerateSitemap::class,
        
        // Удаляем или комментируем отсутствующие команды
        // \App\Console\Commands\UpdateIngredientsStructure::class,
        // \App\Console\Commands\ReindexRecipes::class,
        // \App\Console\Commands\CollectAllUrls::class,
        // \App\Console\Commands\GenerateRssFeedCommand::class,
    ];

    /**
     * Define the application's command schedule.
     */
    protected function schedule(Schedule $schedule): void
    {
        // Генерация фидов для Яндекса каждый день в 3:00
        $schedule->command('yandex:feeds')->dailyAt('03:00');
        
        // Генерация sitemap каждый день в 3:30
        // Исправляем имя команды с generate:sitemap на sitemap:generate
        $schedule->command('sitemap:generate')->dailyAt('03:30')
                ->withoutOverlapping() // Предотвращает запуск, если предыдущий экземпляр все еще работает
                ->runInBackground()    // Запускает в фоновом режиме
                ->appendOutputTo(storage_path('logs/sitemap-generation.log'));
        
        // Генерация YML-фида каждый день в 01:00
        $schedule->command('feed:generate-yml --save')
                ->dailyAt('01:00')
                ->withoutOverlapping()
                ->runInBackground()
                ->appendOutputTo(storage_path('logs/yml-feed.log'));
    }

    /**
     * Register the commands for the application.
     */
    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');

        require base_path('routes/console.php');
    }
}
