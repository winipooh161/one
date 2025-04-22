@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between align-items-center mb-4">
        <div class="col-md-6">
            <h1 class="animate-on-scroll"><i class="fas fa-file-import text-primary me-2"></i> Предпросмотр рецепта</h1>
            <p class="animate-on-scroll">Проверьте данные перед импортом рецепта.</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.parser.index') }}" class="btn btn-secondary animate-on-scroll">
                <i class="fas fa-arrow-left me-1"></i> Назад к парсеру
            </a>
        </div>
    </div>
    
    <div class="card animate-on-scroll">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-eye me-2"></i> Предпросмотр данных рецепта
        </div>
        <div class="card-body">
            <form action="{{ route('admin.parser.store') }}" method="POST">
                @csrf
                <input type="hidden" name="source_url" value="{{ $parseResult['source_url'] }}">
                
                <!-- Изображения -->
                @if(!empty($parseResult['image_urls']))
                    <div class="mb-4">
                        <label class="form-label fw-bold">Изображения рецепта</label>
                        <div class="row mb-2">
                            @foreach($parseResult['image_urls'] as $index => $imageUrl)
                                <div class="col-md-3 mb-3">
                                    <div class="card">
                                        <img src="{{ $imageUrl }}" class="card-img-top" alt="Фото рецепта" style="height: 150px; object-fit: cover;">
                                        <div class="card-body p-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" name="image_urls[]" value="{{ $imageUrl }}" id="img{{ $index }}" {{ $index == 0 ? 'checked' : '' }}>
                                                <label class="form-check-label" for="img{{ $index }}">
                                                    {{ $index == 0 ? 'Основное фото' : 'Дополнительное' }}
                                                </label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                        <div class="form-text">Выберите изображения для сохранения с рецептом. Первое выбранное будет основным.</div>
                    </div>
                @endif
                
                <div class="row">
                    <!-- Левая колонка -->
                    <div class="col-md-6">
                        <!-- Название рецепта -->
                        <div class="mb-3">
                            <label for="title" class="form-label fw-bold">Название рецепта</label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ $parseResult['title'] ?? '' }}" required>
                        </div>
                        
                        <!-- Описание -->
                        <div class="mb-3">
                            <label for="description" class="form-label fw-bold">Описание</label>
                            <textarea class="form-control" id="description" name="description" rows="3">{{ $parseResult['description'] ?? '' }}</textarea>
                        </div>
                        
                        <!-- Категории -->
                        <div class="mb-3">
                            <label for="categories" class="form-label fw-bold">Категории</label>
                            <select multiple class="form-select" id="categories" name="categories[]" size="6">
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" {{ in_array($category->id, $selectedCategories ?? []) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="form-text">Удерживайте Ctrl (Cmd на Mac) для выбора нескольких категорий</div>
                            
                            @if(!empty($newCategories))
                                <div class="alert alert-info mt-2">
                                    <i class="fas fa-info-circle"></i> Обнаружены и созданы новые категории: 
                                    @foreach($newCategories as $newCategory)
                                        <span class="badge bg-primary">{{ $newCategory->name }}</span>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        
                        <!-- Время приготовления -->
                        <div class="mb-3">
                            <label for="cooking_time" class="form-label fw-bold">Время приготовления (мин)</label>
                            <input type="number" class="form-control" id="cooking_time" name="cooking_time" value="{{ $parseResult['cooking_time'] ?? '' }}">
                        </div>
                        
                        <!-- Порции -->
                        <div class="mb-3">
                            <label for="servings" class="form-label fw-bold">Количество порций</label>
                            <input type="number" class="form-control" id="servings" name="servings" value="{{ $parseResult['servings'] ?? '' }}">
                        </div>
                    </div>
                    
                    <!-- Правая колонка -->
                    <div class="col-md-6">
                        <!-- Питательная ценность -->
                        <div class="card mb-3">
                            <div class="card-header bg-info text-white">
                                <i class="fas fa-calculator me-1"></i> Питательная ценность
                            </div>
                            <div class="card-body">
                                <div class="row g-2">
                                    <div class="col-md-6">
                                        <label for="calories" class="form-label">Калорийность (ккал)</label>
                                        <input type="number" class="form-control" id="calories" name="calories" value="{{ $parseResult['calories'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="proteins" class="form-label">Белки (г)</label>
                                        <input type="number" step="0.1" class="form-control" id="proteins" name="proteins" value="{{ $parseResult['proteins'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="fats" class="form-label">Жиры (г)</label>
                                        <input type="number" step="0.1" class="form-control" id="fats" name="fats" value="{{ $parseResult['fats'] ?? '' }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label for="carbs" class="form-label">Углеводы (г)</label>
                                        <input type="number" step="0.1" class="form-control" id="carbs" name="carbs" value="{{ $parseResult['carbs'] ?? '' }}">
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Публикация -->
                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="is_published" name="is_published" checked>
                            <label class="form-check-label" for="is_published">Опубликовать рецепт</label>
                        </div>
                        
                        <!-- Источник -->
                        <div class="mb-3">
                            <label class="form-label fw-bold">Источник</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-link"></i></span>
                                <input type="text" class="form-control" value="{{ $parseResult['source_url'] }}" readonly>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Ингредиенты -->
                <div class="mb-3">
                    <label for="ingredients" class="form-label fw-bold">Ингредиенты</label>
                    <textarea class="form-control" id="ingredients" name="ingredients" rows="8" required>{{ $parseResult['ingredients'] ?? '' }}</textarea>
                </div>
                
                <!-- Структурированные ингредиенты (если есть) -->
                @if(!empty($parseResult['structured_ingredients']))
                    <input type="hidden" name="structured_ingredients" value="{{ json_encode($parseResult['structured_ingredients']) }}">
                @endif
                
                <!-- Инструкции -->
                <div class="mb-3">
                    <label for="instructions" class="form-label fw-bold">Инструкция приготовления</label>
                    <textarea class="form-control" id="instructions" name="instructions" rows="12" required>{{ $parseResult['instructions'] ?? '' }}</textarea>
                </div>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                    <a href="{{ route('admin.parser.index') }}" class="btn btn-secondary me-md-2">
                        <i class="fas fa-times me-1"></i> Отмена
                    </a>
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-1"></i> Сохранить рецепт
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Подсчет символов в текстовых полях
        const textareas = document.querySelectorAll('textarea');
        textareas.forEach(textarea => {
            textarea.addEventListener('input', function() {
                const count = this.value.length;
                const countElement = this.nextElementSibling;
                if (countElement && countElement.classList.contains('char-count')) {
                    countElement.textContent = `${count} символов`;
                }
            });
            
            // Инициализация счетчика
            const count = textarea.value.length;
            let countElement = textarea.nextElementSibling;
            if (!countElement || !countElement.classList.contains('char-count')) {
                countElement = document.createElement('div');
                countElement.classList.add('form-text', 'char-count');
                textarea.parentNode.insertBefore(countElement, textarea.nextSibling);
            }
            countElement.textContent = `${count} символов`;
        });
    });
</script>
@endpush
@endsection
