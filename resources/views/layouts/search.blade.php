<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    
    <!-- Базовая информация для SEO -->
    <title>@yield('title', config('app.name', 'Laravel'))</title>
    <meta name="description" content="@yield('description', 'Лучшие рецепты на любой вкус')">
    <meta name="keywords" content="@yield('keywords', 'рецепты, кулинария')">
    
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <!-- Управление индексацией роботами -->
    @if(isset($noindex) && $noindex)
        <meta name="robots" content="noindex, follow">
    @elseif(request()->has('page') && request()->input('page') > 1)
        <meta name="robots" content="noindex, follow">
    @elseif(request()->has('query') || request()->has('sort') || request()->has('category') || request()->has('cooking_time'))
        <meta name="robots" content="noindex, follow">
    @else
        <meta name="robots" content="index, follow">
    @endif

    <!-- SEO секция для включения в конкретных шаблонах -->
    @hasSection('seo')
        @yield('seo')
    @endif
    
    <!-- Schema.org разметка -->
    @hasSection('schema_org')
        @yield('schema_org')
    @endif
    
    <!-- Мета-теги для социальных сетей -->
    <meta property="og:site_name" content="{{ config('app.name') }}">
    @hasSection('og_title')
        <meta property="og:title" content="@yield('og_title')">
    @else
        <meta property="og:title" content="@yield('title', config('app.name'))">
    @endif
    
    @hasSection('og_description')
        <meta property="og:description" content="@yield('og_description')">
    @else
        <meta property="og:description" content="@yield('description', 'Лучшие рецепты на любой вкус')">
    @endif
    
    <meta property="og:url" content="{{ url()->current() }}">
    
    @hasSection('og_image')
        <meta property="og:image" content="@yield('og_image')">
    @else
        <meta property="og:image" content="{{ asset('images/og-default.jpg') }}">
    @endif
    
    <meta property="og:type" content="@yield('og_type', 'website')">
    <meta property="og:locale" content="ru_RU">
    <link
  rel="stylesheet"
  href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"
/>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
    <!-- Twitter Card -->
    <meta name="twitter:card" content="@yield('twitter_card', 'summary_large_image')">
    <meta name="twitter:title" content="@yield('twitter_title', config('app.name'))">
    <meta name="twitter:description" content="@yield('twitter_description', '')">
    @if(!View::hasSection('twitter_description') && View::hasSection('description'))
        <meta name="twitter:description" content="@yield('description')">
    @elseif(!View::hasSection('twitter_description') && !View::hasSection('description'))
        <meta name="twitter:description" content="Лучшие рецепты на любой вкус">
    @endif
    
    @hasSection('twitter_image')
        <meta name="twitter:image" content="@yield('twitter_image')">
    @else
        <meta name="twitter:image" content="{{ asset('images/og-default.jpg') }}">
    @endif
    
    <!-- Иконки сайта -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ffffff">

    <!-- CSS -->
    <link href="{{ asset('assets/css/bootstrap.min.css') }}" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    
    <!-- Scripts -->

    
    @yield('styles')
    
    <style>
        body {
            padding-top: 56px; /* Высота стандартной навигационной панели */
        }
        
        .navbar {
            position: fixed;
            top: 0;
            width: 100%;
            z-index: 1030;
        }
        
        /* Остальные стили останутся без изменений */
    </style>
</head>
<body class="{{ session('darkMode', false) ? 'dark-mode' : '' }}" itemscope itemtype="https://schema.org/WebPage">
    <div id="app">
        @include('partials.header')
        
        <main>
            <!-- Breadcrumbs -->
            <div class="container mt-3">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb" itemscope itemtype="https://schema.org/BreadcrumbList">
                        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a href="{{ url('/') }}" itemprop="item"><span itemprop="name">Главная</span></a>
                            <meta itemprop="position" content="1" />
                        </li>
                        @yield('breadcrumbs')
                    </ol>
                </nav>
            </div>
            
            @yield('content')
        </main>
        
        @include('partials.footer')
    </div>
    
    <!-- Кнопка наверх -->
    <div class="back-to-top" id="back-to-top" aria-label="Прокрутить наверх">
        <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </div>

    <!-- JavaScript -->
    <script src="{{ asset('assets/js/bootstrap.bundle.min.js') }}"></script>
    
    <!-- Обработчик изображений -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Обработчик ошибок загрузки изображений
            const imgErrorHandler = function(event) {
                const img = event.target;
                const isCategory = img.closest('.category-card') !== null || img.classList.contains('category-img');
                const placeholderPath = isCategory ? '/images/category-placeholder.jpg' : '/images/placeholder.jpg';
                
                if (!img.src.includes('placeholder')) {
                    img.src = placeholderPath;
                    img.alt = 'Изображение недоступно';
                }
                
                // Предотвращаем повторные ошибки
                img.onerror = null;
            };
            
            // Применяем обработчик ко всем изображениям
            document.querySelectorAll('img').forEach(img => {
                if (!img.hasAttribute('data-skip-error-handler')) {
                    img.addEventListener('error', imgErrorHandler);
                }
            });
            
            // Скрипт для кнопки прокрутки вверх
            const backToTopButton = document.getElementById('back-to-top');
            
            window.addEventListener('scroll', function() {
                if (window.pageYOffset > 300) {
                    backToTopButton.style.display = 'flex';
                    backToTopButton.style.alignItems = 'center';
                    backToTopButton.style.justifyContent = 'center';
                } else {
                    backToTopButton.style.display = 'none';
                }
            });
            
            backToTopButton.addEventListener('click', function() {
                window.scrollTo({
                    top: 0,
                    behavior: 'smooth'
                });
            });
        });
    </script>
    
    <!-- Случайные изображения для замены -->
    <script>
        window.handleImageError = function(img) {
            const randomImage = '/images/defolt/default' + Math.floor(Math.random() * 11 + 1) + '.jpg';
            img.src = randomImage;
            img.alt = 'Изображение рецепта';
            img.onerror = null;
        };
    </script>
    
    @yield('scripts')
    
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i.l=1*new Date();
        for (var j = 0; j < document.scripts.length; j++) {if (document.scripts[j].src === r) { return; }}
        k=e.createElement(t),a=e.getElementsByTagName(t)[0],k.async=1,k.src=r,a.parentNode.insertBefore(k,a)})
        (window, document, "script", "https://mc.yandex.ru/metrika/tag.js", "ym");
        ym(100639873, "init", {
             clickmap:true,
             trackLinks:true,
             accurateTrackBounce:true,
             webvisor:true
        });
    </script>
    <noscript><div><img src="https://mc.yandex.ru/watch/100639873" style="position:absolute; left:-9999px;" alt="" /></div></noscript>
</body>
</html>