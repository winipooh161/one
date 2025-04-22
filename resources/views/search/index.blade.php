@extends('layouts.search')

@section('seo')
    @include('seo.search_seo', ['query' => $query, 'results' => $results, 'seo' => app('App\Services\SeoService')])
@endsection

@section('content')
<div class="container my-4">
    <h1 class="mb-4">Результаты поиска: "{{ $query }}"</h1>
    
    <div class="search-results-container">
        @if(isset($results) && $results->total() > 0)
            <p class="search-summary">Найдено {{ $results->total() }} {{ trans_choice('рецепт|рецепта|рецептов', $results->total()) }}</p>
            
            <div class="row">
                @foreach($results as $recipe)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <a href="{{ route('recipes.show', $recipe->slug) }}">
                                <img src="{{ $recipe->image_url ?: asset('images/placeholder.jpg') }}" class="card-img-top" alt="{{ $recipe->title }}">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none text-dark">
                                        {{ $recipe->title }}
                                    </a>
                                </h5>
                                <p class="card-text">{{ Str::limit($recipe->description, 100) }}</p>
                            </div>
                            <div class="card-footer d-flex justify-content-between align-items-center">
                                <small class="text-muted">
                                    <i class="fas fa-clock"></i> {{ $recipe->cooking_time }} мин
                                </small>
                                <small class="text-muted">
                                    <i class="fas fa-eye"></i> {{ $recipe->views }}
                                </small>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
            
            <div class="d-flex justify-content-center mt-4">
                {{ $results->appends(['query' => $query])->links() }}
            </div>
        @else
            <div class="alert alert-info">
                <p class="mb-0">По вашему запросу ничего не найдено. Попробуйте изменить поисковый запрос.</p>
            </div>
            
            <h3 class="mt-5 mb-3">Возможно, вам будет интересно</h3>
            <div class="row">
                @foreach(\App\Models\Recipe::where('is_published', true)->inRandomOrder()->limit(3)->get() as $recipe)
                    <div class="col-md-4 mb-4">
                        <div class="card h-100">
                            <a href="{{ route('recipes.show', $recipe->slug) }}">
                                <img src="{{ $recipe->image_url ?: asset('images/placeholder.jpg') }}" class="card-img-top" alt="{{ $recipe->title }}">
                            </a>
                            <div class="card-body">
                                <h5 class="card-title">
                                    <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none text-dark">
                                        {{ $recipe->title }}
                                    </a>
                                </h5>
                                <p class="card-text">{{ Str::limit($recipe->description, 100) }}</p>
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
@endsection
