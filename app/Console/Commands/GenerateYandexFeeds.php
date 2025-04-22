<?php

namespace App\Console\Commands;

use App\Models\Recipe;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;

class GenerateYandexFeeds extends Command
{
    protected $signature = 'yandex:feeds';
    protected $description = 'Generate Yandex feeds with schema.org markup for recipes';

    public function handle()
    {
        // Получаем все рецепты
        $recipes = Recipe::latest()->get();
        
        // Создаем фид с рецептами
        $recipesContent = View::make('feeds.yandex.index', compact('recipes'))->render();
        File::put(public_path('feeds/yandex/recipes.xml'), $recipesContent);
        
        // Создаем комбинированный фид
        $combinedContent = View::make('feeds.yandex.combined', compact('recipes'))->render();
        File::put(public_path('feeds/yandex/combined.xml'), $combinedContent);
        
        $this->info('Yandex feeds generated successfully');
        
        return Command::SUCCESS;
    }
}
