@php
// Получаем метатеги для страницы списка категорий через SeoService
$metaTags = isset($seo) ? $seo->getCategoriesPageMeta() : [];

// Заголовок и описание страницы
$title = $metaTags['title'] ?? 'Категории рецептов - ' . config('app.name');
$description = $metaTags['meta_description'] ?? 'Полный каталог кулинарных категорий. Найдите рецепты по любой категории блюд.';
$keywords = $metaTags['meta_keywords'] ?? 'категории рецептов, кулинарные категории, рецепты по категориям, яедок, я едок';
$canonical = $metaTags['canonical'] ?? route('categories.index');

// Общие данные для всех страниц
$ogImage = asset('images/categories-cover.jpg');
$totalCategories = $categoriesCount ?? 0;
@endphp

{{-- Основные мета-теги --}}
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<link rel="canonical" href="{{ $canonical }}">

{{-- Open Graph теги для социальных сетей --}}
<meta property="og:title" content="{{ $metaTags['og_title'] ?? $title }}">
<meta property="og:description" content="{{ $metaTags['og_description'] ?? $description }}">
<meta property="og:url" content="{{ $metaTags['og_url'] ?? url()->current() }}">
<meta property="og:type" content="{{ $metaTags['og_type'] ?? 'website' }}">
<meta property="og:site_name" content="{{ $metaTags['og_site_name'] ?? config('app.name') }}">
<meta property="og:image" content="{{ $metaTags['og_image'] ?? $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="ru_RU">

{{-- Twitter карточки --}}
<meta name="twitter:card" content="{{ $metaTags['twitter_card'] ?? 'summary_large_image' }}">
<meta name="twitter:title" content="{{ $metaTags['twitter_title'] ?? $title }}">
<meta name="twitter:description" content="{{ $metaTags['twitter_description'] ?? $description }}">
<meta name="twitter:site" content="@imedokru">
<meta name="twitter:image" content="{{ $metaTags['twitter_image'] ?? $ogImage }}">

{{-- Дополнительные метатеги --}}
<meta name="robots" content="{{ $metaTags['robots'] ?? 'index, follow' }}">
<meta name="author" content="{{ config('app.name') }}">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

{{-- Подключаем Schema.org структурированные данные для страницы списка категорий --}}
@include('schema_org.categories_index_schema', ['popularCategories' => $popularCategories, 'categoriesCount' => $categoriesCount])
