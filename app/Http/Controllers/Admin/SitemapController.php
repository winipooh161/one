<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Carbon\Carbon;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\User;
use App\Models\News;

class SitemapController extends Controller
{
    /**
     * Отображение страницы управления картой сайта
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $mainSitemapPath = public_path('sitemap.xml');
        $recipesSitemapPath = public_path('sitemap-recipes.xml');
        $categoriesSitemapPath = public_path('sitemap-categories.xml');
        $staticSitemapPath = public_path('sitemap-static.xml');
        $paginationSitemapPath = public_path('sitemap-pagination.xml');
        $usersSitemapPath = public_path('sitemap-users.xml');
        $newsSitemapPath = public_path('sitemap-news.xml');
        
        $mainSitemapExists = File::exists($mainSitemapPath);
        $mainSitemapLastUpdated = $mainSitemapExists ? Carbon::createFromTimestamp(File::lastModified($mainSitemapPath))->diffForHumans() : 'Файл не существует';
        
        $recipesSitemapExists = File::exists($recipesSitemapPath);
        $recipesSitemapLastUpdated = $recipesSitemapExists ? Carbon::createFromTimestamp(File::lastModified($recipesSitemapPath))->diffForHumans() : 'Файл не существует';
        
        $categoriesSitemapExists = File::exists($categoriesSitemapPath);
        $categoriesSitemapLastUpdated = $categoriesSitemapExists ? Carbon::createFromTimestamp(File::lastModified($categoriesSitemapPath))->diffForHumans() : 'Файл не существует';
        
        $staticSitemapExists = File::exists($staticSitemapPath);
        $staticSitemapLastUpdated = $staticSitemapExists ? Carbon::createFromTimestamp(File::lastModified($staticSitemapPath))->diffForHumans() : 'Файл не существует';
        
        $paginationSitemapExists = File::exists($paginationSitemapPath);
        $paginationSitemapLastUpdated = $paginationSitemapExists ? Carbon::createFromTimestamp(File::lastModified($paginationSitemapPath))->diffForHumans() : 'Файл не существует';
        
        $usersSitemapExists = File::exists($usersSitemapPath);
        $usersSitemapLastUpdated = $usersSitemapExists ? Carbon::createFromTimestamp(File::lastModified($usersSitemapPath))->diffForHumans() : 'Файл не существует';
        
        $newsSitemapExists = File::exists($newsSitemapPath);
        $newsSitemapLastUpdated = $newsSitemapExists ? Carbon::createFromTimestamp(File::lastModified($newsSitemapPath))->diffForHumans() : 'Файл не существует';
        
        $sitemaps = [
            [
                'name' => 'Основной sitemap.xml',
                'path' => 'sitemap.xml',
                'exists' => $mainSitemapExists,
                'last_updated' => $mainSitemapLastUpdated,
            ],
            [
                'name' => 'Рецепты sitemap-recipes.xml',
                'path' => 'sitemap-recipes.xml',
                'exists' => $recipesSitemapExists,
                'last_updated' => $recipesSitemapLastUpdated,
            ],
            [
                'name' => 'Категории sitemap-categories.xml',
                'path' => 'sitemap-categories.xml',
                'exists' => $categoriesSitemapExists,
                'last_updated' => $categoriesSitemapLastUpdated,
            ],
            [
                'name' => 'Статические страницы sitemap-static.xml',
                'path' => 'sitemap-static.xml',
                'exists' => $staticSitemapExists,
                'last_updated' => $staticSitemapLastUpdated,
            ],
            [
                'name' => 'Пагинация sitemap-pagination.xml',
                'path' => 'sitemap-pagination.xml',
                'exists' => $paginationSitemapExists,
                'last_updated' => $paginationSitemapLastUpdated,
            ],
            [
                'name' => 'Пользователи sitemap-users.xml',
                'path' => 'sitemap-users.xml',
                'exists' => $usersSitemapExists,
                'last_updated' => $usersSitemapLastUpdated,
            ],
            [
                'name' => 'Новости sitemap-news.xml',
                'path' => 'sitemap-news.xml',
                'exists' => $newsSitemapExists,
                'last_updated' => $newsSitemapLastUpdated,
            ]
        ];
        
        // Получаем общее количество рецептов, категорий и пользователей без загрузки всех записей
        $counts = [
            'recipes' => Recipe::count(),
            'categories' => Category::count(),
            'users' => User::has('recipes')->count(),
            'news' => News::count(),
        ];
        
        return view('admin.sitemap.index', compact('sitemaps', 'counts'));
    }
    
    /**
     * Сгенерировать все карты сайта
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generate(Request $request)
    {
        // Увеличиваем лимит памяти для генерации sitemap
        ini_set('memory_limit', '512M');
        
        try {
            // Запускаем команду для генерации sitemap с правильным именем
            $exitCode = Artisan::call('sitemap:generate', ['--details' => true]);
            
            if ($exitCode !== 0) {
                throw new \Exception('Команда вернула код ошибки: ' . $exitCode);
            }
            
            // Очищаем память после выполнения команды
            gc_collect_cycles();
            
            $referer = $request->header('referer');
            
            // Если запрос был отправлен со страницы новостей, возвращаемся туда
            if (strpos($referer, 'admin/news') !== false) {
                return redirect()->route('admin.news.index')
                    ->with('sitemap_success', 'Все карты сайта успешно сгенерированы!');
            }
            
            return redirect()->route('admin.sitemap.index')
                ->with('success', 'Все карты сайта успешно сгенерированы!');
        } catch (\Exception $e) {
            return back()->with('error', 'Ошибка при генерации карт сайта: ' . $e->getMessage());
        }
    }
    
    /**
     * Генерирует основной файл sitemap.xml
     */
    private function generateMainSitemap()
    {
        $now = Carbon::now()->toAtomString();
        $content = view('sitemaps.index', compact('now'))->render();
        File::put(public_path('sitemap.xml'), $content);
    }
    
    /**
     * Генерирует sitemap для рецептов
     */
    private function generateRecipesSitemap()
    {
        $recipes = Recipe::where('is_published', true)
            ->orderBy('updated_at', 'desc')
            ->get();
            
        $urls = $recipes->map(function($recipe) {
            return [
                'loc' => route('recipes.show', $recipe->slug),
                'lastmod' => $recipe->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.8'
            ];
        });
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-recipes.xml'), $content);
    }
    
    /**
     * Генерирует sitemap для категорий
     */
    private function generateCategoriesSitemap()
    {
        $categories = Category::orderBy('updated_at', 'desc')->get();
            
        $urls = $categories->map(function($category) {
            return [
                'loc' => route('categories.show', $category->slug),
                'lastmod' => $category->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.7'
            ];
        });
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-categories.xml'), $content);
    }
    
    /**
     * Генерирует sitemap для статических страниц
     */
    private function generateStaticSitemap()
    {
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
            ]
        ];
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-static.xml'), $content);
    }
    
    /**
     * Генерирует sitemap для пагинации
     */
    private function generatePaginationSitemap()
    {
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
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-pagination.xml'), $content);
    }
    
    /**
     * Генерирует sitemap для пользователей
     */
    private function generateUsersSitemap()
    {
        // Убираем withCount и условие recipes_count > 0, т.к. has('recipes') 
        // уже проверяет наличие рецептов
        $users = User::has('recipes')
            ->orderBy('id', 'asc')
            ->get();
        
        $urls = $users->map(function($user) {
            return [
                'loc' => route('user.profile', $user->id),
                'lastmod' => $user->updated_at->toAtomString(),
                'changefreq' => 'weekly',
                'priority' => '0.6'
            ];
        });
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-users.xml'), $content);
    }
    
    /**
     * Генерирует sitemap для новостей
     */
    private function generateNewsSitemap()
    {
        $news = News::where('is_published', true)
            ->orderBy('updated_at', 'desc')
            ->get();
            
        $urls = $news->map(function($item) {
            return [
                'loc' => route('news.show', $item->slug),
                'lastmod' => $item->updated_at->toAtomString(),
                'changefreq' => 'daily',
                'priority' => '0.8',
                'image' => $item->image_url ? asset('uploads/' . $item->image_url) : null
            ];
        });
        
        $content = view('sitemaps.urls', compact('urls'))->render();
        File::put(public_path('sitemap-news.xml'), $content);
    }
}
