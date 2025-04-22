<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SocialPost;
use App\Services\TelegramService;
use App\Services\VkService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SocialPostController extends Controller
{
    protected $telegramService;
    protected $vkService;

    public function __construct(TelegramService $telegramService, VkService $vkService)
    {
        $this->telegramService = $telegramService;
        $this->vkService = $vkService;
    }

    public function index()
    {
        $posts = SocialPost::latest()->paginate(10);
        return view('admin.social_posts.index', compact('posts'));
    }

    public function create()
    {
        return view('admin.social_posts.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|max:2048',
            'publish_to_telegram' => 'nullable|boolean',
            'publish_to_vk' => 'nullable|boolean',
        ]);

        $post = new SocialPost();
        $post->title = $validated['title'];
        $post->content = $validated['content'];

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('social_posts', 'public');
            $post->image_url = asset('storage/' . $imagePath);
        }

        $post->save();

        $successMessages = [];
        $errorMessages = [];

        // Публикация в Telegram
        if ($request->has('publish_to_telegram')) {
            if ($this->publishToTelegramAction($post)) {
                $successMessages[] = 'Пост успешно опубликован в Telegram';
            } else {
                $errorMessages[] = 'Не удалось опубликовать пост в Telegram';
            }
        }

        // Публикация во ВКонтакте
        if ($request->has('publish_to_vk')) {
            if ($this->publishToVkAction($post)) {
                $successMessages[] = 'Пост успешно опубликован во ВКонтакте';
            } else {
                $errorMessages[] = 'Не удалось опубликовать пост во ВКонтакте';
            }
        }

        $redirectMessage = !empty($successMessages) 
            ? 'success' 
            : 'error';

        $messageText = !empty($successMessages) 
            ? implode(', ', $successMessages) 
            : implode(', ', $errorMessages);

        if (!empty($successMessages) && !empty($errorMessages)) {
            $messageText = implode(', ', $successMessages) . '. Ошибки: ' . implode(', ', $errorMessages);
            $redirectMessage = 'warning';
        }

        return redirect()->route('admin.social-posts.index')
            ->with($redirectMessage, $messageText);
    }

    public function edit(SocialPost $socialPost)
    {
        return view('admin.social_posts.edit', compact('socialPost'));
    }

    public function update(Request $request, SocialPost $socialPost)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|max:2048',
        ]);

        $socialPost->title = $validated['title'];
        $socialPost->content = $validated['content'];

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('social_posts', 'public');
            $socialPost->image_url = asset('storage/' . $imagePath);
        }

        $socialPost->save();

        return redirect()->route('admin.social-posts.index')
            ->with('success', 'Пост успешно обновлен');
    }

    /**
     * Публикация в Telegram
     */
    public function publishToTelegram(SocialPost $socialPost)
    {
        if ($this->publishToTelegramAction($socialPost)) {
            return redirect()->back()->with('success', 'Пост опубликован в Telegram');
        }
        
        return redirect()->back()->with('error', 'Ошибка публикации в Telegram. Проверьте логи для деталей.');
    }

    /**
     * Публикация во ВКонтакте
     */
    public function publishToVk(SocialPost $socialPost)
    {
        if ($this->publishToVkAction($socialPost)) {
            return redirect()->back()->with('success', 'Пост опубликован во ВКонтакте');
        }
        
        return redirect()->back()->with('error', 'Ошибка публикации во ВКонтакте. Проверьте логи для деталей.');
    }

    /**
     * Вспомогательный метод для публикации в Telegram
     */
    private function publishToTelegramAction(SocialPost $socialPost)
    {
        if ($socialPost->isPublishedToTelegram()) {
            Log::warning('Попытка повторной публикации в Telegram', ['post_id' => $socialPost->id]);
            return false;
        }
        
        // Проверяем настройки Telegram
        $telegramSettings = $this->telegramService->checkSettings();
        if (!$telegramSettings['success']) {
            Log::error('Ошибка конфигурации Telegram', $telegramSettings['errors']);
            return false;
        }
        
        // Формируем контент для публикации
        $content = "*{$socialPost->title}*\n\n{$socialPost->content}";
        
        if ($this->telegramService->sendMessage($content, $socialPost->image_url)) {
            $socialPost->update([
                'telegram_status' => true,
                'telegram_posted_at' => now(),
            ]);
            return true;
        }
        
        return false;
    }

    /**
     * Вспомогательный метод для публикации во ВКонтакте
     */
    private function publishToVkAction(SocialPost $socialPost)
    {
        if ($socialPost->isPublishedToVk()) {
            Log::warning('Попытка повторной публикации во ВКонтакте', ['post_id' => $socialPost->id]);
            return false;
        }
        
        // Проверяем настройки ВКонтакте
        $vkSettings = $this->vkService->checkSettings();
        if (!$vkSettings['success']) {
            Log::error('Ошибка конфигурации ВКонтакте', $vkSettings['errors']);
            return false;
        }
        
        return $this->vkService->publishPost($socialPost);
    }

    public function destroy(SocialPost $socialPost)
    {
        $socialPost->delete();
        return redirect()->route('admin.social-posts.index')
            ->with('success', 'Пост успешно удален');
    }
}
