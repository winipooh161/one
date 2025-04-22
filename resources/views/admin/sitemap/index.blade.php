@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4 align-items-center">
        <div class="col-md-6">
            <h1><i class="fas fa-sitemap text-primary me-2"></i> Управление Sitemap</h1>
            <p class="text-muted">Создание и настройка карты сайта для поисковых систем</p>
        </div>
        <div class="col-md-6 text-end">
            <form action="{{ route('admin.sitemap.generate') }}" method="POST" class="d-inline">
                @csrf
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-sync me-2"></i> Сгенерировать Sitemap
                </button>
            </form>
            <a href="{{ url('/sitemap.xml') }}" target="_blank" class="btn btn-outline-primary ms-2">
                <i class="fas fa-external-link-alt me-2"></i> Просмотр Sitemap
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    @if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-7">
            <!-- Основная информация о Sitemap -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Файлы Sitemap</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Файл</th>
                                <th>Записей</th>
                                <th>Размер</th>
                                <th>Последнее обновление</th>
                                <th>Статус</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr>
                                <td>
                                    <a href="{{ url('/sitemap.xml') }}" target="_blank">sitemap.xml</a>
                                    <small class="text-muted d-block">Основной индекс</small>
                                </td>
                                <td>{{ $sitemapInfo['main']['url_count'] }}</td>
                                <td>{{ $sitemapInfo['main']['size'] }}</td>
                                <td>{{ $sitemapInfo['main']['last_modified'] }}</td>
                                <td>
                                    @if($sitemapInfo['main']['exists'])
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Создан</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Отсутствует</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('/sitemap-recipes.xml') }}" target="_blank">sitemap-recipes.xml</a>
                                    <small class="text-muted d-block">Рецепты</small>
                                </td>
                                <td>{{ $sitemapInfo['recipes']['url_count'] }}</td>
                                <td>{{ $sitemapInfo['recipes']['size'] }}</td>
                                <td>{{ $sitemapInfo['recipes']['last_modified'] }}</td>
                                <td>
                                    @if($sitemapInfo['recipes']['exists'])
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Создан</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Отсутствует</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('/sitemap-categories.xml') }}" target="_blank">sitemap-categories.xml</a>
                                    <small class="text-muted d-block">Категории</small>
                                </td>
                                <td>{{ $sitemapInfo['categories']['url_count'] }}</td>
                                <td>{{ $sitemapInfo['categories']['size'] }}</td>
                                <td>{{ $sitemapInfo['categories']['last_modified'] }}</td>
                                <td>
                                    @if($sitemapInfo['categories']['exists'])
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Создан</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Отсутствует</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('/sitemap-pagination.xml') }}" target="_blank">sitemap-pagination.xml</a>
                                    <small class="text-muted d-block">Страницы пагинации</small>
                                </td>
                                <td>{{ $sitemapInfo['pagination']['url_count'] }}</td>
                                <td>{{ $sitemapInfo['pagination']['size'] }}</td>
                                <td>{{ $sitemapInfo['pagination']['last_modified'] }}</td>
                                <td>
                                    @if($sitemapInfo['pagination']['exists'])
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Создан</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Отсутствует</span>
                                    @endif
                                </td>
                            </tr>
                            <tr>
                                <td>
                                    <a href="{{ url('/sitemap-static.xml') }}" target="_blank">sitemap-static.xml</a>
                                    <small class="text-muted d-block">Статические страницы</small>
                                </td>
                                <td>{{ $sitemapInfo['static']['url_count'] }}</td>
                                <td>{{ $sitemapInfo['static']['size'] }}</td>
                                <td>{{ $sitemapInfo['static']['last_modified'] }}</td>
                                <td>
                                    @if($sitemapInfo['static']['exists'])
                                        <span class="badge bg-success"><i class="fas fa-check me-1"></i> Создан</span>
                                    @else
                                        <span class="badge bg-danger"><i class="fas fa-times me-1"></i> Отсутствует</span>
                                    @endif
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-5">
            <!-- Информация и статистика -->
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Статистика контента</h3>
                </div>
                <div class="card-body">
                    <div class="info-box bg-light">
                        <div class="info-box-content">
                            <span class="info-box-text text-center text-muted">Всего рецептов в Sitemap</span>
                            <span class="info-box-number text-center text-muted mb-0">{{ $sitemapInfo['total_recipes'] }}</span>
                        </div>
                    </div>
                    <div class="info-box bg-light">
                        <div class="info-box-content">
                            <span class="info-box-text text-center text-muted">Всего категорий в Sitemap</span>
                            <span class="info-box-number text-center text-muted mb-0">{{ $sitemapInfo['total_categories'] }}</span>
                        </div>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <h5><i class="icon fas fa-info"></i> Информация</h5>
                        <ul class="mb-0">
                            <li>Файлы sitemap обновляются при запуске команды генерации</li>
                            <li>Рекомендуется обновлять карту сайта при добавлении нового контента</li>
                            <li>Для автоматического обновления настройте cron-задачу</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Инструкции по подключению -->
            <div class="card mt-3">
                <div class="card-header">
                    <h3 class="card-title">Подключение Sitemap</h3>
                </div>
                <div class="card-body">
                    <p><strong>Добавьте в robots.txt:</strong></p>
                    <pre><code>Sitemap: {{ url('/sitemap.xml') }}</code></pre>
                    
                    <p class="mt-3"><strong>Отправьте в Google:</strong></p>
                    <a href="https://search.google.com/search-console" target="_blank" class="btn btn-outline-primary">
                        <i class="fab fa-google me-1"></i> Google Search Console
                    </a>
                    
                    <p class="mt-3"><strong>Отправьте в Яндекс:</strong></p>
                    <a href="https://webmaster.yandex.ru" target="_blank" class="btn btn-outline-primary">
                        <i class="fab fa-yandex me-1"></i> Яндекс.Вебмастер
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
