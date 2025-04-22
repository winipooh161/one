@extends('layouts.app')

@section('title', $title ?? $recipe->title)
@section('description', $description ?? Str::limit(strip_tags($recipe->content), 150, '...'))
@section('keywords', $keywords ?? '')

@section('meta_tags')
    @include('seo.show_recipe_seo')
@endsection

@section('schema_org')
    @include('schema_org.show_recipe_schema')
@endsection

@section('breadcrumbs')
    <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="{{ route('recipes.index') }}" itemprop="item"><span itemprop="name">Рецепты</span></a>
        <meta itemprop="position" content="2" />
    </li>
    @if($recipe->categories->isNotEmpty())
        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
            <a href="{{ route('categories.show', $recipe->categories->first()->slug) }}" itemprop="item">
                <span itemprop="name">{{ $recipe->categories->first()->name }}</span>
            </a>
            <meta itemprop="position" content="3" />
        </li>
    @endif
    <li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
        <span itemprop="name">{{ $recipe->title }}</span>
        <meta itemprop="position" content="{{ $recipe->categories->isNotEmpty() ? '4' : '3' }}" />
    </li>
@endsection

@section('seo')
    @include('seo.recipe_seo', ['recipe' => $recipe, 'seo' => app('App\Services\SeoService')])
@endsection

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Основной контент рецепта -->
        <div class="col-lg-8 hrecipe">
            <!-- Заголовок и мета-информация -->
            <div class="mb-4">
                <h1 class="display-5 fw-bold fn">{{ $recipe->title }}</h1>
                
                <!-- Мета-информация только для десктопа -->
                <div class="d-flex flex-wrap align-items-center text-muted mb-3 d-none d-md-flex">
                    <span class="me-3">
                        <i class="far fa-calendar-alt me-1"></i> {{ $recipe->created_at->format('d.m.Y') }}
                    </span>
                    <span class="me-3">
                        <i class="far fa-eye me-1"></i> {{ $recipe->views }} просмотров
                    </span>
                    @if($recipe->cooking_time)
                        <span class="me-3">
                            <i class="far fa-clock me-1"></i> {{ $recipe->cooking_time }} мин
                        </span>
                    @endif
                    @if($recipe->servings)
                        <span class="me-3">
                            <i class="fas fa-utensils me-1"></i> {{ $recipe->servings }} {{ trans_choice('порция|порции|порций', $recipe->servings) }}
                        </span>
                    @endif
                    @if($recipe->user)
                        <span>
                            <i class="far fa-user me-1"></i> Автор: 
                            <a href="{{ route('profile.show', $recipe->user->id) }}" class="text-decoration-none">
                                {{ $recipe->user->name }}
                            </a>
                        </span>
                    @endif
                </div>
                
                <!-- Категории для десктопа -->
                @if($recipe->categories->isNotEmpty())
                    <div class="mb-3 d-none d-md-block">
                        @foreach($recipe->categories as $category)
                            <a href="{{ route('categories.show', $category->slug) }}" class="badge bg-secondary text-decoration-none me-1">
                                {{ $category->name }}
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
            
            <!-- Слайдер с изображениями рецепта -->
            <div class="recipe-slider-container mb-4">
                <div class="swiper recipe-swiper">
                    <div class="swiper-wrapper">
                        <!-- Основное изображение -->
                        <div class="swiper-slide">
                            <img src="{{ $recipe->image_url }}" class="img-fluid rounded shadow w-100 photo" 
                                 alt="{{ $recipe->title }}" 
                                 data-original-src="{{ $recipe->image_url }}"
                                 onerror="window.handleImageError(this)">
                        </div>
                        
                        <!-- Дополнительные изображения из additional_data -->
                        @php
                            $additionalImages = [];
                            if (!empty($recipe->additional_data)) {
                                $data = is_array($recipe->additional_data) ? 
                                        $recipe->additional_data : 
                                        json_decode($recipe->additional_data, true);
                                
                                // Проверяем различные форматы хранения изображений
                                if (isset($data['slider_images']) && is_array($data['slider_images'])) {
                                    $additionalImages = array_merge($additionalImages, $data['slider_images']);
                                }
                                
                                if (isset($data['saved_images']) && is_array($data['saved_images'])) {
                                    foreach($data['saved_images'] as $img) {
                                        if (isset($img['saved_path'])) {
                                            $additionalImages[] = $img['saved_path'];
                                        }
                                    }
                                }
                                
                                if (isset($data['step_images']) && is_array($data['step_images'])) {
                                    foreach($data['step_images'] as $stepNum => $img) {
                                        $additionalImages[] = $img;
                                    }
                                }
                            }
                            // Удаляем дубликаты
                            $additionalImages = array_unique($additionalImages);
                        @endphp
                        
                        @foreach($additionalImages as $image)
                            <div class="swiper-slide">
                                <img src="{{ asset($image) }}" class="img-fluid rounded shadow w-100" 
                                     alt="{{ $recipe->title }} - изображение {{ $loop->iteration + 1 }}"
                                     data-original-src="{{ asset($image) }}"
                                     onerror="window.handleImageError(this)">
                            </div>
                        @endforeach
                    </div>
                    
                    <!-- Добавляем навигацию слайдера -->
                    <div class="swiper-button-next"></div>
                    <div class="swiper-button-prev"></div>
                    <div class="swiper-pagination"></div>
                </div>
            </div>

            
            <!-- JavaScript для инициализации слайдера -->
            <script>
                document.addEventListener('DOMContentLoaded', function() {
                    // Проверяем, загружен ли Swiper
                    if (typeof Swiper !== 'undefined') {
                        initRecipeSwiper();
                    } else {
                        // Загружаем Swiper динамически, если он не доступен
                        loadSwiper();
                    }
                    
                    function loadSwiper() {
                        // Загружаем CSS
                        const swiperCss = document.createElement('link');
                        swiperCss.rel = 'stylesheet';
                        swiperCss.href = 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.css';
                        document.head.appendChild(swiperCss);
                        
                        // Загружаем JavaScript
                        const swiperJs = document.createElement('script');
                        swiperJs.src = 'https://cdn.jsdelivr.net/npm/swiper@10/swiper-bundle.min.js';
                        swiperJs.onload = initRecipeSwiper;
                        document.body.appendChild(swiperJs);
                    }
                    
                    function initRecipeSwiper() {
                        new Swiper('.recipe-swiper', {
                            slidesPerView: 1,
                            spaceBetween: 10,
                            loop: {{ count($additionalImages) > 0 ? 'true' : 'false' }},
                            autoplay: {
                                delay: 5000,
                                disableOnInteraction: false,
                            },
                            pagination: {
                                el: '.swiper-pagination',
                                clickable: true,
                            },
                            navigation: {
                                nextEl: '.swiper-button-next',
                                prevEl: '.swiper-button-prev',
                            },
                            effect: 'fade',
                            fadeEffect: {
                                crossFade: true
                            },
                            lazy: true,
                        });
                    }
                });
            </script>
            
            <!-- Описание -->
            @if($recipe->description)
                <div class="recipe-description mb-4">
                    <h2 class="h5 border-bottom pb-2 mb-3">Описание</h2>
                    <p class="lead summary">{{ $recipe->description }}</p>
                </div>
            @endif
            
            <!-- Единый информационный блок для мобильных устройств -->
            <div class="d-md-none mobile-info-section mb-4">
                <!-- Основная информация о рецепте -->
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-header bg-primary text-white py-2">
                        <h3 class="mb-0 h5">Информация о рецепте</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="d-flex flex-wrap justify-content-between">
                            <div class="info-item text-center px-2 py-1 flex-grow-1">
                                <i class="far fa-clock text-primary d-block mb-1"></i>
                                <div class="fw-bold">{{ $recipe->cooking_time ?? '-' }} мин</div>
                                <small class="text-muted">Время</small>
                            </div>
                            <div class="info-item text-center px-2 py-1 flex-grow-1">
                                <i class="fas fa-utensils text-primary d-block mb-1"></i>
                                <div class="fw-bold">{{ $recipe->servings ?? '-' }}</div>
                                <small class="text-muted">Порций</small>
                            </div>
                            <div class="info-item text-center px-2 py-1 flex-grow-1">
                                <i class="fas fa-fire text-primary d-block mb-1"></i>
                                <div class="fw-bold">{{ $recipe->calories ?? '-' }} ккал</div>
                                <small class="text-muted">Калории</small>
                            </div>
                            <div class="info-item text-center px-2 py-1 flex-grow-1">
                                <i class="far fa-eye text-primary d-block mb-1"></i>
                                <div class="fw-bold">{{ $recipe->views }}</div>
                                <small class="text-muted">Просмотров</small>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Категории -->
                @if($recipe->categories->isNotEmpty())
                <div class="card border-0 shadow-sm mb-3">
                    <div class="card-body p-2">
                        <h6 class="mb-2 fw-bold">Категории:</h6>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach($recipe->categories as $category)
                                <a href="{{ route('categories.show', $category->slug) }}" class="btn btn-sm btn-outline-secondary">
                                    {{ $category->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
                @endif
                
                <!-- Питательная ценность - компактный вид для мобильных -->
                @if($recipe->proteins || $recipe->fats || $recipe->carbs)
                <div class="card border-0 shadow-sm">
                    <div class="card-header bg-success text-white py-2">
                        <h3 class="mb-0 h5">Пищевая ценность</h3>
                    </div>
                    <div class="card-body p-2">
                        <div class="row g-1">
                            @if($recipe->proteins)
                            <div class="col-6">
                                <div class="nutrition-item rounded border p-1 text-center">
                                    <small class="fw-bold text-success">Белки</small>
                                    <div class="fs-6 mb-0">{{ $recipe->proteins }} г</div>
                                </div>
                            </div>
                            @endif
                            
                            @if($recipe->fats)
                            <div class="col-6">
                                <div class="nutrition-item rounded border p-1 text-center">
                                    <small class="fw-bold text-success">Жиры</small>
                                    <div class="fs-6 mb-0">{{ $recipe->fats }} г</div>
                                </div>
                            </div>
                            @endif
                            
                            @if($recipe->carbs)
                            <div class="col-6">
                                <div class="nutrition-item rounded border p-1 text-center">
                                    <small class="fw-bold text-success">Углеводы</small>
                                    <div class="fs-6 mb-0">{{ $recipe->carbs }} г</div>
                                </div>
                            </div>
                            @endif
                            
                            @if($recipe->calories)
                            <div class="col-6">
                                <div class="nutrition-item rounded border p-1 text-center">
                                    <small class="fw-bold text-success">Калории</small>
                                    <div class="fs-6 mb-0">{{ $recipe->calories }} ккал</div>
                                </div>
                            </div>
                            @endif
                        </div>
                    </div>
                </div>
                @endif
            </div>

            <!-- Ингредиенты -->
            <div class="recipe-ingredients mb-4" id="ingredients">
                <h2 class="h4 border-bottom pb-2 mb-3">
                    <i class="fas fa-shopping-basket me-2 text-primary"></i> Ингредиенты
                </h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <!-- Калькулятор порций -->
                        <div class="serving-calculator mb-3">
                            <div class="d-flex align-items-center serving-calculator-flex justify-content-between">
                               
                                    <label for="servings-input" class="form-label mb-0 fw-bold col-sm-12">
                                        <i class="fas fa-calculator me-1 text-primary"></i> Калькулятор порций:
                                    </label>
                             
                                <div class="input-group serving-input col-sm-12" style="width: auto;" >
                                    <button type="button" class="btn btn-outline-secondary serving-btn" id="decrease-servings">
                                        <i class="fas fa-minus"></i>
                                    </button>
                                    <input type="number" class="form-control text-center" id="servings-input" min="1" value="{{ $recipe->servings ?? 4 }}" 
                                           data-original="{{ $recipe->servings ?? 4 }}" style="max-width: 70px;">
                                    <button type="button" class="btn btn-outline-secondary serving-btn" id="increase-servings">
                                        <i class="fas fa-plus"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary ms-2" id="reset-servings">
                                        <i class="fas fa-undo-alt me-1"></i> Сбросить
                                    </button>
                                </div>
                            </div>
                            <div class="text-center mt-2 serving-message d-none alert alert-success py-1">
                                <small>Количество ингредиентов пересчитано на <span id="serving-count">{{ $recipe->servings ?? 4 }}</span> {{ trans_choice('порция|порции|порций', $recipe->servings ?? 4) }}</small>
                            </div>
                        </div>
                        
                        <div class="d-flex justify-content-end mb-3">
                            <button class="btn btn-sm btn-outline-primary" id="toggle-checkboxes">
                                <i class="fas fa-tasks me-1"></i> Отметить купленные
                            </button>
                        </div>
                        <div class="ingredients-list">
                            @if(isset($recipe->ingredients) && is_object($recipe->ingredients) && $recipe->ingredients->count() > 0)
                                @php
                                    $ingredientGroups = $recipe->ingredients->groupBy('ingredient_group_id');
                                @endphp

                                @foreach($ingredientGroups as $groupId => $groupIngredients)
                                    @if($groupId && $group = \App\Models\IngredientGroup::find($groupId))
                                        <h5 class="ingredient-group-title mb-2">{{ $group->name }}</h5>
                                    @endif

                                    <ul class="list-group list-group-flush mb-3">
                                        @foreach($groupIngredients as $ingredient)
                                            <li class="list-group-item ingredient-item d-flex align-items-center border-0 py-2 ingredient">
                                                <div class="ingredient-checkbox me-2 d-none">
                                                    <input type="checkbox" class="form-check-input" id="ingredient-{{ $ingredient->id }}">
                                                </div>
                                                <label class="ingredient-label mb-0" for="ingredient-{{ $ingredient->id }}">
                                                    @if($ingredient->quantity)
                                                        <span class="fw-bold">
                                                            <span class="amount" data-original="{{ $ingredient->quantity }}">{{ $ingredient->quantity }}</span> 
                                                            <span class="type">{{ $ingredient->unit ?? '' }}</span>
                                                        </span>
                                                    @endif
                                                    <span class="name">{{ $ingredient->name }}</span>
                                                    @if($ingredient->optional)
                                                        <span class="text-muted">(по желанию)</span>
                                                    @endif
                                                    @if($ingredient->notes)
                                                        <span class="text-muted notes"> - {{ $ingredient->notes }}</span>
                                                    @endif
                                                </label>
                                            </li>
                                        @endforeach
                                    </ul>
                                @endforeach
                            @elseif($recipe->structured_ingredients)
                                <!-- Используем структурированные ингредиенты, если нет DB записей -->
                                @foreach($recipe->structured_ingredients as $group)
                                    @if(isset($group['name']) && isset($group['items']))
                                        <!-- Это группа ингредиентов -->
                                        <h5 class="ingredient-group-title mb-2">{{ $group['name'] }}</h5>
                                        <ul class="list-group list-group-flush mb-3">
                                            @foreach($group['items'] as $ingredient)
                                                <li class="list-group-item ingredient-item d-flex align-items-center border-0 py-2 ingredient">
                                                    <div class="ingredient-checkbox me-2 d-none">
                                                        <input type="checkbox" class="form-check-input" id="ingredient-{{ $loop->parent->index }}-{{ $loop->index }}">
                                                    </div>
                                                    <label class="ingredient-label mb-0" for="ingredient-{{ $loop->parent->index }}-{{ $loop->index }}">
                                                        @if(isset($ingredient['quantity']) && $ingredient['quantity'])
                                                            <span class="fw-bold">
                                                                <span class="amount" data-original="{{ $ingredient['quantity'] }}">{{ $ingredient['quantity'] }}</span> 
                                                                <span class="type">{{ $ingredient['unit'] ?? '' }}</span>
                                                            </span>
                                                        @endif
                                                        <span class="name">{{ $ingredient['name'] }}</span>
                                                        @if(isset($ingredient['optional']) && $ingredient['optional'])
                                                            <span class="text-muted">(по желанию)</span>
                                                        @endif
                                                        @if(isset($ingredient['notes']) && $ingredient['notes'])
                                                            <span class="text-muted notes"> - {{ $ingredient['notes'] }}</span>
                                                        @endif
                                                    </label>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @else
                                        <!-- Это одиночный ингредиент -->
                                        <ul class="list-group list-group-flush mb-3">
                                            <li class="list-group-item ingredient-item d-flex align-items-center border-0 py-2 ingredient">
                                                <div class="ingredient-checkbox me-2 d-none">
                                                    <input type="checkbox" class="form-check-input" id="ingredient-single-{{ $loop->index }}">
                                                </div>
                                                <label class="ingredient-label mb-0" for="ingredient-single-{{ $loop->index }}">
                                                    @if(isset($group['quantity']) && $group['quantity'])
                                                        <span class="fw-bold">
                                                            <span class="amount" data-original="{{ $group['quantity'] }}">{{ $group['quantity'] }}</span> 
                                                            <span class="type">{{ $group['unit'] ?? '' }}</span>
                                                        </span>
                                                    @endif
                                                    <span class="name">{{ $group['name'] ?? 'Ингредиент' }}</span>
                                                    @if(isset($group['optional']) && $group['optional'])
                                                        <span class="text-muted">(по желанию)</span>
                                                    @endif
                                                    @if(isset($group['notes']) && $group['notes'])
                                                        <span class="text-muted notes"> - {{ $group['notes'] }}</span>
                                                    @endif
                                                </label>
                                            </li>
                                        </ul>
                                    @endif
                                @endforeach
                            @else
                                <ul class="list-group list-group-flush">
                                    @foreach($recipe->getIngredientsArray() as $index => $ingredient)
                                        @if(!empty(trim($ingredient)))
                                            <li class="list-group-item ingredient-item d-flex align-items-center border-0 py-2 ingredient">
                                                <div class="ingredient-checkbox me-2 d-none">
                                                    <input type="checkbox" class="form-check-input" id="ingredient-{{ $index }}">
                                                </div>
                                                <label class="ingredient-label mb-0" for="ingredient-{{ $index }}">
                                                    <span class="name">{{ $ingredient }}</span>
                                                </label>
                                            </li>
                                        @endif
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <!-- Инструкции -->
            <div class="recipe-instructions mb-4" id="instructions">
                <h2 class="h4 border-bottom pb-2 mb-3">
                    <i class="fas fa-list-ol me-2 text-primary"></i> Пошаговые инструкции
                </h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <ol class="instructions-list instructions">
                            @php
                                // Проверяем тип данных и обрабатываем соответственно
                                $instructionsArray = [];
                                if (is_array($recipe->instructions)) {
                                    // Если это уже массив, используем его напрямую
                                    $instructionsArray = $recipe->instructions;
                                } elseif (is_string($recipe->instructions)) {
                                    // Если это строка, разбиваем ее на строки
                                    $instructionsArray = preg_split('/\r\n|\r|\n/', $recipe->instructions);
                                }
                            @endphp

                            @if(is_array($instructionsArray) || $instructionsArray instanceof \Traversable)
                                @foreach($instructionsArray as $index => $instruction)
                                    @php
                                        // Определяем текст инструкции в зависимости от структуры
                                        $instructionText = is_array($instruction) ? 
                                            ($instruction['text'] ?? '') : 
                                            (is_string($instruction) ? $instruction : '');
                                    @endphp
                                    @if(!empty(trim($instructionText)))
                                        <li class="mb-3 instruction">
                                            <p>{{ $instructionText }}</p>
                                            @if(is_array($instruction) && isset($instruction['image']) && !empty($instruction['image']))
                                                <div class="instruction-image mt-2">
                                                    <img src="{{ asset($instruction['image']) }}" 
                                                         alt="Шаг {{ $index + 1 }}" 
                                                         class="img-fluid rounded"
                                                         onerror="this.onerror=null; this.src='{{ asset('images/placeholder.jpg') }}';">
                                                </div>
                                            @endif
                                        </li>
                                    @endif
                                @endforeach
                            @else
                                <li class="mb-3 instruction">
                                    <p>Подробные инструкции отсутствуют.</p>
                                </li>
                            @endif
                        </ol>
                    </div>
                </div>
            </div>
            
            <!-- Метаданные для микроформата hRecipe -->
            <div class="recipe-metadata">
                <span class="duration">{{ $recipe->total_time ?? ($recipe->cooking_time ? 'PT'.($recipe->cooking_time + 15).'M' : 'PT45M') }}</span>
                <span class="published">{{ $recipe->created_at->toIso8601String() }}</span>
                <span class="yield">{{ $recipe->servings ?? '4' }} порции</span>
                <span class="author">{{ $recipe->user ? $recipe->user->name : config('app.name') }}</span>
            </div>
            
            <!-- Заметки и советы - скрываем на мобильных устройствах -->
            <div class="recipe-notes mb-4 d-none d-md-block" id="notes">
                <h2 class="h4 border-bottom pb-2 mb-3">
                    <i class="fas fa-sticky-note me-2 text-primary"></i> Заметки и советы
                </h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <p>Здесь вы можете добавить свои заметки и советы по приготовлению рецепта.</p>
                    </div>
                </div>
            </div>
            
            <!-- Кнопки действий -->
            <div class="recipe-actions mb-4">
                <div class="d-flex flex-wrap gap-2">
                    <button class="btn btn-outline-primary">
                        <i class="far fa-bookmark me-1"></i> Сохранить
                    </button>
                    <button class="btn btn-outline-success">
                        <i class="fas fa-print me-1"></i> Распечатать
                    </button>
                    <!-- Поделиться в соцсетях -->
                    <div class="dropdown">
                        <button class="btn btn-outline-info dropdown-toggle" type="button" id="shareDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-share-alt me-1"></i> Поделиться
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="shareDropdown">
                            <li>
                                <a class="dropdown-item" href="https://vk.com/share.php?url={{ urlencode(route('recipes.show', $recipe->slug)) }}&title={{ urlencode($recipe->title) }}" target="_blank">
                                    <i class="fab fa-vk text-primary me-2"></i> ВКонтакте
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="https://connect.ok.ru/offer?url={{ urlencode(route('recipes.show', $recipe->slug)) }}&title={{ urlencode($recipe->title) }}" target="_blank">
                                    <i class="fab fa-odnoklassniki text-warning me-2"></i> Одноклассники
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="https://t.me/share/url?url={{ urlencode(route('recipes.show', $recipe->slug)) }}&text={{ urlencode($recipe->title) }}" target="_blank">
                                    <i class="fab fa-telegram text-info me-2"></i> Телеграм
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="https://wa.me/?text={{ urlencode($recipe->title . ' ' . route('recipes.show', $recipe->slug)) }}" target="_blank">
                                    <i class="fab fa-whatsapp text-success me-2"></i> WhatsApp
                                </a>
                            </li>
                            <li>
                                <a class="dropdown-item" href="https://dzen.ru/share/?url={{ urlencode(route('recipes.show', $recipe->slug)) }}" target="_blank">
                                    <i class="fas fa-bold text-danger me-2"></i> Дзен
                                </a>
                            </li>
                        </ul>
                    </div>
                    <button class="btn btn-outline-warning" id="scaleRecipe">
                        <i class="fas fa-balance-scale me-1"></i> Изменить порции
                    </button>
                </div>
            </div>
            
            <!-- Похожие рецепты -->
            <div class="related-recipes mb-4" id="related">
                <h2 class="h4 border-bottom pb-2 mb-3">
                    <i class="fas fa-utensils me-2 text-primary"></i> Похожие рецепты
                </h2>
                
                @if($relatedRecipes->count() > 0)
                    <div class="row row-cols-1 row-cols-md-3 g-4">
                        @foreach($relatedRecipes as $relatedRecipe)
                            <div class="col">
                                <div class="card h-100 border-0 shadow-sm">
                                    <img src="{{ asset($relatedRecipe->image_url) }}" class="card-img-top related-recipe-img" 
                                         alt="{{ $relatedRecipe->title }}"
                                         data-original-src="{{ asset($relatedRecipe->image_url) }}"
                                         onerror="window.handleImageError(this)">
                                    <div class="card-body">
                                        <h5 class="card-title">{{ $relatedRecipe->title }}</h5>
                                        @if($relatedRecipe->cooking_time)
                                            <p class="card-text text-muted">
                                                <i class="far fa-clock me-1"></i> {{ $relatedRecipe->cooking_time }} мин
                                            </p>
                                        @endif
                                    </div>
                                    <div class="card-footer bg-transparent border-0">
                                        <a href="{{ route('recipes.show', $relatedRecipe->slug) }}" class="btn btn-primary w-100">
                                            <i class="fas fa-eye me-1"></i> Посмотреть
                                        </a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle me-2"></i> Похожие рецепты не найдены.
                    </div>
                @endif
            </div>
            
            <!-- Система рейтингов и отзывов -->
            <div class="recipe-rating mb-4" id="rating">
                <h2 class="h4 border-bottom pb-2 mb-3">
                    <i class="fas fa-star me-2 text-warning"></i> Оценка рецепта
                </h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        @php
                            $hasRated = false;
                            $currentRating = 0;
                            $totalRatings = 0;
                            $averageRating = 0;
                            
                            if (isset($recipe->additional_data['rating'])) {
                                $totalRatings = $recipe->additional_data['rating']['count'] ?? 0;
                                $averageRating = $recipe->additional_data['rating']['value'] ?? 0;
                            }
                            
                            if (auth()->check() && isset($recipe->additional_data['user_ratings']) && 
                                isset($recipe->additional_data['user_ratings'][auth()->id()])) {
                                $hasRated = true;
                                $currentRating = $recipe->additional_data['user_ratings'][auth()->id()];
                            }
                        @endphp
                        
                        <div class="row align-items-center">
                            <div class="col-md-4 text-center mb-3 mb-md-0">
                                <div class="display-4 mb-0 fw-bold text-warning">{{ number_format($averageRating, 1) }}</div>
                                <div class="d-flex justify-content-center align-items-center mb-2">
                                    @for($i = 1; $i <= 5; $i++)
                                        @if($i <= round($averageRating))
                                            <i class="fas fa-star text-warning me-1"></i>
                                        @elseif($i - 0.5 <= $averageRating)
                                            <i class="fas fa-star-half-alt text-warning me-1"></i>
                                        @else
                                            <i class="far fa-star text-warning me-1"></i>
                                        @endif
                                    @endfor
                                </div>
                                <div class="text-muted">
                                    {{ $totalRatings }} {{ trans_choice('оценка|оценки|оценок', $totalRatings) }}
                                </div>
                            </div>
                            
                            <div class="col-md-8">
                                @auth
                                    <form action="{{ route('recipes.rate', $recipe->id) }}" method="POST" class="rating-form">
                                        @csrf
                                        <div class="mb-3">
                                            <label class="form-label">Ваша оценка:</label>
                                            <div class="star-rating">
                                                @for($i = 5; $i >= 1; $i--)
                                                    <input type="radio" id="star{{ $i }}" name="rating" value="{{ $i }}" 
                                                           {{ $currentRating == $i ? 'checked' : '' }} />
                                                    <label for="star{{ $i }}" title="{{ $i }} звезд">
                                                        <i class="fas fa-star"></i>
                                                    </label>
                                                @endfor
                                            </div>
                                        </div>
                                        
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-paper-plane me-1"></i> 
                                            {{ $hasRated ? 'Обновить оценку' : 'Оценить рецепт' }}
                                        </button>
                                    </form>
                                @else
                                    <div class="alert alert-info mb-0">
                                        <i class="fas fa-info-circle me-2"></i> 
                                        Чтобы оценить рецепт, пожалуйста, <a href="{{ route('login') }}">войдите</a> или 
                                        <a href="{{ route('register') }}">зарегистрируйтесь</a>.
                                    </div>
                                @endauth
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Комментарии и пользовательские отзывы -->
            <div class="recipe-comments">
                <h2 class="h4 border-bottom pb-2 mb-3">
                    <i class="fas fa-comments me-2 text-primary"></i> Комментарии и отзывы
                </h2>
                <div class="card border-0 shadow-sm">
                    <div class="card-body">
                        <!-- Показ существующих комментариев -->
                        @if($recipe->comments && $recipe->comments->count() > 0)
                            <div class="comments-list mb-4">
                                @foreach($recipe->comments as $comment)
                                    <div class="comment mb-3 pb-3 border-bottom" id="comment-{{ $comment->id }}">
                                        <div class="d-flex align-items-start">
                                            @if($comment->user && $comment->user->avatar)
                                                <img src="{{ asset('storage/'.$comment->user->avatar) }}" alt="{{ $comment->user->name }}" 
                                                     class="rounded-circle me-2" style="width: 40px; height: 40px; object-fit: cover;">
                                            @else
                                                <div class="avatar-placeholder rounded-circle me-2 d-flex align-items-center justify-content-center text-white bg-primary" 
                                                     style="width: 40px; height: 40px; font-size: 1.2rem;">
                                                    {{ strtoupper(substr($comment->user->name ?? 'A', 0, 1)) }}
                                                </div>
                                            @endif
                                            
                                            <div class="flex-grow-1">
                                                <div class="d-flex justify-content-between align-items-center mb-1">
                                                    <div>
                                                        <strong>{{ $comment->user->name ?? 'Аноним' }}</strong>
                                                        <small class="text-muted ms-2">{{ $comment->created_at->diffForHumans() }}</small>
                                                    </div>
                                                    
                                                    @auth
                                                        @if(auth()->user()->id === $comment->user_id || auth()->user()->isAdmin())
                                                            <div class="dropdown">
                                                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                                                    <i class="fas fa-ellipsis-v"></i>
                                                                </button>
                                                                <ul class="dropdown-menu dropdown-menu-end">
                                                                    <li>
                                                                        <form action="{{ route('comments.destroy', $comment->id) }}" method="POST" 
                                                                              onsubmit="return confirm('Вы уверены, что хотите удалить этот комментарий?')">
                                                                            @csrf
                                                                            @method('DELETE')
                                                                            <button type="submit" class="dropdown-item text-danger">
                                                                                <i class="fas fa-trash-alt me-1"></i> Удалить
                                                                            </button>
                                                                        </form>
                                                                    </li>
                                                                </ul>
                                                            </div>
                                                        @endif
                                                    @endauth
                                                </div>
                                                
                                                <p class="mb-1">{{ $comment->content }}</p>
                                                
                                                <div class="comment-actions">
                                                    <button class="btn btn-sm btn-link text-decoration-none p-0 reply-btn" 
                                                            data-comment-id="{{ $comment->id }}">
                                                        <i class="fas fa-reply me-1"></i> Ответить
                                                    </button>
                                                </div>
                                                
                                                <!-- Форма ответа на комментарий (скрыта по умолчанию) -->
                                                <div class="reply-form mt-2 d-none" id="reply-form-{{ $comment->id }}">
                                                    @auth
                                                        <form action="{{ route('comments.reply', $comment->id) }}" method="POST">
                                                            @csrf
                                                            <div class="mb-2">
                                                                <textarea class="form-control form-control-sm" name="reply" rows="2" required
                                                                          placeholder="Напишите ваш ответ..."></textarea>
                                                            </div>
                                                            <div class="d-flex justify-content-end">
                                                                <button type="button" class="btn btn-sm btn-light me-2 cancel-reply"
                                                                        data-comment-id="{{ $comment->id }}">
                                                                    Отмена
                                                                </button>
                                                                <button type="submit" class="btn btn-sm btn-primary">
                                                                    <i class="fas fa-paper-plane me-1"></i> Отправить
                                                                </button>
                                                            </div>
                                                        </form>
                                                    @else
                                                        <div class="alert alert-info py-2 mb-0">
                                                            Чтобы ответить, пожалуйста, <a href="{{ route('login') }}">войдите</a>
                                                        </div>
                                                    @endauth
                                                </div>
                                                
                                                <!-- Дочерние комментарии (ответы) -->
                                                @if($comment->replies && $comment->replies->count() > 0)
                                                    <div class="replies mt-3 ps-4 border-start">
                                                        @foreach($comment->replies as $reply)
                                                            <div class="reply mb-2 pb-2 {{ !$loop->last ? 'border-bottom' : '' }}">
                                                                <div class="d-flex align-items-start">
                                                                    @if($reply->user && $reply->user->avatar)
                                                                        <img src="{{ asset('storage/'.$reply->user->avatar) }}" 
                                                                             alt="{{ $reply->user->name }}" 
                                                                             class="rounded-circle me-2" 
                                                                             style="width: 30px; height: 30px; object-fit: cover;">
                                                                    @else
                                                                        <div class="avatar-placeholder rounded-circle me-2 d-flex align-items-center justify-content-center text-white bg-primary" 
                                                                             style="width: 30px; height: 30px; font-size: 0.9rem;">
                                                                            {{ strtoupper(substr($reply->user->name ?? 'A', 0, 1)) }}
                                                                        </div>
                                                                    @endif
                                                                    
                                                                    <div class="flex-grow-1">
                                                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                                                            <div>
                                                                                <strong>{{ $reply->user->name ?? 'Аноним' }}</strong>
                                                                                <small class="text-muted ms-2">{{ $reply->created_at->diffForHumans() }}</small>
                                                                            </div>
                                                                        </div>
                                                                        
                                                                        <p class="mb-0">{{ $reply->content }}</p>
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="alert alert-info mb-3">
                                <i class="fas fa-info-circle me-2"></i> Будьте первым, кто оставит комментарий к этому рецепту!
                            </div>
                        @endif
                        
                        <!-- Форма добавления комментария -->
                        @auth
                            <form action="{{ route('comments.store') }}" method="POST">
                                @csrf
                                <input type="hidden" name="recipe_id" value="{{ $recipe->id }}">
                                <div class="mb-3">
                                    <label for="comment" class="form-label">Ваш комментарий</label>
                                    <textarea class="form-control @error('comment') is-invalid @enderror" id="comment" name="comment" rows="3" required></textarea>
                                    @error('comment')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                                <button type="submit" class="btn btn-primary">
                                    <i class="far fa-paper-plane me-1"></i> Отправить
                                </button>
                            </form>
                        @else
                            <div class="alert alert-warning">
                                <i class="fas fa-lock me-2"></i> Для добавления комментариев необходимо <a href="{{ route('login') }}">войти</a> или <a href="{{ route('register') }}">зарегистрироваться</a>.
                            </div>
                        @endauth
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Боковая панель - только для десктопа -->
        <div class="col-lg-4 d-none d-lg-block">
            <!-- Блок приготовления и порций -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h3 class="mb-0 h5">Информация о рецепте</h3>
                </div>
                <div class="card-body">
                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="far fa-clock me-2 text-primary"></i> Время приготовления:</span>
                            <span class="fw-bold">{{ $recipe->cooking_time ?? 'Не указано' }} мин</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-utensils me-2 text-primary"></i> Порций:</span>
                            <span class="fw-bold">{{ $recipe->servings ?? 'Не указано' }}</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-fire ме-2 text-primary"></i> Калорийность:</span>
                            <span class="fw-bold">{{ $recipe->calories ?? 'Не указано' }} ккал</span>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span><i class="fas fa-eye me-2 text-primary"></i> Просмотров:</span>
                            <span class="fw-bold">{{ $recipe->views }}</span>
                        </li>
                    </ul>
                </div>
            </div>

            <!-- Блок энергетической ценности -->
            @if($recipe->calories || $recipe->proteins || $recipe->fats || $recipe->carbs)
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-success text-white">
                    <h3 class="mb-0 h5">Энергетическая ценность (на порцию)</h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        @if($recipe->calories)
                        <div class="col-6 mb-3">
                            <div class="nutrition-item p-3 h-100 rounded border text-center">
                                <div class="fw-bold text-success">
                                    <i class="fas fa-fire me-1"></i> Калории
                                </div>
                                <div class="h4 mb-0 mt-2">{{ $recipe->calories }} ккал</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($recipe->proteins)
                        <div class="col-6 mb-3">
                            <div class="nutrition-item p-3 h-100 rounded border text-center">
                                <div class="fw-bold text-success">
                                    <i class="fas fa-drumstick-bite me-1"></i> Белки
                                </div>
                                <div class="h4 mb-0 mt-2">{{ $recipe->proteins }} г</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($recipe->fats)
                        <div class="col-6 mb-3">
                            <div class="nutrition-item p-3 h-100 rounded border text-center">
                                <div class="fw-bold text-success">
                                    <i class="fas fa-cheese me-1"></i> Жиры
                                </div>
                                <div class="h4 mb-0 mt-2">{{ $recipe->fats }} г</div>
                            </div>
                        </div>
                        @endif
                        
                        @if($recipe->carbs)
                        <div class="col-6 mb-3">
                            <div class="nutrition-item p-3 h-100 rounded border text-center">
                                <div class="fw-bold text-success">
                                    <i class="fas fa-bread-slice me-1"></i> Углеводы
                                </div>
                                <div class="h4 mb-0 mt-2">{{ $recipe->carbs }} г</div>
                            </div>
                        </div>
                        @endif
                    </div>
                    
                    @if($recipe->servings)
                    <div class="alert alert-info mb-0 mt-2">
                        <i class="fas fa-info-circle me-2"></i>
                        Приведены значения в расчете на порцию. Рецепт рассчитан на {{ $recipe->servings }} {{ trans_choice('порция|порции|порций', $recipe->servings) }}.
                    </div>
                    @endif
                </div>
            </div>
            @endif

            <!-- Автор рецепта -->
            @if($recipe->user)
                <div class="card mb-4 border-0 shadow-sm">
                    <div class="card-header bg-light">
                        <h3 class="mb-0 h5">Автор рецепта</h3>
                    </div>
                    <div class="card-body text-center">
                        @if($recipe->user->avatar)
                            <img src="{{ asset('storage/'.$recipe->user->avatar) }}" alt="{{ $recipe->user->name }}" class="rounded-circle mb-3" style="width: 80px; height: 80px; object-fit: cover;">
                        @else
                            <div class="avatar-placeholder rounded-circle mx-auto d-flex align-items-center justify-content-center text-white bg-primary mb-3" style="width: 80px; height: 80px; font-size: 2rem;">
                                {{ strtoupper(substr($recipe->user->name, 0, 1)) }}
                            </div>
                        @endif
                        <h5 class="mb-1">{{ $recipe->user->name }}</h5>
                        @if($recipe->user->isAdmin())
                            <span class="badge bg-danger mb-2">Администратор</span>
                        @endif
                        <p class="text-muted small mb-3">{{ $recipe->user->bio ?: 'Автор рецептов' }}</p>
                        <a href="{{ route('profile.show', $recipe->user->id) }}" class="btn btn-outline-primary btn-sm">
                            <i class="fas fa-user me-1"></i> Профиль автора
                        </a>
                        @if($recipe->user->isAdmin())
                            <div class="mt-2">
                                <a href="https://vk.com/imedokru" target="_blank" class="btn btn-outline-secondary btn-sm">
                                    <i class="fab fa-vk me-1"></i> ВК
                                </a>
                                <a href="https://dzen.ru/imedok" target="_blank" class="btn btn-outline-danger btn-sm">
                                    <i class="fas fa-bold me-1"></i> Дзен
                                </a>
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- Поиск по сайту -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="mb-0 h5">Поиск рецептов</h3>
                </div>
                <div class="card-body">
                    <form action="{{ route('search') }}" method="GET">
                        <div class="input-group">
                            <input type="text" class="form-control" placeholder="Название или ингредиент..." name="query">
                            <button class="btn btn-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Категории -->
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="mb-0 h5">Категории</h3>
                </div>
                <div class="card-body">
                    <div class="row row-cols-2 g-2">
                        @foreach($recipe->categories as $category)
                            <div class="col">
                                <a href="{{ route('categories.show', $category->slug) }}" class="btn btn-outline-secondary btn-sm w-100">
                                    {{ $category->name }}
                                </a>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            
            <!-- Теги (заглушка) -->
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h3 class="mb-0 h5">Теги</h3>
                </div>
                <div class="card-body">
                    <div class="d-flex flex-wrap gap-2">
                        <a href="#" class="badge bg-light text-dark text-decoration-none p-2">рецепт</a>
                        <a href="#" class="badge bg-light text-dark text-decoration-none p-2">домашняя кухня</a>
                        <a href="#" class="badge bg-light text-dark text-decoration-none p-2">просто</a>
                        <a href="#" class="badge bg-light text-dark text-decoration-none p-2">вкусно</a>
                        <a href="#" class="badge bg-light text-dark text-decoration-none p-2">быстро</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="back-to-top" id="back-to-top">
    <i class="fas fa-arrow-up"></i>
</div>


<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Переключение отображения чекбоксов для ингредиентов
        const toggleCheckboxesBtn = document.getElementById('toggle-checkboxes');
        const ingredientCheckboxes = document.querySelectorAll('.ingredient-checkbox');
        const ingredientLabels = document.querySelectorAll('.ingredient-label');
        
        if (toggleCheckboxesBtn) {
            toggleCheckboxesBtn.addEventListener('click', function() {
                ingredientCheckboxes.forEach(checkbox => {
                    checkbox.classList.toggle('d-none');
                });
                
                // Меняем текст кнопки
                if (ingredientCheckboxes[0].classList.contains('d-none')) {
                    toggleCheckboxesBtn.innerHTML = '<i class="fas fa-tasks me-1"></i> Отметить купленные';
                } else {
                    toggleCheckboxesBtn.innerHTML = '<i class="fas fa-times me-1"></i> Скрыть чекбоксы';
                }
            });
        }
        
        // Обработчик для чекбоксов ингредиентов
        document.querySelectorAll('.ingredient-item input[type="checkbox"]').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                const label = this.closest('.ingredient-item').querySelector('.ingredient-label');
                if (this.checked) {
                    label.classList.add('checked');
                } else {
                    label.classList.remove('checked');
                }
            });
        });
        
        // Изменение порций (заглушка)
        const scaleRecipeBtn = document.getElementById('scaleRecipe');
        if (scaleRecipeBtn) {
            scaleRecipeBtn.addEventListener('click', function() {
                const servings = prompt('Введите количество порций:', '{{ $recipe->servings ?? 4 }}');
                if (servings && !isNaN(servings)) {
                    alert('Функция изменения количества порций находится в разработке. Выбрано порций: ' + servings);
                }
            });
        }
        
        // Кнопка прокрутки вверх
        const backToTopBtn = document.getElementById('back-to-top');
        if (backToTopBtn) {
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopBtn.style.display = 'flex';
                } else {
                    backToTopBtn.style.display = 'none';
                }
            });

            backToTopBtn.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        }

        // Активация текущего элемента навигации при прокрутке
        const navLinks = document.querySelectorAll('.recipe-navigation a');
        const sections = document.querySelectorAll('h2');
        
        function setActiveNavItem() {
            const scrollPosition = window.scrollY + 100;
            
            for (let i = sections.length - 1; i >= 0; i--) {
                const section = sections[i];
                if (section.offsetTop <= scrollPosition) {
                    navLinks.forEach(link => {
                        link.classList.remove('active');
                    });
                    
                    const targetLink = document.querySelector(`.recipe-navigation a[href="#${section.parentElement.id}"]`);
                    if (targetLink) {
                        targetLink.classList.add('active');
                    }
                    
                    break;
                }
            }
        }
        
        window.addEventListener('scroll', setActiveNavItem);
        
        // Плавная прокрутка к разделам по якорям
        document.querySelectorAll('a[href^="#"]').forEach(anchor => {
            anchor.addEventListener('click', function(e) {
                e.preventDefault();
                const targetId = this.getAttribute('href');
                const targetElement = document.querySelector(targetId);

                if (targetElement) {
                    const navHeight = document.querySelector('.recipe-navigation').offsetHeight;
                    const targetPosition = targetElement.getBoundingClientRect().top + window.pageYOffset - navHeight;
                    
                    window.scrollTo({
                        top: targetPosition,
                        behavior: 'smooth'
                    });
                }
            });
        });
    });
</script>

@push('scripts')
<script>
    // Существующий код для рецепта
    // ...
    
    // Код для сохранения информации о рецепте для офлайн-режима PWA
    document.addEventListener('DOMContentLoaded', function() {
        if (typeof(Storage) !== "undefined") {
            try {
                // Получаем данные о рецепте
                const recipe = @json($pwaData['recipe'] ?? null);
                
                if (recipe) {
                    // Получаем существующие рецепты из localStorage
                    let recentRecipes = [];
                    if (localStorage.getItem('recentRecipes')) {
                        recentRecipes = JSON.parse(localStorage.getItem('recentRecipes'));
                    }
                    
                    // Проверяем, есть ли уже такой рецепт в массиве
                    const existingIndex = recentRecipes.findIndex(item => item.id === recipe.id);
                    
                    if (existingIndex !== -1) {
                        // Если рецепт уже есть, обновляем только время просмотра
                        recentRecipes[existingIndex].viewed_at = recipe.viewed_at;
                    } else {
                        // Если рецепта нет, добавляем его
                        recentRecipes.push(recipe);
                    }
                    
                    // Ограничиваем список 10 последними рецептами
                    recentRecipes = recentRecipes
                        .sort((a, b) => new Date(b.viewed_at) - new Date(a.viewed_at))
                        .slice(0, 10);
                    
                    // Сохраняем обновленный список
                    localStorage.setItem('recentRecipes', JSON.stringify(recentRecipes));
                }
            } catch (e) {
                console.error('Ошибка при сохранении информации о рецепте:', e);
            }
        }
    });
</script>

<script>
    // Калькулятор порций - скрипт для пересчета ингредиентов
    document.addEventListener('DOMContentLoaded', function() {
        const servingsInput = document.getElementById('servings-input');
        const decreaseBtn = document.getElementById('decrease-servings');
        const increaseBtn = document.getElementById('increase-servings');
        const resetBtn = document.getElementById('reset-servings');
        const servingMessage = document.querySelector('.serving-message');
        const servingCount = document.getElementById('serving-count');
        
        if(!servingsInput) return; // Выходим, если элемент не найден
        
        const originalServings = parseFloat(servingsInput.dataset.original) || 4;
        let currentServings = parseFloat(servingsInput.value) || originalServings;
        
        // Функция для пересчета количества ингредиентов
        function recalculateIngredients() {
            const ratio = currentServings / originalServings;
            const amountElements = document.querySelectorAll('.amount');
            
            amountElements.forEach(el => {
                if(!el.dataset.original) return;
                
                const originalAmount = parseFloat(el.dataset.original);
                if (!isNaN(originalAmount)) {
                    // Рассчитываем новое количество
                    let newAmount = originalAmount * ratio;
                    
                    // Форматируем число с учетом десятичных знаков
                    if (Number.isInteger(originalAmount)) {
                        // Если исходное число целое, округляем до 1 знака
                        newAmount = Math.round(newAmount * 10) / 10;
                    } else {
                        // Если исходное число дробное, сохраняем 2 знака
                        newAmount = Math.round(newAmount * 100) / 100;
                    }
                    
                    // Удаляем .0 в конце числа для большей читаемости
                    if (newAmount === Math.floor(newAmount)) {
                        newAmount = Math.floor(newAmount);
                    }
                    
                    // Обновляем отображение
                    el.textContent = newAmount;
                }
            });
            
            // Обновляем сообщение и показываем его
            servingCount.textContent = currentServings;
            servingMessage.classList.remove('d-none');
            
            // Обновляем склонение слова "порция"
            const lastDigit = currentServings % 10;
            const lastTwoDigits = currentServings % 100;
            let wordForm;
            
            if (lastTwoDigits >= 11 && lastTwoDigits <= 14) {
                wordForm = 'порций';
            } else if (lastDigit === 1) {
                wordForm = 'порция';
            } else if (lastDigit >= 2 && lastDigit <= 4) {
                wordForm = 'порции';
            } else {
                wordForm = 'порций';
            }
            
            servingMessage.querySelector('small').textContent = 
                `Количество ингредиентов пересчитано на ${currentServings} ${wordForm}`;
        }
        
        // Обработчики событий для кнопок и поля ввода
        decreaseBtn.addEventListener('click', function() {
            if (currentServings > 1) {
                currentServings -= 1;
                servingsInput.value = currentServings;
                recalculateIngredients();
            }
        });
        
        increaseBtn.addEventListener('click', function() {
            currentServings += 1;
            servingsInput.value = currentServings;
            recalculateIngredients();
        });
        
        resetBtn.addEventListener('click', function() {
            currentServings = originalServings;
            servingsInput.value = currentServings;
            recalculateIngredients();
            servingMessage.classList.add('d-none'); // Скрываем сообщение при сбросе
        });
        
        servingsInput.addEventListener('change', function() {
            const newValue = parseFloat(this.value);
            if (!isNaN(newValue) && newValue > 0) {
                currentServings = newValue;
                recalculateIngredients();
            } else {
                this.value = currentServings; // Возвращаем предыдущее значение если ввод некорректный
            }
        });
    });
</script>
@endpush

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Обработчики для форм ответа на комментарии
        const replyButtons = document.querySelectorAll('.reply-btn');
        const cancelButtons = document.querySelectorAll('.cancel-reply');
        
        replyButtons.forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const replyForm = document.getElementById(`reply-form-${commentId}`);
                replyForm.classList.remove('d-none');
            });
        });
        
        cancelButtons.forEach(button => {
            button.addEventListener('click', function() {
                const commentId = this.dataset.commentId;
                const replyForm = document.getElementById(`reply-form-${commentId}`);
                replyForm.classList.add('d-none');
            });
        });
        
        // Стилизация звездочного рейтинга
        const stars = document.querySelectorAll('.star-rating input');
        stars.forEach(star => {
            star.addEventListener('change', function() {
                // Подсветка звезд при выборе
                const rating = this.value;
                for (let i = 1; i <= 5; i++) {
                    const starLabel = document.querySelector(`label[for="star${i}"]`);
                    if (i <= rating) {
                        starLabel.classList.add('active');
                    } else {
                        starLabel.classList.remove('active');
                    }
                }
            });
        });
    });
</script>


@endsection




