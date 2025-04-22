@php
// Получаем данные для поисковой страницы
$query = isset($query) ? $query : (request()->input('query') ?? request()->input('q') ?? '');
$hasResults = isset($recipes) && method_exists($recipes, 'count') && $recipes->count() > 0;
$recipesCount = isset($recipes) && method_exists($recipes, 'total') ? $recipes->total() : 0;

// Определяем, есть ли активные фильтры
$hasActiveFilters = request()->has('category') || request()->has('cooking_time') || request()->has('sort');

// Создаем заголовок и метаданные
if (!empty($query)) {
    $pageTitle = 'Поиск: ' . $query . ' - ' . config('app.name');
    $pageDescription = 'Результаты поиска рецептов по запросу: ' . $query . '. Найдено ' . $recipesCount . ' ' . trans_choice('рецепт|рецепта|рецептов', $recipesCount) . '.';
    
    if ($hasActiveFilters) {
        $pageDescription .= ' Применены фильтры.';
    }
    
    $pageKeywords = $query . ', поиск рецептов, кулинария, готовка, ' . 
                   (!empty($query) ? 'рецепт ' . $query . ', как приготовить ' . $query . ', ' : '') .
                   'яедок, я едок';
} else {
    $pageTitle = 'Поиск рецептов - ' . config('app.name');
    $pageDescription = 'Поиск кулинарных рецептов по названиям, ингредиентам и категориям. Найдите идеальный рецепт для вашего стола!';
    $pageKeywords = 'поиск рецептов, найти рецепт, кулинария, ингредиенты, яедок, я едок';
}

// Определяем каноничную ссылку и ссылки пагинации
$baseUrl = url('/search');
$queryParams = array_filter(request()->except(['page']), function($value) {
    return $value !== null && $value !== '';
});

$canonicalUrl = $baseUrl;
if (!empty($queryParams)) {
    $canonicalUrl .= '?' . http_build_query($queryParams);
}

$paginationLinks = [];

if ($hasResults && isset($recipes) && $recipes->hasPages()) {
    if ($recipes->currentPage() > 1) {
        $prevQueryParams = array_merge($queryParams, ['page' => $recipes->currentPage() - 1]);
        $paginationLinks['prev'] = $baseUrl . '?' . http_build_query($prevQueryParams);
    }
    
    if ($recipes->hasMorePages()) {
        $nextQueryParams = array_merge($queryParams, ['page' => $recipes->currentPage() + 1]);
        $paginationLinks['next'] = $baseUrl . '?' . http_build_query($nextQueryParams);
    }
}

// Изображение для Open Graph
$ogImage = asset('images/search-cover.jpg');

// Проверяем нужно ли запретить индексацию страницы
$noindex = !empty($query) || $hasActiveFilters || (isset($recipes) && $recipes->currentPage() > 1);
@endphp

<!-- Базовые метатеги -->
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
<meta name="keywords" content="{{ $pageKeywords }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

<!-- Метатеги пагинации -->
@if(isset($paginationLinks['prev']))
    <link rel="prev" href="{{ $paginationLinks['prev'] }}">
@endif

@if(isset($paginationLinks['next']))
    <link rel="next" href="{{ $paginationLinks['next'] }}">
@endif

<!-- Запрет индексации поисковой выдачи при необходимости -->
@if($noindex)
    <meta name="robots" content="noindex, follow">
@endif

<!-- Open Graph теги для социальных сетей -->
<meta property="og:title" content="{{ $pageTitle }}">
<meta property="og:description" content="{{ $pageDescription }}">
<meta property="og:url" content="{{ url()->current() }}">
<meta property="og:type" content="website">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="ru_RU">

<!-- Дополнительные Open Graph теги для поисковой страницы -->
@if(!empty($query))
    <meta property="og:search_query" content="{{ $query }}">
    <meta property="og:search_results_count" content="{{ $recipesCount }}">
@endif

<!-- Twitter Card -->
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $pageTitle }}">
<meta name="twitter:description" content="{{ $pageDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

<!-- Дополнительные метатеги для SEO -->
<meta name="author" content="{{ config('app.name') }}">
<meta name="application-name" content="{{ config('app.name') }}">
<meta name="apple-mobile-web-app-title" content="{{ config('app.name') }}">
<meta name="format-detection" content="telephone=no">
