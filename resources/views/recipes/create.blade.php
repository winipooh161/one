@extends('layouts.recipes')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-utensils me-2"></i>Поделитесь своим рецептом</h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Заполните форму, чтобы поделиться своим рецептом с другими пользователями. Обязательные поля отмечены звездочкой (*).
                    </div>

                    @if(isset($recipe) && $recipe->moderation_status === 'rejected')
                    <div class="alert alert-danger mb-4">
                        <h5><i class="fas fa-exclamation-triangle"></i> Рецепт не прошел модерацию</h5>
                        <p>Причина: <strong>{{ $recipe->moderation_message }}</strong></p>
                        <p>Пожалуйста, внесите необходимые исправления и отправьте рецепт на повторную модерацию.</p>
                    </div>
                    @endif

                    <form action="{{ route('recipes.store') }}" method="POST" enctype="multipart/form-data" id="recipe-form">
                        @csrf
                        
                        <!-- Основная информация -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Основная информация</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="title" class="form-label">Название рецепта *</label>
                                    <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" 
                                        value="{{ old('title') }}" required minlength="3" maxlength="255" 
                                        placeholder="Например: Домашний борщ с говядиной">
                                    @error('title')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Название должно быть кратким и отражать суть блюда</div>
                                </div>

                                <div class="mb-3">
                                    <label for="description" class="form-label">Краткое описание *</label>
                                    <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" 
                                        rows="3" required minlength="10" placeholder="Опишите ваше блюдо в нескольких предложениях...">{{ old('description') }}</textarea>
                                    @error('description')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                    <div class="form-text">Напишите краткое описание вашего блюда, чтобы заинтересовать читателей</div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-4 mb-3">
                                        <label for="cooking_time" class="form-label">Время приготовления (мин) *</label>
                                        <input type="number" class="form-control @error('cooking_time') is-invalid @enderror" id="cooking_time" name="cooking_time" 
                                            value="{{ old('cooking_time') }}" min="1" max="1440" required placeholder="60">
                                        @error('cooking_time')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="servings" class="form-label">Количество порций *</label>
                                        <input type="number" class="form-control @error('servings') is-invalid @enderror" id="servings" name="servings" 
                                            value="{{ old('servings', 4) }}" min="1" max="100" required placeholder="4">
                                        @error('servings')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                    <div class="col-md-4 mb-3">
                                        <label for="difficulty" class="form-label">Сложность *</label>
                                        <select class="form-select @error('difficulty') is-invalid @enderror" id="difficulty" name="difficulty" required>
                                            <option value="1" {{ old('difficulty') == 1 ? 'selected' : '' }}>Легко</option>
                                            <option value="2" {{ old('difficulty', 2) == 2 ? 'selected' : '' }}>Средне</option>
                                            <option value="3" {{ old('difficulty') == 3 ? 'selected' : '' }}>Сложно</option>
                                        </select>
                                        @error('difficulty')
                                            <div class="invalid-feedback">{{ $message }}</div>
                                        @enderror
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Пищевая ценность -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5>Пищевая ценность (на 100г или на порцию)</h5>
                            </div>
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="calories" class="form-label">Калории (ккал):</label>
                                            <input type="number" step="0.1" class="form-control @error('calories') is-invalid @enderror" 
                                                   id="calories" name="calories" value="{{ old('calories') }}" placeholder="Например: 350">
                                            @error('calories')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="proteins" class="form-label">Белки (г):</label>
                                            <input type="number" step="0.1" class="form-control @error('proteins') is-invalid @enderror" 
                                                   id="proteins" name="proteins" value="{{ old('proteins') }}" placeholder="Например: 10.5">
                                            @error('proteins')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="fats" class="form-label">Жиры (г):</label>
                                            <input type="number" step="0.1" class="form-control @error('fats') is-invalid @enderror" 
                                                   id="fats" name="fats" value="{{ old('fats') }}" placeholder="Например: 15.2">
                                            @error('fats')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                    <div class="col-md-3">
                                        <div class="form-group">
                                            <label for="carbs" class="form-label">Углеводы (г):</label>
                                            <input type="number" step="0.1" class="form-control @error('carbs') is-invalid @enderror" 
                                                   id="carbs" name="carbs" value="{{ old('carbs') }}" placeholder="Например: 40.7">
                                            @error('carbs')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                        </div>
                                    </div>
                                </div>
                                <div class="mt-2 text-muted small">
                                    <p>Укажите примерную пищевую ценность блюда. Эта информация поможет пользователям, которые следят за своим рационом.</p>
                                </div>
                            </div>
                        </div>

                        <!-- Ингредиенты -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-list me-2"></i>Ингредиенты</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="ingredients" class="form-label">Ингредиенты</label>
                                    <textarea class="form-control @error('ingredients') is-invalid @enderror" 
                                              id="ingredients" name="ingredients" rows="6" 
                                              placeholder="Введите ингредиенты, каждый с новой строки">{{ old('ingredients') }}</textarea>
                                    <small class="text-muted">Например: 2 яйца, 200 г муки, 1 стакан молока</small>
                                    @error('ingredients')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                        
                        <!-- Пошаговая инструкция -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Пошаговая инструкция</h5>
                            </div>
                            <div class="card-body">
                                <div id="steps-container">
                                    <div class="row step-row mb-3">
                                        <div class="col-md-11 mb-2 mb-md-0">
                                            <div class="input-group">
                                                <span class="input-group-text">Шаг 1</span>
                                                <textarea class="form-control" name="steps[0][description]" rows="2" required minlength="5" placeholder="Опишите действие..."></textarea>
                                            </div>
                                        </div>
                                        <div class="col-md-1">
                                            <button type="button" class="btn btn-danger btn-sm remove-step" disabled><i class="fas fa-trash"></i></button>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-success btn-sm" id="add-step">
                                    <i class="fas fa-plus me-1"></i> Добавить шаг
                                </button>
                            </div>
                        </div>
                        
                        <div class="row">
                            <!-- Изображение -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-image me-2"></i>Изображение</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="image" class="form-label">Фото блюда</label>
                                            <input class="form-control @error('image') is-invalid @enderror" type="file" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                                            @error('image')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Рекомендуемый размер: 1200×800 пикселей, до 2 МБ</div>
                                            <div class="mt-2 text-center">
                                                <img id="image-preview" src="#" alt="Предпросмотр" class="img-fluid rounded" style="max-height: 200px; display: none;">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Категории -->
                            <div class="col-md-6">
                                <div class="card mb-4">
                                    <div class="card-header bg-light">
                                        <h5 class="mb-0"><i class="fas fa-tags me-2"></i>Категории</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-3">
                                            <label for="categories" class="form-label">Выберите категории *</label>
                                            <select class="form-select @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple required>
                                                @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('categories')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                            <div class="form-text">Выберите минимум одну категорию для вашего рецепта</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Дополнительная информация -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Дополнительно</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label for="source" class="form-label">Источник рецепта</label>
                                    <input type="text" class="form-control" id="source" name="source" value="{{ old('source') }}" 
                                        placeholder="Например: Бабушкин рецепт, Журнал 'Гастроном' и т.д.">
                                    <div class="form-text">Если вы используете чужой рецепт, укажите источник</div>
                                </div>

                                <div class="mb-3">
                                    <label for="notes" class="form-label">Заметки к рецепту</label>
                                    <textarea class="form-control" id="notes" name="notes" rows="3" 
                                        placeholder="Дополнительные советы или примечания...">{{ old('notes') }}</textarea>
                                    <div class="form-text">Здесь можно указать советы, варианты замены ингредиентов и другую полезную информацию</div>
                                </div>
                            </div>
                        </div>

                        @if(isset($recipe) && $recipe->moderation_status === 'rejected')
                            <input type="hidden" name="moderation_status" value="pending">
                            <input type="hidden" name="moderation_message" value="">
                        @endif
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                            <button type="submit" class="btn btn-primary btn-lg px-5">
                                <i class="fas fa-save me-2"></i> Опубликовать рецепт
                            </button>
                            <a href="{{ route('recipes.index') }}" class="btn btn-outline-secondary btn-lg px-5">
                                <i class="fas fa-times me-2"></i> Отмена
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация для категорий 
        const categoriesSelect = document.getElementById('categories');
        if (categoriesSelect) {
            // Здесь можно добавить инициализацию Select2 при необходимости
        }

        // Управление ингредиентами
        let ingredientIndex = 0;
        const addIngredientBtn = document.getElementById('add-ingredient');
        
        if (addIngredientBtn) {
            addIngredientBtn.addEventListener('click', function() {
                ingredientIndex++;
                const newRow = document.createElement('div');
                newRow.className = 'row ingredient-row mb-3 align-items-center';
                newRow.innerHTML = `
                    <div class="col-md-5 mb-2 mb-md-0">
                        <input type="text" class="form-control" name="ingredients[${ingredientIndex}][name]" placeholder="Название ингредиента" required>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <input type="text" class="form-control" name="ingredients[${ingredientIndex}][quantity]" placeholder="Количество" required>
                    </div>
                    <div class="col-md-3 mb-2 mb-md-0">
                        <select class="form-select" name="ingredients[${ingredientIndex}][unit]">
                            <option value="">Единица измерения</option>
                            <option value="г">граммы (г)</option>
                            <option value="кг">килограммы (кг)</option>
                            <option value="мл">миллилитры (мл)</option>
                            <option value="л">литры (л)</option>
                            <option value="шт">штуки (шт)</option>
                            <option value="ст. л.">столовые ложки (ст. л.)</option>
                            <option value="ч. л.">чайные ложки (ч. л.)</option>
                            <option value="по вкусу">по вкусу</option>
                        </select>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-ingredient"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                document.getElementById('ingredients-container').appendChild(newRow);
                enableRemoveButtons();
            });
        }

        // Управление шагами
        let stepIndex = 0;
        const addStepBtn = document.getElementById('add-step');
        
        if (addStepBtn) {
            addStepBtn.addEventListener('click', function() {
                stepIndex++;
                const newRow = document.createElement('div');
                newRow.className = 'row step-row mb-3';
                newRow.innerHTML = `
                    <div class="col-md-11 mb-2 mb-md-0">
                        <div class="input-group">
                            <span class="input-group-text">Шаг ${stepIndex + 1}</span>
                            <textarea class="form-control" name="steps[${stepIndex}][description]" rows="2" required minlength="5" placeholder="Опишите действие..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger btn-sm remove-step"><i class="fas fa-trash"></i></button>
                    </div>
                `;
                document.getElementById('steps-container').appendChild(newRow);
                enableRemoveButtons();
            });
        }

        // Удаление элементов
        document.addEventListener('click', function(e) {
            if (e.target.closest('.remove-ingredient')) {
                e.target.closest('.ingredient-row').remove();
                enableRemoveButtons();
            }
            
            if (e.target.closest('.remove-step')) {
                e.target.closest('.step-row').remove();
                // Обновляем нумерацию шагов
                const steps = document.querySelectorAll('.step-row');
                steps.forEach((step, index) => {
                    step.querySelector('.input-group-text').textContent = `Шаг ${index + 1}`;
                });
                enableRemoveButtons();
            }
        });

        // Включение/отключение кнопок удаления
        function enableRemoveButtons() {
            const ingredientRows = document.querySelectorAll('.ingredient-row');
            const removeIngredientBtns = document.querySelectorAll('.remove-ingredient');
            
            removeIngredientBtns.forEach(btn => {
                btn.disabled = ingredientRows.length <= 1;
            });
            
            const stepRows = document.querySelectorAll('.step-row');
            const removeStepBtns = document.querySelectorAll('.remove-step');
            
            removeStepBtns.forEach(btn => {
                btn.disabled = stepRows.length <= 1;
            });
        }

        // Проверка формы перед отправкой
        const recipeForm = document.getElementById('recipe-form');
        if (recipeForm) {
            recipeForm.addEventListener('submit', function(e) {
                let isValid = true;
                
                // Проверка наличия ингредиентов
                if (document.querySelectorAll('.ingredient-row').length < 1) {
                    alert('Добавьте хотя бы один ингредиент');
                    isValid = false;
                }
                
                // Проверка наличия шагов
                if (document.querySelectorAll('.step-row').length < 1) {
                    alert('Добавьте хотя бы один шаг приготовления');
                    isValid = false;
                }
                
                // Проверка категорий
                const categories = document.getElementById('categories');
                if (categories && (!categories.value || categories.selectedOptions.length === 0)) {
                    alert('Выберите хотя бы одну категорию');
                    isValid = false;
                }
                
                // Предупреждение о отсутствии изображения
                const imageInput = document.getElementById('image');
                const imagePreview = document.getElementById('image-preview');
                if (imageInput && imageInput.value === '' && (!imagePreview || imagePreview.style.display === 'none')) {
                    if (!confirm('Вы не загрузили изображение для рецепта. Продолжить без изображения?')) {
                        isValid = false;
                    }
                }
                
                if (!isValid) {
                    e.preventDefault();
                }
            });
        }
    });

    // Предпросмотр изображения
    function previewImage(input) {
        const preview = document.getElementById('image-preview');
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                preview.src = e.target.result;
                preview.style.display = 'block';
            };
            reader.readAsDataURL(input.files[0]);
        } else {
            preview.src = '';
            preview.style.display = 'none';
        }
    }
</script>
@endsection
