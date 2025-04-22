@php
// Получаем метатеги и SEO информацию для главной страницы
$metaTags = isset($seo) ? $seo->getHomePageMeta() : [];

// Заголовок страницы
$title = $metaTags['title'] ?? 'Яедок - кулинарные рецепты с пошаговыми инструкциями';
$description = $metaTags['meta_description'] ?? 'Кулинарные рецепты с фото и пошаговыми инструкциями. Простые и вкусные рецепты для всей семьи на каждый день и для праздничного стола.';
$keywords = $metaTags['meta_keywords'] ?? 'Яедок, я едок, рецепты, кулинария, готовка, блюда, еда, домашние рецепты, простые рецепты, вкусные рецепты';

// Изображение для Open Graph
$ogImage = $metaTags['og_image'] ?? asset('images/home-cover.jpg');
@endphp

<!-- Основные мета-теги -->
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<link rel="canonical" href="{{ $metaTags['canonical'] ?? url('/') }}">
<meta name="author" content="{{ config('app.name') }}">
<meta name="robots" content="{{ $metaTags['robots'] ?? 'index, follow' }}">

<!-- Open Graph для социальных сетей -->
<meta property="og:type" content="website">
<meta property="og:title" content="{{ $metaTags['og_title'] ?? $title }}">
<meta property="og:description" content="{{ $metaTags['og_description'] ?? $description }}">
<meta property="og:url" content="{{ $metaTags['og_url'] ?? url('/') }}">
<meta property="og:site_name" content="{{ $metaTags['og_site_name'] ?? config('app.name') }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="ru_RU">

<!-- Twitter Card -->
<meta name="twitter:card" content="{{ $metaTags['twitter_card'] ?? 'summary_large_image' }}">
<meta name="twitter:title" content="{{ $metaTags['twitter_title'] ?? $title }}">
<meta name="twitter:description" content="{{ $metaTags['twitter_description'] ?? $description }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<!-- DNS Prefetch для улучшения производительности -->
<link rel="dns-prefetch" href="//fonts.googleapis.com">
<link rel="dns-prefetch" href="//fonts.gstatic.com">
<link rel="dns-prefetch" href="//cdn.jsdelivr.net">
<link rel="dns-prefetch" href="//mc.yandex.ru">

<!-- Preconnect для важных ресурсов -->
<link rel="preconnect" href="https://fonts.googleapis.com" crossorigin>
<link rel="preconnect" href="https://cdn.jsdelivr.net" crossorigin>

<!-- Дополнительные метаданные -->
<meta name="application-name" content="{{ config('app.name') }}">
<meta name="theme-color" content="#ffffff">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">

<!-- Подключаем Schema.org разметку для главной страницы через сервис -->
@php
    echo app(\App\Services\SeoService::class)->generateHomeSchema();
@endphp

<!-- XML-фиды и Sitemap -->
@if(Route::has('feeds.recipes'))
<link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }} - Рецепты" href="{{ route('feeds.recipes') }}">
@endif

@if(Route::has('feeds.categories'))
<link rel="alternate" type="application/rss+xml" title="{{ config('app.name') }} - Категории" href="{{ route('feeds.categories') }}">
@endif

@if(Route::has('sitemap'))
<link rel="sitemap" type="application/xml" title="Sitemap" href="{{ route('sitemap') }}">
@endif
