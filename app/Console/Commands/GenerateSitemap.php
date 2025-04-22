<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\User;
use App\Models\News;
use Carbon\Carbon;

class GenerateSitemap extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'sitemap:generate {--details : Показать детальную информацию о процессе}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Генерирует XML карты сайта';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Начало генерации Sitemap...');
        
        // Генерация основного файла sitemap
        $this->generateMainSitemap();
        
        // Генерация карты сайта для рецептов
        $this->generateRecipesSitemap();
        
        // Генерация карты сайта для категорий
        $this->generateCategoriesSitemap();
        
        // Генерация карты сайта для статических страниц
        $this->generateStaticSitemap();
        
        // Генерация карты сайта для пагинации
        $this->generatePaginationSitemap();
        
        // Генерация карты сайта для пользователей
        $this->generateUsersSitemap();
        
        // Генерация карты сайта для новостей
        $this->generateNewsSitemap();
        
        $this->info('Генерация Sitemap завершена успешно.');
        
        return Command::SUCCESS;
    }
    
    /**
     * Генерирует основной файл sitemap.xml
     */
    private function generateMainSitemap()
    {
        $this->info('Генерация основного sitemap.xml...');
        
        // Явно определяем переменную $now для передачи в шаблон
        $now = Carbon::now()->toAtomString();
        
        // Передаем переменную $now в шаблон с использованием compact
        $content = view('sitemaps.index', ['now' => $now])->render();
        File::put(public_path('sitemap.xml'), $content);
        
        $this->info('Основной sitemap.xml создан успешно.');
    }
    
    /**
     * Генерирует sitemap для рецептов
     */
    private function generateRecipesSitemap()
    {
        $this->info('Генерация sitemap-recipes.xml...');
        
        $urls = [];
        $count = 0;
        
        Recipe::where('is_published', true)
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'asc')
            ->select('id', 'slug', 'updated_at', 'image_url')
            ->chunkById(100, function($recipes) use (&$urls, &$count) {
                foreach ($recipes as $recipe) {
                    $urls[] = [
                        'loc' => route('recipes.show', $recipe->slug),
                        'lastmod' => $recipe->updated_at->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.8',
                        'image' => $recipe->image_url ? asset('uploads/' . $recipe->image_url) : null
                    ];
                    $count++;
                }
            });
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-recipes.xml'), $content);
        
        $this->info("sitemap-recipes.xml создан успешно: {$count} URL.");
    }
    
    /**
     * Генерирует sitemap для категорий
     */
    private function generateCategoriesSitemap()
    {
        $this->info('Генерация sitemap-categories.xml...');
        
        $urls = [];
        $count = 0;
        
        // Исправлено: убираем поле image из запроса
        Category::orderBy('updated_at', 'desc')
            ->orderBy('id', 'asc')
            ->select('id', 'slug', 'updated_at')
            ->chunkById(100, function($categories) use (&$urls, &$count) {
                foreach ($categories as $category) {
                    $urls[] = [
                        'loc' => route('categories.show', $category->slug),
                        'lastmod' => $category->updated_at->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.7'
                    ];
                    $count++;
                }
            });
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-categories.xml'), $content);
        
        $this->info("sitemap-categories.xml создан успешно: {$count} URL.");
    }
    
    /**
     * Генерирует sitemap для статических страниц
     */
    private function generateStaticSitemap()
    {
        $this->info('Генерация sitemap-static.xml...');
        
        $urls = [
            [
                'loc' => route('home'),
                'lastmod' => Carbon::now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '1.0'
            ],
            [
                'loc' => route('recipes.index'),
                'lastmod' => Carbon::now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.9'
            ],
            [
                'loc' => route('categories.index'),
                'lastmod' => Carbon::now()->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ],
            [
                'loc' => route('legal.terms'),
                'lastmod' => Carbon::now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.3'
            ],
            [
                'loc' => route('legal.privacy'),
                'lastmod' => Carbon::now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.3'
            ],
            [
                'loc' => route('legal.disclaimer'),
                'lastmod' => Carbon::now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.3'
            ],
            [
                'loc' => route('legal.dmca'),
                'lastmod' => Carbon::now()->toAtomString(),
                'changefreq' => 'monthly',
                'priority' => '0.3'
            ],
            [
                'loc' => route('news.index'),
                'lastmod' => Carbon::now()->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8'
            ]
        ];
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-static.xml'), $content);
        
        $this->info("sitemap-static.xml создан успешно: " . count($urls) . " URL.");
    }
    
    /**
     * Генерирует sitemap для пагинации
     */
    private function generatePaginationSitemap()
    {
        $this->info('Генерация sitemap-pagination.xml...');
        
        $urls = [];
        
        // Пагинация для страницы рецептов - только первые 5 страниц
        $recipesCount = Recipe::where('is_published', true)->count();
        $recipesPages = min(5, ceil($recipesCount / 12));
        
        for ($i = 2; $i <= $recipesPages; $i++) { // Начинаем со 2-й страницы, так как 1-я уже в основном sitemap
            $urls[] = [
                'loc' => url('/recipes') . '?page=' . $i,
                'lastmod' => Carbon::now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '0.6'
            ];
        }
        
        // Пагинация для категорий - только первые 3 страницы каждой категории
        $categories = Category::all();
        
        foreach ($categories as $category) {
            $categoryRecipesCount = $category->recipes()->where('is_published', true)->count();
            $categoryPages = min(3, ceil($categoryRecipesCount / 12));
            
            for ($i = 2; $i <= $categoryPages; $i++) {
                $urls[] = [
                    'loc' => route('categories.show', $category->slug) . '?page=' . $i,
                    'lastmod' => $category->updated_at->toIso8601String(),
                    'changefreq' => 'weekly',
                    'priority' => '0.5'
                ];
            }
        }
        
        // Пагинация для новостей
        $newsCount = News::where('is_published', true)->count();
        $newsPages = min(5, ceil($newsCount / 10));
        
        // Исправление: добавлен символ $ к переменной i в цикле
        for ($i = 2; $i <= $newsPages; $i++) {
            $urls[] = [
                'loc' => url('/news') . '?page=' . $i,
                'lastmod' => Carbon::now()->toIso8601String(),
                'changefreq' => 'daily',
                'priority' => '0.6'
            ];
        }
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-pagination.xml'), $content);
        
        $this->info("sitemap-pagination.xml создан успешно: " . count($urls) . " URL.");
    }
    
    /**
     * Генерирует sitemap для пользователей
     */
    private function generateUsersSitemap()
    {
        $this->info('Генерация sitemap-users.xml...');
        
        $urls = [];
        $count = 0;
        
        // Изменяем запрос, убирая условие recipes_count > 0, т.к. has('recipes') уже
        // проверяет наличие рецептов и исключаем метод withCount
        User::has('recipes')
            ->orderBy('id', 'asc')
            ->select('id', 'updated_at')
            ->chunkById(100, function($users) use (&$urls, &$count) {
                foreach ($users as $user) {
                    $urls[] = [
                        'loc' => route('user.profile', $user->id),
                        'lastmod' => $user->updated_at->toAtomString(),
                        'changefreq' => 'weekly',
                        'priority' => '0.6'
                    ];
                    $count++;
                }
            });
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-users.xml'), $content);
        
        $this->info("sitemap-users.xml создан успешно: {$count} URL.");
    }
    
    /**
     * Генерирует sitemap для новостей
     */
    private function generateNewsSitemap()
    {
        $this->info('Генерация sitemap-news.xml...');
        
        $urls = [];
        $count = 0;
        
        News::where('is_published', true)
            ->orderBy('updated_at', 'desc')
            ->orderBy('id', 'asc')
            ->select('id', 'slug', 'updated_at', 'image_url')
            ->chunkById(100, function($news) use (&$urls, &$count) {
                foreach ($news as $item) {
                    $urls[] = [
                        'loc' => route('news.show', $item->slug),
                        'lastmod' => $item->updated_at->toAtomString(),
                        'changefreq' => 'daily',
                        'priority' => '0.8',
                        'image' => $item->image_url ? asset('uploads/' . $item->image_url) : null
                    ];
                    $count++;
                }
            });
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-news.xml'), $content);
        
        $this->info("sitemap-news.xml создан успешно: {$count} URL.");
    }
}
