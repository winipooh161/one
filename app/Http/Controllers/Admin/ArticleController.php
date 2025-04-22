<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Article;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ArticleController extends Controller
{
    /**
     * Отображает список новостей
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $articles = Article::with('user', 'categories')
            ->orderBy('created_at', 'desc')
            ->paginate(15);

        return view('admin.articles.index', compact('articles'));
    }

    /**
     * Отображает форму создания новой новости
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $categories = Category::all();
        return view('admin.articles.create', compact('categories'));
    }

    /**
     * Сохраняет новую новость в базе данных
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|max:255',
                'slug' => 'nullable|alpha_dash|unique:articles,slug',
                'excerpt' => 'nullable',
                'content' => 'required',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Увеличено до 10MB
                'type' => 'required|in:news,article,guide',
                'status' => 'required|in:draft,published',
                'published_at' => 'nullable|date',
                'seo_title' => 'nullable|max:255',
                'seo_description' => 'nullable',
                'seo_keywords' => 'nullable',
                'categories' => 'nullable|array',
            ], [
                'image.max' => 'Изображение не должно превышать 10MB (10240 KB).',
                'image.mimes' => 'Изображение должно быть в формате: jpeg, png, jpg, gif.',
                'image.image' => 'Загруженный файл должен быть изображением.',
            ]);

            // Генерируем slug, если он не был предоставлен
            if (empty($validatedData['slug'])) {
                $validatedData['slug'] = Str::slug($validatedData['title']);
                
                // Проверяем уникальность slug
                $count = 0;
                $originalSlug = $validatedData['slug'];
                while (Article::where('slug', $validatedData['slug'])->exists()) {
                    $count++;
                    $validatedData['slug'] = $originalSlug . '-' . $count;
                }
            }

            // Обработка изображения, если оно загружено
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                
                // Оптимизируем изображение, если оно больше 2MB
                if ($image->getSize() > 2 * 1024 * 1024) {
                    $img = Image::make($image->getRealPath());
                    
                    // Уменьшаем размеры если изображение слишком большое
                    if ($img->width() > 1920 || $img->height() > 1080) {
                        $img->resize(1920, 1080, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }
                    
                    // Сжимаем с 80% качеством
                    $imagePath = 'articles/' . $imageName;
                    Storage::disk('public')->put($imagePath, $img->encode(null, 80));
                    
                    // Создаем миниатюру
                    $img->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    
                    $thumbPath = 'articles/thumb_' . $imageName;
                    Storage::disk('public')->put($thumbPath, $img->encode(null, 70));
                    
                    $validatedData['image'] = $imagePath;
                } else {
                    // Если размер в норме, используем стандартную обработку
                    $imagePath = 'articles/' . $imageName;
                    Storage::disk('public')->put($imagePath, file_get_contents($image));
                    
                    // Создаем уменьшенную версию
                    $img = Image::make($image->getRealPath());
                    $img->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    
                    $thumbPath = 'articles/thumb_' . $imageName;
                    Storage::disk('public')->put($thumbPath, $img->encode());
                    
                    $validatedData['image'] = $imagePath;
                }
            }

            // Установка дополнительных полей
            $validatedData['user_id'] = Auth::id();
            
            if ($validatedData['status'] === 'published' && empty($validatedData['published_at'])) {
                $validatedData['published_at'] = now();
            }

            // Создание статьи
            $article = Article::create($validatedData);

            // Привязка категорий
            if (isset($validatedData['categories'])) {
                $article->categories()->sync($validatedData['categories']);
            }

            return redirect()->route('admin.articles.index')
                ->with('success', 'Новость успешно создана!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Произошла ошибка при сохранении новости: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Отображает форму редактирования новости
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function edit(Article $article)
    {
        $categories = Category::all();
        $selectedCategories = $article->categories->pluck('id')->toArray();
        
        return view('admin.articles.edit', compact('article', 'categories', 'selectedCategories'));
    }

    /**
     * Обновляет данные новости
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, Article $article)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|max:255',
                'slug' => 'nullable|alpha_dash|unique:articles,slug,' . $article->id,
                'excerpt' => 'nullable',
                'content' => 'required',
                'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:10240', // Увеличено до 10MB
                'type' => 'required|in:news,article,guide',
                'status' => 'required|in:draft,published',
                'published_at' => 'nullable|date',
                'seo_title' => 'nullable|max:255',
                'seo_description' => 'nullable',
                'seo_keywords' => 'nullable',
                'categories' => 'nullable|array',
            ], [
                'image.max' => 'Изображение не должно превышать 10MB (10240 KB).',
                'image.mimes' => 'Изображение должно быть в формате: jpeg, png, jpg, gif.',
                'image.image' => 'Загруженный файл должен быть изображением.',
            ]);

            // Генерируем slug, если он не был предоставлен
            if (empty($validatedData['slug'])) {
                $validatedData['slug'] = Str::slug($validatedData['title']);
                
                // Проверка уникальности slug (исключая текущую новость)
                $count = 0;
                $originalSlug = $validatedData['slug'];
                while (Article::where('slug', $validatedData['slug'])->where('id', '!=', $article->id)->exists()) {
                    $count++;
                    $validatedData['slug'] = $originalSlug . '-' . $count;
                }
            }

            // Обработка изображения, если оно загружено
            if ($request->hasFile('image')) {
                // Удаляем предыдущее изображение, если оно существует
                if ($article->image && Storage::disk('public')->exists($article->image)) {
                    Storage::disk('public')->delete($article->image);
                    
                    // Удаление миниатюры
                    $thumbPath = 'articles/thumb_' . basename($article->image);
                    if (Storage::disk('public')->exists($thumbPath)) {
                        Storage::disk('public')->delete($thumbPath);
                    }
                }

                $image = $request->file('image');
                $imageName = time() . '.' . $image->getClientOriginalExtension();
                
                // Оптимизируем изображение, если оно больше 2MB
                if ($image->getSize() > 2 * 1024 * 1024) {
                    $img = Image::make($image->getRealPath());
                    
                    // Уменьшаем размеры если изображение слишком большое
                    if ($img->width() > 1920 || $img->height() > 1080) {
                        $img->resize(1920, 1080, function ($constraint) {
                            $constraint->aspectRatio();
                            $constraint->upsize();
                        });
                    }
                    
                    // Сжимаем с 80% качеством
                    $imagePath = 'articles/' . $imageName;
                    Storage::disk('public')->put($imagePath, $img->encode(null, 80));
                    
                    // Создаем миниатюру
                    $img->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    
                    $thumbPath = 'articles/thumb_' . $imageName;
                    Storage::disk('public')->put($thumbPath, $img->encode(null, 70));
                    
                    $validatedData['image'] = $imagePath;
                } else {
                    // Если размер в норме, используем стандартную обработку
                    $imagePath = 'articles/' . $imageName;
                    Storage::disk('public')->put($imagePath, file_get_contents($image));
                    
                    // Создаем уменьшенную версию
                    $img = Image::make($image->getRealPath());
                    $img->resize(800, null, function ($constraint) {
                        $constraint->aspectRatio();
                        $constraint->upsize();
                    });
                    
                    $thumbPath = 'articles/thumb_' . $imageName;
                    Storage::disk('public')->put($thumbPath, $img->encode());
                    
                    $validatedData['image'] = $imagePath;
                }
            }

            // Обновление статуса публикации
            if ($validatedData['status'] === 'published' && $article->status !== 'published') {
                $validatedData['published_at'] = $validatedData['published_at'] ?? now();
            }

            // Обновление данных статьи
            $article->update($validatedData);

            // Привязка категорий
            if (isset($validatedData['categories'])) {
                $article->categories()->sync($validatedData['categories']);
            } else {
                $article->categories()->detach();
            }

            return redirect()->route('admin.articles.index')
                ->with('success', 'Новость успешно обновлена!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            return back()->withErrors($e->validator)->withInput();
        } catch (\Exception $e) {
            return back()->with('error', 'Произошла ошибка при обновлении новости: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Удаляет новость
     *
     * @param  \App\Models\Article  $article
     * @return \Illuminate\Http\Response
     */
    public function destroy(Article $article)
    {
        try {
            // Удаляем изображение, если оно существует
            if ($article->image && Storage::disk('public')->exists($article->image)) {
                Storage::disk('public')->delete($article->image);
                
                // Удаление миниатюры
                $thumbPath = 'articles/thumb_' . basename($article->image);
                if (Storage::disk('public')->exists($thumbPath)) {
                    Storage::disk('public')->delete($thumbPath);
                }
            }

            // Отвязываем категории перед удалением
            $article->categories()->detach();
            
            // Удаляем статью
            $article->delete();
            
            return redirect()->route('admin.articles.index')
                ->with('success', 'Новость успешно удалена!');
                
        } catch (\Exception $e) {
            return redirect()->route('admin.articles.index')
                ->with('error', 'Ошибка при удалении новости: ' . $e->getMessage());
        }
    }

    /**
     * Генерирует slug для новости на основе заголовка
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateSlug(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'article_id' => 'nullable|integer|exists:articles,id',
        ]);

        $title = $request->input('title');
        $articleId = $request->input('article_id');
        
        $slug = Str::slug($title);
        
        // Проверяем уникальность slug
        $count = 0;
        $originalSlug = $slug;
        
        $query = Article::where('slug', $slug);
        if ($articleId) {
            $query->where('id', '!=', $articleId);
        }
        
        while ($query->exists()) {
            $count++;
            $slug = $originalSlug . '-' . $count;
            $query = Article::where('slug', $slug);
            if ($articleId) {
                $query->where('id', '!=', $articleId);
            }
        }
        
        return response()->json(['slug' => $slug]);
    }

    /**
     * Генерирует контент новости с использованием GPT API
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function generateWithGpt(Request $request)
    {
        try {
            $validated = $request->validate([
                'topic' => 'required|string|max:255',
                'type' => 'required|in:news,article,guide',
                'include_title' => 'boolean',
                'include_excerpt' => 'boolean',
                'include_seo' => 'boolean',
            ]);
            
            // Получаем API ключ и модель из конфигурации
            $apiKey = config('services.openai.api_key');
            $model = config('services.openai.model', 'gpt-3.5-turbo');
            
            // Проверяем форматирование названия модели - исправляем "gpt-4.0" на "gpt-4"
            if ($model === 'gpt-4.0') {
                $model = 'gpt-4';
            }
            
            if (!$apiKey) {
                return response()->json(['error' => 'API ключ не настроен. Пожалуйста, настройте API ключ OpenAI в конфигурации.'], 500);
            }
            
            // Формируем промпт в зависимости от типа контента
            $contentType = match($validated['type']) {
                'news' => 'новостной статьи',
                'article' => 'познавательной статьи',
                'guide' => 'подробного руководства',
                default => 'статьи'
            };
            
            $systemPrompt = "Вы опытный редактор кулинарного портала. Создайте качественный текст {$contentType} на русском языке о кулинарии по указанной теме. Используйте структурированный текст с HTML-разметкой: заголовки h2 и h3, абзацы p, списки ul/li, выделение важных моментов strong.";
            
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => "Напишите {$contentType} на тему: {$validated['topic']}. Текст должен быть информативным и интересным для читателей кулинарного сайта."]
            ];
            
            if ($validated['include_title'] ?? true) {
                $messages[] = ['role' => 'user', 'content' => "Также придумайте привлекательный и SEO-оптимизированный заголовок для этой статьи."];
            }
            
            if ($validated['include_excerpt'] ?? true) {
                $messages[] = ['role' => 'user', 'content' => "Добавьте краткое описание (excerpt) размером не более 2-3 предложений."];
            }
            
            if ($validated['include_seo'] ?? true) {
                $messages[] = ['role' => 'user', 'content' => "И создайте SEO-метатеги: заголовок (до 60 символов), описание (до 160 символов) и ключевые слова (до 10 ключевых слов через запятую)."];
            }
            
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => $messages,
                'temperature' => 0.7,
                'max_tokens' => 2500,
            ]);
            
            if ($response->failed()) {
                $errorBody = $response->json();
                $errorMessage = $errorBody['error']['message'] ?? 'Неизвестная ошибка API';
                $errorType = $errorBody['error']['type'] ?? '';
                
                // Специальная обработка ошибок квоты
                if ($errorType === 'insufficient_quota' || $response->status() === 429) {
                    Log::error('OpenAI API quota exceeded', [
                        'status' => $response->status(),
                        'body' => $response->body(),
                    ]);
                    return response()->json([
                        'error' => 'Превышен лимит запросов к OpenAI API. Пожалуйста, проверьте баланс вашего аккаунта OpenAI и настройки биллинга.',
                        'help' => 'Подробнее на https://platform.openai.com/account/billing'
                    ], 429);
                }
                
                Log::error('OpenAI API error', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return response()->json(['error' => 'Ошибка при обращении к API: ' . $errorMessage], 500);
            }
            
            $responseData = $response->json();
            $content = $responseData['choices'][0]['message']['content'] ?? '';
            
            // Парсим полученный контент
            $result = $this->parseGptResponse($content);
            
            return response()->json($result);
            
        } catch (\Exception $e) {
            Log::error('Error generating content with GPT', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return response()->json(['error' => 'Произошла ошибка при генерации контента: ' . $e->getMessage()], 500);
        }
    }
    
    /**
     * Парсит ответ GPT и извлекает заголовок, описание и контент
     */
    private function parseGptResponse($content)
    {
        $result = [
            'content' => $content, // По умолчанию весь контент
        ];
        
        // Извлекаем заголовок
        if (preg_match('/Заголовок:?\s*(?:<[^>]+>)?([^<\n]+)(?:<\/[^>]+>)?/i', $content, $matches)) {
            $result['title'] = trim($matches[1]);
            // Удаляем строку с заголовком из основного контента
            $content = str_replace($matches[0], '', $content);
        }
        
        // Извлекаем краткое описание
        if (preg_match('/Краткое описание:?\s*(?:<[^>]+>)?([^<]+)(?:<\/[^>]+>)?/is', $content, $matches)) {
            $result['excerpt'] = trim($matches[1]);
            // Удаляем строку с кратким описанием из основного контента
            $content = str_replace($matches[0], '', $content);
        }
        
        // Извлекаем SEO-заголовок
        if (preg_match('/SEO-заголовок:?\s*(?:<[^>]+>)?([^<\n]+)(?:<\/[^>]+>)?/i', $content, $matches)) {
            $result['seo_title'] = trim($matches[1]);
            // Удаляем строку с SEO-заголовком из основного контента
            $content = str_replace($matches[0], '', $content);
        }
        
        // Извлекаем Meta-описание
        if (preg_match('/Meta-?описание:?\s*(?:<[^>]+>)?([^<\n]+)(?:<\/[^>]+>)?/i', $content, $matches)) {
            $result['seo_description'] = trim($matches[1]);
            // Удаляем строку с Meta-описанием из основного контента
            $content = str_replace($matches[0], '', $content);
        }
        
        // Извлекаем ключевые слова
        if (preg_match('/Ключевые слова:?\s*(?:<[^>]+>)?([^<\n]+)(?:<\/[^>]+>)?/i', $content, $matches)) {
            $result['seo_keywords'] = trim($matches[1]);
            // Удаляем строку с ключевыми словами из основного контента
            $content = str_replace($matches[0], '', $content);
        }
        
        // Очищаем контент от лишних строк и обозначений
        $content = preg_replace('/^\s*Содержание:?\s*/i', '', $content);
        $content = trim($content);
        
        // Обновляем основной контент
        $result['content'] = $content;
        
        return $result;
    }
}
