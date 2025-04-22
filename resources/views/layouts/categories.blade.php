<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="index, follow">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- Базовые мета-теги -->
    @hasSection('meta_tags')
        @yield('meta_tags')
    @else
        <title>@yield('title', config('app.name', 'Категории рецептов'))</title>
        <meta name="description" content="@yield('description', 'Категории рецептов и кулинарных блюд')">
        <meta name="keywords" content="@yield('keywords', 'категории, рецепты, кулинария')">
    @endif

    <!-- SEO Schema.org разметка -->
    @hasSection('schema_org')
        @yield('schema_org')
    @endif

    <!-- SEO секция для включения в конкретных шаблонах -->
    @hasSection('seo')
        @yield('seo')
    @endif

    <!-- Canonical и ссылки пагинации -->
    @if(isset($canonical))
    <link rel="canonical" href="{{ $canonical }}">
    @endif
    
    @if(isset($prevPageUrl))
    <link rel="prev" href="{{ $prevPageUrl }}">
    @endif
    
    @if(isset($nextPageUrl))
    <link rel="next" href="{{ $nextPageUrl }}">
    @endif

    <!-- Favicons -->
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
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
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
        
        /* Стили для калькулятора порций */
        @media (max-width: 767px) {
            .serving-calculator .calculator-label {
                margin-bottom: 10px;
                display: block;
                width: 100%;
                text-align: center;
            }
            
            .serving-calculator .d-flex {
                flex-direction: column;
            }
            
            .serving-input {
                width: 100% !important;
                margin-top: 10px;
                justify-content: center;
            }
            
            .serving-calculator .btn-group {
                width: 100%;
                margin-bottom: 10px;
            }
            
            #reset-servings {
                margin-left: 0 !important;
                margin-top: 10px;
                width: 100%;
            }
            
            #toggle-checkboxes {
                width: 100%;
                margin-bottom: 15px;
            }
            
            .justify-content-end {
                justify-content: center !important;
            }
        }
    </style>
</head>
<body class="{{ session('darkMode', false) ? 'dark-mode' : '' }}">
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
                        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a href="{{ route('categories.index') }}" itemprop="item"><span itemprop="name">Категории</span></a>
                            <meta itemprop="position" content="2" />
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
    <div class="back-to-top" id="back-to-top">
        <i class="fas fa-arrow-up"></i>
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
            img.onerror = null;
        };
    </script>
    
    @yield('scripts')
    
    <!-- Yandex.Metrika counter -->
    <script type="text/javascript">
        (function(m,e,t,r,i,k,a){m[i]=m[i]||function(){(m[i].a=m[i].a||[]).push(arguments)};
        m[i].l=1*new Date();
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