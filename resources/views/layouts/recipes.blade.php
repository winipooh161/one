<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Базовые SEO-теги -->
    <title>@yield('title', config('app.name', 'Яедок - кулинарные рецепты'))</title>
    <meta name="description" content="@yield('description', 'Кулинарные рецепты с пошаговыми инструкциями и фото')">
    <meta name="keywords" content="@yield('keywords', 'Яедок, я едок, рецепты, кулинария, еда, готовка')">
    
    <!-- Canonical URL -->
    <link rel="canonical" href="@yield('canonical', url()->current())">
    
    <!-- Open Graph -->
    <meta property="og:title" content="@yield('og_title', config('app.name'))">
    <meta property="og:description" content="@yield('og_description', 'Кулинарные рецепты с пошаговыми инструкциями')">
    <meta property="og:url" content="@yield('og_url', url()->current())">
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:site_name" content="{{ config('app.name') }}">
    <meta property="og:image" content="@yield('og_image', asset('images/og-default.jpg'))">
    
    <!-- Twitter Card -->
    <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:title" content="@yield('twitter_title', config('app.name'))">
    <meta name="twitter:description" content="@yield('twitter_description', 'Кулинарные рецепты с пошаговыми инструкциями')">
    <meta name="twitter:image" content="@yield('twitter_image', asset('images/twitter-default.jpg'))">
    
    <!-- Дополнительные SEO-теги -->
    @yield('meta_tags')
    
    <!-- Пагинация для SEO -->
    @if(isset($paginationLinks))
        @if(isset($paginationLinks['prev']))
            <link rel="prev" href="{{ $paginationLinks['prev'] }}">
        @endif
        
        @if(isset($paginationLinks['next']))
            <link rel="next" href="{{ $paginationLinks['next'] }}">
        @endif
    @endif
    
    <!-- Стили -->
    <link rel="stylesheet" href="{{ asset('css/app.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    @yield('styles')
    
    <!-- Шрифты -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap" rel="stylesheet">
    
    <!-- Фавиконки -->
    <link rel="apple-touch-icon" sizes="180x180" href="{{ asset('images/favicon/apple-touch-icon.png') }}">
    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('images/favicon/favicon-32x32.png') }}">
    <link rel="icon" type="image/png" sizes="16x16" href="{{ asset('images/favicon/favicon-16x16.png') }}">
    <link rel="manifest" href="{{ asset('images/favicon/site.webmanifest') }}">
    
    <!-- Schema.org разметка -->
    @yield('schema_org')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Скрипты -->
    <script src="{{ asset('js/app.js') }}" defer></script>
    @stack('scripts-header')
    
</head>
<body>
    <header>
        <!-- Верхнее меню -->
        <nav class="navbar navbar-expand-lg navbar-light bg-light">
            <div class="container">
                <a class="navbar-brand" href="{{ route('home') }}">
                    <img src="{{ asset('images/logo.png') }}" alt="{{ config('app.name') }}" height="40">
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarMain">
                    <span class="navbar-toggler-icon"></span>
                </button>
                <div class="collapse navbar-collapse" id="navbarMain">
                    <ul class="navbar-nav me-auto">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('home') }}">Главная</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('recipes.index') }}">Рецепты</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('categories.index') }}">Категории</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="{{ route('about') }}">О проекте</a>
                        </li>
                    </ul>
                    
                    <!-- Поиск -->
                    <form class="d-flex me-2" action="{{ route('search') }}" method="GET">
                        <div class="input-group">
                            <input class="form-control" type="search" name="query" placeholder="Поиск рецептов...">
                            <button class="btn btn-outline-primary" type="submit">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </form>
                    
                    <!-- Авторизация -->
                    <ul class="navbar-nav">
                        @guest
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('login') }}">Войти</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="{{ route('register') }}">Регистрация</a>
                            </li>
                        @else
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                    {{ Auth::user()->name }}
                                </a>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li><a class="dropdown-item" href="{{ route('profile.show', Auth::id()) }}">Профиль</a></li>
                                    <li><a class="dropdown-item" href="{{ route('profile.recipes') }}">Мои рецепты</a></li>
                                    <li><a class="dropdown-item" href="{{ route('recipes.create') }}">Добавить рецепт</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="dropdown-item">Выйти</button>
                                        </form>
                                    </li>
                                </ul>
                            </li>
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
        
        <!-- Хлебные крошки -->
        <div class="container mt-3">
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                    <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                        <a href="{{ route('home') }}" itemprop="item">
                            <span itemprop="name">Главная</span>
                        </a>
                        <meta itemprop="position" content="1" />
                    </li>
                    @yield('breadcrumbs')
                </ol>
            </nav>
        </div>
    </header>
    
    <main>
        <!-- Подключение SEO-элементов для конкретной страницы -->
        @yield('seo')
        
        <!-- Основной контент -->
        @yield('content')
    </main>
    
    <footer class="bg-light mt-5 py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-4">
                    <h5>О проекте</h5>
                    <p>Яедок - кулинарный портал с большой коллекцией рецептов для любителей вкусной и здоровой еды.</p>
                </div>
                <div class="col-md-4">
                    <h5>Разделы</h5>
                    <ul class="list-unstyled">
                        <li><a href="{{ route('home') }}">Главная</a></li>
                        <li><a href="{{ route('recipes.index') }}">Рецепты</a></li>
                        <li><a href="{{ route('categories.index') }}">Категории</a></li>
                        <li><a href="{{ route('about') }}">О проекте</a></li>
                    </ul>
                </div>
                <div class="col-md-4">
                    <h5>Подписывайтесь</h5>
                    <div class="social-links">
                        <a href="https://vk.com/imedokru" target="_blank" rel="nofollow"><i class="fab fa-vk"></i></a>
                        <a href="https://t.me/imedokru" target="_blank" rel="nofollow"><i class="fab fa-telegram"></i></a>
                        <a href="https://dzen.ru/imedok" target="_blank" rel="nofollow"><i class="fab fa-yandex"></i></a>
                    </div>
                </div>
            </div>
            <hr>
            <div class="row">
                <div class="col-12 text-center">
                    <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Все права защищены.</p>
                </div>
            </div>
        </div>
    </footer>
    
    @stack('scripts')
    @yield('scripts')
</body>
</html>