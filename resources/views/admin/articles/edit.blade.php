@extends('layouts.admin')

@section('title', 'Редактирование новости')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Редактирование новости</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="{{ route('admin.articles.index') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-arrow-left"></i> Назад к списку
                </a>
                @if($article->status === 'published')
                    <a href="{{ route('articles.show', $article->slug) }}" class="btn btn-sm btn-outline-primary" target="_blank">
                        <i class="fas fa-eye"></i> Просмотр на сайте
                    </a>
                @endif
            </div>
        </div>
    </div>

    @if ($errors->any())
    <div class="alert alert-danger">
        <ul class="mb-0">
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <form action="{{ route('admin.articles.update', $article) }}" method="POST" enctype="multipart/form-data" id="articleForm">
        @csrf
        @method('PUT')
        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Основная информация</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="title" class="form-label">Заголовок <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="{{ old('title', $article->title) }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="slug" class="form-label">SEO URL (slug)</label>
                            <div class="input-group">
                                <input type="text" class="form-control" id="slug" name="slug" value="{{ old('slug', $article->slug) }}">
                                <button class="btn btn-outline-secondary" type="button" id="generateSlugBtn">Сгенерировать</button>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="excerpt" class="form-label">Краткое описание</label>
                            <textarea class="form-control" id="excerpt" name="excerpt" rows="3">{{ old('excerpt', $article->excerpt) }}</textarea>
                            <small class="text-muted">Краткое описание статьи для списков и предпросмотров</small>
                        </div>

                        <div class="mb-3">
                            <label for="content" class="form-label">Содержание <span class="text-danger">*</span></label>
                            <div id="editor-container" style="height: 400px; border: 1px solid #ced4da; border-radius: 0.25rem;">{!! old('content', $article->content) !!}</div>
                            <input type="hidden" name="content" id="content-input">
                            <small class="text-muted mt-2 d-block">Используйте панель инструментов для форматирования текста</small>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">SEO настройки</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="seo_title" class="form-label">SEO заголовок</label>
                            <input type="text" class="form-control" id="seo_title" name="seo_title" value="{{ old('seo_title', $article->seo_title) }}">
                            <small class="text-muted">Оставьте пустым, чтобы использовать основной заголовок</small>
                        </div>

                        <div class="mb-3">
                            <label for="seo_description" class="form-label">Meta описание</label>
                            <textarea class="form-control" id="seo_description" name="seo_description" rows="2">{{ old('seo_description', $article->seo_description) }}</textarea>
                        </div>

                        <div class="mb-3">
                            <label for="seo_keywords" class="form-label">Meta ключевые слова</label>
                            <input type="text" class="form-control" id="seo_keywords" name="seo_keywords" value="{{ old('seo_keywords', $article->seo_keywords) }}">
                            <small class="text-muted">Разделяйте ключевые слова запятыми</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Публикация</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="type" class="form-label">Тип контента <span class="text-danger">*</span></label>
                            <select class="form-select" id="type" name="type" required>
                                <option value="news" {{ old('type', $article->type) == 'news' ? 'selected' : '' }}>Новость</option>
                                <option value="article" {{ old('type', $article->type) == 'article' ? 'selected' : '' }}>Статья</option>
                                <option value="guide" {{ old('type', $article->type) == 'guide' ? 'selected' : '' }}>Руководство</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="status" class="form-label">Статус <span class="text-danger">*</span></label>
                            <select class="form-select" id="status" name="status" required>
                                <option value="draft" {{ old('status', $article->status) == 'draft' ? 'selected' : '' }}>Черновик</option>
                                <option value="published" {{ old('status', $article->status) == 'published' ? 'selected' : '' }}>Опубликовано</option>
                            </select>
                        </div>

                        <div class="mb-3">
                            <label for="published_at" class="form-label">Дата публикации</label>
                            <input type="datetime-local" class="form-control" id="published_at" name="published_at" 
                                   value="{{ old('published_at', $article->published_at ? $article->published_at->format('Y-m-d\TH:i') : '') }}">
                        </div>

                        <div class="d-grid gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить изменения
                            </button>
                            <a href="{{ route('admin.articles.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-times"></i> Отмена
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Изображение</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            @if($article->image)
                                <div class="mb-3 text-center">
                                    <p class="mb-1">Текущее изображение:</p>
                                    <img src="{{ asset('storage/' . $article->image) }}" class="img-fluid img-thumbnail" style="max-height: 200px;">
                                    <div class="form-check mt-2">
                                        <input class="form-check-input" type="checkbox" id="removeCurrentImage" name="remove_image">
                                        <label class="form-check-label" for="removeCurrentImage">
                                            Удалить текущее изображение
                                        </label>
                                    </div>
                                </div>
                            @endif

                            <label for="image" class="form-label">Загрузить новое изображение</label>
                            <input class="form-control" type="file" id="image" name="image" accept="image/*" onchange="validateImageSize(this)">
                            <div class="form-text">
                                <span class="text-primary">Рекомендуемый размер: 1200x630px</span>
                                <br>
                                <span class="text-success">Максимальный размер файла: 10MB</span>
                                <br>
                                <span class="text-info">Большие изображения будут автоматически оптимизированы</span>
                            </div>
                        </div>
                        <div id="imagePreview" class="mt-3 text-center d-none">
                            <p>Предпросмотр нового изображения:</p>
                            <img src="" class="img-fluid img-thumbnail" id="previewImg">
                            <div class="mt-2">
                                <button type="button" class="btn btn-sm btn-danger" id="removeImage">Отменить</button>
                                <button type="button" class="btn btn-sm btn-success" id="compressImage" style="display: none;">Сжать изображение</button>
                            </div>
                            <div id="imageInfo" class="small text-muted mt-2"></div>
                        </div>
                    </div>
                </div>

                <div class="card mb-4">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Категории</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <div class="form-check form-check-inline mb-2">
                                <button type="button" class="btn btn-sm btn-outline-secondary select-all-categories">Выбрать все</button>
                                <button type="button" class="btn btn-sm btn-outline-secondary ms-1 deselect-all-categories">Снять все</button>
                            </div>
                            <div style="max-height: 200px; overflow-y: auto;">
                                @foreach($categories as $category)
                                    <div class="form-check">
                                        <input class="form-check-input category-checkbox" type="checkbox" name="categories[]" 
                                               value="{{ $category->id }}" id="category{{ $category->id }}"
                                               {{ in_array($category->id, old('categories', $selectedCategories)) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="category{{ $category->id }}">
                                            {{ $category->name }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection


<link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
<style>
    .ql-editor {
        min-height: 350px;
    }
    .ql-editor img {
        max-width: 100%;
        height: auto;
    }
    .ql-toolbar.ql-snow {
        border-top-left-radius: 0.25rem;
        border-top-right-radius: 0.25rem;
    }
    .ql-container.ql-snow {
        border-bottom-left-radius: 0.25rem;
        border-bottom-right-radius: 0.25rem;
    }
</style>



<script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация Quill-редактора
        var quill = new Quill('#editor-container', {
            theme: 'snow',
            modules: {
                toolbar: [
                    [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                    ['bold', 'italic', 'underline', 'strike'],
                    [{ 'color': [] }, { 'background': [] }],
                    [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                    [{ 'align': [] }],
                    ['link', 'image'],
                    ['blockquote', 'code-block'],
                    ['clean']
                ]
            }
        });

        // При отправке формы сохраняем содержимое редактора в скрытое поле
        document.getElementById('articleForm').addEventListener('submit', function() {
            var content = document.querySelector('#content-input');
            content.value = quill.root.innerHTML;
        });

        // Предпросмотр изображения
        document.getElementById('image').addEventListener('change', function(event) {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                const preview = document.getElementById('imagePreview');
                const previewImg = document.getElementById('previewImg');
                const imageInfo = document.getElementById('imageInfo');
                const compressButton = document.getElementById('compressImage');
                
                reader.onload = function(e) {
                    // Загрузка изображения для проверки размеров
                    const img = new Image();
                    img.onload = function() {
                        // Показываем информацию о размерах изображения
                        const fileSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                        imageInfo.innerHTML = `
                            <strong>Размер файла:</strong> ${fileSizeMB} MB<br>
                            <strong>Разрешение:</strong> ${img.naturalWidth}x${img.naturalHeight} пикселей<br>
                            <strong>Тип файла:</strong> ${file.type}
                        `;
                        
                        // Показываем предупреждение и кнопку сжатия для больших файлов
                        if (file.size > 2 * 1024 * 1024) {
                            imageInfo.innerHTML += `
                                <div class="alert alert-warning mt-2">
                                    <i class="fas fa-exclamation-triangle"></i> 
                                    Файл превышает рекомендуемый размер 2MB. 
                                    Вы можете сжать изображение перед загрузкой.
                                </div>
                            `;
                            compressButton.style.display = 'inline-block';
                        } else {
                            compressButton.style.display = 'none';
                        }
                    };
                    
                    img.src = e.target.result;
                    previewImg.src = e.target.result;
                    preview.classList.remove('d-none');
                };

                reader.readAsDataURL(file);
            }
        });

        // Удаление изображения
        document.getElementById('removeImage').addEventListener('click', function() {
            const fileInput = document.getElementById('image');
            const preview = document.getElementById('imagePreview');
            
            fileInput.value = '';
            preview.classList.add('d-none');
        });

        // Генерация slug из заголовка
        document.getElementById('generateSlugBtn').addEventListener('click', function() {
            const titleInput = document.getElementById('title');
            if (titleInput.value.trim() === '') {
                alert('Введите заголовок для генерации URL');
                return;
            }

            fetch('{{ route("admin.articles.generate-slug") }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': '{{ csrf_token() }}'
                },
                body: JSON.stringify({
                    title: titleInput.value,
                    article_id: {{ $article->id }}
                })
            })
            .then(response => response.json())
            .then(data => {
                document.getElementById('slug').value = data.slug;
            })
            .catch(error => {
                console.error('Ошибка:', error);
            });
        });

        // Выбрать/Снять все категории
        document.querySelector('.select-all-categories').addEventListener('click', function() {
            document.querySelectorAll('.category-checkbox').forEach(checkbox => {
                checkbox.checked = true;
            });
        });

        document.querySelector('.deselect-all-categories').addEventListener('click', function() {
            document.querySelectorAll('.category-checkbox').forEach(checkbox => {
                checkbox.checked = false;
            });
        });

        // Функция валидации размера изображения
        window.validateImageSize = function(input) {
            const file = input.files[0];
            if (file) {
                const fileSize = file.size / 1024 / 1024; // в МБ
                if (fileSize > 10) {
                    alert('Внимание! Размер файла превышает допустимый предел в 10MB. Текущий размер: ' + fileSize.toFixed(2) + 'MB. Пожалуйста, выберите файл меньшего размера или сожмите его перед загрузкой.');
                    input.value = ''; // Очищаем input
                    document.getElementById('imagePreview').classList.add('d-none');
                    return false;
                }
            }
            return true;
        };
        
        // Сжатие изображения
        document.getElementById('compressImage').addEventListener('click', function() {
            const fileInput = document.getElementById('image');
            const file = fileInput.files[0];
            if (!file) return;
            
            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    const canvas = document.createElement('canvas');
                    let width = img.width;
                    let height = img.height;
                    
                    // Адаптивное масштабирование в зависимости от размера
                    let maxDimension = 1920;
                    let quality = 0.8;
                    
                    // Для очень больших изображений уменьшаем сильнее
                    if (width > 3000 || height > 3000) {
                        maxDimension = 1600;
                        quality = 0.75;
                    } else if (width > 2000 || height > 2000) {
                        maxDimension = 1800;
                        quality = 0.78;
                    }
                    
                    if (width > maxDimension || height > maxDimension) {
                        if (width > height) {
                            height = Math.round((height * maxDimension) / width);
                            width = maxDimension;
                        } else {
                            width = Math.round((width * maxDimension) / height);
                            height = maxDimension;
                        }
                    }
                    
                    canvas.width = width;
                    canvas.height = height;
                    const ctx = canvas.getContext('2d');
                    ctx.drawImage(img, 0, 0, width, height);
                    
                    canvas.toBlob(function(blob) {
                        // Создаем новый File объект
                        const compressedFile = new File([blob], file.name, {
                            type: 'image/jpeg',
                            lastModified: new Date().getTime()
                        });
                        
                        // Обновляем input file
                        const container = fileInput.parentNode;
                        const newFileInput = document.createElement('input');
                        newFileInput.className = fileInput.className;
                        newFileInput.id = fileInput.id;
                        newFileInput.name = fileInput.name;
                        newFileInput.type = 'file';
                        newFileInput.accept = fileInput.accept;
                        newFileInput.onchange = fileInput.onchange;
                        container.replaceChild(newFileInput, fileInput);
                        
                        // Создаем DataTransfer для имитации выбора файла
                        const dataTransfer = new DataTransfer();
                        dataTransfer.items.add(compressedFile);
                        newFileInput.files = dataTransfer.files;
                        
                        // Обновляем предпросмотр
                        const previewImg = document.getElementById('previewImg');
                        const imageInfo = document.getElementById('imageInfo');
                        const compressButton = document.getElementById('compressImage');
                        
                        previewImg.src = URL.createObjectURL(blob);
                        const origSizeMB = (file.size / (1024 * 1024)).toFixed(2);
                        const newSizeMB = (blob.size / (1024 * 1024)).toFixed(2);
                        const reduction = Math.round((1 - blob.size / file.size) * 100);
                        
                        imageInfo.innerHTML = `
                            <div class="alert alert-success">
                                <strong>Сжатие выполнено:</strong><br>
                                Исходный размер: ${origSizeMB} MB<br>
                                Новый размер: ${newSizeMB} MB<br>
                                Уменьшение: ${reduction}%
                            </div>
                            <strong>Разрешение:</strong> ${width}x${height} пикселей<br>
                        `;
                        
                        // Скрываем кнопку сжатия
                        compressButton.style.display = 'none';
                        
                        // Инициируем событие change, чтобы сработали другие обработчики
                        const event = new Event('change', { bubbles: true });
                        newFileInput.dispatchEvent(event);
                    }, 'image/jpeg', quality);
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        });
    });
</script>

