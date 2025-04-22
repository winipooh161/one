@extends('layouts.app')

@section('title', 'Страница не найдена - 404')
@section('description', 'К сожалению, запрашиваемая страница не найдена.')

@section('seo')
<meta name="robots" content="noindex, follow">
@endsection

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8 text-center">
            <h1 class="display-4 fw-bold">404</h1>
            <p class="fs-1 text-muted">Страница не найдена</p>
            <p class="lead mb-4">К сожалению, запрашиваемая страница не существует или была удалена.</p>
            
            <div class="mb-5">
                <a href="{{ url('/') }}" class="btn btn-primary me-2">
                    <i class="fas fa-home me-1"></i> На главную
                </a>
                <a href="{{ route('recipes.index') }}" class="btn btn-outline-primary">
                    <i class="fas fa-utensils me-1"></i> Смотреть рецепты
                </a>
            </div>
            
            <div class="mt-5">
                <h3 class="h5 mb-3">Возможно, вас заинтересует:</h3>
                <div class="row row-cols-1 row-cols-md-3 g-4">
                    @php
                        // Получаем случайные рецепты для рекомендаций
                        $randomRecipes = \App\Models\Recipe::where('is_published', true)
                            ->inRandomOrder()
                            ->limit(3)
                            ->get();
                    @endphp
                    
                    @foreach($randomRecipes as $recipe)
                        <div class="col">
                            <div class="card h-100 border-0 shadow-sm">
                                <img src="{{ asset($recipe->image_url) }}" 
                                     class="card-img-top" 
                                     alt="{{ $recipe->title }}" 
                                     style="height: 150px; object-fit: cover;"
                                     onerror="window.handleImageError(this)">
                                <div class="card-body">
                                    <h5 class="card-title">{{ $recipe->title }}</h5>
                                    <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-sm btn-primary mt-2">Посмотреть</a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
