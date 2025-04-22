@extends('admin.layouts.app')

@section('styles')
<style>
    .tox-tinymce {
        border-radius: 0.25rem;
    }
    .ck-editor__editable_inline {
        min-height: 400px;
    }
    .form-check-input {
        width: 1.2em;
        height: 1.2em;
    }
    .form-label.required::after {
        content: " *";
        color: red;
    }
    #image-preview {
        max-height: 200px;
        max-width: 100%;
        margin-top: 10px;
    }
    .card-video {
        border-top: 3px solid #007bff;
        margin-top: 20px;
    }
    
    .video-preview {
        padding: 10px;
        background: #f8f9fa;
        border-radius: 5px;
        margin-top: 10px;
    }
    
    .video-tags-input {
        min-height: 38px;
    }
    .video-url-input {
        position: relative;
    }
    
    .video-url-input .spinner-border {
        position: absolute;
        right: 10px;
        top: 50%;
        transform: translateY(-50%);
        width: 20px;
        height: 20px;
        display: none;
    }
    
    .video-url-input.loading .spinner-border {
        display: block;
    }
    
    .video-platform-icon {
        font-size: 1.2rem;
        margin-right: 0.5rem;
    }
</style>
@endsection

@section('content')
<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1>Редактирование новости</h1>
        <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Назад к списку
        </a>
    </div>
    
    <div class="card">
        <div class="card-header bg-white">
            <h5 class="mb-0">Редактирование: {{ Str::limit($news->title, 70) }}</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.news.update', $news) }}" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                
                <div class="mb-3">
                    <label for="title" class="form-label required">Заголовок</label>
                    <input type="text" class="form-control @error('title') is-invalid @enderror" 
                           id="title" name="title" value="{{ old('title', $news->title) }}"maxlength="65"  required>
                    @error('title')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="short_description" class="form-label required">Краткое описание</label>
                    <textarea class="form-control @error('short_description') is-invalid @enderror" 
                              id="short_description" name="short_description" rows="3" required>{{ old('short_description', $news->short_description) }}</textarea>
                    @error('short_description')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="content" class="form-label required">Содержание новости</label>
                    <div id="editor-container">
                        <textarea class="form-control @error('content') is-invalid @enderror" 
                              id="content" name="content">{{ old('content', $news->content) }}</textarea>
                    </div>
                    @error('content')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                </div>
                
                <div class="mb-3">
                    <label for="image" class="form-label">Изображение</label>
                    <input type="file" class="form-control @error('image') is-invalid @enderror" 
                           id="image" name="image" accept="image/*" onchange="previewImage(event)">
                    <div class="form-text">
                        Рекомендуемый размер: 1200x630px, максимальный размер: 2МБ.
                        Изображения сохраняются в директории <code>public/storage/news</code>
                    </div>
                    @error('image')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    
                    <div class="mt-2">
                        @if($news->image_url)
                            <div class="mb-2" id="current-image-block">
                                <label class="form-label">Текущее изображение:</label>
                                @if(Storage::disk('public')->exists('' . $news->image_url))
                                    <img src="{{ asset('storage/' . $news->image_url) }}" alt="{{ $news->title }}" 
                                         class="img-thumbnail" style="max-height: 200px;">
                                @else
                                    <div class="alert alert-warning">
                                        Изображение не найдено по пути: storage/{{ $news->image_url }}
                                    </div>
                                @endif
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="delete_image" name="delete_image" value="1">
                                    <label class="form-check-label" for="delete_image">
                                        Удалить текущее изображение
                                    </label>
                                </div>
                            </div>
                        @else
                            <p class="text-muted">Изображение не загружено</p>
                        @endif
                        <img id="image-preview" src="#" alt="Preview" style="display: none;">
                    </div>
                </div>
                
                <!-- Блок для встраивания видео -->
                <div class="card card-video">
                    <div class="card-header bg-light d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Встроить видео ВКонтакте</h5>
                        <button type="button" class="btn btn-link p-0" id="toggle-video-details">
                            <span class="collapse-text">{{ $news->video_iframe ? 'Скрыть детали' : 'Показать детали' }}</span>
                            <i class="fas fa-chevron-{{ $news->video_iframe ? 'up' : 'down' }} ms-1"></i>
                        </button>
                    </div>
                    <div class="card-body">
                        <!-- Добавляем новое поле для URL видео -->
                        <div class="mb-3">
                            <label for="video_url" class="form-label">Ссылка на видео</label>
                            <div class="input-group video-url-input" id="video-url-container">
                                <input type="url" class="form-control @error('video_url') is-invalid @enderror" 
                                       id="video_url" name="video_url" value="{{ old('video_url') }}" 
                                       placeholder="https://vk.com/video... или https://vkvideo.ru/...">
                                <button class="btn btn-outline-primary" type="button" id="extract-video-data">
                                    Извлечь данные
                                </button>
                                <div class="spinner-border text-primary" role="status">
                                    <span class="visually-hidden">Загрузка...</span>
                                </div>
                                @error('video_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div id="video-url-feedback" class="form-text text-danger"></div>
                        </div>
                        
                        <div id="video-data-container" style="display: {{ $news->video_iframe ? 'block' : 'none' }};">
                            <div class="mb-3">
                                <label for="video_iframe" class="form-label">Код встраивания видео (iframe)</label>
                                <textarea class="form-control @error('video_iframe') is-invalid @enderror" 
                                        id="video_iframe" name="video_iframe" rows="3" placeholder="Вставьте код iframe с ВКонтакте или Rutube">{{ old('video_iframe', $news->video_iframe) }}</textarea>
                                <div class="form-text">
                                    Код встраивания будет автоматически сгенерирован из ссылки на видео.
                                    <button type="button" class="btn btn-sm btn-link p-0 ms-2" data-bs-toggle="modal" data-bs-target="#embedHelpModal">
                                        Как получить код встраивания вручную?
                                    </button>
                                </div>
                                @error('video_iframe')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                
                                <div id="video-preview" class="video-preview mt-3" style="{{ $news->video_iframe ? '' : 'display:none;' }}">
                                    <div id="iframe-container">
                                        @if($news->video_iframe)
                                            {!! $news->video_iframe !!}
                                        @endif
                                    </div>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="video_author_name" class="form-label">Имя автора видео</label>
                                    <input type="text" class="form-control @error('video_author_name') is-invalid @enderror" 
                                        id="video_author_name" name="video_author_name" value="{{ old('video_author_name', $news->video_author_name) }}">
                                    @error('video_author_name')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="video_author_link" class="form-label">Ссылка на автора</label>
                                    <input type="url" class="form-control @error('video_author_link') is-invalid @enderror" 
                                        id="video_author_link" name="video_author_link" value="{{ old('video_author_link', $news->video_author_link) }}" placeholder="https://">
                                    @error('video_author_link')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="video_title" class="form-label">Название видео</label>
                                <input type="text" class="form-control @error('video_title') is-invalid @enderror" 
                                    id="video_title" name="video_title" value="{{ old('video_title', $news->video_title) }}">
                                <div class="form-text">Если не указано, будет использован заголовок новости</div>
                                @error('video_title')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="video_description" class="form-label">Описание видео</label>
                                <textarea class="form-control @error('video_description') is-invalid @enderror" 
                                        id="video_description" name="video_description" rows="3">{{ old('video_description', $news->video_description) }}</textarea>
                                <div class="form-text">Если не указано, будет использовано краткое описание новости</div>
                                @error('video_description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="video_tags" class="form-label">Теги видео</label>
                                <input type="text" class="form-control video-tags-input @error('video_tags') is-invalid @enderror" 
                                    id="video_tags" name="video_tags" value="{{ old('video_tags', $news->video_tags) }}" placeholder="Введите теги через запятую">
                                @error('video_tags')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="mb-4 mt-4">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="is_published" 
                               name="is_published" {{ old('is_published', $news->is_published) ? 'checked' : '' }}>
                        <label class="form-check-label" for="is_published">Опубликовать</label>
                    </div>
                </div>
                
                <div class="d-flex justify-content-between">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> Сохранить изменения
                    </button>
                    <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">Отмена</a>
                </div>
            </form>
        </div>
        <div class="card-footer bg-white">
            <div class="d-flex justify-content-between align-items-center">
                <span class="text-muted">ID: {{ $news->id }} | Создано: {{ $news->created_at->format('d.m.Y H:i') }}</span>
                <a href="{{ route('news.show', $news->slug) }}" class="btn btn-sm btn-info" target="_blank">
                    <i class="fas fa-eye"></i> Просмотр на сайте
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно с инструкцией по получению кода встраивания -->
<div class="modal fade" id="embedHelpModal" tabindex="-1" aria-labelledby="embedHelpModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="embedHelpModalLabel">Как получить код встраивания видео</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-4">
                    <h5>ВКонтакте:</h5>
                    <ol>
                        <li>Откройте видео на сайте ВКонтакте</li>
                        <li>Нажмите на кнопку "Поделиться" под видео</li>
                        <li>Выберите вкладку "Экспорт"</li>
                        <li>Скопируйте весь код из поля "Код для вставки"</li>
                        <li>Вставьте скопированный код в поле "Код встраивания видео"</li>
                    </ol>
                    <div class="alert alert-info">
                        Пример кода: <code>&lt;iframe src="https://vk.com/video_ext.php?oid=-123456&id=456789&hash=abcdef123456" width="640" height="360" frameborder="0" allowfullscreen&gt;&lt;/iframe&gt;</code>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Закрыть</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script src="https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/ckeditor.js"></script>
<script src="https://cdn.ckeditor.com/ckeditor5/40.2.0/classic/translations/ru.js"></script>
<script>
    // Класс адаптера загрузки для CKEditor
    class UploadAdapter {
        constructor(loader) {
            this.loader = loader;
        }

        upload() {
            return this.loader.file
                .then(file => new Promise((resolve, reject) => {
                    this._uploadFile(file).then(response => {
                        // Проверяем структуру ответа и извлекаем правильный URL
                        let url = '';
                        if (response && response.location) {
                            url = response.location;
                        } else if (response && response.url) {
                            url = response.url;
                        } else if (typeof response === 'string') {
                            url = response;
                        }
                        
                        console.log('Изображение успешно загружено:', url);
                        
                        resolve({
                            default: url
                        });
                    }).catch(error => {
                        console.error('Ошибка загрузки изображения:', error);
                        reject(error);
                    });
                }));
        }

        abort() {
            // Реализация метода отмены загрузки (опционально)
        }

        _uploadFile(file) {
            const data = new FormData();
            data.append('file', file);
            data.append('_token', document.querySelector('meta[name="csrf-token"]').getAttribute('content'));
            
            console.log('Начата загрузка файла:', file.name);

            return fetch('{{ route('admin.tinymce.upload') }}', {
                method: 'POST',
                body: data
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка загрузки: ' + response.statusText);
                }
                return response.json();
            })
            .then(data => {
                console.log('Получен ответ от сервера:', data);
                return data;
            });
        }
    }

    // Плагин для подключения адаптера загрузки
    function uploadAdapterPlugin(editor) {
        editor.plugins.get('FileRepository').createUploadAdapter = (loader) => {
            return new UploadAdapter(loader);
        };
    }

    // Инициализация CKEditor с исправленной конфигурацией
    document.addEventListener('DOMContentLoaded', function() {
        if (!document.querySelector('#content')) {
            console.error('Элемент #content не найден на странице');
            return;
        }
        
        ClassicEditor
            .create(document.querySelector('#content'), {
                language: 'ru',
                extraPlugins: [uploadAdapterPlugin],
                toolbar: [
                    'heading', 
                    '|', 
                    'bold', 
                    'italic', 
                    'link', 
                    'bulletedList', 
                    'numberedList',
                    '|',
                    'outdent', 
                    'indent',
                    '|',
                    'imageUpload',
                    'blockQuote',
                    'insertTable',
                    '|',
                    'undo',
                    'redo'
                ],
                heading: {
                    options: [
                        { model: 'paragraph', title: 'Параграф', class: 'ck-heading_paragraph' },
                        { model: 'heading1', view: 'h1', title: 'Заголовок 1', class: 'ck-heading_heading1' },
                        { model: 'heading2', view: 'h2', title: 'Заголовок 2', class: 'ck-heading_heading2' },
                        { model: 'heading3', view: 'h3', title: 'Заголовок 3', class: 'ck-heading_heading3' }
                    ]
                },
                image: {
                    toolbar: ['imageTextAlternative']
                },
                table: {
                    contentToolbar: ['tableColumn', 'tableRow', 'mergeTableCells']
                }
            })
            .then(editor => {
                window.editor = editor;
                console.log('CKEditor инициализирован успешно', editor);
                
                // Автосохранение каждые 2 минуты
                setInterval(() => {
                    localStorage.setItem('news_edit_draft_{{ $news->id }}', editor.getData());
                    console.log('Автосохранение черновика выполнено в:', new Date().toLocaleTimeString());
                }, 120000);
                
                // Восстановление автосохраненного черновика если он есть
                const savedDraft = localStorage.getItem('news_edit_draft_{{ $news->id }}');
                if (savedDraft && editor.getData() !== savedDraft) {
                    const confirmRestore = confirm('Найдена автосохраненная версия статьи. Восстановить?');
                    if (confirmRestore) {
                        editor.setData(savedDraft);
                    } else {
                        localStorage.removeItem('news_edit_draft_{{ $news->id }}');
                    }
                }
            })
            .catch(error => {
                console.error('Произошла ошибка при инициализации CKEditor:', error);
                
                // Запасной вариант - обычное текстовое поле
                document.querySelector('#content').style.display = 'block';
                document.querySelector('#content').style.height = '500px';
            });
    });
    
    // Функция для предварительного просмотра изображения
    function previewImage(event) {
        const input = event.target;
        if (input.files && input.files[0]) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const imagePreview = document.getElementById('image-preview');
                imagePreview.src = e.target.result;
                imagePreview.style.display = 'block';
                
                // Если загружается новое изображение, скрыть блок с текущим изображением
                const currentImageBlock = document.getElementById('current-image-block');
                if (currentImageBlock) {
                    currentImageBlock.style.display = 'none';
                }
            };
            reader.readAsDataURL(input.files[0]);
        }
    }
    
    // Добавляем отладочную информацию при загрузке страницы
    document.addEventListener('DOMContentLoaded', function() {
        console.log('Путь к изображению в БД:', '{{ $news->image_url }}');
        
        // Показываем в консоли полные пути, которые используются для проверки и отображения
        @if($news->image_url)
            console.log('Проверка существования по пути:', 'storage/{{ $news->image_url }}');
            console.log('Отображение по пути:', '{{ asset("storage/" . $news->image_url) }}');
            
            // Альтернативный путь - проверка без префикса 
            console.log('Альтернативный путь (только image_url):', '{{ asset("storage/" . $news->image_url) }}');
        @endif
    });

    // Функция для предварительного просмотра видео
    document.addEventListener('DOMContentLoaded', function() {
        const videoIframeField = document.getElementById('video_iframe');
        const videoPreview = document.getElementById('video-preview');
        const iframeContainer = document.getElementById('iframe-container');
        
        videoIframeField.addEventListener('input', function() {
            const iframeCode = this.value.trim();
            
            if (iframeCode) {
                // Простая проверка на наличие iframe
                if (iframeCode.includes('<iframe') && iframeCode.includes('</iframe>')) {
                    // Проверка на разрешенные домены
                    if (iframeCode.includes('vk.com') || iframeCode.includes('rutube.ru')) {
                        iframeContainer.innerHTML = iframeCode;
                        videoPreview.style.display = 'block';
                    } else {
                        iframeContainer.innerHTML = '<div class="alert alert-danger">Разрешены только видео с ВКонтакте или Rutube</div>';
                        videoPreview.style.display = 'block';
                    }
                } else {
                    iframeContainer.innerHTML = '<div class="alert alert-warning">Введите корректный код iframe</div>';
                    videoPreview.style.display = 'block';
                }
            } else {
                videoPreview.style.display = 'none';
            }
        });
        
        // Инициализируем предпросмотр, если поле не пустое
        if (videoIframeField.value.trim()) {
            videoIframeField.dispatchEvent(new Event('input'));
        }
    });

    // Обработка извлечения метаданных видео из URL
    document.addEventListener('DOMContentLoaded', function() {
        const videoUrlContainer = document.getElementById('video-url-container');
        const videoUrlField = document.getElementById('video_url');
        const extractButton = document.getElementById('extract-video-data');
        const videoUrlFeedback = document.getElementById('video-url-feedback');
        const videoIframeField = document.getElementById('video_iframe');
        const videoPreview = document.getElementById('video-preview');
        const iframeContainer = document.getElementById('iframe-container');
        const videoTitleField = document.getElementById('video_title');
        const videoDescField = document.getElementById('video_description');
        const videoTagsField = document.getElementById('video_tags');
        const videoAuthorNameField = document.getElementById('video_author_name');
        const videoAuthorLinkField = document.getElementById('video_author_link');
        const videoDataContainer = document.getElementById('video-data-container');
        const toggleVideoDetails = document.getElementById('toggle-video-details');
        
        // Переключение отображения деталей видео
        if (toggleVideoDetails && videoDataContainer) {
            toggleVideoDetails.addEventListener('click', function() {
                const isHidden = videoDataContainer.style.display === 'none';
                videoDataContainer.style.display = isHidden ? 'block' : 'none';
                
                const collapseText = this.querySelector('.collapse-text');
                const icon = this.querySelector('i');
                
                if (collapseText) collapseText.textContent = isHidden ? 'Скрыть детали' : 'Показать детали';
                if (icon) {
                    icon.classList.toggle('fa-chevron-up', isHidden);
                    icon.classList.toggle('fa-chevron-down', !isHidden);
                }
            });
        }
        
        // Автоматически извлекаем данные видео при изменении URL
        if (videoUrlField) {
            videoUrlField.addEventListener('blur', function() {
                if (this.value.trim()) {
                    extractVideoMetadata();
                }
            });
        }
        
        // Обработчик кнопки извлечения данных
        if (extractButton) {
            extractButton.addEventListener('click', function() {
                extractVideoMetadata();
            });
        }
        
        // Функция для извлечения метаданных видео
        function extractVideoMetadata() {
            if (!videoUrlField || !videoUrlContainer || !videoUrlFeedback) {
                console.error('Необходимые DOM-элементы не найдены');
                return;
            }
            
            const url = videoUrlField.value.trim();
            
            // Валидация URL
            if (!url) {
                showVideoUrlError('Введите URL видео');
                return;
            }
            
            if (!url.includes('vk.com') && !url.includes('vkvideo.ru')) {
                showVideoUrlError('Поддерживаются только видео с ВКонтакте');
                return;
            }
            
            // Показываем индикатор загрузки
            videoUrlContainer.classList.add('loading');
            videoUrlFeedback.textContent = '';
            
            // Улучшенное получение CSRF-токена с запасными вариантами
            let csrfToken = '';
            
            // Метод 1: Пробуем получить из мета-тега
            const metaToken = document.querySelector('meta[name="csrf-token"]');
            if (metaToken) {
                csrfToken = metaToken.getAttribute('content');
                console.log('CSRF токен получен из meta тега');
            }
            
            // Метод 2: Пробуем получить из формы
            if (!csrfToken) {
                const csrfInput = document.querySelector('input[name="_token"]');
                if (csrfInput) {
                    csrfToken = csrfInput.value;
                    console.log('CSRF токен получен из скрытого поля формы');
                }
            }
            
            // Метод 3: Для Laravel 9+ можно также проверить cookie XSRF-TOKEN
            if (!csrfToken) {
                // Получение из cookie
                const cookies = document.cookie.split(';');
                for (let i = 0; i < cookies.length; i++) {
                    const cookie = cookies[i].trim();
                    if (cookie.startsWith('XSRF-TOKEN=')) {
                        csrfToken = decodeURIComponent(cookie.substring(11));
                        console.log('CSRF токен получен из cookie');
                        break;
                    }
                }
            }
            
            if (!csrfToken) {
                console.error('CSRF токен не найден. Проверьте наличие meta тега, скрытого поля формы или cookie');
                videoUrlContainer.classList.remove('loading');
                showVideoUrlError('Ошибка безопасности: CSRF токен не найден. Обновите страницу и попробуйте снова');
                
                // Логирование для отладки
                console.log('Метатеги на странице:');
                document.querySelectorAll('meta').forEach(meta => {
                    console.log(meta.outerHTML);
                });
                
                return;
            }
            
            // Отправляем запрос на сервер для извлечения метаданных
            fetch('{{ route('admin.video.extract-metadata') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({ url: url })
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Сетевая ошибка: ' + response.status);
                }
                return response.json();
            })
            .then(data => {
                // Скрываем индикатор загрузки
                if (videoUrlContainer) {
                    videoUrlContainer.classList.remove('loading');
                }
                
                if (data.error) {
                    showVideoUrlError(data.error);
                    console.error('Ошибка извлечения видео:', data.error);
                    return;
                }
                
                // Проверка, что данные и videoDataContainer существуют
                if (!data.data) {
                    showVideoUrlError('Получены некорректные данные от сервера');
                    console.error('Некорректные данные:', data);
                    return;
                }
                
                // Показываем контейнер с данными видео
                if (videoDataContainer) {
                    videoDataContainer.style.display = 'block';
                    
                    if (toggleVideoDetails) {
                        const collapseText = toggleVideoDetails.querySelector('.collapse-text');
                        if (collapseText) collapseText.textContent = 'Скрыть детали';
                        
                        const icon = toggleVideoDetails.querySelector('i');
                        if (icon) icon.classList.replace('fa-chevron-down', 'fa-chevron-up');
                    }
                }
                
                console.log('Получены метаданные видео:', data.data); // Для отладки
                
                // Безопасно заполняем поля данными с проверками на существование элементов и данных
                if (videoIframeField && data.data.iframe) {
                    videoIframeField.value = data.data.iframe;
                    
                    if (iframeContainer) {
                        iframeContainer.innerHTML = data.data.iframe;
                        
                        if (videoPreview) {
                            videoPreview.style.display = 'block';
                        }
                    }
                }
                
                if (videoTitleField && data.data.title) {
                    videoTitleField.value = data.data.title;
                }
                
                if (videoDescField && data.data.description) {
                    videoDescField.value = data.data.description;
                }
                
                if (videoTagsField && data.data.tags) {
                    videoTagsField.value = data.data.tags;
                }
                
                if (videoAuthorNameField && data.data.author_name) {
                    videoAuthorNameField.value = data.data.author_name;
                }
                
                if (videoAuthorLinkField && data.data.author_link) {
                    videoAuthorLinkField.value = data.data.author_link;
                }
                
                // Добавляем индикатор платформы
                if (videoUrlFeedback && data.data.platform === 'vk') {
                    videoUrlFeedback.innerHTML = '<i class="fab fa-vk text-primary video-platform-icon"></i><span class="text-success">Данные видео успешно получены</span>';
                    videoUrlFeedback.classList.remove('text-danger');
                    videoUrlFeedback.classList.add('text-success');
                }
                
                // Если получена обложка видео, добавляем опцию для скачивания
                if (data.data.thumbnail && videoPreview) {
                    const existingSection = document.querySelector('.video-thumbnail-section');
                    if (existingSection) {
                        existingSection.remove();
                    }

                    const imagePreviewSection = document.createElement('div');
                    imagePreviewSection.className = 'mt-3 border-top pt-3 video-thumbnail-section';
                    imagePreviewSection.innerHTML = `
                        <h6 class="mb-2"><i class="fas fa-image me-2"></i>Обложка видео</h6>
                        <div class="d-flex flex-column">
                            <img src="${data.data.thumbnail}" class="img-fluid rounded mb-2" style="max-height: 200px; width: auto;">
                            <div class="btn-group">
                                <a href="${data.data.thumbnail}" class="btn btn-sm btn-primary" download="video_thumbnail.jpg" target="_blank">
                                    <i class="fas fa-download me-1"></i> Скачать обложку
                                </a>
                                <button type="button" class="btn btn-sm btn-success use-as-thumbnail">
                                    <i class="fas fa-check me-1"></i> Использовать как изображение новости
                                </button>
                            </div>
                        </div>
                    `;
                    
                    videoPreview.appendChild(imagePreviewSection);
                    
                    // Добавляем обработчик для кнопки "Использовать как изображение"
                    const button = imagePreviewSection.querySelector('.use-as-thumbnail');
                    if (button) {
                        button.addEventListener('click', function() {
                            downloadAndUseAsThumbnail(data.data.thumbnail);
                        });
                    }
                }
            })
            .catch(error => {
                if (videoUrlContainer) {
                    videoUrlContainer.classList.remove('loading');
                }
                showVideoUrlError('Произошла ошибка при получении данных видео: ' + error.message);
                console.error('Error:', error);
            });
        }
        
        // Показываем ошибку URL
        function showVideoUrlError(message) {
            if (videoUrlFeedback) {
                videoUrlFeedback.textContent = message;
                videoUrlFeedback.classList.remove('text-success');
                videoUrlFeedback.classList.add('text-danger');
            } else {
                console.error('Элемент для отображения ошибки не найден');
                alert(message);
            }
        }
    });
</script>

<!-- Добавляем дополнительные стили для редактора -->
<style>
    /* Улучшенный стиль для редактора */
    .ck-editor__editable {
        min-height: 500px;
        max-height: 800px;
        overflow-y: auto;
    }
    .ck-editor__editable_inline {
        padding: 1rem;
        border: 1px solid #ddd !important;
        border-radius: 0 0 4px 4px !important;
    }
    .ck-toolbar {
        border-radius: 4px 4px 0 0 !important;
        border: 1px solid #ddd !important;
    }
    /* Стили для различных блоков контента */
    .ck-content blockquote {
        border-left: 5px solid #ccc;
        font-style: italic;
        margin-left: 0;
        padding-left: 1.5em;
    }
    .ck-content .image {
        margin: 1em 0;
    }
    .ck-content .image img {
        max-width: 100%;
        height: auto;
    }
    .ck-content .image-style-side {
        float: right;
        margin-left: 1.5em;
        max-width: 50%;
    }
    
    /* Стили для режима чтения */
    .content-preview img {
        max-width: 100%;
        height: auto;
        margin: 1em 0;
    }
    .content-preview blockquote {
        border-left: 5px solid #ccc;
        margin-left: 0;
        padding-left: 1.5em;
        font-style: italic;
    }
    .content-preview table {
        border-collapse: collapse;
        width: 100%;
        margin: 1em 0;
    }
    .content-preview table td, .content-preview table th {
        border: 1px solid #ddd;
        padding: 8px;
    }
    .content-preview table tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    .content-preview table th {
        padding-top: 12px;
        padding-bottom: 12px;
        text-align: left;
        background-color: #f8f9fa;
    }
</style>
@endsection
