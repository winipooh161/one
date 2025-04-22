{{-- SEO мета-теги для страницы со списком рецептов --}}
@php
$pageTitle = isset($seo) ? $seo->getTitle() : ($title ?? 'Все рецепты');
$pageDescription = isset($seo) ? $seo->getDescription() : ($description ?? 'Каталог кулинарных рецептов с пошаговыми инструкциями и фото');
$pageKeywords = isset($seo) ? $seo->getKeywords() : ($keywords ?? 'рецепты, кулинария, блюда, готовка, Яедок, я едок');

// Канонический URL с учетом пагинации
$currentPage = request()->input('page', 1);
$baseUrl = url()->current();
$canonicalUrl = $currentPage > 1 ? $baseUrl . '?page=' . $currentPage : $baseUrl;

// Мета-теги для пагинации
$paginationLinks = [];
if(isset($recipes) && $recipes instanceof \Illuminate\Pagination\LengthAwarePaginator) {
    if($recipes->previousPageUrl()) {
        $paginationLinks['prev'] = $recipes->previousPageUrl();
    }
    if($recipes->nextPageUrl()) {
        $paginationLinks['next'] = $recipes->nextPageUrl();
    }
}

// Определение типа страницы для Open Graph
$ogType = 'website';

// Изображение для Open Graph
$ogImage = asset('images/recipes-cover.jpg');
@endphp

{{-- Основные SEO-теги --}}
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
<meta name="keywords" content="{{ $pageKeywords }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Пагинация для SEO --}}
@if(isset($paginationLinks['prev']))
    <link rel="prev" href="{{ $paginationLinks['prev'] }}">
@endif
@if(isset($paginationLinks['next']))
    <link rel="next" href="{{ $paginationLinks['next'] }}">
@endif

{{-- Open Graph теги --}}
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:site_name" content="{{ config('app.name') }}">

{{-- Twitter Card теги --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

{{-- Дополнительные мета-теги --}}
@if(!empty($search))
    <meta name="robots" content="noindex, follow">
@else
    <meta name="robots" content="index, follow">
@endif

<!-- Подключаем Schema.org структурированные данные для страницы списка рецептов -->
@include('schema_org.recipes_schema', ['recipes' => $recipes])
