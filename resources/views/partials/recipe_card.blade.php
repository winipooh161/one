@if($view == 'grid')
    {{-- Карточка рецепта в режиме сетки --}}
    <div class="card h-100 recipe-card border-0 shadow-sm">
        <div class="position-relative">
            <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none" title="{{ $recipe->title }}">
                <img src="{{ asset($recipe->image_url ?? 'images/placeholder.jpg') }}" 
                     class="card-img-top recipe-img" 
                     alt="{{ $recipe->title }}" 
                     loading="lazy"
                     onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}';">
            </a>
            
            @if($recipe->cooking_time)
                <span class="position-absolute top-0 end-0 badge bg-primary m-2">
                    <i class="far fa-clock me-1"></i> {{ $recipe->cooking_time }} мин
                </span>
            @endif
            
            @if(isset($recipe->difficulty))
                <span class="position-absolute top-0 start-0 badge bg-secondary m-2">
                    {{ method_exists($recipe, 'getDifficultyLabel') ? $recipe->getDifficultyLabel() : 'Средне' }}
                </span>
            @endif
        </div>
        
        <div class="card-body">
            <h3 class="h5 card-title mb-2">
                <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none text-dark" title="{{ $recipe->title }}">
                    {{ \Illuminate\Support\Str::limit($recipe->title, 60) }}
                </a>
            </h3>
            
            @if($recipe->short_description)
                <p class="card-text small text-muted mb-2">
                    {{ \Illuminate\Support\Str::limit($recipe->short_description, 100) }}
                </p>
            @endif
            
            @if($recipe->categories && $recipe->categories->count() > 0)
                <div class="mb-2">
                    @foreach($recipe->categories->take(3) as $cat)
                        <a href="{{ route('categories.show', $cat->slug) }}" class="badge bg-light text-dark text-decoration-none me-1">
                            {{ $cat->name }}
                        </a>
                    @endforeach
                </div>
            @endif
            
            <div class="d-flex justify-content-between align-items-center mt-2">
                <div class="recipe-meta small text-muted">
                    <i class="far fa-eye me-1"></i> {{ $recipe->views ?? 0 }}
                    @if(isset($recipe->rating) && $recipe->rating > 0)
                        <span class="ms-2">
                            <i class="fas fa-star text-warning me-1"></i> {{ number_format($recipe->rating, 1) }}
                        </span>
                    @endif
                </div>
                
                <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-sm btn-outline-primary" title="Посмотреть рецепт {{ $recipe->title }}">
                    <i class="fas fa-eye me-1"></i> Рецепт
                </a>
            </div>
        </div>
    </div>
@else
    {{-- Карточка рецепта в режиме списка --}}
    <div class="list-group-item p-3 recipe-list-item border-0 shadow-sm mb-3">
        <div class="row g-0">
            <div class="col-md-3 col-lg-2">
                <a href="{{ route('recipes.show', $recipe->slug) }}" title="{{ $recipe->title }}">
                    <img src="{{ asset($recipe->image_url ?? 'images/placeholder.jpg') }}" 
                         class="img-fluid rounded" 
                         alt="{{ $recipe->title }}" 
                         loading="lazy"
                         style="object-fit: cover; height: 120px; width: 100%;"
                         onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}';">
                </a>
            </div>
            <div class="col-md-9 col-lg-10">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h3 class="h5 mb-1">
                            <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none text-dark" title="{{ $recipe->title }}">
                                {{ $recipe->title }}
                            </a>
                        </h3>
                        
                        <div class="recipe-meta">
                            @if($recipe->cooking_time)
                                <span class="badge bg-primary">
                                    <i class="far fa-clock me-1"></i> {{ $recipe->cooking_time }} мин
                                </span>
                            @endif
                        </div>
                    </div>
                    
                    @if($recipe->short_description)
                        <p class="card-text text-muted small mb-2">
                            {{ \Illuminate\Support\Str::limit($recipe->short_description, 150) }}
                        </p>
                    @endif
                    
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            @if($recipe->categories && $recipe->categories->count() > 0)
                                <div class="mb-0">
                                    @foreach($recipe->categories->take(3) as $cat)
                                        <a href="{{ route('categories.show', $cat->slug) }}" class="badge bg-light text-dark text-decoration-none me-1">
                                            {{ $cat->name }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                        
                        <div class="d-flex align-items-center">
                            <div class="recipe-stats me-3 small text-muted">
                                <i class="far fa-eye me-1"></i> {{ $recipe->views ?? 0 }}
                                @if(isset($recipe->rating) && $recipe->rating > 0)
                                    <span class="ms-2">
                                        <i class="fas fa-star text-warning me-1"></i> {{ number_format($recipe->rating, 1) }}
                                    </span>
                                @endif
                            </div>
                            
                            <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-sm btn-outline-primary" title="Посмотреть рецепт {{ $recipe->title }}">
                                <i class="fas fa-eye me-1"></i> Рецепт
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
