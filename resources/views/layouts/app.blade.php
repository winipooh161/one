<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="index, follow">

    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <!-- SEO-разметка, уникальная для каждой страницы -->
    @yield('seo')

    <!-- Основные Favicons - только самые необходимые -->
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ffffff">

    <!-- Preconnect для критических доменов -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">

    <!-- Font Awesome с отложенной загрузкой -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css"
        media="print" onload="this.media='all'">

    <!-- Шрифты с оптимизированной загрузкой -->
    <link href="https://fonts.bunny.net/css?family=Nunito:400,600,700&display=swap" rel="stylesheet" 
        media="print" onload="this.media='all'">

    <!-- Scripts через Vite -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])

    <!-- AMP Link для страниц рецептов -->
    @if (isset($isRecipe) && $isRecipe && Route::has('recipes.amp'))
        <link rel="amphtml" href="{{ route('recipes.amp', ['slug' => $recipe->slug]) }}">
    @endif

    @yield('styles')
</head>

<body>
    <div id="app">
        @include('partials.header')
        
        <main class="py-4">
            @if(!request()->is('/'))
            <div class="container mt-2">
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb small" itemscope itemtype="https://schema.org/BreadcrumbList">
                        <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a href="{{ url('/') }}" itemprop="item"><span itemprop="name">Главная</span></a>
                            <meta itemprop="position" content="1" />
                        </li>
                        
                        @yield('breadcrumbs')
                        
                        @if(isset($activeRoute))
                            <li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem" aria-current="page">
                                <span itemprop="name">{{ $activeRoute }}</span>
                                <meta itemprop="position" content="2" />
                            </li>
                        @endif
                    </ol>
                </nav>
            </div>
            @endif

            @if(session('success'))
            <div class="container">
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            @endif
            
            @if(session('error'))
            <div class="container">
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            </div>
            @endif
            
            @yield('content')
        </main>
        
        @include('partials.footer')
    </div>

    <!-- Cookie Consent Banner -->
    <div id="cookie-consent" class="position-fixed bottom-0 start-0 end-0 bg-dark text-white p-3" style="z-index: 9999; display: none;">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-md-8">
                    <p class="mb-md-0">Мы используем файлы cookie для улучшения работы сайта. Продолжая пользоваться сайтом, вы соглашаетесь с использованием файлов cookie и принимаете условия <a href="{{ route('legal.privacy') }}" class="text-white text-decoration-underline">Политики конфиденциальности</a>.</p>
                </div>
                <div class="col-md-4 text-md-end mt-2 mt-md-0">
                    <button id="accept-cookies" class="btn btn-primary me-2">Принять</button>
                    <button id="decline-cookies" class="btn btn-outline-light">Отказаться</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Объединенные базовые функции сайта -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Cookie Consent
            const cookieConsent = document.getElementById('cookie-consent');
            const acceptCookies = document.getElementById('accept-cookies');
            const declineCookies = document.getElementById('decline-cookies');

            if (!localStorage.getItem('cookieConsent')) {
                cookieConsent.style.display = 'block';
            }

            if (acceptCookies) {
                acceptCookies.addEventListener('click', function() {
                    localStorage.setItem('cookieConsent', 'accepted');
                    cookieConsent.style.display = 'none';
                });
            }

            if (declineCookies) {
                declineCookies.addEventListener('click', function() {
                    localStorage.setItem('cookieConsent', 'declined');
                    cookieConsent.style.display = 'none';
                });
            }

            // Обработчик ошибок изображений
            document.querySelectorAll('img').forEach(img => {
                if (!img.hasAttribute('data-skip-error-handler')) {
                    img.addEventListener('error', function() {
                        if (!this.src.includes('placeholder')) {
                            const isCategory = this.closest('.category-card') !== null || 
                                              this.classList.contains('category-img');
                            this.src = isCategory ? '/images/category-placeholder.jpg' : '/images/placeholder.jpg';
                            this.onerror = null;
                        }
                    });
                }
            });
        });

        // Функция для выбора случайного изображения
        window.getRandomDefaultImage = function() {
            const defaultImages = [
                '/images/defolt/default1.jpg',
                '/images/defolt/default2.jpg',
                '/images/defolt/default3.jpg',
                '/images/defolt/default4.jpg',
                '/images/defolt/default5.jpg'
            ];
            return defaultImages[Math.floor(Math.random() * defaultImages.length)];
        };

        // Обработчик ошибок изображений
        window.handleImageError = function(img) {
            img.src = window.getRandomDefaultImage();
            img.onerror = null;
        };

        // Service Worker для PWA
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', function() {
                navigator.serviceWorker.register('/service-worker.js')
                    .catch(function(err) {
                        console.log('ServiceWorker registration failed: ', err);
                    });
            });
        }
    </script>

    @yield('scripts')
    @stack('scripts')
</body>
</html>
