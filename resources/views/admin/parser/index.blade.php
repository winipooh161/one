@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between align-items-center mb-4">
        <div class="col-md-6">
            <h1 class="animate-on-scroll"><i class="fas fa-spider text-primary me-2"></i> Парсер рецептов</h1>
            <p class="animate-on-scroll">Импортируйте рецепты с других сайтов, указав URL страницы с рецептом.</p>
        </div>
        <div class="col-md-6 text-end">
            <a href="{{ route('admin.parser.collect_links') }}" class="btn btn-outline-info me-2">
                <i class="fas fa-search"></i> Сбор ссылок с категории
            </a>
            <a href="{{ route('admin.parser.batch') }}" class="btn btn-outline-success me-2">
                <i class="fas fa-list-ol"></i> Пакетный парсинг
            </a>
          
            <a href="{{ route('admin.sitemap.index') }}" class="btn btn-outline-primary me-2">
                <i class="fas fa-sitemap"></i> Управление Sitemap
            </a>
            <a href="{{ route('admin.recipes.index') }}" class="btn btn-secondary animate-on-scroll">
                <i class="fas fa-arrow-left me-1"></i> Назад к рецептам
            </a>
        </div>
    </div>
    
    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show animate-on-scroll" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('success'))
    <div class="alert alert-success">
        {{ session('success') }}
    </div>
    @endif

    @if(isset($queueStatus) && !empty($queueStatus))
    <div class="card mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fas fa-tasks me-2"></i>Статус отложенного парсинга</h5>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <h6>Ожидающие ссылки: <span class="badge bg-primary">{{ $queueStatus['pending'] }}</span></h6>
                    <div class="progress mb-3">
                        <div class="progress-bar" role="progressbar" 
                            style="width: {{ $queueStatus['progress'] }}%" 
                            aria-valuenow="{{ $queueStatus['progress'] }}" 
                            aria-valuemin="0" 
                            aria-valuemax="100">{{ $queueStatus['progress'] }}%</div>
                    </div>
                </div>
                <div class="col-md-6">
                    <p><strong>Последнее обновление:</strong> {{ $queueStatus['last_update'] }}</p>
                    <p><strong>Следующий запуск:</strong> {{ $queueStatus['next_run'] }}</p>
                </div>
            </div>
        </div>
    </div>
    @endif
    
    <div class="card animate-on-scroll">
        <div class="card-header bg-primary text-white">
            <i class="fas fa-link me-2"></i> Ввод URL для импорта
        </div>
        <div class="card-body">
            <form action="{{ route('admin.parser.parse') }}" method="POST">
                @csrf
                
                <div class="mb-4">
                    <label for="url" class="form-label">URL страницы с рецептом</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-globe"></i></span>
                        <input type="url" class="form-control @error('url') is-invalid @enderror" id="url" name="url" 
                               placeholder="https://example.com/recipe/..." required value="{{ old('url') }}">
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-cloud-download-alt me-1"></i> Импортировать
                        </button>
                    </div>
                    @error('url')
                        <div class="invalid-feedback">{{ $message }}</div>
                    @enderror
                    <div class="form-text">Вставьте URL страницы с рецептом, который хотите импортировать.</div>
                </div>
                
                <div class="mb-3">
                    <label for="categories" class="form-label">Выберите категории (опционально)</label>
                    <select multiple class="form-select" id="categories" name="categories[]">
                        @foreach($categories as $category)
                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                        @endforeach
                    </select>
                    <div class="form-text">Удерживайте Ctrl (Cmd на Mac) для выбора нескольких категорий</div>
                </div>
            </form>
        </div>
    </div>
    
    <div class="card mt-4 animate-on-scroll">
        <div class="card-header bg-info text-white">
            <i class="fas fa-info-circle me-2"></i> Информация по использованию
        </div>
        <div class="card-body">
            <h5>Как это работает?</h5>
            <ol>
                <li>Вставьте URL страницы с рецептом в поле ввода выше.</li>
                <li>Выберите категории, в которые хотите добавить рецепт (опционально).</li>
                <li>Нажмите кнопку "Импортировать".</li>
                <li>Система попытается извлечь данные рецепта с указанной страницы.</li>
                <li>Вы сможете просмотреть и отредактировать извлеченные данные перед сохранением.</li>
            </ol>
            
            <h5 class="mt-4">Поддерживаемые форматы данных</h5>
            <p>Парсер может обрабатывать следующие типы данных:</p>
            <ul>
                <li><strong>Структурированные данные JSON-LD</strong> (наиболее точный метод)</li>
                <li><strong>Микроданные Schema.org</strong> (хорошая точность)</li>
                <li><strong>Обычные HTML-страницы</strong> (точность зависит от структуры страницы)</li>
            </ul>
            
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle me-2"></i> <strong>Обратите внимание:</strong> 
                Парсер может извлекать не все данные корректно, особенно при нестандартной структуре страницы. 
                Всегда проверяйте и корректируйте результаты перед сохранением.
            </div>
            
            <h5 class="mt-4">Популярные кулинарные сайты с хорошей поддержкой</h5>
            <div class="row">
                <div class="col-md-6">
                    <ul>
                        <li>Едим Дома</li>
                        <li>Поваренок</li>
                        <li>Allrecipes</li>
                        <li>Food Network</li>
                        <li>Bon Appétit</li>
                    </ul>
                </div>
                <div class="col-md-6">
                    <ul>
                        <li>Eda.ru</li>
                        <li>Готовим.ру</li>
                        <li>Вкусно и просто</li>
                        <li>BBC Good Food</li>
                        <li>New York Times Cooking</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
