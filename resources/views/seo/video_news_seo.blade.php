@php
// Определяем заголовок и описание для видео новости
$title = $news->video_title ?? $news->title;
$title .= " - Видео | " . config('app.name');

$description = $news->video_description ?? $news->short_description ?? Str::limit(strip_tags($news->content), 160);

// Извлекаем URL видео из iframe для Open Graph тегов
$embedUrl = "";
if ($news->video_iframe) {
    preg_match('/src="([^"]+)"/', $news->video_iframe, $matches);
    $embedUrl = $matches[1] ?? '';
}

// Ключевые слова
$keywords = "видео, новости кулинарии, " . ($news->video_tags ?? "кулинарное видео, мастер-класс");

// Каноническая ссылка
$canonicalUrl = route('news.show', $news->slug);

// Определяем платформу видео для разметки
$siteName = config('app.name');
$contentUrl = "";
$platform = "";
if ($embedUrl) {
    if (strpos($embedUrl, 'vk.com') !== false) {
        $platform = 'ВКонтакте';
        $siteName .= " / ВКонтакте";
        // Получаем видео ID для VK
        if (preg_match('/oid=(-?\d+)&id=(\d+)/', $embedUrl, $idMatches)) {
            $ownerId = $idMatches[1];
            $videoId = $idMatches[2];
            $contentUrl = "https://vk.com/video{$ownerId}_{$videoId}";
        }
    } elseif (strpos($embedUrl, 'rutube.ru') !== false) {
        $platform = 'Rutube';
        $siteName .= " / Rutube";
        // Получаем видео ID для Rutube
        if (preg_match('/embed\/([^\/\?]+)/', $embedUrl, $idMatches)) {
            $videoId = $idMatches[1];
            $contentUrl = "https://rutube.ru/video/{$videoId}/";
        }
    }
}

// URL изображения для OpenGraph
$imageUrl = $news->image_url ? asset('uploads/' . $news->image_url) : asset('images/news-placeholder.jpg');
@endphp

{{-- Базовые мета-теги --}}
<title>{{ $title }}</title>
<meta name="description" content="{{ $description }}">
<meta name="keywords" content="{{ $keywords }}">

{{-- Каноническая ссылка --}}
<link rel="canonical" href="{{ $canonicalUrl }}">

{{-- Open Graph теги для видео --}}
<meta property="og:title" content="{{ $title }}">
<meta property="og:description" content="{{ $description }}">
<meta property="og:url" content="{{ $canonicalUrl }}">
<meta property="og:type" content="video.other">
<meta property="og:image" content="{{ $imageUrl }}">
<meta property="og:image:width" content="1200">
<meta property="og:image:height" content="630">
<meta property="og:site_name" content="{{ $siteName }}">

@if($contentUrl)
    <meta property="og:video" content="{{ $contentUrl }}">
    <meta property="og:video:url" content="{{ $contentUrl }}">
    <meta property="og:video:secure_url" content="{{ $contentUrl }}">
    <meta property="og:video:type" content="text/html">
@endif

@if($embedUrl)
    <meta property="og:video:url" content="{{ $embedUrl }}">
    <meta property="og:video:secure_url" content="{{ $embedUrl }}">
    <meta property="og:video:type" content="text/html">
    <meta property="og:video:width" content="1280">
    <meta property="og:video:height" content="720">
@endif

{{-- Дополнительные мета-теги для статьи --}}
<meta property="article:published_time" content="{{ $news->created_at->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $news->updated_at->toIso8601String() }}">
<meta property="article:author" content="{{ $news->user ? $news->user->name : ($news->video_author_name ?? config('app.name')) }}">

@if($news->video_tags)
    @foreach(explode(',', $news->video_tags) as $tag)
        <meta property="article:tag" content="{{ trim($tag) }}">
    @endforeach
@endif

{{-- Twitter Card теги --}}
<meta name="twitter:card" content="player">
<meta name="twitter:title" content="{{ $title }}">
<meta name="twitter:description" content="{{ $description }}">
<meta name="twitter:url" content="{{ $canonicalUrl }}">
<meta name="twitter:image" content="{{ $imageUrl }}">
@if($embedUrl)
    <meta name="twitter:player" content="{{ $embedUrl }}">
    <meta name="twitter:player:width" content="1280">
    <meta name="twitter:player:height" content="720">
@endif
