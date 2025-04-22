<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    /**
     * Сохранение нового комментария
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        // Валидация данных
        $validated = $request->validate([
            'recipe_id' => 'required|exists:recipes,id',
            'comment' => 'required|string|min:2|max:1000',
        ]);
        
        // Создание комментария
        $comment = new Comment([
            'recipe_id' => $validated['recipe_id'],
            'user_id' => Auth::id(),
            'content' => $validated['comment'],
        ]);
        
        $comment->save();
        
        // Перенаправление обратно на страницу рецепта
        $recipe = Recipe::find($validated['recipe_id']);
        
        return redirect()
            ->route('recipes.show', $recipe->slug)
            ->with('success', 'Комментарий успешно добавлен!');
    }
}
