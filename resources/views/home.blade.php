@extends('layouts.app')

@section('title', $title ?? 'Яедок')
@section('description', $description ?? 'Лучшие рецепты на любой вкус')
@section('keywords', $keywords ?? 'рецепты, кулинария, еда')

@section('seo')
    @include('seo.home_seo', ['seo' => app(App\Services\SeoService::class)])
@endsection

@section('content')
<div class="container py-4">
    <!-- Главный баннер с расширенным поиском -->
    <div class="row mb-5">
        <div class="col-md-12">
            <div class="homepage-header p-4 p-md-5 mb-4 text-white rounded bg-dark position-relative overflow-hidden">
                <!-- Фоновое изображение с наложением -->
                <div class="banner-background" style="background-image: url('{{ asset('images/banner-bg.jpg') }}');"></div>
                
                <div class="row position-relative">
                    <div class="col-lg-7">
                        <h1 class="display-4 fw-bold">Кулинарная книга</h1>
                        <p class="lead my-3">Найдите идеальный рецепт для вашего следующего кулинарного шедевра</p>
                        
                        <!-- Расширенная форма поиска -->
                        <div class="search-container bg-white p-3 rounded shadow-sm my-4">
                            <ul class="nav nav-tabs" id="searchTabs" role="tablist">
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link active" id="recipe-tab" data-bs-toggle="tab" data-bs-target="#recipe-search" 
                                        type="button" role="tab" aria-selected="true">
                                        <i class="fas fa-search me-1"></i> По названию
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="ingredient-tab" data-bs-toggle="tab" data-bs-target="#ingredient-search" 
                                        type="button" role="tab" aria-selected="false">
                                        <i class="fas fa-carrot me-1"></i> По ингредиентам
                                    </button>
                                </li>
                                <li class="nav-item" role="presentation">
                                    <button class="nav-link" id="advanced-tab" data-bs-toggle="tab" data-bs-target="#advanced-search" 
                                        type="button" role="tab" aria-selected="false">
                                        <i class="fas fa-sliders-h me-1"></i> Расширенный
                                    </button>
                                </li>
                            </ul>
                            
                            <div class="tab-content p-3" id="searchTabsContent">
                                <!-- Поиск по названию -->
                                <div class="tab-pane fade show active" id="recipe-search" role="tabpanel">
                                    @if(Route::has('search'))
                                    <form action="{{ route('search') }}" method="GET">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="query" placeholder="Введите название блюда...">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-search me-1"></i> Найти
                                            </button>
                                        </div>
                                    </form>
                                    @else
                                    <form action="{{ route('recipes.index') }}" method="GET">
                                        <div class="input-group">
                                            <input type="text" class="form-control" name="search" placeholder="Введите название блюда...">
                                            <button class="btn btn-primary" type="submit">
                                                <i class="fas fa-search me-1"></i> Найти
                                            </button>
                                        </div>
                                    </form>
                                    @endif
                                </div>
                                
                                <!-- Поиск по ингредиентам -->
                                <div class="tab-pane fade" id="ingredient-search" role="tabpanel">
                                    @if(Route::has('search'))
                                    <form action="{{ route('search') }}" method="GET">
                                        <input type="hidden" name="search_type" value="ingredients">
                                        <div class="mb-3">
                                            <div class="ingredient-tags input-group">
                                                <input type="text" id="ingredient-input" class="form-control" placeholder="Введите ингредиент и нажмите Enter...">
                                                <button type="button" id="add-ingredient" class="btn btn-outline-secondary">
                                                    <i class="fas fa-plus"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <div id="selected-ingredients" class="mb-3"></div>
                                        <div id="ingredient-chips" class="mb-3"></div>
                                        <button class="btn btn-primary" type="submit">
                                            <i class="fas fa-utensils me-1"></i> Найти рецепты
                                        </button>
                                    </form>
                                    @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> Поиск по ингредиентам скоро будет доступен!
                                    </div>
                                    @endif
                                </div>
                                
                                <!-- Расширенный поиск -->
                                <div class="tab-pane fade" id="advanced-search" role="tabpanel">
                                    @if(Route::has('search'))
                                    <form action="{{ route('search') }}" method="GET">
                                        <input type="hidden" name="search_type" value="advanced">
                                        <div class="row g-3">
                                            <div class="col-md-6">
                                                <label for="category" class="form-label">Категория</label>
                                                <select name="category" id="category" class="form-select">
                                                    <option value="">Любая категория</option>
                                                    @if(isset($popularCategories) && $popularCategories->count() > 0)
                                                        @foreach($popularCategories as $category)
                                                            <option value="{{ $category->id }}">{{ $category->name }}</option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                            <div class="col-md-6">
                                                <label for="cooking_time" class="form-label">Время приготовления</label>
                                                <select name="cooking_time" id="cooking_time" class="form-select">
                                                    <option value="">Любое время</option>
                                                    <option value="15">До 15 минут</option>
                                                    <option value="30">До 30 минут</option>
                                                    <option value="60">До 1 часа</option>
                                                    <option value="120">До 2 часов</option>
                                                </select>
                                            </div>
                                            <div class="col-md-12">
                                                <div class="form-check form-switch">
                                                    <input class="form-check-input" type="checkbox" id="has_image" name="has_image" value="1">
                                                    <label class="form-check-label" for="has_image">Только с фото</label>
                                                </div>
                                            </div>
                                            <div class="col-12">
                                                <button class="btn btn-primary w-100" type="submit">
                                                    <i class="fas fa-filter me-1"></i> Применить фильтры
                                                </button>
                                            </div>
                                        </div>
                                    </form>
                                    @else
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i> Расширенный поиск скоро будет доступен!
                                    </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <!-- Кнопка для установки приложения на главном экране -->
                        <div class="pwa-install-prompt mt-4 d-none">
                            <div class="alert alert-info d-flex align-items-center justify-content-between">
                                <div>
                                    <i class="fas fa-mobile-alt me-2"></i>
                                    <strong>Удобнее с приложением!</strong> Устанавливайте наше приложение на ваше устройство
                                </div>
                                <button id="home-install-pwa" class="btn btn-primary btn-sm ms-3">
                                    <i class="fas fa-download me-1"></i> Установить
                                </button>
                            </div>
                        </div>

                        <div class="popular-searches mt-2">
                            <small class="text-light">Популярные запросы:</small>
                            <div class="d-flex flex-wrap gap-1 mt-1">
                                @if(Route::has('search'))
                                <a href="{{ route('search', ['query' => 'завтрак']) }}" class="badge bg-light text-dark text-decoration-none">завтрак</a>
                                <a href="{{ route('search', ['query' => 'десерт']) }}" class="badge bg-light text-dark text-decoration-none">десерт</a>
                                <a href="{{ route('search', ['query' => 'суп']) }}" class="badge bg-light text-dark text-decoration-none">суп</a>
                                <a href="{{ route('search', ['query' => 'салат']) }}" class="badge bg-light text-dark text-decoration-none">салат</a>
                                <a href="{{ route('search', ['query' => 'ужин']) }}" class="badge bg-light text-dark text-decoration-none">ужин</a>
                                @else
                                <a href="{{ route('recipes.index', ['search' => 'завтрак']) }}" class="badge bg-light text-dark text-decoration-none">завтрак</a>
                                <a href="{{ route('recipes.index', ['search' => 'десерт']) }}" class="badge bg-light text-dark text-decoration-none">десерт</a>
                                <a href="{{ route('recipes.index', ['search' => 'суп']) }}" class="badge bg-light text-dark text-decoration-none">суп</a>
                                <a href="{{ route('recipes.index', ['search' => 'салат']) }}" class="badge bg-light text-dark text-decoration-none">салат</a>
                                <a href="{{ route('recipes.index', ['search' => 'ужин']) }}" class="badge bg-light text-dark text-decoration-none">ужин</a>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Быстрый доступ к популярным категориям -->
    <div class="row mb-5">
    <script src="https://yandex.ru/ads/system/context.js" async></script>
<!-- Yandex.RTB R-A-14978340-1 -->
<div id="yandex_rtb_R-A-14978340-1"></div>
<script>
window.yaContextCb.push(() => {
    Ya.Context.AdvManager.render({
        "blockId": "R-A-14978340-1",
        "renderTo": "yandex_rtb_R-A-14978340-1"
    })
})
</script>
   
    </div>

    <!-- Секция "Последние рецепты" -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-clock text-primary me-2"></i> Новые рецепты</h2>
                <a href="{{ route('recipes.index', ['sort' => 'latest']) }}" class="btn btn-outline-primary">
                    Все новинки <i class="fas fa-arrow-right ms-1"></i>
                </a>
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                @if(isset($latestRecipes) && $latestRecipes->count() > 0)
                    @foreach($latestRecipes as $recipe)
                    <div class="col">
                        <div class="card h-100 recipe-card hover-shadow">
                            <div class="position-relative">
                                <img src="{{ $recipe->image_url }}" class="card-img-top recipe-img" alt="{{ $recipe->title }}" 
                                     onerror="window.handleImageError(this)">
                                @if($recipe->cooking_time)
                                <span class="position-absolute top-0 end-0 badge bg-primary m-2">
                                    <i class="far fa-clock me-1"></i> {{ $recipe->cooking_time }} мин
                                </span>
                                @endif
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">{{ $recipe->title }}</h5>
                                <p class="card-text text-muted">{{ Str::limit($recipe->description ?? 'Вкусный рецепт', 100) }}</p>
                                
                                <div class="recipe-meta d-flex justify-content-between align-items-center mb-3">
                                    <div>
                                        @if($recipe->calories)
                                        <span class="badge bg-warning text-dark me-1"><i class="fas fa-fire"></i> {{ $recipe->calories }} ккал</span>
                                        @endif
                                        
                                        @if(!$recipe->categories->isEmpty())
                                        <span class="badge bg-light text-dark">
                                            <i class="fas fa-tag"></i> {{ $recipe->categories->first()->name }}
                                        </span>
                                        @endif
                                    </div>
                                    
                                    <div class="recipe-difficulty">
                                        @php
                                            $difficulty = 'Легкий';
                                            $difficultyClass = 'text-success';
                                            if ($recipe->cooking_time > 60) {
                                                $difficulty = 'Сложный';
                                                $difficultyClass = 'text-danger';
                                            } elseif ($recipe->cooking_time > 30) {
                                                $difficulty = 'Средний';
                                                $difficultyClass = 'text-warning';
                                            }
                                        @endphp
                                        <small class="{{ $difficultyClass }}">
                                            <i class="fas fa-circle me-1"></i> {{ $difficulty }}
                                        </small>
                                    </div>
                                </div>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-grid">
                                    <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-primary">
                                        <i class="fas fa-book-open me-1"></i> Смотреть рецепт
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endforeach
                @else
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Скоро здесь появятся новые рецепты!
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Секция "Быстрые рецепты" -->
    <div class="row mb-5">
        <!-- Реклама РСЯ -->
        <div class="col-12 mb-4">
        <script>window.yaContextCb=window.yaContextCb||[]</script>
<div id="yandex_rtb_R-A-14978340-3"></div>
<script>
window.yaContextCb.push(() => {
    Ya.Context.AdvManager.render({
        "blockId": "R-A-14978340-3",
        "renderTo": "yandex_rtb_R-A-14978340-3",
        "type": "feed"
    })
})
</script>
        </div>
        
        <div class="col-12">
            <div class="quick-recipes-section p-4 rounded shadow-sm bg-light">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-bolt text-warning me-2"></i> Быстрые рецепты</h2>
                    <a href="{{ route('recipes.index', ['cooking_time' => 30]) }}" class="btn btn-warning">
                        <i class="fas fa-stopwatch me-1"></i> Все быстрые рецепты
                    </a>
                </div>
                
                <div class="row">
                    @if(isset($quickRecipes) && $quickRecipes->count() > 0)
                        @foreach($quickRecipes as $recipe)
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card h-100 border-0 shadow-sm quick-recipe-card">
                                <div class="position-relative">
                                    <img src="{{ $recipe->image_url }}" 
                                         class="card-img-top quick-recipe-img" alt="{{ $recipe->title }}" 
                                         onerror="window.handleImageError(this)">
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-warning text-dark">
                                            <i class="fas fa-bolt me-1"></i> {{ $recipe->cooking_time }} мин
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">{{ $recipe->title }}</h5>
                                    <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-sm btn-outline-warning w-100 mt-2">
                                        <i class="fas fa-utensils me-1"></i> Приготовить
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Быстрые рецепты появятся здесь совсем скоро!
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Секция сезонных рецептов -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="seasonal-recipes p-4 rounded shadow-sm">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2><i class="fas fa-calendar-check text-success me-2"></i> {{ ucfirst($season) }}ние рецепты</h2>
                    <a href="{{ route('recipes.index', ['season' => $season]) }}" class="btn btn-outline-success">
                        <i class="fas fa-leaf me-1"></i> Все {{ $season }}ние рецепты
                    </a>
                </div>
                
                <div class="row">
                    @if(isset($seasonalRecipes) && $seasonalRecipes->count() > 0)
                        @foreach($seasonalRecipes as $recipe)
                        <div class="col-md-6 col-lg-3 mb-3">
                            <div class="card h-100 border-0 shadow-sm seasonal-recipe-card">
                                <div class="position-relative">
                                    <img src="{{ $recipe->image_url }}" 
                                         class="card-img-top seasonal-recipe-img" alt="{{ $recipe->title }}" 
                                         loading="lazy"
                                         sizes="(max-width: 576px) 100vw, (max-width: 992px) 50vw, 25vw"
                                         srcset="{{ $recipe->image_url }} 800w, {{ $recipe->thumbnail_url ?? $recipe->image_url }} 400w"
                                         onerror="window.handleImageError(this)">
                                    <div class="position-absolute top-0 start-0 m-2">
                                        <span class="badge bg-success">
                                            <i class="fas fa-leaf me-1"></i> {{ ucfirst($season) }}
                                        </span>
                                    </div>
                                </div>
                                <div class="card-body">
                                    <h5 class="card-title">{{ $recipe->title }}</h5>
                                    <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-sm btn-outline-success w-100 mt-2">
                                        <i class="fas fa-utensils me-1"></i> Приготовить
                                    </a>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    @else
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> Сезонные рецепты скоро появятся. Следите за обновлениями!
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
    
    <!-- Персональные рекомендации для авторизованных пользователей -->
    @auth
    <div class="row mb-5">
        <div class="col-12">
            <div class="personal-recommendations p-4 rounded shadow-sm bg-light">
                <h2 class="mb-4"><i class="fas fa-thumbs-up text-primary me-2"></i> Рекомендации для вас</h2>
                
                <div id="personalRecipes" class="row g-4">
                    <div class="col-12">
                        <div class="text-center p-4">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Загрузка...</span>
                            </div>
                            <p class="mt-2">Подбираем рецепты специально для вас...</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endauth
    
    <!-- Блок - Рецепты на все случаи -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="mb-4"><i class="fas fa-calendar-day text-primary me-2"></i> Рецепты на все случаи</h2>
            
            <div class="row g-4">
                <!-- Карточка "Завтрак" -->
                <div class="col-md-4">
                    <div class="meal-type-card position-relative overflow-hidden rounded shadow">
                        <img src="{{ asset('images/breakfast.jpg') }}" alt="Завтрак" class="w-100 meal-type-img" 
                             onerror="window.handleImageError(this)">
                        <div class="meal-type-overlay d-flex align-items-end">
                            <div class="w-100 p-3">
                                <h3 class="text-white mb-2">Завтрак</h3>
                                <p class="text-white mb-3">Начните день с вкусного и полезного завтрака</p>
                                @if(Route::has('search'))
                                <a href="{{ route('search', ['query' => 'завтрак']) }}" class="btn btn-light">
                                @else
                                <a href="{{ route('recipes.index', ['search' => 'завтрак']) }}" class="btn btn-light">
                                @endif
                                    <i class="fas fa-coffee me-1"></i> Идеи для завтрака
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка "Обед" -->
                <div class="col-md-4">
                    <div class="meal-type-card position-relative overflow-hidden rounded shadow">
                        <img src="{{ asset('images/lunch.jpg') }}" alt="Обед" class="w-100 meal-type-img" onerror="window.handleImageError(this)">
                        <div class="meal-type-overlay d-flex align-items-end">
                            <div class="w-100 p-3">
                                <h3 class="text-white mb-2">Обед</h3>
                                <p class="text-white mb-3">Сытные и разнообразные блюда для обеденного перерыва</p>
                                @if(Route::has('search'))
                                <a href="{{ route('search', ['query' => 'обед']) }}" class="btn btn-light">
                                @else
                                <a href="{{ route('recipes.index', ['search' => 'обед']) }}" class="btn btn-light">
                                @endif
                                    <i class="fas fa-hamburger me-1"></i> Обеденные рецепты
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Карточка "Ужин" -->
                <div class="col-md-4">
                    <div class="meal-type-card position-relative overflow-hidden rounded shadow">
                        <img src="{{ asset('images/dinner.jpg') }}" alt="Ужин" class="w-100 meal-type-img" onerror="window.handleImageError(this)">
                        <div class="meal-type-overlay d-flex align-items-end">
                            <div class="w-100 p-3">
                                <h3 class="text-white mb-2">Ужин</h3>
                                <p class="text-white mb-3">Вечерние блюда для всей семьи</p>
                                @if(Route::has('search'))
                                <a href="{{ route('search', ['query' => 'ужин']) }}" class="btn btn-light">
                                @else
                                <a href="{{ route('recipes.index', ['search' => 'ужин']) }}" class="btn btn-light">
                                @endif
                                    <i class="fas fa-utensils me-1"></i> Рецепты для ужина
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Часто задаваемые вопросы (FAQ) -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="faq-section p-4 rounded shadow-sm bg-light">
                <h2 class="mb-4"><i class="fas fa-question-circle text-info me-2"></i> Часто задаваемые вопросы</h2>
                
                <div class="accordion" id="faqAccordion">
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faqHeading1">
                            <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse1" aria-expanded="true" aria-controls="faqCollapse1">
                                Как добавить свой рецепт на сайт?
                            </button>
                        </h3>
                        <div id="faqCollapse1" class="accordion-collapse collapse show" aria-labelledby="faqHeading1" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Чтобы добавить свой рецепт, вам необходимо <a href="{{ route('login') }}">войти</a> или <a href="{{ route('register') }}">зарегистрироваться</a> на нашем сайте. После авторизации нажмите на кнопку "Добавить рецепт", заполните все необходимые поля и опубликуйте свой кулинарный шедевр!
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faqHeading2">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse2" aria-expanded="false" aria-controls="faqCollapse2">
                                Могу ли я сохранять понравившиеся рецепты?
                            </button>
                        </h3>
                        <div id="faqCollapse2" class="accordion-collapse collapse" aria-labelledby="faqHeading2" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Да, после регистрации вы можете добавлять рецепты в избранное, нажав на значок звездочки на странице рецепта. Все сохраненные рецепты будут доступны в разделе "Избранное" в вашем профиле.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faqHeading3">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse3" aria-expanded="false" aria-controls="faqCollapse3">
                                Как найти рецепты по имеющимся ингредиентам?
                            </button>
                        </h3>
                        <div id="faqCollapse3" class="accordion-collapse collapse" aria-labelledby="faqHeading3" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Воспользуйтесь расширенным поиском на главной странице. На вкладке "По ингредиентам" введите имеющиеся у вас продукты, и система подберет подходящие рецепты.
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h3 class="accordion-header" id="faqHeading4">
                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#faqCollapse4" aria-expanded="false" aria-controls="faqCollapse4">
                                Как рассчитывается калорийность блюд?
                            </button>
                        </h3>
                        <div id="faqCollapse4" class="accordion-collapse collapse" aria-labelledby="faqHeading4" data-bs-parent="#faqAccordion">
                            <div class="accordion-body">
                                Калорийность рассчитывается автоматически на основе ингредиентов и их количества в рецепте. Обратите внимание, что это приблизительные значения, которые могут незначительно отличаться в зависимости от конкретных продуктов.
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Секция "Популярные категории" -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-tags text-primary me-2"></i> Популярные категории</h2>
                @if(Route::has('categories.index'))
                <a href="{{ route('categories.index') }}" class="btn btn-outline-primary">
                    Все категории <i class="fas fa-arrow-right ms-1"></i>
                </a>
                @endif
            </div>
            
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                @foreach($featuredCategories->take(3) as $category)
                <div class="col">
                    @if(Route::has('categories.show'))
                    <a href="{{ route('categories.show', $category->slug) }}" class="text-decoration-none">
                    @else
                    <a href="{{ route('recipes.index', ['category_id' => $category->id]) }}" class="text-decoration-none">
                    @endif
                        <div class="card h-100 category-card border-0 shadow-sm">
                            <div class="card-body text-center p-4">
                                <div class="category-icon mb-3">
                                    <span class="category-icon-circle bg-light d-inline-block rounded-circle">
                                        <i class="fas fa-utensils text-primary"></i>
                                    </span>
                                </div>
                                <h3 class="card-title">{{ $category->name }}</h3>
                                <p class="card-text text-muted">
                                    {{ $category->recipes_count }} {{ trans_choice('рецепт|рецепта|рецептов', $category->recipes_count) }}
                                </p>
                                <div class="category-view-btn">
                                    <span class="btn btn-sm btn-primary px-4">
                                        <i class="fas fa-eye me-1"></i> Посмотреть
                                    </span>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
                @endforeach
            </div>
        </div>
    </div>
    
    <!-- Кнопки социального шаринга -->
    <div class="row mb-5">
        <div class="col-12 text-center">
            <h4 class="mb-3"><i class="fas fa-share-alt text-primary me-2"></i> Поделитесь кулинарным вдохновением с друзьями</h4>
            <div class="social-share-buttons">
                <a href="https://vk.com/share.php?url={{ urlencode(url('/')) }}" target="_blank" class="btn btn-primary me-2">
                    <i class="fab fa-vk"></i> ВКонтакте
                </a>
                <a href="https://t.me/share/url?url={{ urlencode(url('/')) }}&text={{ urlencode('Яедок - лучшие кулинарные рецепты!') }}" target="_blank" class="btn btn-info me-2 text-white">
                    <i class="fab fa-telegram"></i> Telegram
                </a>
                <a href="https://wa.me/?text={{ urlencode('Яедок - лучшие кулинарные рецепты! ' . url('/')) }}" target="_blank" class="btn btn-success me-2">
                    <i class="fab fa-whatsapp"></i> WhatsApp
                </a>
                <a href="https://connect.ok.ru/offer?url={{ urlencode(url('/')) }}" target="_blank" class="btn btn-warning me-2">
                    <i class="fab fa-odnoklassniki"></i> Одноклассники
                </a>
                <a href="mailto:?subject={{ urlencode('Яедок - лучшие кулинарные рецепты!') }}&body={{ urlencode('Привет! Посмотри этот отличный сайт с кулинарными рецептами: ' . url('/')) }}" class="btn btn-danger">
                    <i class="fas fa-envelope"></i> Email
                </a>
            </div>
        </div>
    </div>
    
    <!-- Секция "Советы и рекомендации" -->
    <div class="row mb-5">

        <div class="col-12">
            <div class="cooking-tips bg-light p-4 rounded shadow-sm">
                <h2 class="mb-4"><i class="fas fa-lightbulb text-warning me-2"></i> Кулинарные советы</h2>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-temperature-high text-danger me-2"></i> Готовка на плите</h5>
                                <p class="card-text">Не спешите с высокой температурой. Часто медленное приготовление на среднем огне дает лучший результат и равномерное прожаривание.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-mortar-pestle text-success me-2"></i> Специи и травы</h5>
                                <p class="card-text">Добавляйте сухие травы в начале готовки, а свежие - в конце, чтобы сохранить их аромат и полезные свойства.</p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <div class="card h-100 border-0">
                            <div class="card-body">
                                <h5 class="card-title"><i class="fas fa-blender text-primary me-2"></i> Подготовка продуктов</h5>
                                <p class="card-text">Заранее подготовьте и нарежьте все ингредиенты перед началом готовки, чтобы не тратить время в процессе.</p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="text-center mt-3">
                    <a href="{{ route('recipes.index') }}" class="btn btn-warning">
                        <i class="fas fa-book me-1"></i> Больше советов
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Секция "Присоединяйтесь к нам в Telegram" -->
    <div class="row mb-5">
        <div class="col-12">
            <h2 class="mb-4"><i class="fab fa-telegram text-primary me-2"></i> Присоединяйтесь к нам в Telegram</h2>
            
            <div class="row">
                <!-- Telegram-сообщество -->
                <div class="col-md-6 mb-4">
                    <div class="card telegram-card h-300 border-0 shadow-sm">
                        <div class="position-relative">
                            <img src="{{ asset('images/telegram-community.jpg') }}" alt="Telegram сообщество" class="card-img-top telegram-img" 
                                 onerror="window.handleImageError(this)">
                            <div class="telegram-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                                <div class="telegram-logo">
                                    <i class="fab fa-telegram-plane"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title">Наше сообщество</h4>
                            <p class="card-text">Присоединяйтесь к нашему кулинарному сообществу в Telegram. Обсуждайте рецепты, делитесь опытом и узнавайте о новинках первыми!</p>
                            <a href="https://t.me/imedokru" target="_blank" class="btn btn-primary w-100">
                                <i class="fab fa-telegram me-1"></i> Присоединиться
                            </a>
                        </div>
                    </div>
                </div>
                
                <!-- Telegram-бот -->
                <div class="col-md-6 mb-4">
                    <div class="card telegram-card h-300 border-0 shadow-sm">
                        <div class="position-relative">
                            <img src="{{ asset('images/telegram-bot.jpg') }}" alt="Telegram бот" class="card-img-top telegram-img" 
                                 onerror="window.handleImageError(this)">
                            <div class="telegram-overlay position-absolute top-0 start-0 w-100 h-100 d-flex align-items-center justify-content-center">
                                <div class="telegram-logo">
                                    <i class="fas fa-robot"></i>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h4 class="card-title">Наш бот-помощник</h4>
                            <p class="card-text">Воспользуйтесь нашим ботом, чтобы получать персональные рекомендации рецептов, искать блюда по ингредиентам и сохранять избранное!</p>
                            <a href="https://t.me/edokru_bot" target="_blank" class="btn btn-info text-white w-100">
                                <i class="fab fa-telegram me-1"></i> Запустить бота
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Секция "Присоединяйтесь к нам" -->
    <div class="row">
        <div class="col-12">
            <div class="join-us-section text-center bg-primary text-white p-5 rounded shadow">
                <h2 class="mb-3"><i class="fas fa-users me-2"></i> Присоединяйтесь к нашему сообществу</h2>
                <p class="lead mb-4">Делитесь своими рецептами, получайте отзывы и вдохновляйтесь новыми идеями!</p>
                
                <div class="d-grid gap-2 d-md-flex justify-content-md-center">
                    @guest
                        @if(Route::has('login'))
                        <a href="{{ route('login') }}" class="btn btn-light btn-lg px-4 me-md-2">
                            <i class="fas fa-sign-in-alt me-1"></i> Войти
                        </a>
                        @endif
                        
                        @if(Route::has('register'))
                        <a href="{{ route('register') }}" class="btn btn-outline-light btn-lg px-4">
                            <i class="fas fa-user-plus me-1"></i> Зарегистрироваться
                        </a>
                        @endif
                    @else
                        @if(Route::has('admin.recipes.create'))
                        <a href="{{ route('admin.recipes.create') }}" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-plus-circle me-1"></i> Добавить свой рецепт
                        </a>
                        @elseif(Route::has('recipes.create'))
                        <a href="{{ route('recipes.create') }}" class="btn btn-light btn-lg px-4">
                            <i class="fas fa-plus-circle me-1"></i> Добавить свой рецепт
                        </a>
                        @endif
                    @endguest
                </div>
            </div>
        </div>
    </div>
</div>


<!-- JavaScript для интерактивных элементов с проверкой существования DOM элементов -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Функционал для добавления ингредиентов в поиск
    const ingredientInput = document.getElementById('ingredient-input');
    const addIngredientBtn = document.getElementById('add-ingredient');
    const ingredientChips = document.getElementById('ingredient-chips');
    const selectedIngredientsContainer = document.getElementById('selected-ingredients');
    
    if (ingredientInput && addIngredientBtn && ingredientChips && selectedIngredientsContainer) {
        let selectedIngredients = [];
        
        // Добавление ингредиента
        function addIngredient() {
            const ingredient = ingredientInput.value.trim();
            if (ingredient && !selectedIngredients.includes(ingredient)) {
                selectedIngredients.push(ingredient);
                updateIngredientChips();
                updateHiddenFields();
                ingredientInput.value = '';
            }
        }
        
        // Обновление отображения выбранных ингредиентов
        function updateIngredientChips() {
            ingredientChips.innerHTML = '';
            
            selectedIngredients.forEach((ingredient, index) => {
                const chip = document.createElement('div');
                chip.className = 'ingredient-chip';
                chip.innerHTML = `
                    ${ingredient}
                    <span class="remove-ingredient" data-index="${index}">
                        <i class="fas fa-times-circle"></i>
                    </span>
                `;
                ingredientChips.appendChild(chip);
            });
            
            // Обработчики для кнопок удаления
            document.querySelectorAll('.remove-ingredient').forEach(btn => {
                btn.addEventListener('click', function() {
                    const index = parseInt(this.dataset.index);
                    selectedIngredients.splice(index, 1);
                    updateIngredientChips();
                    updateHiddenFields();
                });
            });
        }
        
        // Обновление скрытых полей формы
        function updateHiddenFields() {
            selectedIngredientsContainer.innerHTML = '';
            
            selectedIngredients.forEach(ingredient => {
                const input = document.createElement('input');
                input.type = 'hidden';
                input.name = 'ingredients[]';
                input.value = ingredient;
                selectedIngredientsContainer.appendChild(input);
            });
        }
        
        // Добавление ингредиента по клику на кнопку
        addIngredientBtn.addEventListener('click', addIngredient);
        
        // Добавление ингредиента по нажатию Enter
        ingredientInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                addIngredient();
            }
        });
    }
    
    // Реализация поведения для любых страниц, где могут отсутствовать необходимые элементы
    const searchTabs = document.getElementById('searchTabs');
    if (searchTabs) {
        const tabButtons = searchTabs.querySelectorAll('[data-bs-toggle="tab"]');
        tabButtons.forEach(button => {
            button.addEventListener('click', function() {
                // Убираем класс active у всех кнопок
                tabButtons.forEach(btn => {
                    btn.classList.remove('active');
                    btn.setAttribute('aria-selected', 'false');
                    document.querySelector(btn.dataset.bsTarget).classList.remove('show', 'active');
                });
                
                // Добавляем класс active на кликнутую кнопку
                this.classList.add('active');
                this.setAttribute('aria-selected', 'true');
                document.querySelector(this.dataset.bsTarget).classList.add('show', 'active');
            });
        });
    }

    // Настройка всех изображений категорий и типов блюд
    document.querySelectorAll('.meal-type-img, .recipe-img, .quick-recipe-img').forEach(img => {
        if (!img.complete || img.naturalHeight === 0) {
            img.src = window.getRandomDefaultImage(); 
            // Добавляем обработчик ошибок
            img.onerror = function() {
                window.handleImageError(this);
            };
        }
    });

    // Обработчик для кнопки установки на главной странице
    let deferredPrompt;
    const homeInstallButton = document.getElementById('home-install-pwa');
    const installPrompt = document.querySelector('.pwa-install-prompt');
    
    if (homeInstallButton && installPrompt) {
        // Перехватываем событие установки
        window.addEventListener('beforeinstallprompt', (e) => {
            // Предотвращаем показ стандартного диалога установки
            e.preventDefault();
            // Сохраняем событие для использования позже
            deferredPrompt = e;
            // Показываем блок с кнопкой установки
            installPrompt.classList.remove('d-none');
            
            // Обработчик клика для кнопки установки
            homeInstallButton.addEventListener('click', async () => {
                // Скрываем prompt
                installPrompt.classList.add('d-none');
                
                // Показываем диалог установки
                deferredPrompt.prompt();
                // Ожидаем выбор пользователя
                const { outcome } = await deferredPrompt.userChoice;
                
                // Логируем результат
                console.log(`Пользователь ${outcome === 'accepted' ? 'установил' : 'отклонил'} установку`);
                
                // Очищаем сохраненное событие
                deferredPrompt = null;
            });
        });
        
        // Если приложение уже установлено, скрываем блок
        window.addEventListener('appinstalled', () => {
            installPrompt.classList.add('d-none');
            deferredPrompt = null;
        });
    }

    // Загрузка персональных рекомендаций для авторизованных пользователей
    const personalRecipesContainer = document.getElementById('personalRecipes');
    if (personalRecipesContainer) {
        fetch('/api/recommendations')
            .then(response => response.json())
            .then(data => {
                personalRecipesContainer.innerHTML = '';
                
                if (data.length === 0) {
                    personalRecipesContainer.innerHTML = `
                        <div class="col-12">
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i> У нас пока недостаточно данных для персональных рекомендаций. 
                                Попробуйте посмотреть больше рецептов!
                            </div>
                        </div>
                    `;
                    return;
                }
                
                data.forEach(recipe => {
                    const recipeCard = document.createElement('div');
                    recipeCard.className = 'col-md-6 col-lg-3';
                    recipeCard.innerHTML = `
                        <div class="card h-100 recipe-card hover-shadow">
                            <div class="position-relative">
                                <img src="${recipe.image_url}" 
                                     class="card-img-top recipe-img" 
                                     alt="${recipe.title}" 
                                     loading="lazy"
                                     sizes="(max-width: 576px) 100vw, (max-width: 992px) 50vw, 25vw"
                                     srcset="${recipe.image_url} 800w, ${recipe.thumbnail_url || recipe.image_url} 400w"
                                     onerror="window.handleImageError(this)">
                                ${recipe.cooking_time ? `
                                <span class="position-absolute top-0 end-0 badge bg-primary m-2">
                                    <i class="far fa-clock me-1"></i> ${recipe.cooking_time} мин
                                </span>` : ''}
                            </div>
                            <div class="card-body">
                                <h5 class="card-title">${recipe.title}</h5>
                                <p class="card-text text-muted">${recipe.description ? recipe.description.substring(0, 100) + '...' : 'Вкусный рецепт'}</p>
                            </div>
                            <div class="card-footer bg-transparent border-top-0">
                                <div class="d-grid">
                                    <a href="/recipes/${recipe.slug}" class="btn btn-primary">
                                        <i class="fas fa-book-open me-1"></i> Смотреть рецепт
                                    </a>
                                </div>
                            </div>
                        </div>
                    `;
                    personalRecipesContainer.appendChild(recipeCard);
                });
            })
            .catch(error => {
                console.error('Error loading recommendations:', error);
                personalRecipesContainer.innerHTML = `
                    <div class="col-12">
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-circle me-2"></i> Ошибка при загрузке рекомендаций. Пожалуйста, попробуйте позже.
                        </div>
                    </div>
                `;
            });
    }
});
</script>
@endsection
