{{-- SEO мета-теги для шаблона рецепта --}}
@php
// Используем SeoService для генерации оптимальных мета-тегов
$seoMeta = isset($seo) ? $seo->getRecipeMeta($recipe) : [];

// Основные SEO параметры
$pageTitle = $seoMeta['title'] ?? ($recipe->title . ' - пошаговый рецепт | ' . config('app.name'));
$pageDescription = $seoMeta['meta_description'] ?? Str::limit(strip_tags($recipe->description), 160);
$pageKeywords = $seoMeta['meta_keywords'] ?? ($recipe->title . ', рецепт, приготовление, кулинария, Яедок, я едок');
$canonicalUrl = $seoMeta['canonical'] ?? route('recipes.show', $recipe->slug);

// Open Graph параметры
$ogTitle = $seoMeta['og_title'] ?? $pageTitle;
$ogDescription = $seoMeta['og_description'] ?? $pageDescription;
$ogImage = $seoMeta['og_image'] ?? asset($recipe->image_url ?: 'images/placeholder.jpg');
$ogType = $seoMeta['og_type'] ?? 'article';
@endphp

{{-- Основные SEO-теги --}}
<title>{{ $pageTitle }}</title>
<meta name="description" content="{{ $pageDescription }}">
<meta name="keywords" content="{{ $pageKeywords }}">
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph теги --}}
<meta property="og:title" content="{{ $ogTitle }}">
<meta property="og:description" content="{{ $ogDescription }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="{{ $ogType }}">
<meta property="og:image" content="{{ $ogImage }}">
<meta property="og:site_name" content="{{ config('app.name') }}">

@if($ogType === 'article')
    <meta property="article:published_time" content="{{ $recipe->created_at->toIso8601String() }}">
    <meta property="article:modified_time" content="{{ $recipe->updated_at->toIso8601String() }}">
    @if($recipe->categories->isNotEmpty())
        <meta property="article:section" content="{{ $recipe->categories->first()->name }}">
    @endif
@endif

{{-- Twitter Card теги --}}
<meta name="twitter:card" content="{{ $seoMeta['twitter_card'] ?? 'summary_large_image' }}">
<meta name="twitter:title" content="{{ $ogTitle }}">
<meta name="twitter:description" content="{{ $ogDescription }}">
<meta name="twitter:image" content="{{ $ogImage }}">

{{-- Дополнительные мета-теги для рецепта --}}
@if($recipe->cooking_time)
    <meta name="recipe:cooking_time" content="{{ $recipe->cooking_time }} минут">
@endif
@if($recipe->servings)
    <meta name="recipe:yield" content="{{ $recipe->servings }} порций">
@endif
@if($recipe->calories)
    <meta name="recipe:calories" content="{{ $recipe->calories }} ккал">
@endif
