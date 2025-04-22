@extends('layouts.news')

@section('title', isset($searchTerm) ? "Поиск: $searchTerm - Кулинарные новости" : "Кулинарные новости и статьи")

@section('schema_org')
    @include('schema_org.news_schema')
@endsection

@section('head')
    @include('seo.news_seo')
@endsection

@section('breadcrumbs')
<li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <span itemprop="name">Новости</span>
    <meta itemprop="position" content="2" />
</li>
@endsection

@section('content')
<div class="container py-4">
    <div class="row">
        <!-- Основной контент с новостями -->
        <div class="col-lg-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="news-title">Кулинарные новости</h1>
                <div class="d-flex">
                    <!-- Переключатель вида отображения (плитка/список) -->
                    <div class="btn-group me-2">
                        <button type="button" class="btn btn-outline-primary btn-sm view-type-btn" data-view="grid">
                            <i class="fas fa-th-large"></i>
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm view-type-btn" data-view="list">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                    <!-- Дропдаун для сортировки -->
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sort me-1"></i> Сортировка
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item {{ request('sort') == 'newest' || !request('sort') ? 'active' : '' }}" href="{{ route('news.index', array_merge(request()->except('sort'), ['sort' => 'newest'])) }}">Сначала новые</a></li>
                            <li><a class="dropdown-item {{ request('sort') == 'popular' ? 'active' : '' }}" href="{{ route('news.index', array_merge(request()->except('sort'), ['sort' => 'popular'])) }}">По популярности</a></li>
                            <li><a class="dropdown-item {{ request('sort') == 'oldest' ? 'active' : '' }}" href="{{ route('news.index', array_merge(request()->except('sort'), ['sort' => 'oldest'])) }}">Сначала старые</a></li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <!-- Категории новостей -->
            @if(isset($categories) && $categories->count() > 0)
            <div class="news-categories-wrapper mb-4">
                <div class="news-categories">
                    <a href="{{ route('news.index') }}" class="category-item {{ !request('category') ? 'active' : '' }}">
                        Все новости
                    </a>
                    @foreach($categories as $category)
                    <a href="{{ route('news.index', ['category' => $category->slug]) }}" 
                       class="category-item {{ request('category') == $category->slug ? 'active' : '' }}">
                        {{ $category->name }}
                    </a>
                    @endforeach
                </div>
                <button class="btn btn-sm btn-light category-scroll-btn category-scroll-right">
                    <i class="fas fa-chevron-right"></i>
                </button>
            </div>
            @endif
            
            <!-- Добавляем фильтры по типу новостей (видео/обычные) -->
            <div class="news-filters mb-4">
                <div class="btn-group w-100">
                    <a href="{{ route('news.index', array_merge(request()->except('type'), ['type' => ''])) }}" 
                       class="btn {{ !request('type') ? 'btn-primary' : 'btn-outline-primary' }} flex-fill">
                        <i class="fas fa-newspaper me-1"></i> Все новости
                    </a>
                    <a href="{{ route('news.index', array_merge(request()->except('type'), ['type' => 'video'])) }}" 
                       class="btn {{ request('type') == 'video' ? 'btn-primary' : 'btn-outline-primary' }} flex-fill">
                        <i class="fas fa-video me-1"></i> Видео новости
                    </a>
                    <a href="{{ route('news.index', array_merge(request()->except('type'), ['type' => 'regular'])) }}" 
                       class="btn {{ request('type') == 'regular' ? 'btn-primary' : 'btn-outline-primary' }} flex-fill">
                        <i class="fas fa-align-left me-1"></i> Обычные новости
                    </a>
                </div>
            </div>
            
            <!-- Форма поиска -->
            <div class="card mb-4">
                <div class="card-body">
                    <form action="{{ route('news.index') }}" method="GET" class="row g-3">
                        <div class="col-md-10">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search"></i></span>
                                <input type="text" name="search" class="form-control" placeholder="Поиск по новостям..." value="{{ $searchTerm ?? '' }}">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Найти</button>
                        </div>
                        
                        <!-- Активные фильтры -->
                        @if(request('category') || request('tag') || request('sort'))
                        <div class="col-12 mt-2">
                            <div class="active-filters d-flex flex-wrap gap-2 align-items-center">
                                <span class="text-muted small">Активные фильтры:</span>
                                
                                @if(request('category'))
                                <span class="badge bg-primary d-flex align-items-center">
                                    Категория: {{ request('category') }}
                                    <a href="{{ route('news.index', array_merge(request()->except('category'), ['search' => $searchTerm])) }}" class="ms-1 text-white" aria-label="Удалить фильтр">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                                @endif
                                
                                @if(request('tag'))
                                <span class="badge bg-info d-flex align-items-center">
                                    Тег: {{ request('tag') }}
                                    <a href="{{ route('news.index', array_merge(request()->except('tag'), ['search' => $searchTerm])) }}" class="ms-1 text-white" aria-label="Удалить фильтр">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                                @endif
                                
                                @if(request('sort'))
                                <span class="badge bg-secondary d-flex align-items-center">
                                    Сортировка: {{ request('sort') == 'newest' ? 'Сначала новые' : (request('sort') == 'popular' ? 'По популярности' : 'Сначала старые') }}
                                    <a href="{{ route('news.index', array_merge(request()->except('sort'), ['search' => $searchTerm])) }}" class="ms-1 text-white" aria-label="Удалить фильтр">
                                        <i class="fas fa-times-circle"></i>
                                    </a>
                                </span>
                                @endif
                                
                                <a href="{{ route('news.index') }}" class="btn btn-sm btn-outline-secondary">
                                    Сбросить все фильтры
                                </a>
                            </div>
                        </div>
                        @endif
                    </form>
                </div>
            </div>
            
            <!-- Результаты -->
            @if(isset($searchTerm) && $searchTerm)
                <div class="alert alert-info">
                    Результаты поиска по запросу: <strong>{{ $searchTerm }}</strong>
                    <a href="{{ route('news.index') }}" class="float-end">Сбросить</a>
                </div>
            @endif
            
            <!-- Список новостей (изначально в режиме сетки) -->
            <div id="news-container" class="row view-grid" itemscope itemtype="https://schema.org/CollectionPage">
                @php
                    // Создаем массив для отслеживания уже отображенных новостей
                    $displayedNewsIds = [];
                @endphp
                
                @forelse($news as $item)
                    @if(!in_array($item->id, $displayedNewsIds))
                        @php
                            // Добавляем ID новости в массив отображенных
                            $displayedNewsIds[] = $item->id;
                        @endphp
                        
                        <div class="col-lg-4 col-md-6 mb-4">
                            <div class="card h-100 news-card {{ !empty($item->video_iframe) ? 'video-news-card' : '' }} shadow-hover">
                                <a href="{{ route('news.show', $item->slug) }}" class="card-img-top-link position-relative">
                                    <img src="{{ $item->getThumbnailUrl() }}" 
                                        class="card-img-top" 
                                        alt="{{ $item->title }}" 
                                        loading="lazy">
                                        
                                    @if(!empty($item->video_iframe))
                                    <div class="video-indicator">
                                        <i class="fas fa-play-circle"></i>
                                    </div>
                                    @endif
                                </a>
                                <div class="card-body">
                                    <h5 class="card-title">
                                        <a href="{{ route('news.show', $item->slug) }}" class="text-dark text-decoration-none">
                                            {{ $item->title }}
                                        </a>
                                    </h5>
                                    <p class="card-text text-muted small mb-2">
                                        <i class="far fa-calendar-alt me-1"></i> {{ $item->created_at->format('d.m.Y') }}
                                        <i class="far fa-eye ms-2 me-1"></i> {{ $item->views }}
                                        @if(isset($item->comments_count))
                                        <i class="far fa-comment ms-2 me-1"></i> {{ $item->comments_count }}
                                        @endif
                                    </p>
                                    <p class="card-text">{{ Str::limit($item->short_description, 100) }}</p>
                                </div>
                                <div class="card-footer bg-white border-0">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <a href="{{ route('news.show', $item->slug) }}" class="btn btn-outline-primary btn-sm">
                                            Читать полностью
                                        </a>
                                        
                                        <div>
                                            @if(!empty($item->video_iframe))
                                            <span class="badge bg-danger">
                                                <i class="fas fa-play-circle"></i> Видео
                                            </span>
                                            @endif
                                            
                                            @if(isset($item->comments_count) && $item->comments_count > 0)
                                            <span class="badge bg-secondary ms-1">
                                                <i class="far fa-comments"></i> {{ $item->comments_count }}
                                            </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                @empty
                    <div class="col-12">
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i> Новости не найдены. Пожалуйста, попробуйте другой поисковый запрос.
                        </div>
                    </div>
                @endforelse
            </div>
            
            <!-- Индикатор загрузки для бесконечной прокрутки -->
            <div class="text-center my-4 d-none" id="loading-indicator">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Загрузка...</span>
                </div>
                <p class="mt-2">Загружаем новости...</p>
            </div>
            
            <!-- Пагинация (будет скрыта JS-ом) -->
            <div class="d-flex justify-content-center mt-4" id="pagination-container">
                {{ $news->withQueryString()->links() }}
            </div>
        </div>
        
        <!-- Правая колонка с дополнительной информацией -->
        <div class="col-lg-4">
            <!-- Блок подписки на новости -->
            <div class="card mb-4 newsletter-card">
                <div class="card-header bg-primary text-white">
                    <i class="fas fa-envelope me-2"></i> Будьте в курсе новостей
                </div>
                <div class="card-body">
                    <p>Подпишитесь на нашу рассылку и получайте свежие кулинарные новости и рецепты</p>
                    <form id="newsletter-form">
                        <div class="mb-3">
                            <input type="email" class="form-control" placeholder="Ваш e-mail" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="agree-terms" required>
                            <label class="form-check-label small" for="agree-terms">
                                Я согласен получать новости и обновления
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Подписаться</button>
                    </form>
                </div>
            </div>

      
            
            <!-- Рекомендуемые рецепты -->
            @if(isset($recommendedRecipes) && $recommendedRecipes->count() > 0)
            <div class="card mb-4 recipe-card">
                <div class="card-header bg-light">
                    <i class="fas fa-utensils me-2"></i> Попробуйте приготовить
                </div>
                <div class="card-body p-0">
                    @foreach($recommendedRecipes as $recipe)
                    <a href="{{ route('recipes.show', $recipe->slug) }}" class="text-decoration-none">
                        <div class="d-flex align-items-center p-3 border-bottom recipe-item">
                            @if($recipe->image)
                                <img src="{{ asset($recipe->image) }}" alt="{{ $recipe->title }}" class="me-3 recipe-thumbnail">
                            @endif
                            <div>
                                <h6 class="mb-1 text-dark">{{ Str::limit($recipe->title, 40) }}</h6>
                                <div class="d-flex align-items-center text-muted small">
                                    <i class="fas fa-star me-1 text-warning"></i>
                                    <span>{{ number_format($recipe->rating_avg, 1) }}</span>
                                    <span class="mx-2">•</span>
                                    <i class="far fa-clock me-1"></i>
                                    <span>{{ $recipe->cooking_time ?? '30 мин' }}</span>
                                </div>
                            </div>
                        </div>
                    </a>
                    @endforeach
                </div>
                <div class="card-footer text-center">
                    <a href="{{ route('recipes.index') }}" class="btn btn-sm btn-outline-primary">Все рецепты</a>
                </div>
            </div>
            @endif

            <!-- Облако тегов для новостей -->
            <div class="card mb-4 tags-card">
                <div class="card-header bg-light">
                    <i class="fas fa-tags me-2"></i> Популярные темы
                </div>
                <div class="card-body">
                    <div class="tags-cloud">
                        @if(isset($tags) && $tags->count() > 0)
                            @foreach($tags as $tag)
                                <a href="{{ route('news.index', ['tag' => $tag->slug]) }}" 
                                   class="tag-item {{ request('tag') == $tag->slug ? 'active' : '' }}"
                                   style="font-size: {{ 0.8 + ($tag->news_count / 10) }}rem">
                                    {{ $tag->name }}
                                    <span class="badge rounded-pill bg-light text-dark ms-1">{{ $tag->news_count }}</span>
                                </a>
                            @endforeach
                        @else
                            <div class="d-flex flex-wrap gap-2">
                                <a href="{{ route('news.index', ['search' => 'рецепты']) }}" class="btn btn-sm btn-outline-secondary">Рецепты</a>
                                <a href="{{ route('news.index', ['search' => 'кулинария']) }}" class="btn btn-sm btn-outline-secondary">Кулинария</a>
                                <a href="{{ route('news.index', ['search' => 'здоровое питание']) }}" class="btn btn-sm btn-outline-secondary">Здоровое питание</a>
                                <a href="{{ route('news.index', ['search' => 'десерты']) }}" class="btn btn-sm btn-outline-secondary">Десерты</a>
                                <a href="{{ route('news.index', ['search' => 'мастер-класс']) }}" class="btn btn-sm btn-outline-secondary">Мастер-классы</a>
                                <a href="{{ route('news.index', ['search' => 'советы']) }}" class="btn btn-sm btn-outline-secondary">Советы</a>
                                <a href="{{ route('news.index', ['search' => 'праздничный стол']) }}" class="btn btn-sm btn-outline-secondary">Праздничный стол</a>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            
            <!-- Календарь публикаций -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <i class="fas fa-calendar me-2"></i> Архив публикаций
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        @foreach($archives ?? [] as $archive)
                            <a href="{{ route('news.index', ['year' => $archive->year, 'month' => $archive->month]) }}" 
                               class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">
                                {{ $archive->month_name }} {{ $archive->year }}
                                <span class="badge bg-primary rounded-pill">{{ $archive->count }}</span>
                            </a>
                        @endforeach
                        
                        <!-- Заглушка, если архива нет -->
                        @if(empty($archives) || count($archives) === 0)
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Май 2023
                                <span class="badge bg-primary rounded-pill">12</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Апрель 2023
                                <span class="badge bg-primary rounded-pill">8</span>
                            </div>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                Март 2023
                                <span class="badge bg-primary rounded-pill">15</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Основные элементы
    let currentPage = 1;
    const lastPage = {{ $news->lastPage() }};
    let isLoading = false;
    const container = document.getElementById('news-container');
    const loadingIndicator = document.getElementById('loading-indicator');
    const paginationContainer = document.getElementById('pagination-container');
    
    // Скрываем стандартную пагинацию
    if (paginationContainer) {
        paginationContainer.style.display = 'none';
    }
    
    // Функция загрузки следующей страницы
    function loadNextPage() {
        if (isLoading || currentPage >= lastPage) return;
        
        isLoading = true;
        currentPage++;
        
        // Показываем индикатор загрузки
        loadingIndicator.classList.remove('d-none');
        
        // Формируем URL для запроса
        const url = new URL(window.location.href);
        url.searchParams.set('page', currentPage);
        
        // Выполняем AJAX запрос
        fetch(url.toString(), {
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.text())
        .then(html => {
            // Скрываем индикатор загрузки
            loadingIndicator.classList.add('d-none');
            
            // Добавляем новый контент
            container.insertAdjacentHTML('beforeend', html);
            
            // Инициализируем ленивую загрузку для новых изображений
            initLazyLoad();
            
            // Анимация новых элементов
            const newItems = container.querySelectorAll('.news-item:not(.animated)');
            newItems.forEach((item, index) => {
                setTimeout(() => {
                    item.classList.add('animated', 'fadeIn');
                }, index * 100);
            });
            
            isLoading = false;
        })
        .catch(error => {
            console.error('Ошибка загрузки данных:', error);
            loadingIndicator.classList.add('d-none');
            isLoading = false;
        });
    }
    
    // Отслеживаем скролл страницы
    window.addEventListener('scroll', () => {
        // Проверяем, дошел ли пользователь до конца страницы
        if (window.innerHeight + window.scrollY >= document.body.offsetHeight - 800) {
            loadNextPage();
        }
    });
    
    // Переключение вида отображения (плитка/список)
    const viewButtons = document.querySelectorAll('.view-type-btn');
    viewButtons.forEach(button => {
        button.addEventListener('click', function() {
            const viewType = this.getAttribute('data-view');
            
            // Обновляем классы кнопок
            viewButtons.forEach(btn => {
                btn.classList.remove('btn-outline-primary', 'btn-outline-secondary');
                btn.classList.add(btn === this ? 'btn-outline-primary' : 'btn-outline-secondary');
            });
            
            // Обновляем вид контейнера с анимацией
            container.style.opacity = '0';
            setTimeout(() => {
                container.className = 'row';
                if (viewType === 'list') {
                    container.classList.add('view-list');
                } else {
                    container.classList.add('view-grid');
                }
                container.style.opacity = '1';
            }, 200);
            
            // Сохраняем предпочтение в localStorage
            localStorage.setItem('news-view-type', viewType);
        });
    });
    
    // Загружаем сохраненный вид отображения
    const savedViewType = localStorage.getItem('news-view-type') || 'grid';
    const activeButton = document.querySelector(`.view-type-btn[data-view="${savedViewType}"]`);
    if (activeButton) {
        activeButton.click();
    }
    
    // Обработка формы подписки на рассылку
    const newsletterForm = document.getElementById('newsletter-form');
    if (newsletterForm) {
        newsletterForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const email = this.querySelector('input[type="email"]').value;
            
            // Показываем анимацию загрузки
            this.innerHTML = `
                <div class="text-center py-3">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Отправка...</span>
                    </div>
                </div>
            `;
            
            // Имитируем отправку запроса
            setTimeout(() => {
                // Показываем уведомление об успешной подписке
                this.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Спасибо за подписку! Мы будем держать вас в курсе новостей.
                    </div>
                `;
                
                // Сохраняем подписку в localStorage для демонстрации
                localStorage.setItem('newsletter-subscribed', 'true');
                localStorage.setItem('subscriber-email', email);
            }, 1000);
        });
    }
    
    // Проверяем, подписан ли пользователь уже
    if(localStorage.getItem('newsletter-subscribed') === 'true' && newsletterForm) {
        const email = localStorage.getItem('subscriber-email');
        newsletterForm.innerHTML = `
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle me-2"></i>
                Вы уже подписаны на нашу рассылку с адресом ${email}.
                <button class="btn btn-sm btn-outline-secondary mt-2 w-100" id="change-subscription">Изменить данные подписки</button>
            </div>
        `;
        
        // Обработчик для изменения подписки
        const changeButton = document.getElementById('change-subscription');
        if (changeButton) {
            changeButton.addEventListener('click', function() {
                localStorage.removeItem('newsletter-subscribed');
                localStorage.removeItem('subscriber-email');
                location.reload();
            });
        }
    }
    
    // Инициализация ленивой загрузки изображений
    function initLazyLoad() {
        const lazyImages = document.querySelectorAll('.lazy-load:not(.loaded)');
        
        if ('IntersectionObserver' in window) {
            const imageObserver = new IntersectionObserver((entries, observer) => {
                entries.forEach(entry => {
                    if (entry.isIntersecting) {
                        const img = entry.target;
                        img.src = img.dataset.src || img.src;
                        img.classList.add('loaded');
                        imageObserver.unobserve(img);
                    }
                });
            });
            
            lazyImages.forEach(img => {
                img.dataset.src = img.src;
                imageObserver.observe(img);
            });
        } else {
            // Запасной вариант для браузеров без поддержки IntersectionObserver
            lazyImages.forEach(img => {
                img.classList.add('loaded');
            });
        }
    }
    
    // Инициализируем ленивую загрузку при загрузке страницы
    initLazyLoad();
    
    // Инициализация анимации для первых элементов
    setTimeout(() => {
        const items = document.querySelectorAll('.news-item');
        items.forEach((item, index) => {
            setTimeout(() => {
                item.classList.add('animated', 'fadeIn');
            }, index * 100);
        });
    }, 300);
    
    // Горизонтальная прокрутка категорий
    const categoriesWrapper = document.querySelector('.news-categories-wrapper');
    const categoriesList = document.querySelector('.news-categories');
    const rightBtn = document.querySelector('.category-scroll-right');
    
    if (categoriesWrapper && categoriesList) {
        // Проверяем, нужно ли показывать кнопку прокрутки
        function checkScroll() {
            if (categoriesList.scrollWidth > categoriesWrapper.clientWidth) {
                rightBtn.style.display = 'flex';
            } else {
                rightBtn.style.display = 'none';
            }
        }
        
        // Обработчик для прокрутки категорий
        if (rightBtn) {
            rightBtn.addEventListener('click', function() {
                categoriesList.scrollBy({
                    left: 200,
                    behavior: 'smooth'
                });
            });
        }
        
        // Проверяем при загрузке и изменении размера окна
        window.addEventListener('resize', checkScroll);
        checkScroll();
        
        // Добавляем возможность прокрутки свайпом на мобильных
        let touchStartX = 0;
        let touchEndX = 0;
        
        categoriesList.addEventListener('touchstart', e => {
            touchStartX = e.changedTouches[0].screenX;
        }, { passive: true });
        
        categoriesList.addEventListener('touchend', e => {
            touchEndX = e.changedTouches[0].screenX;
            handleSwipe();
        }, { passive: true });
        
        function handleSwipe() {
            const diff = touchStartX - touchEndX;
            if (diff > 50) { // Свайп влево
                categoriesList.scrollBy({
                    left: 150,
                    behavior: 'smooth'
                });
            } else if (diff < -50) { // Свайп вправо
                categoriesList.scrollBy({
                    left: -150,
                    behavior: 'smooth'
                });
            }
        }
    }
});
</script>

@endsection
