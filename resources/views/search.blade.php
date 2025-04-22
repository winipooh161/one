@extends('layouts.search')

@section('title', $title ?? 'Поиск рецептов')
@section('description', $description ?? 'Результаты поиска рецептов')
@section('keywords', $keywords ?? 'поиск, рецепты, яедок, я едок')

@section('meta_tags')
    <title>{{ $title ?? 'Поиск рецептов' }} | {{ config('app.name') }}</title>
    <meta name="description" content="{{ $description ?? 'Найдите идеальный рецепт с помощью нашего удобного поиска. Фильтруйте по категориям и времени приготовления.' }}">
    
    <link rel="canonical" href="{{ $canonicalUrl ?? route('search', request()->except(['page'])) }}" />
    
    @if(isset($paginationLinks['prev']))
        <link rel="prev" href="{{ $paginationLinks['prev'] }}" />
    @endif
    
    @if(isset($paginationLinks['next']))
        <link rel="next" href="{{ $paginationLinks['next'] }}" />
    @endif
    
    <!-- Запрет индексации страниц поиска и страниц с параметрами -->
    @if(!empty($query) || request()->has('sort') || request()->has('category') || request()->has('cooking_time') || (request()->has('page') && request()->input('page') > 1))
        <meta name="robots" content="noindex,follow">
    @endif
    
    <meta property="og:title" content="{{ $title ?? 'Поиск рецептов' }} | {{ config('app.name') }}">
    <meta property="og:description" content="{{ $description ?? 'Найдите идеальный рецепт с помощью нашего удобного поиска.' }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta property="og:type" content="website">
    <meta property="og:image" content="{{ asset('images/search-cover.jpg') }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    
    <!-- Twitter Card data -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Поиск рецептов' }} | {{ config('app.name') }}">
    <meta name="twitter:description" content="{{ $description ?? 'Найдите идеальный рецепт с помощью нашего удобного поиска.' }}">
    <meta name="twitter:image" content="{{ asset('images/search-cover.jpg') }}">
@endsection

@section('schema_org')
    @if(isset($schemaData))
        <script type="application/ld+json">
            {!! $schemaData !!} 
        </script>
    @endif
    @include('schema_org.search_schema', [
        'query' => $query ?? '',
        'recipes' => $recipes ?? collect([]),
        'resultsCount' => isset($recipes) && method_exists($recipes, 'total') ? $recipes->total() : 0
    ])
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <span itemprop="name">Поиск</span>
        <meta itemprop="position" content="2" />
        <meta itemprop="item" content="{{ route('search') }}">
    </li>
    
    @if(!empty($query))
        <li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <span itemprop="name">{{ $query }}</span>
            <meta itemprop="position" content="3" />
            <meta itemprop="item" content="{{ route('search', ['query' => $query]) }}">
        </li>
    @endif
@endsection

@section('seo')
    @include('seo.search_seo', [
        'query' => $query ?? null,
        'recipes' => $recipes ?? collect(),
        'recipesCount' => isset($recipes) && method_exists($recipes, 'total') ? $recipes->total() : 0
    ])
@endsection

@section('content')
<div class="container py-4" itemscope itemtype="https://schema.org/SearchResultsPage">
    <div class="row">
        <div class="col-lg-8">
            <h1 class="mb-4" itemprop="name">{{ !empty($query) ? 'Поиск: ' . $query : 'Поиск рецептов' }}</h1>
            <meta itemprop="description" content="{{ !empty($query) ? 'Результаты поиска по запросу: ' . $query : 'Поиск кулинарных рецептов' }}">
            
            <!-- Форма поиска -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-body">
                    <form action="{{ route('search') }}" method="GET" class="row g-3" id="search-form" role="search" aria-label="Форма поиска рецептов">
                        <div class="col-md-8">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search" aria-hidden="true"></i></span>
                                <input type="text" name="query" class="form-control" value="{{ $query ?? '' }}" 
                                       placeholder="Введите название блюда или ингредиент..." 
                                       autofocus aria-label="Поисковый запрос">
                                <button type="submit" class="btn btn-primary" aria-label="Искать">Найти</button>
                            </div>
                        </div>
                        
                        <div class="col-md-4">
                            <label for="sort" class="visually-hidden">Сортировка результатов</label>
                            <select name="sort" id="sort" class="form-select" onchange="this.form.submit()" 
                                    aria-label="Сортировка результатов">
                                <option value="relevance" {{ ($sort ?? '') == 'relevance' ? 'selected' : '' }}>По релевантности</option>
                                <option value="popular" {{ ($sort ?? '') == 'popular' ? 'selected' : '' }}>По популярности</option>
                                <option value="latest" {{ ($sort ?? '') == 'latest' ? 'selected' : '' }}>Сначала новые</option>
                                <option value="cooking_time_asc" {{ ($sort ?? '') == 'cooking_time_asc' ? 'selected' : '' }}>По времени (возр.)</option>
                                <option value="cooking_time_desc" {{ ($sort ?? '') == 'cooking_time_desc' ? 'selected' : '' }}>По времени (убыв.)</option>
                            </select>
                        </div>
                        
                        <!-- Расширенные фильтры -->
                        <div class="col-12">
                            <div class="collapse {{ request()->has('category') || request()->has('cooking_time') ? 'show' : '' }}" id="advancedFilters">
                                <div class="row g-3 mt-2">
                                    <div class="col-md-6">
                                        <label for="category" class="form-label">Категория</label>
                                        <select name="category" id="category" class="form-select" aria-label="Фильтр по категории">
                                            <option value="">Все категории</option>
                                            @foreach($categories as $category)
                                                <option value="{{ $category->id }}" {{ (request('category') == $category->id) ? 'selected' : '' }}>
                                                    {{ $category->name }} ({{ $category->recipes_count }})
                                                </option>
                                            @endforeach
                                        </select>
                                    </div>
                                    
                                    <div class="col-md-6">
                                        <label for="cooking_time" class="form-label">Время приготовления</label>
                                        <select name="cooking_time" id="cooking_time" class="form-select" aria-label="Фильтр по времени приготовления">
                                            <option value="">Любое время</option>
                                            <option value="15" {{ request('cooking_time') == '15' ? 'selected' : '' }}>До 15 минут</option>
                                            <option value="30" {{ request('cooking_time') == '30' ? 'selected' : '' }}>До 30 минут</option>
                                            <option value="60" {{ request('cooking_time') == '60' ? 'selected' : '' }}>До 1 часа</option>
                                            <option value="120" {{ request('cooking_time') == '120' ? 'selected' : '' }}>До 2 часов</option>
                                        </select>
                                    </div>
                                </div>
                                
                                <div class="row mt-3">
                                    <div class="col-12 d-flex justify-content-between">
                                        <button type="submit" class="btn btn-primary" aria-label="Применить фильтры">
                                            <i class="fas fa-filter me-1" aria-hidden="true"></i> Применить фильтры
                                        </button>
                                        
                                        <a href="{{ route('search') }}" class="btn btn-outline-secondary" aria-label="Сбросить все фильтры">
                                            <i class="fas fa-undo me-1" aria-hidden="true"></i> Сбросить все
                                        </a>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="text-center mt-2">
                                <button class="btn btn-link text-decoration-none" type="button" 
                                        data-bs-toggle="collapse" data-bs-target="#advancedFilters" 
                                        aria-expanded="{{ request()->has('category') || request()->has('cooking_time') ? 'true' : 'false' }}" 
                                        aria-controls="advancedFilters">
                                    <span class="when-collapsed"><i class="fas fa-sliders-h me-1" aria-hidden="true"></i> Дополнительные фильтры</span>
                                    <span class="when-expanded"><i class="fas fa-chevron-up me-1" aria-hidden="true"></i> Скрыть фильтры</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            
            <!-- Активные фильтры -->
            @if(!empty($query) || request()->has('category') || request()->has('cooking_time') || ($sort ?? '') != 'relevance')
                <div class="mb-4" aria-live="polite" aria-atomic="true">
                    <div class="d-flex flex-wrap align-items-center">
                        <span class="me-2">Активные фильтры:</span>
                        
                        @if(!empty($query))
                            <span class="badge bg-primary me-2 mb-2">
                                Поиск: {{ $query }}
                                <a href="{{ route('search', array_merge(request()->except('query'), ['page' => null])) }}" 
                                   class="text-white ms-1" aria-label="Удалить фильтр '{{ $query }}'">
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                </a>
                            </span>
                        @endif
                        
                        @if(request()->has('category') && request('category'))
                            @php
                                $categoryName = $categories->firstWhere('id', request('category'))->name ?? '';
                            @endphp
                            @if($categoryName)
                                <span class="badge bg-primary me-2 mb-2">
                                    Категория: {{ $categoryName }}
                                    <a href="{{ route('search', array_merge(request()->except('category'), ['page' => null])) }}" 
                                       class="text-white ms-1" aria-label="Удалить фильтр категории '{{ $categoryName }}'">
                                        <i class="fas fa-times" aria-hidden="true"></i>
                                    </a>
                                </span>
                            @endif
                        @endif
                        
                        @if(request()->has('cooking_time') && request('cooking_time'))
                            <span class="badge bg-primary me-2 mb-2">
                                Время: до {{ request('cooking_time') }} минут
                                <a href="{{ route('search', array_merge(request()->except('cooking_time'), ['page' => null])) }}" 
                                   class="text-white ms-1" aria-label="Удалить фильтр времени '{{ request('cooking_time') }} минут'">
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                </a>
                            </span>
                        @endif
                        
                        @if(isset($sort) && $sort != 'relevance')
                            <span class="badge bg-primary me-2 mb-2">
                                Сортировка: 
                                @if($sort == 'popular')
                                    По популярности
                                @elseif($sort == 'latest')
                                    Сначала новые
                                @elseif($sort == 'cooking_time_asc')
                                    По времени (возр.)
                                @elseif($sort == 'cooking_time_desc')
                                    По времени (убыв.)
                                @endif
                                <a href="{{ route('search', array_merge(request()->except('sort'), ['page' => null])) }}" 
                                   class="text-white ms-1" aria-label="Удалить сортировку">
                                    <i class="fas fa-times" aria-hidden="true"></i>
                                </a>
                            </span>
                        @endif
                        
                        <a href="{{ route('search') }}" class="btn btn-sm btn-outline-danger ms-auto mb-2" 
                           aria-label="Сбросить все фильтры">
                            <i class="fas fa-times me-1" aria-hidden="true"></i> Сбросить все
                        </a>
                    </div>
                </div>
            @endif
            
            <!-- Результаты поиска -->
            <div class="search-results" itemprop="mainEntity" itemscope itemtype="https://schema.org/ItemList">
                <meta itemprop="numberOfItems" content="{{ isset($recipes) && method_exists($recipes, 'total') ? $recipes->total() : 0 }}">
                
                @if(isset($recipes) && $recipes->total() > 0)
                    <!-- Информация о результатах -->
                    <div class="alert alert-info mb-4">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <i class="fas fa-info-circle me-2" aria-hidden="true"></i> 
                                Найдено: <strong>{{ $recipes->total() }}</strong> {{ trans_choice('рецепт|рецепта|рецептов', $recipes->total()) }}
                            </div>
                            
                            @if($recipes->hasPages())
                                <div class="small text-muted">
                                    Страница {{ $recipes->currentPage() }} из {{ $recipes->lastPage() }}
                                </div>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Список рецептов -->
                    <div class="row row-cols-1 row-cols-md-2 g-4">
                        @foreach($recipes as $recipe)
                            <div class="col">
                                <article class="card h-100 recipe-card border-0 shadow-sm" itemprop="itemListElement" 
                                         itemscope itemtype="https://schema.org/ListItem">
                                    <meta itemprop="position" content="{{ ($recipes->currentPage() - 1) * $recipes->perPage() + $loop->iteration }}">
                                    
                                    <div class="position-relative">
                                        <a href="{{ route('recipes.show', $recipe->slug) }}" itemprop="url">
                                            <img src="{{ $recipe->image_url }}" 
                                                class="card-img-top" 
                                                loading="{{ $loop->iteration > 2 ? 'lazy' : 'eager' }}" 
                                                alt="{{ $recipe->title }}" 
                                                width="100%" 
                                                height="200"
                                                style="object-fit: cover;"
                                                itemprop="image"
                                                onerror="window.handleImageError(this)">
                                        </a>
                                        
                                        @if($recipe->cooking_time)
                                            <span class="position-absolute top-0 end-0 badge bg-primary m-2">
                                                <i class="far fa-clock me-1" aria-hidden="true"></i> {{ $recipe->cooking_time }} мин
                                            </span>
                                        @endif
                                        
                                        @if(isset($recipe->relevance_percent) && !empty($query))
                                            <span class="position-absolute top-0 start-0 badge bg-{{ $recipe->relevance_percent > 75 ? 'success' : ($recipe->relevance_percent > 50 ? 'info' : 'secondary') }} m-2">
                                                {{ $recipe->relevance_percent }}% совпадение
                                            </span>
                                        @endif
                                    </div>
                                    
                                    <div class="card-body" itemprop="item" itemscope itemtype="https://schema.org/Recipe">
                                        <meta itemprop="url" content="{{ route('recipes.show', $recipe->slug) }}">
                                        
                                        <h2 class="h5 card-title">
                                            <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none text-dark" itemprop="name">
                                                @if(isset($searchHighlight[$recipe->id]['title']))
                                                    {!! $searchHighlight[$recipe->id]['title'] !!}
                                                @else
                                                    {{ $recipe->title }}
                                                @endif
                                            </a>
                                        </h2>
                                        
                                        <p class="card-text text-muted small" itemprop="description">
                                            @if(isset($searchHighlight[$recipe->id]['description']))
                                                {!! $searchHighlight[$recipe->id]['description'] !!}
                                            @else
                                                {{ Str::limit($recipe->description, 80) }}
                                            @endif
                                        </p>
                                        
                                        @if($recipe->cooking_time)
                                            <meta itemprop="cookTime" content="PT{{ $recipe->cooking_time }}M">
                                        @endif
                                        
                                        @if($recipe->categories->isNotEmpty())
                                            <meta itemprop="recipeCategory" content="{{ $recipe->categories->first()->name }}">
                                        @endif
                                        
                                        <div class="recipe-meta d-flex justify-content-between align-items-center">
                                            <div>
                                                @if($recipe->categories->isNotEmpty())
                                                    <span class="badge bg-light text-dark">
                                                        <i class="fas fa-tag me-1" aria-hidden="true"></i> {{ $recipe->categories->first()->name }}
                                                    </span>
                                                @endif
                                                
                                                <span class="badge bg-light text-dark">
                                                    <i class="far fa-eye me-1" aria-hidden="true"></i> {{ $recipe->views }}
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                    
                                    <div class="card-footer bg-white border-0">
                                        <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-primary w-100">
                                            <i class="fas fa-utensils me-1" aria-hidden="true"></i> Смотреть рецепт
                                        </a>
                                    </div>
                                </article>
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Пагинация -->
                    <nav aria-label="Пагинация результатов поиска" class="mt-4">
                        <div class="d-flex justify-content-center">
                            {{ $recipes->withQueryString()->onEachSide(1)->links() }}
                        </div>
                    </nav>
                @elseif(!empty($query))
                    <div class="alert alert-info" role="alert">
                        <i class="fas fa-search me-2" aria-hidden="true"></i> По запросу <strong>"{{ $query }}"</strong> ничего не найдено.
                    </div>
                    
                    @if(!empty($searchSuggestions))
                        <div class="card mb-4">
                            <div class="card-body">
                                <h3 class="h5 mb-3">Возможно, вы искали:</h3>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($searchSuggestions as $suggestion)
                                        <a href="{{ route('search', ['query' => $suggestion]) }}" class="badge rounded-pill bg-light text-dark text-decoration-none p-2">
                                            {{ $suggestion }}
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <section aria-labelledby="popular-recipes-heading">
                        <h3 id="popular-recipes-heading" class="h5 mt-4 mb-3">Популярные рецепты</h3>
                        <div class="row row-cols-1 row-cols-md-2 g-4">
                            @foreach($popularRecipes as $recipe)
                                <div class="col">
                                    <article class="card h-100 recipe-card border-0 shadow-sm">
                                        <div class="position-relative">
                                            <a href="{{ route('recipes.show', $recipe->slug) }}">
                                                <img src="{{ $recipe->image_url }}" class="card-img-top" alt="{{ $recipe->title }}" 
                                                     style="height: 200px; object-fit: cover;"
                                                     loading="lazy"
                                                     onerror="window.handleImageError(this)">
                                            </a>
                                            
                                            @if($recipe->cooking_time)
                                                <span class="position-absolute top-0 end-0 badge bg-primary m-2">
                                                    <i class="far fa-clock me-1" aria-hidden="true"></i> {{ $recipe->cooking_time }} мин
                                                </span>
                                            @endif
                                        </div>
                                        
                                        <div class="card-body" itemscope itemtype="https://schema.org/Recipe">
                                            <meta itemprop="url" content="{{ route('recipes.show', $recipe->slug) }}">
                                            <h3 class="h5 card-title">
                                                <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none text-dark" itemprop="name">
                                                    {{ $recipe->title }}
                                                </a>
                                            </h3>
                                            
                                            <p class="card-text text-muted small" itemprop="description">
                                                {{ Str::limit($recipe->description, 80) }}
                                            </p>
                                            
                                            @if($recipe->cooking_time)
                                                <meta itemprop="cookTime" content="PT{{ $recipe->cooking_time }}M">
                                            @endif
                                            
                                            @if($recipe->categories->isNotEmpty())
                                                <meta itemprop="recipeCategory" content="{{ $recipe->categories->first()->name }}">
                                            @endif
                                        </div>
                                        
                                        <div class="card-footer bg-white border-0">
                                            <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-primary w-100">
                                                <i class="fas fa-utensils me-1" aria-hidden="true"></i> Смотреть рецепт
                                            </a>
                                        </div>
                                    </article>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @else
                    <div class="alert alert-info mb-4" role="alert">
                        <i class="fas fa-info-circle me-2" aria-hidden="true"></i> Введите поисковый запрос, чтобы найти рецепты.
                    </div>
                    
                    <!-- Популярные поисковые запросы -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <h3 class="h5 mb-3">Популярные поисковые запросы:</h3>
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('search', ['query' => 'завтрак']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none p-2">завтрак</a>
                                <a href="{{ route('search', ['query' => 'ужин']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none p-2">ужин</a>
                                <a href="{{ route('search', ['query' => 'десерт']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none п-2">десерт</a>
                                <a href="{{ route('search', ['query' => 'суп']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none п-2">суп</a>
                                <a href="{{ route('search', ['query' => 'салат']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none п-2">салат</a>
                                <a href="{{ route('search', ['query' => 'выпечка']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none п-2">выпечка</a>
                                <a href="{{ route('search', ['query' => 'мясо']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none п-2">мясо</a>
                                <a href="{{ route('search', ['query' => 'рыба']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none п-2">рыба</a>
                                <a href="{{ route('search', ['query' => 'вегетарианский']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none п-2">вегетарианский</a>
                                <a href="{{ route('search', ['query' => 'быстрый рецепт']) }}" class="badge rounded-pill bg-light text-dark text-decoration-none п-2">быстрый рецепт</a>
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </div>
        
        <!-- Боковая панель -->
        <aside class="col-lg-4">
            <!-- Категории -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0"><i class="fas fa-tags me-2" aria-hidden="true"></i> Категории</h3>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach($categories->take(12) as $category)
                            <div class="col-6">
                                <a href="{{ route('search', ['category' => $category->id]) }}" class="btn btn-outline-primary btn-sm d-block text-truncate">
                                    {{ $category->name }}
                                    <span class="badge bg-primary">{{ $category->recipes_count }}</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                    
                    @if($categories->count() > 12)
                        <div class="text-center mt-3">
                            <a href="{{ route('categories.index') }}" class="btn btn-sm btn-outline-secondary">
                                <i class="fas fa-list me-1" aria-hidden="true"></i> Все категории
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            
            <!-- Быстрые фильтры -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0"><i class="fas fa-filter me-2" aria-hidden="true"></i> Быстрые фильтры</h3>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <a href="{{ route('search', ['cooking_time' => 15]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-bolt text-warning me-2" aria-hidden="true"></i> Очень быстрые рецепты</div>
                            <span class="badge bg-primary rounded-pill">до 15 мин</span>
                        </a>
                        <a href="{{ route('search', ['cooking_time' => 30]) }}" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                            <div><i class="fas fa-clock text-success me-2" aria-hidden="true"></i> Быстрые рецепты</div>
                            <span class="badge bg-primary rounded-pill">до 30 мин</span>
                        </a>
                        <a href="{{ route('search', ['sort' => 'popular']) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-fire text-danger me-2" aria-hidden="true"></i> Популярные рецепты
                        </a>
                        <a href="{{ route('search', ['sort' => 'latest']) }}" class="list-group-item list-group-item-action">
                            <i class="fas fa-calendar-alt text-primary me-2" aria-hidden="true"></i> Новые рецепты
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Популярные ингредиенты -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="h5 mb-0"><i class="fas fa-carrot me-2" aria-hidden="true"></i> Популярные ингредиенты</h3>
                </div>
                <div class="card-body">
                    <div class="row g-2">
                        @foreach(['курица', 'говядина', 'свинина', 'рыба', 'грибы', 'сыр', 'творог', 'яйца', 'картофель', 'рис', 'макароны', 'тесто'] as $ingredient)
                            <div class="col-6">
                                <a href="{{ route('search', ['query' => $ingredient]) }}" class="btn btn-outline-secondary btn-sm d-block text-truncate">
                                    {{ $ingredient }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </aside>
    </div>
</div>

<style>
    .recipe-card {
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .recipe-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    .when-collapsed {
        display: inline;
    }
    
    .when-expanded {
        display: none;
    }
    
    #advancedFilters.show ~ div .when-collapsed {
        display: none;
    }
    
    #advancedFilters.show ~ div .when-expanded {
        display: inline;
    }
    
    .highlight {
        background-color: #fff9c4;
        padding: 0 2px;
    }
</style>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Автоматическая отправка формы при изменении селектов
        const formSelects = document.querySelectorAll('form select');
        formSelects.forEach(select => {
            select.addEventListener('change', function() {
                if (this.name === 'sort') {
                    this.form.submit();
                }
            });
        });
        
        // Установка фокуса на поле поиска, только если страница не была прокручена пользователем
        if (window.scrollY === 0) {
            const searchInput = document.querySelector('input[name="query"]');
            if (searchInput) {
                setTimeout(() => {
                    searchInput.focus();
                }, 300);
            }
        }
    });
</script>
@endsection
