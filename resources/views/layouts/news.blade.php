<!doctype html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, maximum-scale=5">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="robots" content="index, follow">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>@yield('title', config('app.name'))</title>
    <meta name="description" content="@yield('description', 'Новости кулинарии и рецепты на каждый день')">
    <meta name="keywords" content="@yield('keywords', 'новости кулинарии, рецепты, кулинарные тренды')">
    
    <!-- SEO метатеги -->
    @hasSection('seo')
        @yield('seo')
    @endif
    
    <!-- Schema.org разметка -->
    @hasSection('schema_org')
        @yield('schema_org')
    @endif
    
    <!-- Иконки -->
    <link rel="apple-touch-icon" sizes="180x180" href="/apple-touch-icon.png">
    <link rel="icon" type="image/png" sizes="32x32" href="/favicon-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="/favicon-16x16.png">
    <link rel="manifest" href="/site.webmanifest">
    <meta name="theme-color" content="#ffffff">

    <!-- Preconnect и DNS-Prefetch -->
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="dns-prefetch" href="//fonts.bunny.net">
    <link rel="dns-prefetch" href="//cdn.jsdelivr.net">
    
    <!-- Font Awesome с отложенной загрузкой -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    
    <!-- Swiper слайдер -->
    <link href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" rel="stylesheet" media="print" onload="this.media='all'">
    
    <!-- Scripts -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    @yield('styles')

    <style>
     
    </style>
</head>
<body class="{{ session('darkMode', false) ? 'dark-mode' : '' }}" itemscope itemtype="https://schema.org/WebPage">
    <div id="app">
        <!-- Подключаем хедер -->
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
        
        <!-- Подключаем футер -->
        @include('partials.footer')
    </div>
    
    <!-- Кнопка наверх -->
    <div class="back-to-top" id="back-to-top" aria-label="Прокрутить наверх" style="display:none;">
        <i class="fas fa-arrow-up" aria-hidden="true"></i>
    </div>
    
    <!-- Swiper JS -->
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    
    <!-- Глобальный обработчик ошибок изображений -->
    <script>
        window.getRandomDefaultImage = function() {
            const defaultImages = [
                '/images/defolt/default1.jpg',
                '/images/defolt/default2.jpg',
                '/images/defolt/default3.jpg',
                '/images/defolt/default4.jpg',
                '/images/defolt/default5.jpg',
                '/images/defolt/default6.jpg',
                '/images/defolt/default7.jpg',
                '/images/defolt/default8.jpg',
                '/images/defolt/default9.jpg',
                '/images/defolt/default10.jpg',
                '/images/defolt/default11.jpg'
            ];
            const randomIndex = Math.floor(Math.random() * defaultImages.length);
            return defaultImages[randomIndex];
        };

        window.handleImageError = function(img) {
            const randomImage = window.getRandomDefaultImage();
            img.src = randomImage;
            img.onerror = null;
        };

        document.addEventListener('DOMContentLoaded', function() {
            // Обработчик ошибок загрузки изображений
            document.querySelectorAll('img:not([data-no-random])').forEach(img => {
                img.addEventListener('error', function() {
                    window.handleImageError(this);
                });
            });
            
            // Кнопка прокрутки вверх
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
    
    <!-- Инициализация Swiper слайдера для новостных карточек -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Инициализация слайдера для галереи новостей, если она существует
            if (document.querySelector('.news-swiper')) {
                new Swiper('.news-swiper', {
                    slidesPerView: 1,
                    spaceBetween: 30,
                    loop: true,
                    pagination: {
                        el: '.swiper-pagination',
                        clickable: true,
                    },
                    navigation: {
                        nextEl: '.swiper-button-next',
                        prevEl: '.swiper-button-prev',
                    },
                    autoplay: {
                        delay: 5000,
                        disableOnInteraction: false,
                    },
                    breakpoints: {
                        640: {
                            slidesPerView: 1,
                            spaceBetween: 20,
                        },
                        768: {
                            slidesPerView: 2,
                            spaceBetween: 30,
                        },
                        1024: {
                            slidesPerView: 3,
                            spaceBetween: 30,
                        },
                    }
                });
            }
            
            // Анимация для новостных элементов при прокрутке
            function animateNewsOnScroll() {
                const newsElements = document.querySelectorAll('.news-card:not(.animated)');
                
                newsElements.forEach(element => {
                    const elementPosition = element.getBoundingClientRect().top;
                    const screenHeight = window.innerHeight;
                    
                    if (elementPosition < screenHeight - 100) {
                        element.classList.add('animate-news', 'animated');
                    }
                });
            }
            
            // Первоначальный запуск после загрузки страницы
            setTimeout(animateNewsOnScroll, 300);
            
            // Запуск при скролле
            window.addEventListener('scroll', animateNewsOnScroll);
        });
    </script>
    
    <!-- Специфичные для страницы скрипты -->
    @yield('scripts')
    
    <!-- Яндекс.Метрика -->
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