<?php

namespace App\Http\Controllers;

use App\Models\News;
use App\Models\NewsComment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NewsCommentController extends Controller
{
    /**
     * Сохранение нового комментария
     */
    public function store(Request $request)
    {
        $request->validate([
            'news_id' => 'required|exists:news,id',
            'content' => 'required|string|max:1000',
        ]);

        $comment = NewsComment::create([
            'news_id' => $request->news_id,
            'user_id' => Auth::id(),
            'content' => $request->content,
            'is_approved' => true, // По умолчанию комментарии одобрены
        ]);

        // Загружаем связанного пользователя
        $comment->load('user');

        // Если это AJAX запрос, возвращаем JSON
        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'comment' => $comment,
                'userName' => Auth::user()->name,
                'userAvatar' => Auth::user()->avatar ? asset(Auth::user()->avatar) : asset('images/default-avatar.png')
            ]);
        }

        return redirect()->back()->with('success', 'Комментарий добавлен!');
    }

    /**
     * Удаление комментария
     */
    public function destroy(NewsComment $comment)
    {
        // Проверяем, что текущий пользователь является автором комментария или админом
        if (Auth::id() === $comment->user_id || Auth::user()->isAdmin()) {
            $comment->delete();
            
            // Если это AJAX запрос, возвращаем JSON
            if (request()->ajax()) {
                return response()->json(['success' => true]);
            }
            
            return redirect()->back()->with('success', 'Комментарий удален!');
        }
        
        // Если это AJAX запрос, возвращаем JSON с ошибкой
        if (request()->ajax()) {
            return response()->json(['success' => false, 'message' => 'У вас нет прав для удаления этого комментария'], 403);
        }

        return redirect()->back()->with('error', 'У вас нет прав для удаления этого комментария');
    }
}
