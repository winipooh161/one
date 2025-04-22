<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AjaxController extends Controller
{
    /**
     * Конструктор с проверкой прав доступа
     */
    public function __construct()
    {
        $this->middleware(['auth', 'admin']);
    }

    /**
     * Поиск рецептов с определением степени похожести
     */
    public function searchRecipes(Request $request)
    {
        $query = $request->input('query');
        $excludeId = $request->input('exclude');
        
        if (empty($query) || strlen($query) < 3) {
            return response()->json([]);
        }
        
        // Разбиваем запрос на слова для более точного сопоставления
        $searchTerms = preg_split('/\s+/', $query, -1, PREG_SPLIT_NO_EMPTY);
        
        // Базовый запрос
        $recipes = Recipe::where('is_published', true)
            ->when($excludeId, function($q) use ($excludeId) {
                return $q->where('id', '!=', $excludeId);
            })
            ->where(function($q) use ($searchTerms) {
                foreach ($searchTerms as $term) {
                    if (strlen($term) >= 3) {
                        $q->where(function($query) use ($term) {
                            $searchTerm = '%' . $term . '%';
                            $query->where('title', 'like', $searchTerm)
                                  ->orWhere('description', 'like', $searchTerm)
                                  ->orWhere('ingredients', 'like', $searchTerm);
                        });
                    }
                }
            })
            ->with('user')
            ->limit(5)
            ->get();
        
        // Форматируем ответ с вычислением степени схожести
        $formattedRecipes = $recipes->map(function($recipe) use ($query) {
            // Вычисляем примерную степень схожести (простой алгоритм)
            $titleSimilarity = similar_text(strtolower($query), strtolower($recipe->title), $titlePercent);
            $descSimilarity = similar_text(strtolower($query), strtolower(Str::limit($recipe->description, 100)), $descPercent);
            
            // Общая оценка схожести (простая формула, может быть улучшена)
            $similarity = round(($titlePercent * 0.7) + ($descPercent * 0.3));
            
            return [
                'id' => $recipe->id,
                'title' => $recipe->title,
                'description' => Str::limit($recipe->description, 100),
                'url' => route('recipes.show', $recipe->slug),
                'author' => $recipe->user ? $recipe->user->name : 'Система',
                'date' => $recipe->created_at->format('d.m.Y'),
                'similarity' => min(100, $similarity) // Ограничиваем максимальное значение до 100%
            ];
        });
        
        // Сортируем результаты по степени схожести (сначала наиболее похожие)
        $sortedRecipes = $formattedRecipes->sortByDesc('similarity')->values()->all();
        
        return response()->json($sortedRecipes);
    }
}
