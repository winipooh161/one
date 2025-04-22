<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\Recipe;
use App\Models\NewsComment; 
use Illuminate\Http\Request;
use App\Services\SeoService;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema;

class NewsController extends Controller
{
    protected $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    /**
     * Отображение списка новостей с поиском
     */
    public function index(Request $request)
    {
        $query = News::query()->where('is_published', true);
        
        // Обработка поиска
        $searchTerm = trim($request->input('search', $request->input('q', '')));
        if (!empty($searchTerm)) {
            $lowerSearchTerm = strtolower($searchTerm);
            $query->where(function($q) use ($lowerSearchTerm) {
                $q->whereRaw('LOWER(title) like ?', ['%' . $lowerSearchTerm . '%'])
                  ->orWhereRaw('LOWER(short_description) like ?', ['%' . $lowerSearchTerm . '%'])
                  ->orWhereRaw('LOWER(content) like ?', ['%' . $lowerSearchTerm . '%']);
            });
        }
        
        // Фильтрация по типу новости (видео/обычные)
        $type = $request->input('type');
        if ($type === 'video') {
            $query->whereNotNull('video_iframe');
        } elseif ($type === 'regular') {
            $query->whereNull('video_iframe');
        }
        
        // Сортировка (по умолчанию новые записи сверху)
        $query->orderBy('created_at', 'desc');
        
        // Применяем пагинацию напрямую к запросу без промежуточной выборки всех новостей
        // Это предотвращает дублирование при организации выдачи
        $perPage = 9;
        $news = $query->paginate($perPage);
        
        // Обработка AJAX-запросов для бесконечной прокрутки
        if ($request->ajax()) {
            return view('news.partials.news_items', compact('news'));
        }
        
        // Получаем популярные новости для сайдбара
        $popularNews = News::getPopular(5);
        
        // Получаем популярные рецепты для блока рекомендаций
        $recommendedRecipes = Recipe::where('is_published', true)
            ->orderBy('views', 'desc')
            ->limit(3)
            ->get();
        
        return view('news.index', compact('news', 'searchTerm', 'popularNews', 'recommendedRecipes', 'type'));
    }

    /**
     * Отображение отдельной новости
     */
    public function show(string $slug)
    {
        $news = News::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
        
        // Увеличиваем счетчик просмотров
        $news->increment('views');
        
        // Рассчитываем время чтения
        $readingTime = $news->calculateReadingTime();
        
        // Получаем похожие новости
        $relatedNews = $news->getRelatedNews(4);
        
        // Получаем следующую и предыдущую новости
        $nextNews = $news->getNextNews();
        $prevNews = $news->getPreviousNews();
        
        // Получаем популярные рецепты для блока рекомендаций
        $recommendedRecipes = Recipe::where('is_published', true)
            ->orderBy('views', 'desc')
            ->limit(3)
            ->get();
            
        // Получаем комментарии к новости только если таблица существует
        $comments = collect(); // По умолчанию пустая коллекция
        
        if (Schema::hasTable('news_comments')) {
            $comments = $news->comments()
                ->with('user')
                ->orderBy('created_at', 'desc')
                ->get();
        }
        
        // SEO настройки
        $metaTitle = $news->meta_title ?: $news->title;
        $metaDescription = $news->meta_description ?: Str::limit($news->short_description, 160);
        
        $this->seoService->setTitle($metaTitle)
            ->setDescription($metaDescription)
            ->setKeywords($news->meta_keywords ?: 'кулинарные новости, новости кулинарии, рецепты, ' . $news->title)
            ->setCanonical(route('news.show', $news->slug))
            ->setOgType('article')
            ->setOgImage($news->getOgImage());
            
        // Устанавливаем дополнительные Open Graph теги для статьи
        // Используем альтернативный подход без метода setMeta
        // Примечание: Обновите согласно имеющимся методам в вашем SeoService
        
        // JSON-LD разметка для статьи (улучшенная схема)
        $jsonLd = [
            '@context' => 'https://schema.org',
            '@type' => 'NewsArticle',
            'headline' => $news->title,
            'description' => $news->short_description,
            'image' => $news->image_url ? [asset('uploads/' . $news->image_url)] : [asset('images/news-placeholder.jpg')],
            'datePublished' => $news->created_at->toIso8601String(),
            'dateModified' => $news->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $news->user ? $news->user->name : config('app.name')
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png'),
                    'width' => '192',
                    'height' => '192'
                ]
            ],
            'mainEntityOfPage' => [
                '@type' => 'WebPage',
                '@id' => route('news.show', $news->slug)
            ],
            'wordCount' => str_word_count(strip_tags($news->content)),
            'articleBody' => Str::limit(strip_tags($news->content), 500)
        ];
        
        $this->seoService->setSchema($jsonLd);
        
        return view('news.show', compact('news', 'relatedNews', 'readingTime', 'nextNews', 'prevNews', 'recommendedRecipes', 'comments'));
    }
}
