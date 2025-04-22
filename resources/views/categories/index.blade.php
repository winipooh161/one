@extends('layouts.categories')

@section('seo')
    @include('seo.categories_index_seo', [
        'popularCategories' => $popularCategories, 
        'categoriesCount' => $categoriesCount ?? count($categoriesByLetter)
    ])
@endsection

@section('title', $title ?? 'Все категории рецептов | Яедок')
@section('description', $description ?? 'Полный каталог кулинарных категорий на сайте Яедок. Найдите рецепты по любой категории блюд.')
@section('keywords', $keywords ?? 'категории рецептов, кулинария, список категорий')

@section('breadcrumbs')
<nav aria-label="breadcrumb" class="mt-3">
    <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="{{ url('/') }}" itemprop="item"><span itemprop="name">Главная</span></a>
            <meta itemprop="position" content="1" />
        </li>
        <li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
            <span itemprop="name">Категории рецептов</span>
            <meta itemprop="position" content="2" />
        </li>
    </ol>
</nav>
@endsection

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col-12">
            <h1 class="mb-3">{{ $title ?? 'Категории рецептов' }}</h1>
            <p class="lead text-muted">{{ $description ?? 'Выберите категорию, чтобы найти вдохновляющие идеи для приготовления.' }}</p>
        </div>
    </div>
    
    <!-- Популярные категории -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded">
                <div class="card-header bg-primary text-white">
                    <h2 class="h4 mb-0"><i class="fas fa-star me-2"></i> Популярные категории</h2>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @foreach($popularCategories as $category)
                            <div class="col-md-3 col-sm-6">
                                <a href="{{ route('categories.show', $category->slug) }}" class="text-decoration-none" title="Рецепты в категории {{ $category->name }}">
                                    <div class="category-image-container">
                                        <img src="{{ asset($category->image_url) }}" 
                                             class="category-image" 
                                             alt="Категория {{ $category->name }}" 
                                             loading="lazy"
                                             onerror="this.onerror=null; this.src='{{ asset('images/category-placeholder.jpg') }}';">
                                    </div>
                                </a>
                                <div class="card h-100 popular-category-card border-0 shadow-sm">
                                    <div class="card-body text-center">
                                        <h3 class="h5 card-title mb-2">{{ $category->name }}</h3>
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-book me-1"></i> {{ $category->recipes_count }} {{ trans_choice('рецепт|рецепта|рецептов', $category->recipes_count) }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Алфавитный указатель -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header">
                    <h2 class="h4 mb-0"><i class="fas fa-th-list me-2"></i> Все категории</h2>
                </div>
                <div class="card-body">
                    <div class="alphabetical-index mb-4">
                        <p>Быстрый переход:</p>
                        <div class="d-flex flex-wrap alphabet-links">
                            @foreach($categoriesByLetter as $letter => $items)
                                <a href="#letter-{{ $letter }}" class="letter-link me-2 mb-2 btn btn-sm btn-outline-primary">{{ $letter }}</a>
                            @endforeach
                        </div>
                    </div>
                    
                    <div class="categories-list mt-4">
                        @foreach($categoriesByLetter as $letter => $items)
                            <div class="letter-section mb-4" id="letter-{{ $letter }}">
                                <h3 class="letter-heading border-bottom pb-2 mb-3">{{ $letter }}</h3>
                                <div class="row g-3">
                                    @foreach($items as $category)
                                        <div class="col-md-4 col-sm-6">
                                            <a href="{{ route('categories.show', $category->slug) }}" class="category-link" title="Рецепты в категории {{ $category->name }}">
                                                <div class="d-flex align-items-center p-2 rounded hover-shadow">
                                                    <div class="category-icon me-3 rounded-circle {{ $category->getColorClass() }} text-white">
                                                        <i class="fas fa-utensils"></i>
                                                    </div>
                                                    <div>
                                                        <h4 class="h5 mb-0">{{ $category->name }}</h4>
                                                        <small class="text-muted">{{ $category->recipes_count }} {{ trans_choice('рецепт|рецепта|рецептов', $category->recipes_count) }}</small>
                                                    </div>
                                                </div>
                                            </a>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Случайные рецепты для вдохновения -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h2 class="h4 mb-0"><i class="fas fa-lightbulb me-2"></i> Вдохновение дня</h2>
                </div>
                <div class="card-body">
                    <div class="row g-4">
                        @foreach($featuredRecipes as $recipe)
                            <div class="col-md-3 col-sm-6">
                                <div class="card h-100 featured-recipe-card border-0 shadow-sm">
                                    <div class="position-relative">
                                        <img src="{{ asset($recipe->image_url) }}" 
                                             class="card-img-top featured-recipe-img" 
                                             alt="{{ $recipe->title }}" 
                                             loading="lazy"
                                             onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}';">
                                        @if($recipe->cooking_time)
                                            <span class="position-absolute top-0 end-0 badge bg-primary m-2">
                                                <i class="far fa-clock me-1"></i> {{ $recipe->cooking_time }} мин
                                            </span>
                                        @endif
                                    </div>
                                    <div class="card-body">
                                        <h3 class="h5 card-title">{{ $recipe->title }}</h3>
                                        @if(!$recipe->categories->isEmpty())
                                            <div class="mb-2">
                                                @foreach($recipe->categories->take(3) as $category)
                                                    <a href="{{ route('categories.show', $category->slug) }}" class="badge bg-light text-dark text-decoration-none me-1">
                                                        {{ $category->name }}
                                                    </a>
                                                @endforeach
                                            </div>
                                        @endif
                                        <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-sm btn-outline-primary mt-2" title="Посмотреть рецепт {{ $recipe->title }}">
                                            <i class="fas fa-eye me-1"></i> Посмотреть рецепт
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Стили для страницы категорий */
    .category-img {
        height: 150px;
        object-fit: cover;
        opacity: 0.8;
    }
    
    .category-image-container {
        height: 150px;
        overflow: hidden;
        position: relative;
    }
    
    .popular-category-card {
        transition: transform 0.3s, box-shadow 0.3s;
    }
    
    .popular-category-card:hover {
        transform: translateY(-5px);
        box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
    }
    
    .category-icon {
        display: flex;
        justify-content: center;
        align-items: center;
        width: 40px;
        height: 40px;
    }
    
    .letter-heading {
        color: #6c757d;
        font-weight: 500;
    }
    
    .category-link {
        color: inherit;
        text-decoration: none;
    }
    
    .hover-shadow {
        transition: background-color 0.3s, box-shadow 0.3s;
    }
    
    .hover-shadow:hover {
        background-color: #f8f9fa;
        box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .featured-recipe-img {
        height: 180px;
        object-fit: cover;
    }
    
    .featured-recipe-card {
        transition: transform 0.3s;
    }
    
    .featured-recipe-card:hover {
        transform: translateY(-5px);
    }
    
    .letter-link {
        text-decoration: none;
        min-width: 36px;
        text-align: center;
    }
    
    /* Плавная прокрутка до секций */
    html {
        scroll-behavior: smooth;
    }
</style>
@endsection
