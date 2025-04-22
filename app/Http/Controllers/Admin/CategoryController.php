<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CategoryController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }
    
    /**
     * Список категорий в админке
     */
    public function index()
    {
        $categories = Category::withCount('recipes')->latest()->paginate(10);
        return view('admin.categories.index', compact('categories'));
    }
    
    /**
     * Форма создания категории
     */
    public function create()
    {
        return view('admin.categories.create');
    }
    
    /**
     * Сохранение новой категории
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|unique:categories',
            'description' => 'nullable',
        ]);
        
        $validated['slug'] = Str::slug($request->name);
        
        Category::create($validated);
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно создана!');
    }
    
    /**
     * Форма редактирования категории
     */
    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }
    
    /**
     * Обновление категории
     */
    public function update(Request $request, Category $category)
    {
        $validated = $request->validate([
            'name' => 'required|max:255|unique:categories,name,' . $category->id,
            'description' => 'nullable',
        ]);
        
        $validated['slug'] = Str::slug($request->name);
        
        $category->update($validated);
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно обновлена!');
    }
    
    /**
     * Удаление категории
     */
    public function destroy(Category $category)
    {
        $category->delete();
        
        return redirect()->route('admin.categories.index')
            ->with('success', 'Категория успешно удалена!');
    }
}
