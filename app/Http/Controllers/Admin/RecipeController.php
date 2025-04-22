<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Recipe;
use App\Models\Category;
use App\Models\User;
use App\Models\Ingredient; // Добавляем импорт модели Ingredient
use App\Models\Step; // Добавляем импорт модели Step
use App\Services\IngredientParser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class RecipeController extends Controller
{
    protected $ingredientParser;
    
    /**
     * Конструктор с проверкой аутентификации
     */
    public function __construct(IngredientParser $ingredientParser)
    {
        $this->ingredientParser = $ingredientParser;
        $this->middleware('auth');
    }

    /**
     * Отображение списка рецептов в админке
     */
    public function index(Request $request)
    {
        $query = Recipe::with(['categories', 'user']);
        
        // Для обычных пользователей показываем только их рецепты
        if (!auth()->user()->isAdmin()) {
            $query->where('user_id', auth()->id());
        }
        
        // Применяем фильтр по поиску
        if ($request->filled('search')) {
            $searchTerm = '%' . $request->search . '%';
            $query->where(function($q) use ($searchTerm) {
                $q->where('title', 'like', $searchTerm)
                  ->orWhere('ingredients', 'like', $searchTerm)
                  ->orWhere('description', 'like', $searchTerm)
                  ->orWhere('instructions', 'like', $searchTerm);
            });
        }
        
        // Применяем фильтр по категории
        if ($request->filled('category_id')) {
            $query->whereHas('categories', function($q) use ($request) {
                $q->where('categories.id', $request->category_id);
            });
        }
        
        // Применяем фильтр по статусу публикации
        if ($request->filled('status')) {
            $query->where('is_published', $request->status);
        }

        // Фильтр по автору (только для админов)
        if (auth()->user()->isAdmin() && $request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }
        
        // Применяем сортировку
        $sort = $request->input('sort', 'created_at_desc');
        switch ($sort) {
            case 'created_at_asc':
                $query->oldest();
                break;
            case 'title_asc':
                $query->orderBy('title', 'asc');
                break;
            case 'title_desc':
                $query->orderBy('title', 'desc');
                break;
            case 'views_desc':
                $query->orderBy('views', 'desc');
                break;
            default:
                $query->latest(); // сортировка по умолчанию - сначала новые
                break;
        }
        
        // Количество элементов на странице
        $perPage = $request->input('per_page', 10);
        
        $recipes = $query->paginate($perPage)->withQueryString();
        $categories = Category::orderBy('name')->get();
        
        // Для админов добавляем список пользователей для фильтра
        $users = null;
        if (auth()->user()->isAdmin()) {
            $users = User::orderBy('name')->get();
        }
        
        return view('admin.recipes.index', compact('recipes', 'categories', 'users'));
    }

    /**
     * Форма создания рецепта
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();
        return view('admin.recipes.create', compact('categories'));
    }

    /**
     * Сохранение нового рецепта
     */
    public function store(Request $request)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'cooking_time' => 'required|integer|min:1',
                'servings' => 'required|integer|min:1',
                'difficulty' => 'required|integer|min:1|max:5',
                'ingredients' => 'required|string', // Явно указываем string
                'instructions' => 'required|string',
                'categories' => 'array',
                'image' => 'nullable|image|max:2048',
                'source_url' => 'nullable|url|max:255',
                'notes' => 'nullable|string',
                'calories' => 'nullable|integer|min:0',
                'proteins' => 'nullable|numeric|min:0',
                'fats' => 'nullable|numeric|min:0',
                'carbs' => 'nullable|numeric|min:0',
                'is_published' => 'nullable|boolean',
                'structured_ingredients' => 'nullable|array', // Делаем это поле необязательным
                'structured_ingredients.*.name' => 'required_with:structured_ingredients|string',
                'structured_ingredients.*.quantity' => 'nullable|string',
                'structured_ingredients.*.unit' => 'nullable|string',
                'structured_ingredients.*.optional' => 'nullable|boolean',
            ]);
            
            // Генерируем уникальный слаг
            $validatedData['slug'] = Recipe::generateUniqueSlug($validatedData['title']);
            $validatedData['user_id'] = auth()->id();
            $validatedData['is_published'] = $request->has('is_published') ? 1 : 0;
            
            // Обработка загрузки изображения
            if ($request->hasFile('image')) {
                $image = $request->file('image');
                $filename = time() . '_' . $validatedData['slug'] . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('recipes', $filename, 'public');
                $validatedData['image_url'] = 'storage/' . $path; // Убираем ведущий слеш для единообразия
            }
            
            // Преобразуем структурированные ингредиенты в JSON
            if (isset($validatedData['structured_ingredients'])) {
                $validatedData['additional_data'] = json_encode(['structured_ingredients' => $validatedData['structured_ingredients']]);
                unset($validatedData['structured_ingredients']);
            }
            
            $recipe = Recipe::create($validatedData);
            
            // Прикрепляем категории
            if (isset($validatedData['categories'])) {
                $recipe->categories()->sync($validatedData['categories']);
            }
            
            return redirect()->route('admin.recipes.index')
                ->with('success', 'Рецепт успешно создан!');
        } catch (\Exception $e) {
            \Log::error('Ошибка при создании рецепта: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Произошла ошибка при создании рецепта: ' . $e->getMessage());
        }
    }

    /**
     * Форма редактирования рецепта
     */
    public function edit(Recipe $recipe)
    {
        $categories = Category::orderBy('name')->get();
        $selectedCategories = $recipe->categories->pluck('id')->toArray();
        
        // Загружаем ингредиенты и шаги
        $ingredients = $recipe->allIngredients()->orderBy('position')->get();
        $steps = $recipe->steps()->orderBy('order')->get();
        
        return view('admin.recipes.edit', compact('recipe', 'categories', 'selectedCategories', 'ingredients', 'steps'));
    }

    /**
     * Обновление рецепта
     */
    public function update(Request $request, Recipe $recipe)
    {
        try {
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'cooking_time' => 'required|integer|min:1',
                'servings' => 'required|integer|min:1',
                'difficulty' => 'required|integer|min:1|max:5',
                'ingredients' => 'required|string', // Явно указываем string
                'instructions' => 'required|string',
                'categories' => 'array',
                'image' => 'nullable|image|max:2048',
                'source_url' => 'nullable|url|max:255',
                'notes' => 'nullable|string',
                'calories' => 'nullable|integer|min:0',
                'proteins' => 'nullable|numeric|min:0',
                'fats' => 'nullable|numeric|min:0',
                'carbs' => 'nullable|numeric|min:0',
                'is_published' => 'nullable|boolean',
                'structured_ingredients' => 'nullable|array', // Делаем это поле необязательным
                'structured_ingredients.*.name' => 'required_with:structured_ingredients|string',
                'structured_ingredients.*.quantity' => 'nullable|string',
                'structured_ingredients.*.unit' => 'nullable|string',
                'structured_ingredients.*.optional' => 'nullable|boolean',
            ]);
            
            // Генерируем уникальный слаг при изменении названия
            if ($validatedData['title'] !== $recipe->title) {
                $validatedData['slug'] = Recipe::generateUniqueSlug($validatedData['title'], $recipe->id);
            }
            
            $validatedData['is_published'] = $request->has('is_published') ? 1 : 0;
            
            // Обработка загрузки изображения
            if ($request->hasFile('image')) {
                // Удаляем старое изображение, если есть
                if ($recipe->image_url && Storage::exists('public/' . str_replace('storage/', '', $recipe->image_url))) {
                    Storage::delete('public/' . str_replace('storage/', '', $recipe->image_url));
                }
                
                $image = $request->file('image');
                $filename = time() . '_' . ($validatedData['slug'] ?? $recipe->slug) . '.' . $image->getClientOriginalExtension();
                $path = $image->storeAs('recipes', $filename, 'public');
                $validatedData['image_url'] = 'storage/' . $path; // Убираем ведущий слеш для единообразия
            }
            
            // Преобразуем структурированные ингредиенты в JSON
            if (isset($validatedData['structured_ingredients'])) {
                $validatedData['additional_data'] = json_encode(['structured_ingredients' => $validatedData['structured_ingredients']]);
                unset($validatedData['structured_ingredients']);
            }
            
            $recipe->update($validatedData);
            
            // Обновляем категории
            if (isset($validatedData['categories'])) {
                $recipe->categories()->sync($validatedData['categories']);
            }
            
            return redirect()->route('admin.recipes.index')
                ->with('success', 'Рецепт успешно обновлен!');
        } catch (\Exception $e) {
            \Log::error('Ошибка при обновлении рецепта: ' . $e->getMessage());
            return redirect()->back()
                ->withInput()
                ->with('error', 'Произошла ошибка при обновлении рецепта: ' . $e->getMessage());
        }
    }

    /**
     * Удаление рецепта
     */
    public function destroy(Recipe $recipe)
    {
        // Проверяем, имеет ли пользователь право удалять этот рецепт
        if (!$recipe->isOwnedBy(auth()->user())) {
            return redirect()->route('admin.recipes.index')
                ->with('error', 'У вас нет доступа к удалению этого рецепта.');
        }
        
        // Удаляем изображение
        if ($recipe->image_url) {
            $path = str_replace('/storage/', '', $recipe->image_url);
            Storage::disk('public')->delete($path);
        }
        
        $recipe->delete();
        return redirect()->route('admin.recipes.index')
            ->with('success', 'Рецепт успешно удален!');
    }

    /**
     * Генерирует уникальный слаг для рецепта
     *
     * @param string $baseSlug Базовый слаг
     * @param int|null $exceptId ID рецепта, который нужно исключить из проверки
     * @return string Уникальный слаг
     */
    private function generateUniqueSlug($baseSlug, $exceptId = null)
    {
        $slug = $baseSlug;
        $counter = 1;

        // Проверяем существование слага, исключая указанный ID
        $query = Recipe::where('slug', $slug);
        if ($exceptId) {
            $query->where('id', '!=', $exceptId);
        }
        while ($query->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            // Предотвращаем бесконечный цикл
            if ($counter > 100) {
                $slug = $baseSlug . '-' . uniqid();
                break;
            }
            $query = Recipe::where('slug', $slug);
            if ($exceptId) {
                $query->where('id', '!=', $exceptId);
            }
        }
        
        return $slug;
    }

    /**
     * Одобрение рецепта
     */
    public function approve(Recipe $recipe)
    {
        $recipe->is_published = true;
        $recipe->save();

        return redirect()->route('admin.recipes.moderation')
            ->with('success', 'Рецепт успешно одобрен и опубликован.');
    }

    /**
     * Отклонение рецепта
     */
    public function reject(Recipe $recipe)
    {
        $recipe->delete();

        return redirect()->route('admin.recipes.moderation')
            ->with('success', 'Рецепт успешно отклонен и удален.');
    }

    /**
     * Генерирует slug из заголовка рецепта
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function generateSlug(Request $request)
    {
        $title = $request->input('title');
        $slug = Str::slug($title);

        // Проверяем уникальность slug
        $count = 1;
        $originalSlug = $slug;
        while (Recipe::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return response()->json(['slug' => $slug]);
    }

    protected function validateRecipe(Request $request, $id = null)
    {
        return $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'ingredients' => 'required|string',
            'instructions' => 'required|string',
            'image' => $id ? 'nullable|image|max:5120' : 'required|image|max:5120',
            'cooking_time' => 'nullable|integer|min:1',
            'servings' => 'nullable|integer|min:1',
            'categories' => 'required|array',
            'categories.*' => 'exists:categories,id',
            'difficulty' => 'nullable|integer|in:1,2,3',
            'source_url' => 'nullable|url|max:255',
            'calories' => 'nullable|numeric|min:0',
            'proteins' => 'nullable|numeric|min:0',
            'fats' => 'nullable|numeric|min:0',
            'carbs' => 'nullable|numeric|min:0',
        ]);
    }

    /**
     * Display a list of recipes pending moderation.
     *
     * @return \Illuminate\View\View
     */
    public function moderation()
    {
        // Используем только существующие колонки в таблице recipes
        $recipes = Recipe::where('is_published', false)
            ->whereNotNull('user_id')
            ->with('user', 'categories')
            ->latest()
            ->paginate(15);
            
        return view('admin.recipes.moderation', compact('recipes'));
    }
}