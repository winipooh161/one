@extends('admin.layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <h1 class="h2 mb-0">Управление новостями</h1>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.news.create') }}" class="btn btn-primary d-flex align-items-center">
                <i class="fas fa-plus-circle me-2"></i> Создать новость
            </a>
            <!-- Добавляем кнопку для генерации sitemap -->
            <form action="{{ route('admin.sitemap.generate') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-secondary d-flex align-items-center">
                    <i class="fas fa-sitemap me-2"></i> Обновить Sitemap
                </button>
            </form>
            <div class="btn-group">
                <button type="button" class="btn btn-outline-secondary" id="toggle-view-table" title="Табличный вид">
                    <i class="fas fa-list"></i>
                </button>
                <button type="button" class="btn btn-outline-secondary" id="toggle-view-grid" title="Сетка">
                    <i class="fas fa-th-large"></i>
                </button>
            </div>
        </div>
    </div>
    
    <!-- Отображаем сообщение об успешной генерации sitemap, если оно есть -->
    @if(session('sitemap_success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <div>{{ session('sitemap_success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif
    
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <div>{{ session('success') }}</div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
        </div>
    @endif
    
    <!-- Фильтры для новостей -->
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Фильтры</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.news.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-search text-muted"></i>
                        </span>
                        <input type="text" class="form-control" id="search" name="search" 
                               value="{{ request('search') }}" placeholder="Поиск по заголовку">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-filter text-muted"></i>
                        </span>
                        <select class="form-select" id="type" name="type">
                            <option value="">Все типы</option>
                            <option value="regular" {{ request('type') == 'regular' ? 'selected' : '' }}>Обычные новости</option>
                            <option value="video" {{ request('type') == 'video' ? 'selected' : '' }}>Видео новости</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="input-group">
                        <span class="input-group-text bg-light">
                            <i class="fas fa-globe text-muted"></i>
                        </span>
                        <select class="form-select" id="status" name="status">
                            <option value="">Все статусы</option>
                            <option value="published" {{ request('status') == 'published' ? 'selected' : '' }}>Опубликовано</option>
                            <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Черновик</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search me-1"></i> Найти
                    </button>
                    <a href="{{ route('admin.news.index') }}" class="btn btn-outline-secondary">
                        <i class="fas fa-times me-1"></i> Сброс
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Информационная панель -->
    <div class="row mb-4">
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card bg-primary bg-gradient text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Всего новостей</h6>
                            <h2 class="mt-2 mb-0">{{ $news->total() }}</h2>
                        </div>
                        <i class="fas fa-newspaper fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card bg-success bg-gradient text-white shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Опубликовано</h6>
                            <h2 class="mt-2 mb-0">{{ App\Models\News::where('is_published', true)->count() }}</h2>
                        </div>
                        <i class="fas fa-check-circle fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4 mb-3 mb-md-0">
            <div class="card bg-warning bg-gradient text-dark shadow-sm h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h6 class="card-title mb-0">Черновиков</h6>
                            <h2 class="mt-2 mb-0">{{ App\Models\News::where('is_published', false)->count() }}</h2>
                        </div>
                        <i class="fas fa-edit fa-2x opacity-50"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Режим таблицы (по умолчанию) -->
    <div id="table-view">
        <div class="card shadow-sm">
            <div class="card-header bg-white py-3">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Список новостей</h5>
                    <span class="badge bg-primary rounded-pill">{{ $news->total() }}</span>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th scope="col" width="60" class="ps-3">#</th>
                                <th scope="col" width="100">Изображение</th>
                                <th scope="col">Заголовок</th>
                                <th scope="col" width="130">Тип</th>
                                <th scope="col" width="130">Дата</th>
                                <th scope="col" width="100">Статус</th>
                                <th scope="col" width="150" class="text-end pe-3">Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($news as $item)
                                <tr>
                                    <td class="ps-3">{{ $item->id }}</td>
                                    <td>
                                        @if($item->image_url)
                                            <img src="{{ asset('uploads/' . $item->image_url) }}" 
                                                 alt="{{ $item->title }}" 
                                                 class="img-thumbnail shadow-sm" 
                                                 style="width: 60px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="bg-light d-flex align-items-center justify-content-center text-muted"
                                                 style="width: 60px; height: 40px;">
                                                <i class="fas fa-image"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ route('admin.news.edit', $item) }}" class="text-decoration-none fw-medium">
                                            {{ Str::limit($item->title, 70) }}
                                        </a>
                                        <div class="small text-muted mt-1">
                                            {{ Str::limit($item->short_description, 100) }}
                                        </div>
                                    </td>
                                    <td>
                                        @if($item->hasVideo())
                                            <div class="badge bg-danger bg-gradient">
                                                <i class="fas fa-video me-1"></i> Видео
                                            </div>
                                        @else
                                            <div class="badge bg-info bg-gradient">
                                                <i class="fas fa-newspaper me-1"></i> Текст
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <div>{{ $item->created_at->format('d.m.Y') }}</div>
                                        <div class="small text-muted">{{ $item->created_at->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        @if($item->is_published)
                                            <div class="badge bg-success bg-gradient">Опубликовано</div>
                                        @else
                                            <div class="badge bg-warning text-dark bg-gradient">Черновик</div>
                                        @endif
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group" role="group">
                                            <a href="{{ route('admin.news.edit', $item) }}" class="btn btn-sm btn-primary" 
                                               data-bs-toggle="tooltip" title="Редактировать">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="{{ route('admin.news.show', $item) }}" class="btn btn-sm btn-info text-white" 
                                               data-bs-toggle="tooltip" title="Просмотр">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="{{ route('news.show', $item->slug) }}" target="_blank" class="btn btn-sm btn-success" 
                                               data-bs-toggle="tooltip" title="Открыть на сайте">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-danger" data-bs-toggle="modal" 
                                                    data-bs-target="#deleteModal{{ $item->id }}" title="Удалить">
                                                <i class="fas fa-trash"></i>
                                            </button>
                                        </div>

                                        <!-- Модальное окно подтверждения удаления -->
                                        <div class="modal fade" id="deleteModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Подтверждение удаления</h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                    </div>
                                                    <div class="modal-body text-start">
                                                        <p>Вы уверены, что хотите удалить новость "<strong>{{ $item->title }}</strong>"?</p>
                                                        <p class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Это действие нельзя отменить!</p>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                                        <form action="{{ route('admin.news.destroy', $item) }}" method="POST" class="d-inline">
                                                            @csrf
                                                            @method('DELETE')
                                                            <button type="submit" class="btn btn-danger">Удалить</button>
                                                        </form>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center py-4">
                                        <div class="py-5">
                                            <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                                            <p class="text-muted mb-0">Новости не найдены</p>
                                            <a href="{{ route('admin.news.create') }}" class="btn btn-primary mt-3">
                                                <i class="fas fa-plus me-2"></i> Создать новость
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
            
            @if($news->total() > 0)
            <div class="card-footer bg-white py-3">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-3">
                    <div>
                        Показано {{ $news->firstItem() ?? 0 }} - {{ $news->lastItem() ?? 0 }} из {{ $news->total() }} записей
                    </div>
                    <div class="pagination-container">
                        {{ $news->withQueryString()->links() }}
                    </div>
                </div>
            </div>
            @endif
        </div>
    </div>
    
    <!-- Режим сетки (скрыт по умолчанию) -->
    <div id="grid-view" style="display: none;">
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            @forelse($news as $item)
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        @if($item->image_url)
                            <div class="position-relative">
                                <img src="{{ asset('uploads/' . $item->image_url) }}" 
                                     class="card-img-top" alt="{{ $item->title }}"
                                     style="height: 160px; object-fit: cover;">
                                @if($item->hasVideo())
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-danger">
                                            <i class="fas fa-video"></i>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="position-relative bg-light d-flex align-items-center justify-content-center" style="height: 160px;">
                                <i class="fas fa-image fa-3x text-muted"></i>
                                @if($item->hasVideo())
                                    <div class="position-absolute top-0 end-0 m-2">
                                        <span class="badge bg-danger">
                                            <i class="fas fa-video"></i>
                                        </span>
                                    </div>
                                @endif
                            </div>
                        @endif
                        
                        <div class="card-body">
                            <h5 class="card-title">
                                <a href="{{ route('admin.news.edit', $item) }}" class="text-decoration-none">
                                    {{ Str::limit($item->title, 50) }}
                                </a>
                            </h5>
                            <p class="card-text small text-muted">
                                {{ Str::limit($item->short_description, 100) }}
                            </p>
                        </div>
                        
                        <div class="card-footer bg-white d-flex justify-content-between align-items-center">
                            <div class="small">
                                <span class="badge {{ $item->is_published ? 'bg-success' : 'bg-warning text-dark' }}">
                                    {{ $item->is_published ? 'Опубликовано' : 'Черновик' }}
                                </span>
                                <span class="text-muted ms-2">{{ $item->created_at->format('d.m.Y') }}</span>
                            </div>
                            
                            <div class="btn-group btn-group-sm">
                                <a href="{{ route('admin.news.show', $item) }}" class="btn btn-outline-secondary" title="Просмотр">
                                    <i class="fas fa-eye"></i>
                                </a>
                                <a href="{{ route('admin.news.edit', $item) }}" class="btn btn-outline-primary" title="Редактировать">
                                    <i class="fas fa-edit"></i>
                                </a>
                                <a href="{{ route('news.show', $item->slug) }}" target="_blank" class="btn btn-outline-success" title="Открыть на сайте">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                                <button type="button" class="btn btn-outline-danger" data-bs-toggle="modal" 
                                        data-bs-target="#deleteModalGrid{{ $item->id }}" title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                            
                            <!-- Модальное окно подтверждения удаления (для режима сетки) -->
                            <div class="modal fade" id="deleteModalGrid{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <div class="modal-header">
                                            <h5 class="modal-title">Подтверждение удаления</h5>
                                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                        </div>
                                        <div class="modal-body">
                                            <p>Вы уверены, что хотите удалить новость "<strong>{{ $item->title }}</strong>"?</p>
                                            <p class="text-danger mb-0"><i class="fas fa-exclamation-triangle me-2"></i> Это действие нельзя отменить!</p>
                                        </div>
                                        <div class="modal-footer">
                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Отмена</button>
                                            <form action="{{ route('admin.news.destroy', $item) }}" method="POST" class="d-inline">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-danger">Удалить</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center py-5">
                    <i class="fas fa-newspaper fa-3x text-muted mb-3"></i>
                    <p class="text-muted">Новости не найдены</p>
                    <a href="{{ route('admin.news.create') }}" class="btn btn-primary mt-2">
                        <i class="fas fa-plus me-2"></i> Создать новость
                    </a>
                </div>
            @endforelse
        </div>
        
        @if($news->total() > 0)
        <div class="d-flex justify-content-between align-items-center mt-4 flex-wrap gap-3">
            <div class="text-muted">
                Показано {{ $news->firstItem() ?? 0 }} - {{ $news->lastItem() ?? 0 }} из {{ $news->total() }} записей
            </div>
            <div class="pagination-container">
                {{ $news->withQueryString()->links() }}
            </div>
        </div>
        @endif
    </div>
</div>
@endsection

@section('styles')
<style>
    /* Стили для пагинации */
    .pagination-container .pagination {
        margin-bottom: 0;
    }
    
    /* Улучшенные стили для таблицы */
    .table-hover tbody tr:hover {
        background-color: rgba(0, 123, 255, 0.03);
    }
    
    /* Стили для карточек в режиме сетки */
    .card {
        transition: transform 0.2s, box-shadow 0.2s;
    }
    
    #grid-view .card:hover {
        transform: translateY(-5px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.15) !important;
    }
    
    /* Улучшенные стили для кнопок переключения режима просмотра */
    .btn-group .btn.active {
        background-color: #6c757d;
        color: white;
    }
    
    /* Адаптивные стили */
    @media (max-width: 767.98px) {
        .table-responsive {
            font-size: 0.9rem;
        }
        
        .btn-group .btn {
            padding: 0.25rem 0.5rem;
        }
    }
    
    /* Стили для статистических карточек */
    .card .opacity-50 {
        opacity: 0.5;
    }
    
    .bg-gradient {
        background-image: linear-gradient(to right, rgba(255,255,255,0.1), rgba(255,255,255,0));
    }
</style>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Инициализация тултипов Bootstrap
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Переключение между режимами просмотра
        const tableView = document.getElementById('table-view');
        const gridView = document.getElementById('grid-view');
        const toggleTableBtn = document.getElementById('toggle-view-table');
        const toggleGridBtn = document.getElementById('toggle-view-grid');
        
        // Загрузка сохраненного предпочтения пользователя
        const savedViewMode = localStorage.getItem('news_view_mode');
        if (savedViewMode === 'grid') {
            tableView.style.display = 'none';
            gridView.style.display = 'block';
            toggleGridBtn.classList.add('active');
        } else {
            toggleTableBtn.classList.add('active');
        }
        
        // Обработчики событий для кнопок переключения
        toggleTableBtn.addEventListener('click', function() {
            tableView.style.display = 'block';
            gridView.style.display = 'none';
            toggleTableBtn.classList.add('active');
            toggleGridBtn.classList.remove('active');
            localStorage.setItem('news_view_mode', 'table');
        });
        
        toggleGridBtn.addEventListener('click', function() {
            tableView.style.display = 'none';
            gridView.style.display = 'block';
            toggleGridBtn.classList.add('active');
            toggleTableBtn.classList.remove('active');
            localStorage.setItem('news_view_mode', 'grid');
        });
    });
</script>
@endsection
