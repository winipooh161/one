@extends('admin.layouts.app')

@section('content')
<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                <h1 class="h2 mb-0">Просмотр новости</h1>
                <div class="d-flex flex-wrap gap-2">
                    <a href="{{ route('admin.news.edit', $news) }}" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Редактировать
                    </a>
                    <a href="{{ route('admin.news.index') }}" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-1"></i> Назад к списку
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <!-- Основная информация о новости -->
        <div class="col-lg-8">
            <!-- Карточка с контентом -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <div class="d-flex justify-content-between align-items-center flex-wrap">
                        <h2 class="h4 mb-0">{{ $news->title }}</h2>
                        <span class="badge {{ $news->is_published ? 'bg-success' : 'bg-warning text-dark' }} fs-6">
                            {{ $news->is_published ? 'Опубликовано' : 'Черновик' }}
                        </span>
                    </div>
                </div>
                
                @if($news->image_url)
                <div class="position-relative">
                    <img src="{{ asset('uploads/' . $news->image_url) }}" 
                         alt="{{ $news->title }}" 
                         class="card-img-top img-fluid" 
                         style="max-height: 500px; object-fit: contain; background-color: #f8f9fa;">
                    <div class="position-absolute bottom-0 end-0 m-3">
                        <span class="badge bg-dark bg-opacity-75 fs-6">
                            <i class="fas fa-image me-1"></i> Изображение новости
                        </span>
                    </div>
                </div>
                @endif
                
                <!-- Краткое описание -->
                <div class="card-body border-bottom">
                    <h3 class="h5 text-muted mb-3">Краткое описание:</h3>
                    <div class="p-3 bg-light rounded">
                        {{ $news->short_description }}
                    </div>
                </div>
                
                <!-- Видео (если есть) -->
                @if($news->hasVideo())
                <div class="card-body border-bottom">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h3 class="h5 text-muted mb-0">
                            <i class="fas fa-video me-2 text-danger"></i> Видеоконтент
                        </h3>
                        @if($news->video_author_name)
                        <a href="{{ $news->video_author_link }}" target="_blank" class="text-decoration-none">
                            <span class="badge bg-primary bg-opacity-75">
                                <i class="fas fa-user me-1"></i> {{ $news->video_author_name }}
                            </span>
                        </a>
                        @endif
                    </div>
                    
                    <div class="ratio ratio-16x9 mb-3">
                        {!! $news->video_iframe !!}
                    </div>
                    
                    @if($news->video_title || $news->video_description)
                    <div class="p-3 bg-light rounded">
                        @if($news->video_title)
                            <h4 class="h6">{{ $news->video_title }}</h4>
                        @endif
                        @if($news->video_description)
                            <p class="mb-1">{{ $news->video_description }}</p>
                        @endif
                        
                        <!-- Теги видео -->
                        @if($news->video_tags)
                        <div class="mt-2">
                            @foreach($news->getVideoTagsArray() as $tag)
                            <span class="badge bg-secondary me-1">{{ $tag }}</span>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endif
                </div>
                @endif
                
                <!-- Содержание новости -->
                <div class="card-body">
                    <h3 class="h5 text-muted mb-3">Содержание новости:</h3>
                    <div class="content-preview p-2">
                        {!! $news->content !!}
                    </div>
                </div>
                
                <!-- Нижняя панель действий -->
                <div class="card-footer bg-white d-flex justify-content-between align-items-center flex-wrap gap-2 py-3">
                    <div>
                        <a href="{{ route('news.show', $news->slug) }}" target="_blank" class="btn btn-info text-white">
                            <i class="fas fa-external-link-alt me-1"></i> Открыть на сайте
                        </a>
                    </div>
                    <div>
                        <form action="{{ route('admin.news.destroy', $news) }}" method="POST" class="d-inline" 
                              onsubmit="return confirm('Вы уверены, что хотите удалить эту новость?')">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash me-1"></i> Удалить новость
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        <!-- Боковая панель с метаданными -->
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h3 class="h5 mb-0"><i class="fas fa-info-circle me-2"></i> Информация о новости</h3>
                </div>
                <div class="list-group list-group-flush">
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>ID:</strong>
                        <span class="badge bg-secondary">{{ $news->id }}</span>
                    </div>
                    
                    <div class="list-group-item">
                        <strong>Slug:</strong>
                        <div class="mt-1">
                            <code class="user-select-all text-dark">{{ $news->slug }}</code>
                        </div>
                    </div>
                    
                    <div class="list-group-item d-flex justify-content-between align-items-center">
                        <strong>Просмотры:</strong>
                        <span class="badge bg-primary">{{ $news->views }}</span>
                    </div>
                    
                    <div class="list-group-item d-flex flex-column">
                        <strong class="mb-1">Дата создания:</strong>
                        <span class="text-muted">{{ $news->created_at->format('d.m.Y H:i:s') }}</span>
                    </div>
                    
                    <div class="list-group-item d-flex flex-column">
                        <strong class="mb-1">Последнее обновление:</strong>
                        <span class="text-muted">{{ $news->updated_at->format('d.m.Y H:i:s') }}</span>
                    </div>
                    
                    <div class="list-group-item d-flex flex-column">
                        <strong class="mb-1">Автор:</strong>
                        @if($news->user)
                            <div class="d-flex align-items-center mt-1">
                                <div class="bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-2" style="width: 30px; height: 30px;">
                                    <i class="fas fa-user"></i>
                                </div>
                                <span>{{ $news->user->name }}</span>
                            </div>
                        @else
                            <span class="text-muted fst-italic">Не указан</span>
                        @endif
                    </div>
                    
                    @if($news->hasVideo())
                    <div class="list-group-item d-flex flex-column">
                        <strong class="mb-1">Тип контента:</strong>
                        <div class="d-flex align-items-center mt-1">
                            <span class="badge bg-danger me-2"><i class="fas fa-video"></i></span>
                            <span>Видео-новость</span>
                        </div>
                    </div>
                    @endif
                </div>
                
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <a href="{{ route('admin.news.edit', $news) }}" class="btn btn-outline-primary">
                            <i class="fas fa-edit me-1"></i> Редактировать новость
                        </a>
                        @if($news->is_published)
                            <button type="button" class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#unpublishModal">
                                <i class="fas fa-eye-slash me-1"></i> Снять с публикации
                            </button>
                        @else
                            <button type="button" class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#publishModal">
                                <i class="fas fa-globe me-1"></i> Опубликовать
                            </button>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Дополнительная карточка с превью изображения -->
            @if($news->image_url)
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center py-3">
                    <h3 class="h5 mb-0"><i class="fas fa-image me-2"></i> Изображение</h3>
                    <a href="{{ asset('uploads/' . $news->image_url) }}" class="btn btn-sm btn-outline-secondary" download target="_blank">
                        <i class="fas fa-download me-1"></i> Скачать
                    </a>
                </div>
                <div class="card-body text-center">
                    <img src="{{ asset('uploads/' . $news->image_url) }}" 
                         alt="{{ $news->title }}" 
                         class="img-fluid rounded thumbnail-preview mb-2" 
                         style="max-height: 200px;">
                    <div class="text-muted small mt-2">
                        <div>Путь: <code class="user-select-all">{{ $news->image_url }}</code></div>
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Модальное окно для публикации -->
<div class="modal fade" id="publishModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Опубликовать новость</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите опубликовать эту новость? После публикации она станет доступна всем посетителям сайта.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form action="{{ route('admin.news.update', $news) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="is_published" value="1">
                    <input type="hidden" name="title" value="{{ $news->title }}">
                    <input type="hidden" name="short_description" value="{{ $news->short_description }}">
                    <input type="hidden" name="content" value="{{ $news->content }}">
                    <button type="submit" class="btn btn-success">Опубликовать</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для снятия с публикации -->
<div class="modal fade" id="unpublishModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Снять с публикации</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Вы уверены, что хотите снять эту новость с публикации? После этого она будет видна только в административной части сайта.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                <form action="{{ route('admin.news.update', $news) }}" method="POST">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="is_published" value="0">
                    <input type="hidden" name="title" value="{{ $news->title }}">
                    <input type="hidden" name="short_description" value="{{ $news->short_description }}">
                    <input type="hidden" name="content" value="{{ $news->content }}">
                    <button type="submit" class="btn btn-warning">Снять с публикации</button>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Стили для контентной области */
    .content-preview {
        overflow-wrap: break-word;
    }
    
    .content-preview img {
        max-width: 100%;
        height: auto;
        border-radius: 0.25rem;
    }
    
    .content-preview table {
        width: 100%;
        margin-bottom: 1rem;
        border-collapse: collapse;
    }
    
    .content-preview table td,
    .content-preview table th {
        padding: 0.5rem;
        border: 1px solid #dee2e6;
    }
    
    .content-preview blockquote {
        padding: 0.75rem 1.25rem;
        margin-bottom: 1rem;
        border-left: 4px solid #ced4da;
        background-color: #f8f9fa;
    }
    
    /* Адаптивные стили */
    @media (max-width: 767.98px) {
        .d-flex.justify-content-between {
            flex-direction: column;
            gap: 0.5rem;
        }
        
        .d-flex.justify-content-between .btn-group,
        .d-flex.justify-content-between .d-flex {
            width: 100%;
        }
        
        .card-footer .btn {
            width: 100%;
            margin-bottom: 0.5rem;
        }
    }
    
    /* Улучшенные стили для изображения */
    .card-img-top {
        transition: filter 0.3s ease;
    }
    
    .thumbnail-preview {
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    
    .thumbnail-preview:hover {
        transform: scale(1.02);
    }
    
    /* Стиль для iframe видео */
    .ratio-16x9 iframe {
        width: 100%;
        height: 100%;
        border: none;
    }
</style>
@endsection

@section('scripts')
<script>
    // Инициализация всплывающих подсказок Bootstrap
    document.addEventListener('DOMContentLoaded', function() {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Обработка клика по миниатюре для увеличения
        document.querySelectorAll('.thumbnail-preview').forEach(function(image) {
            image.addEventListener('click', function() {
                window.open(this.src, '_blank');
            });
        });
    });
</script>
@endsection
