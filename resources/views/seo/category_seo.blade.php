@php
// Получаем метатеги для страницы конкретной категории
$metaTags = isset($seo) && isset($category) ? $seo->getCategoryMeta($category) : [];

// Заголовок и описание страницы
$title = $metaTags['title'] ?? ($category->meta_title ?? ($category->name . ' - рецепты блюд | Яедок'));
$description = $metaTags['meta_description'] ?? ($category->meta_description ?? ('Рецепты в категории ' . $category->name . '. Подробные пошаговые инструкции с фото и видео. Готовьте вместе с нами!'));
$keywords = $metaTags['meta_keywords'] ?? ($category->meta_keywords ?? ('яедок, я едок, ' . $category->name . ', рецепты, кулинария, готовка'));
$canonical = $metaTags['canonical'] ?? route('categories.show', $category->slug);

// Учитываем пагинацию для canonical URL
if(isset($recipes) && $recipes->currentPage() > 1) {
    $canonical = $recipes->url($recipes->currentPage());
}

// Общие данные для всех страниц
$ogImage = $category->image_path ? asset($category->image_path) : asset('images/categories-cover.jpg');
$recipeCount = isset($recipes) ? $recipes->total() : ($category->recipes_count ?? 0);
@endphp

<!-- Основные метатеги -->
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">
<link rel="canonical" href="{{ $canonical }}">

<!-- Метатеги пагинации -->
@if(isset($recipes) && $recipes->hasPages())
    @if($recipes->currentPage() > 1)
        <link rel="prev" href="{{ $recipes->previousPageUrl() }}">
    @endif
    
    @if($recipes->hasMorePages())
        <link rel="next" href="{{ $recipes->nextPageUrl() }}">
    @endif
@endif

<!-- Open Graph теги для социальных сетей -->
<meta property="og:title" content="{{ $metaTags['og_title'] ?? $title }}">
<meta property="og:description" content="{{ $metaTags['og_description'] ?? $description }}">
<meta property="og:url" content="{{ $metaTags['og_url'] ?? url()->current() }}">
<meta property="og:type" content="{{ $metaTags['og_type'] ?? 'website' }}">
<meta property="og:site_name" content="{{ $metaTags['og_site_name'] ?? config('app.name') }}">
<meta property="og:image" content="{{ $metaTags['og_image'] ?? $ogImage }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:locale" content="ru_RU">

<!-- Twitter Card -->
<meta name="twitter:card" content="{{ $metaTags['twitter_card'] ?? 'summary_large_image' }}">
<meta name="twitter:title" content="{{ $metaTags['twitter_title'] ?? $title }}">
<meta name="twitter:description" content="{{ $metaTags['twitter_description'] ?? $description }}">
<meta name="twitter:image" content="{{ $metaTags['twitter_image'] ?? $ogImage }}">
<meta name="twitter:site" content="@imedokru">
<meta name="twitter:creator" content="@imedokru">

<!-- Дополнительная информация -->
<meta name="author" content="{{ config('app.name') }}">
<meta name="category" content="{{ $category->name }}">
<meta name="robots" content="{{ $metaTags['robots'] ?? 'index, follow' }}">

<!-- Дополнительные метаданные для поисковых систем -->
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<meta name="format-detection" content="telephone=no">
<meta property="article:published_time" content="{{ $category->created_at->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $category->updated_at->toIso8601String() }}">

{{-- Подключаем Schema.org структурированные данные для страницы категории --}}
@includeIf('schema_org.category_schema', [
    'category' => $category, 
    'recipes' => $recipes,
    'popularRecipes' => $popularRecipes ?? null
])
