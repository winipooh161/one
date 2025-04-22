<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image; // Убедитесь, что этот импорт присутствует

class NewsController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $query = News::query();
        
        // Фильтр по поисковому запросу
        if ($search = $request->input('search')) {
            $query->where('title', 'like', "%{$search}%")
                  ->orWhere('short_description', 'like', "%{$search}%");
        }
        
        // Фильтр по типу новости
        if ($type = $request->input('type')) {
            if ($type === 'video') {
                $query->whereNotNull('video_iframe');
            } elseif ($type === 'regular') {
                $query->whereNull('video_iframe');
            }
        }
        
        // Фильтр по статусу публикации
        if ($status = $request->input('status')) {
            if ($status === 'published') {
                $query->where('is_published', true);
            } elseif ($status === 'draft') {
                $query->where('is_published', false);
            }
        }
        
        // Сортировка (по дате создания, новые сверху)
        $query->orderBy('created_at', 'desc');
        
        $news = $query->paginate(10);
        return view('admin.news.index', compact('news'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('admin.news.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $this->validateNews($request);
        
        $news = new News();
        $news->title = $request->title;
        $news->slug = $this->createUniqueSlug($request->title);
        $news->short_description = $request->short_description;
        $news->content = $request->content;
        $news->is_published = $request->has('is_published');
        $news->user_id = auth()->id();
        
        // Обработка полей видео
        $news->video_iframe = $request->video_iframe;
        $news->video_author_name = $request->video_author_name;
        $news->video_author_link = $request->video_author_link;
        $news->video_tags = $request->video_tags;
        $news->video_title = $request->video_title;
        $news->video_description = $request->video_description;
        
        // Обработка изображения в зависимости от выбранного варианта
        if ($request->has('use_video_thumbnail') && $request->input('video_thumbnail_url')) {
            // Использовать обложку из видео
            try {
                // Создаем экземпляр VideoMetadataController для использования его метода downloadThumbnail
                $metadataController = app()->make('App\Http\Controllers\Admin\VideoMetadataController');
                $thumbnailPath = $metadataController->downloadThumbnail($request->input('video_thumbnail_url'));
                
                if ($thumbnailPath) {
                    $news->image_url = $thumbnailPath; // thumbnails/имя_файла.jpg
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Ошибка при загрузке обложки видео: ' . $e->getMessage());
            }
        } elseif ($request->hasFile('image')) {
            // Использовать загруженное изображение
            $news->image_url = $this->handleImageUpload($request->file('image')); // news/имя_файла.jpg
        }
        
        $news->save();
        
        return redirect()->route('admin.news.index')
            ->with('success', 'Новость успешно создана!');
    }

    /**
     * Display the specified resource.
     */
    public function show(News $news)
    {
        return view('admin.news.show', compact('news'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(News $news)
    {
        return view('admin.news.edit', compact('news'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, News $news)
    {
        $this->validateNews($request, $news->id);
        
        $news->title = $request->title;
        
        // Проверяем, изменился ли заголовок
        if ($news->getOriginal('title') !== $request->title) {
            $news->slug = $this->createUniqueSlug($request->title, $news->id);
        }
        
        $news->short_description = $request->short_description;
        $news->content = $request->content;
        $news->is_published = $request->has('is_published');
        
        // Обработка полей видео
        $oldVideoIframe = $news->video_iframe;
        $news->video_iframe = $request->video_iframe;
        $news->video_author_name = $request->video_author_name;
        $news->video_author_link = $request->video_author_link;
        $news->video_tags = $request->video_tags;
        $news->video_title = $request->video_title;
        $news->video_description = $request->video_description;
        
        // Обработка изображения в зависимости от выбранного варианта
        if ($request->has('use_video_thumbnail') && $request->input('video_thumbnail_url')) {
            // Использовать обложку из видео
            try {
                // Создаем экземпляр VideoMetadataController для использования его метода downloadThumbnail
                $metadataController = app()->make('App\Http\Controllers\Admin\VideoMetadataController');
                $thumbnailPath = $metadataController->downloadThumbnail($request->input('video_thumbnail_url'));
                
                if ($thumbnailPath) {
                    // Удаляем старое изображение, если оно существует
                    if ($news->image_url) {
                        $oldImagePath = public_path('uploads/' . $news->image_url);
                        if (file_exists($oldImagePath)) {
                            unlink($oldImagePath);
                        }
                    }
                    
                    $news->image_url = $thumbnailPath;
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error('Ошибка при загрузке обложки видео: ' . $e->getMessage());
                // Продолжаем выполнение даже при ошибке загрузки обложки
            }
        } elseif ($request->hasFile('image')) {
            // Удаляем старое изображение
            if ($news->image_url) {
                $oldImagePath = public_path('uploads/' . $news->image_url);
                if (file_exists($oldImagePath)) {
                    unlink($oldImagePath);
                }
            }
            
            $news->image_url = $this->handleImageUpload($request->file('image'));
        } else if ($request->has('delete_image') && $news->image_url) {
            // Удаляем изображение без замены
            $oldImagePath = public_path('uploads/' . $news->image_url);
            if (file_exists($oldImagePath)) {
                unlink($oldImagePath);
            }
            $news->image_url = null;
        }
        
        $news->save();
        
        return redirect()->route('admin.news.index')
            ->with('success', 'Новость успешно обновлена!');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(News $news)
    {
        // Удаляем изображение новости, если оно существует
        if ($news->image_url) {
            $imagePath = public_path('uploads/' . $news->image_url);
            if (file_exists($imagePath)) {
                unlink($imagePath);
            }
        }
        
        $news->delete();
        
        return redirect()->route('admin.news.index')
            ->with('success', 'Новость успешно удалена!');
    }
    
    /**
     * Валидация данных новости
     */
    private function validateNews(Request $request, $id = null)
    {
        $rules = [
            'title' => 'required|max:255',
            'short_description' => 'required',
            'content' => 'required',
            'image' => 'nullable|image|max:2048', // максимум 2Мб
            // Добавляем правила для полей видео
            'video_iframe' => 'nullable|string',
            'video_author_name' => 'nullable|string|max:255',
            'video_author_link' => 'nullable|url|max:255',
            'video_tags' => 'nullable|string|max:255',
            'video_title' => 'nullable|string|max:255',
            'video_description' => 'nullable|string',
            'video_thumbnail_url' => 'nullable|url', // Добавляем правило для URL обложки видео
        ];
        
        $messages = [
            'title.required' => 'Поле "Заголовок" обязательно для заполнения',
            'short_description.required' => 'Поле "Краткое описание" обязательно для заполнения',
            'content.required' => 'Поле "Содержание" обязательно для заполнения',
            'image.image' => 'Файл должен быть изображением',
            'image.max' => 'Размер изображения не должен превышать 2МБ',
            'video_author_link.url' => 'Ссылка на автора должна быть действительным URL',
        ];
        
        return $request->validate($rules, $messages);
    }
    
    /**
     * Обработка загрузки изображения в директорию public/uploads
     */
    private function handleImageUpload($image)
    {
        // Генерируем уникальное имя файла
        $filename = 'news_' . time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
        
        // Путь для сохранения изображения
        $path = 'news';
        
        // Проверяем и создаем директорию если она не существует
        $uploadPath = public_path('uploads/' . $path);
        if (!file_exists($uploadPath)) {
            mkdir($uploadPath, 0755, true);
        }
        
        // Сохраняем оригинальное изображение в public/uploads
        $img = Image::make($image->getRealPath());
        $img->save(public_path('uploads/' . $path . '/' . $filename));
        
        // Создаем и сохраняем thumbnail
        $img->fit(400, 300);
        $img->save(public_path('uploads/' . $path . '/thumb_' . $filename));
        
        // Возвращаем путь относительно директории uploads
        return $path . '/' . $filename;
    }

    /**
     * Извлекает URL видео из iframe
     */
    private function extractVideoUrlFromIframe($iframe)
    {
        if (empty($iframe)) {
            return null;
        }
        
        preg_match('/src=["\']([^"\']+)["\']/', $iframe, $matches);
        
        if (isset($matches[1])) {
            return $matches[1];
        }
        
        return null;
    }
    
    /**
     * Загружает HTML страницы видео
     */
    private function fetchVideoPage($url)
    {
        try {
            $context = stream_context_create([
                'http' => [
                    'header' => 'User-Agent: Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36'
                ]
            ]);
            
            
            return file_get_contents($url, false, $context);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка загрузки страницы видео: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * Создает уникальный slug на основе заголовка
     * 
     * @param string $title Заголовок новости
     * @param int|null $exceptId ID новости для исключения из проверки (при обновлении)
     * @return string Уникальный slug
     */
    private function createUniqueSlug($title, $exceptId = null)
    {
        // Создаем базовый slug из заголовка
        $baseSlug = Str::slug($title);
        
        // Проверяем уникальность slug
        $slug = $baseSlug;
        $counter = 1;
        
        // Запрос для проверки существования slug
        $query = News::where('slug', $slug);
        
        // Если это обновление записи, исключаем текущую запись из проверки
        if ($exceptId !== null) {
            $query->where('id', '!=', $exceptId);
        }
        
        // Пока существуют записи с таким slug, добавляем числовой суффикс
        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter++;
            $query = News::where('slug', $slug);
            
            if ($exceptId !== null) {
                $query->where('id', '!=', $exceptId);
            }
        }
        
        return $slug;
    }

    /**
     * API-метод для скачивания обложки видео
     */
    public function downloadThumbnail(Request $request)
    {
        $request->validate([
            'url' => 'required|url'
        ]);
        
        try {
            // Создаем экземпляр VideoMetadataController для использования его метода downloadThumbnail
            $metadataController = app()->make('App\Http\Controllers\Admin\VideoMetadataController');
            $thumbnailPath = $metadataController->downloadThumbnail($request->input('url'));
            
            if ($thumbnailPath) {
                return response()->json([
                    'success' => true,
                    'path' => $thumbnailPath,
                    'url' => asset('uploads/' . $thumbnailPath)
                ]);
            }
            
            return response()->json(['error' => 'Не удалось скачать обложку видео'], 422);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Ошибка при скачивании обложки видео: ' . $e->getMessage());
            return response()->json(['error' => 'Произошла ошибка: ' . $e->getMessage()], 500);
        }
    }
}
