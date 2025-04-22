<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\User;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Schema;

class RecipeModerationController extends Controller
{
    /**
     * Конструктор с проверкой прав доступа
     */
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Отображает список рецептов, ожидающих модерации
     */
    public function index(Request $request)
    {
        $query = Recipe::where('is_published', false)
            ->whereNotNull('user_id')
            ->with(['categories', 'user']);
            
        // Фильтр по поиску
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm);
            });
        }
        
        // Фильтр по пользователю
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Фильтр по дате
        if ($request->filled('date_from')) {
            $query->whereDate('created_at', '>=', $request->date_from);
        }
        
        if ($request->filled('date_to')) {
            $query->whereDate('created_at', '<=', $request->date_to);
        }
        
        // Сортировка
        $sort = $request->input('sort', 'newest');
        switch ($sort) {
            case 'oldest':
                $query->oldest();
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'newest':
            default:
                $query->latest();
                break;
        }
        
        $recipes = $query->paginate(10)->withQueryString();
        $users = User::whereHas('recipes', function($q) {
            $q->where('is_published', false);
        })->get();
        
        // Статистика модерации
        $stats = [
            'pending' => Recipe::where('is_published', false)->whereNotNull('user_id')->count(),
            'approved_today' => Recipe::where('is_published', true)
                ->whereNotNull('user_id')
                ->whereDate('updated_at', now()->toDateString())
                ->count(),
            'rejected_today' => Recipe::withTrashed()
                ->whereNotNull('user_id')
                ->whereNotNull('deleted_at')
                ->whereDate('deleted_at', now()->toDateString())
                ->count()
        ];
        
        return view('admin.moderation.index', compact('recipes', 'users', 'stats'));
    }

    /**
     * Показать страницу модерации конкретного рецепта
     */
    public function show(Recipe $recipe)
    {
        return view('admin.moderation.show', compact('recipe'));
    }

    /**
     * Одобрить рецепт
     */
    public function approve(Recipe $recipe)
    {
        // Проверяем наличие полей модерации
        $updateData = [];
        
        if (Schema::hasColumn('recipes', 'moderation_status')) {
            $updateData['moderation_status'] = Recipe::MODERATION_STATUS_APPROVED;
        }
        
        if (Schema::hasColumn('recipes', 'moderation_message')) {
            $updateData['moderation_message'] = null;
        }
        
        // Если поля модерации отсутствуют, используем is_published
        if (empty($updateData)) {
            $updateData['is_published'] = true;
        }
        
        $recipe->update($updateData);
        
        // Создаем уведомление для пользователя
        Notification::create([
            'user_id' => $recipe->user_id,
            'title' => 'Рецепт одобрен',
            'content' => "Ваш рецепт \"{$recipe->title}\" успешно прошел модерацию и опубликован на сайте.",
            'type' => 'moderation_approved',
            'data' => [
                'recipe_id' => $recipe->id,
                'recipe_slug' => $recipe->slug
            ]
        ]);

        return redirect()->route('admin.moderation.index')
            ->with('success', "Рецепт \"{$recipe->title}\" успешно одобрен.");
    }

    /**
     * Отклонить рецепт
     */
    public function reject(Request $request, Recipe $recipe)
    {
        $validated = $request->validate([
            'reason' => 'required|string|min:10',
        ]);
        
        // Изменяем статус рецепта на "отклонен"
        $recipe->status = Recipe::STATUS_REJECTED;
        $recipe->rejection_reason = $validated['reason'];
        $recipe->save();
        
        // Отправка уведомления пользователю
        Notification::createModerationRejected($recipe->user, $recipe, $validated['reason']);

        return response()->json([
            'success' => true,
            'message' => 'Рецепт успешно отклонен'
        ]);
    }

    /**
     * Массовое одобрение рецептов
     */
    public function bulkApprove(Request $request)
    {
        $request->validate([
            'recipe_ids' => 'required|array',
            'recipe_ids.*' => 'exists:recipes,id'
        ]);
        
        // Проверяем наличие столбцов
        $hasApprovedBy = Schema::hasColumn('recipes', 'approved_by');
        $hasApprovedAt = Schema::hasColumn('recipes', 'approved_at');
        
        $count = 0;
        foreach ($request->recipe_ids as $id) {
            $recipe = Recipe::find($id);
            if ($recipe && !$recipe->is_published) {
                $recipe->is_published = true;
                
                if ($hasApprovedBy) {
                    $recipe->approved_by = auth()->id();
                }
                
                if ($hasApprovedAt) {
                    $recipe->approved_at = now();
                }
                
                $recipe->save();
                
                // Отправка уведомления пользователю
                if ($recipe->user) {
                    Notification::create([
                        'user_id' => $recipe->user_id,
                        'title' => 'Рецепт одобрен',
                        'content' => "Ваш рецепт \"{$recipe->title}\" был одобрен и опубликован на сайте.",
                        'link' => route('recipes.show', $recipe->slug),
                        'type' => 'recipe_approved'
                    ]);
                }
                
                $count++;
            }
        }
        
        return redirect()->route('admin.moderation.index')
            ->with('success', "Одобрено $count рецептов.");
    }

    /**
     * Массовое отклонение рецептов
     */
    public function bulkReject(Request $request)
    {
        $request->validate([
            'recipe_ids' => 'required|array',
            'recipe_ids.*' => 'exists:recipes,id',
            'bulk_reason' => 'required|string|min:10'
        ]);
        
        $count = 0;
        foreach ($request->recipe_ids as $id) {
            $recipe = Recipe::find($id);
            if ($recipe && !$recipe->is_published) {
                // Отправка уведомления пользователю
                if ($recipe->user) {
                    Notification::create([
                        'user_id' => $recipe->user_id,
                        'title' => 'Рецепт отклонен',
                        'content' => "Ваш рецепт \"{$recipe->title}\" был отклонен. Причина: {$request->bulk_reason}",
                        'type' => 'recipe_rejected'
                    ]);
                }
                
                // Удаление изображения
                if ($recipe->image_url) {
                    $path = str_replace('/storage/', '', $recipe->image_url);
                    Storage::disk('public')->delete($path);
                }
                
                $recipe->delete();
                $count++;
            }
        }
        
        return redirect()->route('admin.moderation.index')
            ->with('success', "Отклонено $count рецептов.");
    }
}
