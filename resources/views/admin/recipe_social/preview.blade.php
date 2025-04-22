@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="row mb-4 align-items-center">
        <div class="col-lg-6">
            <h1><i class="fas fa-share-alt text-primary me-2"></i> Предпросмотр публикации</h1>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb">
                    <li class="breadcrumb-item"><a href="{{ route('admin.recipes.index') }}">Рецепты</a></li>
                    <li class="breadcrumb-item active">Предпросмотр публикации "{{ $recipe->title }}"</li>
                </ol>
            </nav>
        </div>
        <div class="col-lg-6 text-end">
            <div class="d-flex justify-content-end">
                <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-outline-primary me-2" target="_blank">
                    <i class="fas fa-eye me-1"></i> Просмотр рецепта
                </a>
                <a href="{{ route('admin.recipes.edit', $recipe->id) }}" class="btn btn-outline-secondary me-2">
                    <i class="fas fa-edit me-1"></i> Редактировать рецепт
                </a>
                <a href="{{ route('admin.recipes.index') }}" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-1"></i> К списку рецептов
                </a>
            </div>
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
        <!-- Информация о рецепте -->
        <div class="col-md-4">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i> Информация о рецепте</h5>
                </div>
                <div class="card-body">
                    <div class="text-center mb-3">
                        <img src="/{{ $imageUrl }}" alt="{{ $recipe->title }}" class="img-fluid rounded" style="max-height: 200px;">
                    </div>
                    
                    <h5 class="card-title">{{ $recipe->title }}</h5>
                    
                    @if($recipe->description)
                        <p class="card-text text-muted small">{{ Str::limit($recipe->description, 150) }}</p>
                    @endif
                    
                    <div class="row mt-3">
                        <div class="col-6">
                            <p class="mb-1"><i class="fas fa-clock text-secondary me-1"></i> {{ $recipe->cooking_time }} мин</p>
                            
                            @if($recipe->servings)
                                <p class="mb-1"><i class="fas fa-users text-secondary me-1"></i> {{ $recipe->servings }} порц.</p>
                            @endif
                        </div>
                        <div class="col-6">
                            @if($recipe->calories)
                                <p class="mb-1"><i class="fas fa-fire text-secondary me-1"></i> {{ $recipe->calories }} ккал</p>
                            @endif
                        </div>
                    </div>
                    
                    <!-- SEO-информация о рецепте -->
                    <div class="mt-3">
                        <p class="mb-1"><strong>SEO-данные:</strong></p>
                        <div class="alert alert-info py-2 small">
                            <div class="mb-1"><i class="fas fa-heading me-1"></i> <strong>Title:</strong> {{ $recipe->seo_title ?? $recipe->title }}</div>
                            <div><i class="fas fa-align-left me-1"></i> <strong>Description:</strong> {{ Str::limit($recipe->seo_description ?? $recipe->description, 100) }}</div>
                        </div>
                    </div>
                    
                    @if($recipe->categories && $recipe->categories->count() > 0)
                        <div class="mt-3">
                            <p class="mb-1"><strong>Категории:</strong></p>
                            @foreach($recipe->categories as $category)
                                <span class="badge bg-secondary">{{ $category->name }}</span>
                            @endforeach
                        </div>
                    @endif
                    
                    <div class="mt-3">
                        <p class="mb-1"><strong>История публикаций:</strong></p>
                        @if($recipe->socialPosts && $recipe->socialPosts->count() > 0)
                            <ul class="list-group list-group-flush small">
                                @foreach($recipe->socialPosts as $post)
                                    <li class="list-group-item px-0">
                                        @if($post->platform == 'telegram')
                                            <i class="fab fa-telegram text-primary me-1"></i>
                                        @elseif($post->platform == 'zen')
                                            <i class="fas fa-rss text-warning me-1"></i>
                                        @elseif($post->vk_status)
                                            <i class="fab fa-vk text-primary me-1"></i>
                                        @endif
                                        
                                        {{ ucfirst($post->platform ?? ($post->vk_status ? 'vkontakte' : 'unknown')) }} - 
                                        @if($post->vk_status && $post->vk_posted_at)
                                            {{ $post->vk_posted_at->format('d.m.Y H:i') }}
                                        @elseif(is_string($post->published_at))
                                            {{ $post->published_at }}
                                        @elseif($post->published_at)
                                            {{ $post->published_at->format('d.m.Y H:i') }}
                                        @else
                                            Не опубликован
                                        @endif
                                        
                                        @if($post->post_url)
                                            <a href="{{ $post->post_url }}" target="_blank" class="ms-1">
                                                <i class="fas fa-external-link-alt"></i>
                                            </a>
                                        @endif
                                    </li>
                                @endforeach
                            </ul>
                        @else
                            <div class="alert alert-light py-2 small">
                                <i class="fas fa-info-circle me-1"></i> Рецепт еще не публиковался
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Панель публикации -->
        <div class="col-md-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0"><i class="fas fa-paper-plane me-2"></i> Публикация в соцсети</h5>
                </div>
                
                <div class="card-body">
                    <form action="{{ route('admin.recipe-social.publish', $recipe->id) }}" method="POST">
                        @csrf
                        <div class="form-group mb-4">
                            <label for="title">Заголовок публикации</label>
                            <input type="text" name="title" id="title" class="form-control" value="{{ $recipe->title }}">
                        </div>
                        
                        <div class="form-group mb-4">
                            <label for="content">Текст публикации</label>
                            <textarea name="content" id="content" class="form-control" rows="10">{{ $content }}</textarea>
                            <small class="text-muted">Поддерживается базовое форматирование: *курсив*, **жирный**</small>
                        </div>
                        
                        <div class="form-group mb-4">
                            <label>Выберите социальные сети для публикации</label>
                            <div class="d-flex flex-wrap gap-3 mt-2">
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="publish_to_telegram" name="publish_to_telegram" value="1" checked>
                                    <label class="form-check-label" for="publish_to_telegram">
                                        <i class="fab fa-telegram text-info me-1"></i> Telegram
                                    </label>
                                </div>
                                <div class="form-check form-check-inline">
                                    <input class="form-check-input" type="checkbox" id="publish_to_vk" name="publish_to_vk" value="1">
                                    <label class="form-check-label" for="publish_to_vk">
                                        <i class="fab fa-vk text-primary me-1"></i> ВКонтакте
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-paper-plane me-1"></i> Опубликовать
                        </button>
                    </form>
                    
                    <hr class="my-4">
                    
                    <h5 class="mb-3">Предпросмотр</h5>
                    <ul class="nav nav-tabs" id="previewTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="telegram-tab" data-bs-toggle="tab" data-bs-target="#telegram-preview" type="button" role="tab" aria-controls="telegram-preview" aria-selected="true">
                                <i class="fab fa-telegram me-1"></i> Telegram
                            </button>
                        </li>
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="vk-tab" data-bs-toggle="tab" data-bs-target="#vk-preview" type="button" role="tab" aria-controls="vk-preview" aria-selected="false">
                                <i class="fab fa-vk me-1"></i> ВКонтакте
                            </button>
                        </li>
                    </ul>
                    <div class="tab-content p-3 border border-top-0 rounded-bottom" id="previewTabsContent">
                        <!-- Telegram Preview -->
                        <div class="tab-pane fade show active" id="telegram-preview" role="tabpanel" aria-labelledby="telegram-tab">
                            <div class="telegram-preview p-3 bg-light rounded">
                                @if($imageUrl)
                                    <div class="text-center mb-3">
                                        <img src="{{ $imageUrl }}" alt="{{ $recipe->title }}" class="img-fluid rounded" style="max-height: 200px;">
                                    </div>
                                @endif
                                <div class="telegram-content" style="white-space: pre-wrap; font-family: monospace;">
                                    {{ $content }}
                                </div>
                            </div>
                        </div>
                        
                        <!-- VK Preview -->
                        <div class="tab-pane fade" id="vk-preview" role="tabpanel" aria-labelledby="vk-tab">
                            <div class="vk-preview p-3 bg-light rounded">
                                @if($imageUrl)
                                    <div class="text-center mb-3">
                                        <img src="{{ $imageUrl }}" alt="{{ $recipe->title }}" class="img-fluid rounded" style="max-height: 200px;">
                                    </div>
                                @endif
                                <div class="vk-content mb-3">
                                    <h5 class="vk-title">{{ $recipe->title }}</h5>
                                    <div style="white-space: pre-wrap;">{{ str_replace('*', '', $content) }}</div>
                                </div>
                                <div class="vk-footer d-flex align-items-center">
                                    <button class="btn btn-sm btn-outline-primary me-2" disabled>
                                        <i class="fas fa-thumbs-up"></i> Нравится
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary me-2" disabled>
                                        <i class="fas fa-comment"></i> Комментировать
                                    </button>
                                    <button class="btn btn-sm btn-outline-primary" disabled>
                                        <i class="fas fa-share"></i> Поделиться
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="mt-4">
                        <h5>Индивидуальная публикация:</h5>
                        <div class="d-flex flex-wrap gap-2 mt-2">
                            <form action="{{ route('admin.recipes.social.telegram', $recipe->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-info">
                                    <i class="fab fa-telegram me-1"></i> Только в Telegram
                                </button>
                            </form>
                            
                            <form action="{{ route('admin.recipes.social.vk', $recipe->id) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="btn btn-primary">
                                    <i class="fab fa-vk me-1"></i> Только во ВКонтакте
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .telegram-preview {
        border-left: 4px solid #0088cc;
    }
    .telegram-content {
        line-height: 1.5;
        color: #333;
    }
    .vk-preview {
        border-left: 4px solid #4a76a8;
    }
    .vk-title {
        font-weight: bold;
        margin-bottom: 10px;
    }
    .vk-content {
        line-height: 1.5;
        color: #333;
    }
</style>
@endpush

@push('scripts')
<script>
    // Предварительный просмотр при редактировании текста
    $(document).ready(function() {
        $('#content').on('input', function() {
            var content = $(this).val();
            var title = $('#title').val();
            
            // Обновляем Telegram preview
            $('.telegram-content').text(content);
            
            // Обновляем VK preview
            $('.vk-title').text(title);
            $('.vk-content div').text(content.replace(/\*/g, ''));
        });
        
        $('#title').on('input', function() {
            var title = $(this).val();
            $('.vk-title').text(title);
        });
    });
</script>
@endpush
