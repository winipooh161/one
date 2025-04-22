@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Добавление нового рецепта</h3>
                </div>
                <div class="card-body">
                    <div class="row mb-4">
                        <div class="col-md-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle mr-2"></i> Заполните форму, чтобы добавить свой рецепт. Обязательные поля отмечены звездочкой (*).
                            </div>
                        </div>
                    </div>

                    <form action="{{ route('admin.recipes.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        
                        <div class="row">
                            <div class="col-md-8">
                                <!-- Основная информация -->
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5><i class="fas fa-info-circle mr-2"></i>Основная информация</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="title">Название рецепта *</label>
                                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" 
                                                   value="{{ old('title') }}" required minlength="3" maxlength="255" 
                                                   placeholder="Например: Домашний борщ с говядиной">
                                            @error('title')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="text-muted">Название должно быть кратким (от 3 до 255 символов) и отражать суть блюда.</small>
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="description">Краткое описание *</label>
                                            <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" 
                                                      rows="3" required minlength="10" placeholder="Опишите ваше блюдо в нескольких предложениях...">{{ old('description') }}</textarea>
                                            @error('description')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="text-muted">Напишите краткое описание вашего блюда (не менее 10 символов), чтобы заинтересовать читателей.</small>
                                        </div>
                                        
                                        <div class="row mt-3">
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="cooking_time">Время приготовления (мин) *</label>
                                                    <input type="number" class="form-control @error('cooking_time') is-invalid @enderror" id="cooking_time" name="cooking_time" value="{{ old('cooking_time') }}" min="1" required placeholder="Например: 60">
                                                    @error('cooking_time')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="servings">Количество порций *</label>
                                                    <input type="number" class="form-control @error('servings') is-invalid @enderror" id="servings" name="servings" value="{{ old('servings', 4) }}" min="1" required placeholder="Например: 4">
                                                    @error('servings')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-4">
                                                <div class="form-group">
                                                    <label for="difficulty">Сложность *</label>
                                                    <select class="form-control @error('difficulty') is-invalid @enderror" id="difficulty" name="difficulty" required>
                                                        <option value="">Выберите сложность</option>
                                                        <option value="1" {{ old('difficulty') == 1 ? 'selected' : '' }}>Легкий</option>
                                                        <option value="2" {{ old('difficulty') == 2 ? 'selected' : '' }}>Средний</option>
                                                        <option value="3" {{ old('difficulty') == 3 ? 'selected' : '' }}>Сложный</option>
                                                    </select>
                                                    @error('difficulty')
                                                    <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Пищевая ценность -->
                                <div class="card mt-4">
                                    <div class="card-header bg-light">
                                        <h3 class="card-title">Пищевая ценность (на 100г или на порцию)</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="calories">Калории (ккал)</label>
                                                    <input type="number" step="0.1" class="form-control @error('calories') is-invalid @enderror" 
                                                           id="calories" name="calories" value="{{ old('calories') }}">
                                                    @error('calories')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="proteins">Белки (г)</label>
                                                    <input type="number" step="0.1" class="form-control @error('proteins') is-invalid @enderror" 
                                                           id="proteins" name="proteins" value="{{ old('proteins') }}">
                                                    @error('proteins')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="fats">Жиры (г)</label>
                                                    <input type="number" step="0.1" class="form-control @error('fats') is-invalid @enderror" 
                                                           id="fats" name="fats" value="{{ old('fats') }}">
                                                    @error('fats')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="form-group">
                                                    <label for="carbs">Углеводы (г)</label>
                                                    <input type="number" step="0.1" class="form-control @error('carbs') is-invalid @enderror" 
                                                           id="carbs" name="carbs" value="{{ old('carbs') }}">
                                                    @error('carbs')
                                                        <span class="invalid-feedback">{{ $message }}</span>
                                                    @enderror
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Ингредиенты -->
                                <div class="card mt-4">
                                    <div class="card-header bg-light">
                                        <h5><i class="fas fa-list mr-2"></i>Ингредиенты</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="ingredients-container">
                                            <div class="row ingredient-row mb-2">
                                                <div class="col-md-5">
                                                    <input type="text" class="form-control" name="ingredients[0][name]" placeholder="Название ингредиента" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <input type="text" class="form-control" name="ingredients[0][quantity]" placeholder="Количество" required>
                                                </div>
                                                <div class="col-md-3">
                                                    <select class="form-control" name="ingredients[0][unit]">
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
                                                    <button type="button" class="btn btn-danger remove-ingredient" disabled><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-success btn-sm" id="add-ingredient">
                                                    <i class="fas fa-plus"></i> Добавить ингредиент
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Пошаговая инструкция -->
                                <div class="card mt-4">
                                    <div class="card-header bg-light">
                                        <h5><i class="fas fa-tasks mr-2"></i>Пошаговая инструкция</h5>
                                    </div>
                                    <div class="card-body">
                                        <div id="steps-container">
                                            <div class="row step-row mb-3">
                                                <div class="col-md-11">
                                                    <div class="input-group">
                                                        <div class="input-group-prepend">
                                                            <span class="input-group-text">Шаг 1</span>
                                                        </div>
                                                        <textarea class="form-control" name="steps[0][description]" rows="2" required placeholder="Опишите действие..."></textarea>
                                                    </div>
                                                </div>
                                                <div class="col-md-1">
                                                    <button type="button" class="btn btn-danger remove-step" disabled><i class="fas fa-trash"></i></button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row mt-2">
                                            <div class="col-md-12">
                                                <button type="button" class="btn btn-success btn-sm" id="add-step">
                                                    <i class="fas fa-plus"></i> Добавить шаг
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="col-md-4">
                                <!-- Изображение и дополнительные опции -->
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <h5><i class="fas fa-image mr-2"></i>Изображение</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="image">Основное фото блюда</label>
                                            <div class="custom-file">
                                                <input type="file" class="custom-file-input @error('image') is-invalid @enderror" id="image" name="image" accept="image/*" onchange="previewImage(this)">
                                                <label class="custom-file-label" for="image">Выберите файл</label>
                                                @error('image')
                                                <span class="invalid-feedback">{{ $message }}</span>
                                                @enderror
                                            </div>
                                            <small class="text-muted">Загрузите качественное фото вашего блюда. Оптимальный размер: 1200x800 пикселей.</small>
                                            <div class="mt-2 text-center">
                                                <img id="image-preview" src="#" alt="Предпросмотр" class="img-fluid rounded" style="max-height: 200px; display: none;">
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- Категории -->
                                <div class="card mt-4">
                                    <div class="card-header bg-light">
                                        <h5><i class="fas fa-tags mr-2"></i>Категории</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="categories">Выберите категории *</label>
                                            <select class="select2 form-control @error('categories') is-invalid @enderror" id="categories" name="categories[]" multiple required data-placeholder="Выберите категории">
                                                @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ in_array($category->id, old('categories', [])) ? 'selected' : '' }}>
                                                    {{ $category->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                            @error('categories')
                                            <span class="invalid-feedback">{{ $message }}</span>
                                            @enderror
                                            <small class="text-muted">Выберите минимум одну категорию, которая лучше всего описывает ваш рецепт.</small>
                                        </div>
                                    </div>
                                </div>

                                <!-- Дополнительная информация -->
                                <div class="card mt-4">
                                    <div class="card-header bg-light">
                                        <h5><i class="fas fa-info-circle mr-2"></i>Дополнительно</h5>
                                    </div>
                                    <div class="card-body">
                                        <div class="form-group">
                                            <label for="source">Источник рецепта</label>
                                            <input type="text" class="form-control" id="source" name="source" value="{{ old('source') }}" placeholder="Например: Бабушкин рецепт, Журнал 'Гастроном' и т.д.">
                                            <small class="text-muted">Если вы используете чужой рецепт, укажите источник.</small>
                                        </div>

                                        <div class="form-group mt-3">
                                            <label for="notes">Заметки к рецепту</label>
                                            <textarea class="form-control" id="notes" name="notes" rows="3" placeholder="Дополнительные советы или примечания...">{{ old('notes') }}</textarea>
                                            <small class="text-muted">Здесь можно указать советы, варианты замены ингредиентов и другую полезную информацию.</small>
                                        </div>

                                      
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row mt-4">
                            <div class="col-md-12 text-center">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="fas fa-save mr-2"></i> Сохранить рецепт
                                </button>
                                <a href="{{ route('admin.recipes.index') }}" class="btn btn-secondary btn-lg ml-2">
                                    <i class="fas fa-times mr-2"></i> Отмена
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Инициализация Select2
        $('.select2').select2({
            theme: 'bootstrap4',
            width: '100%'
        });

        // Управление ингредиентами
        let ingredientIndex = 0;

        $('#add-ingredient').on('click', function() {
            ingredientIndex++;
            let newRow = `
                <div class="row ingredient-row mb-2">
                    <div class="col-md-5">
                        <input type="text" class="form-control" name="ingredients[${ingredientIndex}][name]" placeholder="Название ингредиента" required>
                    </div>
                    <div class="col-md-3">
                        <input type="text" class="form-control" name="ingredients[${ingredientIndex}][quantity]" placeholder="Количество" required>
                    </div>
                    <div class="col-md-3">
                        <select class="form-control" name="ingredients[${ingredientIndex}][unit]">
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
                        <button type="button" class="btn btn-danger remove-ingredient"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
            $('#ingredients-container').append(newRow);
            enableRemoveButtons();
        });

        // Удаление ингредиента
        $(document).on('click', '.remove-ingredient', function() {
            $(this).closest('.ingredient-row').remove();
            enableRemoveButtons();
        });

        // Управление шагами
        let stepIndex = 0;

        $('#add-step').on('click', function() {
            stepIndex++;
            let newStep = `
                <div class="row step-row mb-3">
                    <div class="col-md-11">
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text">Шаг ${stepIndex + 1}</span>
                            </div>
                            <textarea class="form-control" name="steps[${stepIndex}][description]" rows="2" required placeholder="Опишите действие..."></textarea>
                        </div>
                    </div>
                    <div class="col-md-1">
                        <button type="button" class="btn btn-danger remove-step"><i class="fas fa-trash"></i></button>
                    </div>
                </div>
            `;
            $('#steps-container').append(newStep);
            enableRemoveButtons();
        });

        // Удаление шага
        $(document).on('click', '.remove-step', function() {
            $(this).closest('.step-row').remove();
            // Обновляем нумерацию шагов
            $('.step-row').each(function(index) {
                $(this).find('.input-group-text').text('Шаг ' + (index + 1));
            });
            enableRemoveButtons();
        });

        function enableRemoveButtons() {
            // Разрешаем удаление, только если есть больше одного элемента
            if ($('.ingredient-row').length > 1) {
                $('.remove-ingredient').prop('disabled', false);
            } else {
                $('.remove-ingredient').prop('disabled', true);
            }

            if ($('.step-row').length > 1) {
                $('.remove-step').prop('disabled', false);
            } else {
                $('.remove-step').prop('disabled', true);
            }
        }
    });

    // Предпросмотр изображения
    function previewImage(input) {
        if (input.files && input.files[0]) {
            var reader = new FileReader();
            
            reader.onload = function(e) {
                $('#image-preview').attr('src', e.target.result).show();
            }
            
            reader.readAsDataURL(input.files[0]);
        }
    }

    // Валидация формы перед отправкой
    $('form').on('submit', function(e) {
        let isValid = true;

        // Проверка наличия ингредиентов
        if ($('.ingredient-row').length < 1) {
            alert('Добавьте хотя бы один ингредиент');
            isValid = false;
        }

        // Преобразование ингредиентов в строку
        if (isValid) {
            const ingredients = [];
            $('.ingredient-row').each(function() {
                const name = $(this).find('input[name*="[name]"]').val();
                const quantity = $(this).find('input[name*="[quantity]"]').val();
                const unit = $(this).find('select[name*="[unit]"]').val();
                if (name && quantity) {
                    ingredients.push(`${name} - ${quantity} ${unit || ''}`.trim());
                }
            });
            $('<input>').attr({
                type: 'hidden',
                name: 'ingredients',
                value: ingredients.join('\n')
            }).appendTo('form');
        }

        // Преобразование шагов в инструкции
        if (isValid) {
            const instructions = [];
            $('.step-row').each(function(index) {
                const description = $(this).find('textarea').val();
                if (description) {
                    instructions.push(`${index + 1}. ${description}`);
                }
            });
            $('<input>').attr({
                type: 'hidden',
                name: 'instructions',
                value: instructions.join('\n')
            }).appendTo('form');
        }

        // Проверка наличия шагов
        if ($('.step-row').length < 1) {
            alert('Добавьте хотя бы один шаг приготовления');
            isValid = false;
        }

        // Проверка категорий
        if ($('#categories').val() === null || $('#categories').val().length === 0) {
            alert('Выберите хотя бы одну категорию');
            isValid = false;
        }

        // Проверка изображения
        if ($('#image').val() === '' && !$('#image-preview').is(':visible')) {
            if (!confirm('Вы не загрузили изображение для рецепта. Продолжить без изображения?')) {
                isValid = false;
            }
        }

        if (!isValid) {
            e.preventDefault();
            return false;
        }
    });
</script>
@endpush
