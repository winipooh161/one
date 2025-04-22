@extends('layouts.app')

@section('title', $title ?? 'Все рецепты')
@section('description', $description ?? 'Список всех рецептов на сайте')
@section('keywords', $keywords ?? 'рецепты, кулинария, список')

@section('meta_tags')
    <title>{{ $pageTitle ?? 'Все рецепты' }} | {{ config('app.name') }}</title>
    <meta name="description" content="{{ $pageDescription ?? 'Кулинарные рецепты с подробными инструкциями, фото и списком ингредиентов. Найдите свой идеальный рецепт!' }}">
    <meta name="keywords" content="рецепты, кулинария, готовка, еда, блюда{{ !empty($search) ? ', ' . $search : '' }}">
    <link rel="canonical" href="{{ $canonicalUrl ?? route('recipes.index') }}" />
    
    @if(isset($paginationLinks['prev']))
        <link rel="prev" href="{{ $paginationLinks['prev'] }}" />
    @endif
    
    @if(isset($paginationLinks['next']))
        <link rel="next" href="{{ $paginationLinks['next'] }}" />
    @endif
    
    <meta property="og:title" content="{{ $pageTitle ?? 'Все рецепты' }} | {{ config('app.name') }}">
    <meta property="og:description" content="{{ $pageDescription ?? 'Кулинарные рецепты с подробными инструкциями, фото и списком ингредиентов. Найдите свой идеальный рецепт!' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('images/recipes-share.jpg') }}">
@endsection

@section('schema_org')
    @if(isset($schemaData))
        <script type="application/ld+json">
            {!! $schemaData !!}
        </script>
    @else
        <script type="application/ld+json">
        {
            "@context": "https://schema.org",
            "@type": "CollectionPage",
            "name": "{{ $pageTitle ?? 'Все рецепты' }}",
            "description": "{{ $pageDescription ?? 'Кулинарные рецепты с подробными инструкциями, фото и списком ингредиентов.' }}",
            "url": "{{ url()->current() }}",
            "mainEntity": {
                "@type": "ItemList",
                "numberOfItems": {{ $recipes->total() }},
                "itemListElement": [
                    @foreach($recipes as $index => $recipe)
                    {
                        "@type": "ListItem",
                        "position": {{ ($recipes->currentPage() - 1) * $recipes->perPage() + $loop->iteration }},
                        "item": {
                            "@type": "Recipe",
                            "name": "{{ $recipe->title }}",
                            "url": "{{ route('recipes.show', $recipe->slug) }}",
                            "image": "{{ $recipe->image_url }}",
                            "description": "{{ Str::limit($recipe->description, 150) }}",
                            "author": {
                                "@type": "Person",
                                "name": "{{ $recipe->user ? $recipe->user->name : config('app.name') }}"
                            },
                            "datePublished": "{{ $recipe->created_at->toIso8601String() }}"
                        }
                    }{{ !$loop->last ? ',' : '' }}
                    @endforeach
                ]
            },
            "breadcrumb": {
                "@type": "BreadcrumbList",
                "itemListElement": [
                    {
                        "@type": "ListItem",
                        "position": 1,
                        "name": "Главная",
                        "item": "{{ url('/') }}"
                    },
                    {
                        "@type": "ListItem",
                        "position": 2,
                        "name": "Рецепты",
                        "item": "{{ route('recipes.index') }}"
                    }
                    @if(!empty($search))
                    ,{
                        "@type": "ListItem",
                        "position": 3,
                        "name": "Поиск: {{ $search }}",
                        "item": "{{ url()->full() }}"
                    }
                    @endif
                ]
            }
        }
        </script>
    @endif
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
        <span itemprop="name">Рецепты</span>
        <meta itemprop="position" content="2" />
    </li>
    @if(!empty($search))
        <li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
            <span itemprop="name">Поиск: {{ $search }}</span>
            <meta itemprop="position" content="3" />
        </li>
    @endif
@endsection

@section('seo')
    @include('seo.recipes_seo', ['recipes' => $recipes, 'request' => request(), 'seo' => app('App\Services\SeoService')])
@endsection

@section('content')
<div class="container">
    <h1 class="mb-4">
        @if(!empty($search))
            Поиск рецептов: {{ $search }}
        @elseif(!empty($categoryId) && ($category = $categories->firstWhere('id', $categoryId)))
            Рецепты в категории "{{ $category->name }}"
        @else
            Все рецепты
        @endif
    </h1>
    
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('recipes.index') }}" method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Поиск рецептов</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-search"></i></span>
                        <input type="text" class="form-control" id="search" name="search" value="{{ $search ?? '' }}" placeholder="Введите название или ингредиент...">
                    </div>
                </div>
                <div class="col-md-4">
                    <label for="category" class="form-label">Категория</label>
                    <select class="form-select" id="category" name="category_id">
                        <option value="">Все категории</option>
                        @foreach($categories as $cat)
                            <option value="{{ $cat->id }}" {{ isset($categoryId) && $categoryId == $cat->id ? 'selected' : '' }}>
                                {{ $cat->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search me-1"></i> Найти
                    </button>
                </div>
            </form>
        </div>
    </div>
    
    @if(!empty($search) || !empty($categoryId) || !empty($cookingTime))
        <div class="active-filters mb-4">
            <div class="d-flex flex-wrap align-items-center">
                <span class="me-2">Активные фильтры:</span>
                
                @if(!empty($search))
                    <span class="badge bg-primary me-2 mb-2">
                        Поиск: {{ $search }}
                        <a href="{{ route('recipes.index', array_merge(request()->except('search'), ['page' => null])) }}" class="text-white ms-1">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                @endif
                
                @if(!empty($categoryId))
                    <span class="badge bg-primary me-2 mb-2">
                        Категория: {{ $categories->firstWhere('id', $categoryId)->name }}
                        <a href="{{ route('recipes.index', array_merge(request()->except('category'), ['page' => null])) }}" class="text-white ms-1">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                @endif
                
                @if(!empty($cookingTime))
                    <span class="badge bg-primary me-2 mb-2">
                        Время: до {{ $cookingTime }} минут
                        <a href="{{ route('recipes.index', array_merge(request()->except('cooking_time'), ['page' => null])) }}" class="text-white ms-1">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                @endif
                
                @if(($sort ?? '') != 'latest')
                    <span class="badge bg-primary me-2 mb-2">
                        Сортировка: 
                        @if(($sort ?? '') == 'popular')
                            По популярности
                        @elseif(($sort ?? '') == 'cooking_time_asc')
                            Время (по возрастанию)
                        @elseif(($sort ?? '') == 'cooking_time_desc')
                            Время (по убыванию)
                        @endif
                        <a href="{{ route('recipes.index', array_merge(request()->except('sort'), ['page' => null])) }}" class="text-white ms-1">
                            <i class="fas fa-times"></i>
                        </a>
                    </span>
                @endif
                
                <a href="{{ route('recipes.index') }}" class="btn btn-sm btn-outline-danger ms-auto mb-2">
                    <i class="fas fa-times me-1"></i> Сбросить все
                </a>
            </div>
        </div>
    @endif
    
    @if($recipes->total() > 0)
        <!-- Статистика по результатам -->
        <div class="mb-4">
            <p class="text-muted">
                <i class="fas fa-info-circle me-1"></i> 
                Найдено {{ $recipes->total() }} {{ trans_choice('рецепт|рецепта|рецептов', $recipes->total()) }}
                @if($recipes->hasPages())
                    , страница {{ $recipes->currentPage() }} из {{ $recipes->lastPage() }}
                @endif
            </p>
        </div>
        
        <!-- Список рецептов -->
        <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
            @foreach($recipes as $recipe)
                <div class="col">
                    <div class="card h-100 recipe-card border-0 shadow-sm">
                        <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none text-dark">
                            <img src="{{ $recipe->image_url }}" 
                                 class="card-img-top" 
                                 alt="{{ $recipe->title }}" 
                                 onerror="window.handleImageError(this)">
                        </a>
                        
                        <div class="card-body">
                            <h2 class="h5 card-title mb-2">
                                <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none text-dark stretched-link">
                                    @if(!empty($searchTerms))
                                        {!! preg_replace('/(' . implode('|', array_map('preg_quote', $searchTerms)) . ')/iu', '<mark>$1</mark>', $recipe->title) !!}
                                    @else
                                        {{ $recipe->title }}
                                    @endif
                                </a>
                            </h2>
                            
                            <p class="card-text text-muted small mb-3">
                                @if(!empty($searchTerms) && $recipe->description)
                                    {!! Str::limit(preg_replace('/(' . implode('|', array_map('preg_quote', $searchTerms)) . ')/iu', '<mark>$1</mark>', $recipe->description), 100) !!}
                                @else
                                    {{ Str::limit($recipe->description, 100) }}
                                @endif
                            </p>
                            
                            <div class="recipe-meta d-flex justify-content-between align-items-center mb-2">
                                <div>
                                    @if($recipe->categories->isNotEmpty())
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-tag me-1"></i> {{ $recipe->categories->first()->name }}
                                        </span>
                                    @endif
                                    
                                    @if($recipe->calories)
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-fire me-1"></i> {{ $recipe->calories }} ккал
                                        </span>
                                    @endif
                                </div>
                                
                                <span class="badge bg-light text-dark">
                                    <i class="far fa-eye me-1"></i> {{ $recipe->views }}
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        
        <!-- Пагинация -->
        <div class="d-flex justify-content-center mt-4">
            {{ $recipes->withQueryString()->onEachSide(1)->links() }}
        </div>
    @else
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> По вашему запросу ничего не найдено. Попробуйте изменить параметры поиска.
        </div>
        
        @if(!empty($search) && !empty($searchSuggestions))
            <div class="card mb-4">
                <div class="card-body">
                    <h3 class="h5 mb-3">Возможно, вы искали:</h3>
                    <div class="d-flex flex-wrap gap-2">
                        @foreach($searchSuggestions as $suggestion)
                            <a href="{{ route('recipes.index', ['search' => $suggestion]) }}" class="badge rounded-pill bg-light text-dark text-decoration-none p-2">
                                {{ $suggestion }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
        @endif
        
        <!-- Предложение популярных категорий при отсутствии результатов -->
        <div class="row mt-4">
            <div class="col-12">
                <h3 class="h5 mb-3">Популярные категории:</h3>
                <div class="row">
                    @foreach($categories->take(8) as $category)
                        <div class="col-6 col-md-3 mb-3">
                            <a href="{{ route('recipes.index', ['category' => $category->id]) }}" class="btn btn-outline-primary w-100">
                                {{ $category->name }} ({{ $category->recipes_count }})
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Скрипт для адаптивных фильтров
        const advancedFilters = document.getElementById('advancedFilters');
        const whenCollapsed = document.querySelector('.when-collapsed');
        const whenExpanded = document.querySelector('.when-expanded');
        
        if (advancedFilters && whenCollapsed && whenExpanded) {
            if (advancedFilters.classList.contains('show')) {
                whenCollapsed.style.display = 'none';
                whenExpanded.style.display = 'inline';
            } else {
                whenCollapsed.style.display = 'inline';
                whenExpanded.style.display = 'none';
            }
            
            advancedFilters.addEventListener('shown.bs.collapse', function () {
                whenCollapsed.style.display = 'none';
                whenExpanded.style.display = 'inline';
            });
            
            advancedFilters.addEventListener('hidden.bs.collapse', function () {
                whenCollapsed.style.display = 'inline';
                whenExpanded.style.display = 'none';
            });
        }
        
        // Автоматическая отправка формы при изменении селектов
        const formSelects = document.querySelectorAll('form select');
        formSelects.forEach(select => {
            select.addEventListener('change', function() {
                this.form.submit();
            });
        });
        
        // Автоматическая отправка формы при клике на чекбокс
        const hasImageCheckbox = document.getElementById('has_image');
        if (hasImageCheckbox) {
            hasImageCheckbox.addEventListener('change', function() {
                this.form.submit();
            });
        }
    });
</script>

<style>
    .recipe-card {
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .recipe-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    mark {
        background-color: #fff9aa;
        padding: 0;
    }
    
    .badge {
        font-weight: normal;
    }
    
    /* Пагинация */
    .pagination {
        --bs-pagination-active-bg: #0d6efd;
        --bs-pagination-active-border-color: #0d6efd;
    }
    
    /* Адаптивные стили */
    @media (max-width: 768px) {
        .when-collapsed, .when-expanded {
            font-size: 0.875rem;
        }
    }
</style>
@endsection
