<?php

namespace App\Http\Controllers;

use App\Models\Article;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class ArticleController extends Controller
{
    /**
     * Отображение списка статей
     */
    public function index()
    {
        $articles = Article::where('is_published', true)
            ->orderBy('created_at', 'desc')
            ->paginate(10);
            
        return view('articles.index', compact('articles'));
    }

    /**
     * Отображение конкретной статьи
     */
    public function show($slug)
    {
        $article = Article::where('slug', $slug)
            ->where('is_published', true)
            ->firstOrFail();
            
        // Увеличиваем счетчик просмотров
        $article->increment('views');
            
        return view('articles.show', compact('article'));
    }
    
    /**
     * Генерация RSS-фида для статей
     */
    public function rss()
    {
        $articles = Cache::remember('articles_rss', 60, function () {
            return Article::where('is_published', true)
                ->orderBy('created_at', 'desc')
                ->limit(50)
                ->get();
        });

        return response()
            ->view('rss.articles', compact('articles'))
            ->header('Content-Type', 'application/rss+xml');
    }
}
