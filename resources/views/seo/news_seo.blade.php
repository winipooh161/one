@php
// Определяем заголовок для мета-тегов
$title = isset($searchTerm) ? "Поиск: $searchTerm - Кулинарные новости" : "Кулинарные новости и статьи";
$description = isset($searchTerm) 
    ? "Результаты поиска новостей по запросу '$searchTerm' на сайте " . config('app.name')
    : "Последние кулинарные новости, статьи, обзоры новинок, рецепты и советы шеф-поваров";

// Если это страница отдельной новости, используем её заголовок
if (isset($news) && is_object($news) && !method_exists($news, 'currentPage')) {
    $title = $news->title . " - " . config('app.name');
    $description = $news->short_description ?? Str::limit(strip_tags($news->content), 160);
}

// Если определена категория, модифицируем заголовок
if (isset($category)) {
    $title = "Новости категории '{$category->name}' - " . config('app.name');
    $description = "Кулинарные новости из категории '{$category->name}'. " . $category->description;
}

// Если определен тег, модифицируем заголовок
if (isset($tag)) {
    $title = "Новости с тегом '{$tag->name}' - " . config('app.name');
    $description = "Новости с тегом '{$tag->name}' на кулинарную тему. Последние публикации из мира кулинарии";
}

// Для SEO: ключевые слова
$keywords = isset($searchTerm)
    ? "$searchTerm, новости кулинарии, кулинарные статьи"
    : "новости кулинарии, кулинарные статьи, кулинарные тренды, рецепты";

// Определяем каноническую ссылку
$canonicalUrl = request()->url();
if (isset($category)) {
    $canonicalUrl = route('news.index', ['category' => $category->slug]);
} elseif (isset($tag)) {
    $canonicalUrl = route('news.index', ['tag' => $tag->slug]);
} elseif (isset($searchTerm)) {
    $canonicalUrl = route('news.index', ['search' => $searchTerm]);
}

// Добавляем номер страницы к каноническому URL для страниц пагинации
// Проверяем, является ли $news объектом пагинации
if (isset($news) && method_exists($news, 'currentPage') && $news->currentPage() > 1) {
    $canonicalUrl = $news->url($news->currentPage());
}

// Ссылки пагинации
$paginationLinks = [];
if (isset($news) && method_exists($news, 'hasPages') && $news->hasPages()) {
    if ($news->currentPage() > 1) {
        $paginationLinks['prev'] = $news->previousPageUrl();
    }

    if ($news->hasMorePages()) {
        $paginationLinks['next'] = $news->nextPageUrl();
    }
}
@endphp

{{-- Базовые мета-теги --}}
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">

{{-- Каноническая ссылка --}}
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Ссылки для пагинации --}}
@if(isset($paginationLinks['prev']))
    <link rel="prev" href="{{ $paginationLinks['prev'] }}">
@endif
@if(isset($paginationLinks['next']))
    <link rel="next" href="{{ $paginationLinks['next'] }}">
@endif

{{-- Open Graph теги --}}
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonicalUrl }}">

@if(isset($news) && is_object($news) && !method_exists($news, 'currentPage') && $news->image_url)
    <meta property="og:image" content="{{ asset('uploads/' . $news->image_url) }}">
    <meta property="og:image:width" content="1200">
    <meta property="og:image:height" content="630">
    <meta property="og:type" content="article">
    <meta property="article:published_time" content="{{ $news->created_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $news->updated_at->toIso8601String() }}">
@else
    <meta property="og:image" content="{{ asset('images/og-news.jpg') }}">
    <meta property="og:type" content="website">
@endif

<meta property="og:site_name" content="{{ config('app.name') }}">

{{-- Twitter Card теги --}}
<meta name="twitter:card" content="{{ (isset($news) && is_object($news) && !method_exists($news, 'currentPage') && $news->image_url) ? 'summary_large_image' : 'summary' }}">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:url" content="{{ $canonicalUrl }}">
@if(isset($news) && is_object($news) && !method_exists($news, 'currentPage') && $news->image_url)
    <meta name="twitter:image" content="{{ asset('uploads/' . $news->image_url) }}">
@else
    <meta name="twitter:image" content="{{ asset('images/og-news.jpg') }}">
@endif

<!-- Дополнительная информация -->
<meta name="robots" content="{{ isset($news) && method_exists($news, 'currentPage') && $news->currentPage() > 1 ? 'noindex, follow' : 'index, follow' }}">
<meta name="author" content="{{ config('app.name') }}">
