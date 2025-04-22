@php
// Подготавливаем данные для структурированной разметки видео
$title = $news->video_title ?? $news->title;
$description = $news->video_description ?? $news->short_description ?? '';
$embedUrl = "";
$thumbnailUrl = $news->image_url 
    ? asset('uploads/' . $news->image_url) 
    : asset('images/news-placeholder.jpg');

// Извлекаем URL видео из iframe
if ($news->video_iframe) {
    preg_match('/src="([^"]+)"/', $news->video_iframe, $matches);
    $embedUrl = $matches[1] ?? '';
}

// Определяем платформу видео
$contentUrl = "";
$platform = "";
if ($embedUrl) {
    if (strpos($embedUrl, 'vk.com') !== false) {
        $platform = 'ВКонтакте';
        // Получаем видео ID для VK
        if (preg_match('/oid=(-?\d+)&id=(\d+)/', $embedUrl, $idMatches)) {
            $ownerId = $idMatches[1];
            $videoId = $idMatches[2];
            $contentUrl = "https://vk.com/video{$ownerId}_{$videoId}";
        }
    } elseif (strpos($embedUrl, 'rutube.ru') !== false) {
        $platform = 'Rutube';
        // Получаем видео ID для Rutube
        if (preg_match('/embed\/([^\/\?]+)/', $embedUrl, $idMatches)) {
            $videoId = $idMatches[1];
            $contentUrl = "https://rutube.ru/video/{$videoId}/";
        }
    }
}

// Канонический URL
$pageUrl = route('news.show', $news->slug);

// Даты для разметки
$uploadDate = $news->created_at->toIso8601String();

// Подготавливаем Schema.org данные для видео-новости
$videoNewsSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'VideoObject',
    'name' => $news->video_title ?? $news->title,
    'description' => $news->video_description ?? $news->short_description,
    'thumbnailUrl' => $news->image_url ? asset('uploads/' . $news->image_url) : asset('images/news-placeholder.jpg'),
    'uploadDate' => $news->created_at->toIso8601String(),
    'publisher' => [
        '@type' => 'Organization',
        'name' => config('app.name'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('images/logo.png')
        ]
    ]
];

// Добавляем дополнительные данные, если они есть
if ($news->video_author_name) {
    $videoNewsSchema['author'] = [
        '@type' => 'Person',
        'name' => $news->video_author_name
    ];
    
    if ($news->video_author_link) {
        $videoNewsSchema['author']['url'] = $news->video_author_link;
    }
}

// Хлебные крошки для видео-новости
$breadcrumbSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'BreadcrumbList',
    'itemListElement' => [
        [
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Главная',
            'item' => url('/')
        ],
        [
            '@type' => 'ListItem',
            'position' => 2,
            'name' => 'Новости',
            'item' => route('news.index')
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $news->title,
            'item' => route('news.show', $news->slug)
        ]
    ]
];

// Дополнительно добавим NewsArticle разметку для полной совместимости
$newsArticleSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'NewsArticle',
    'headline' => $news->title,
    'description' => $news->short_description,
    'image' => $news->image_url ? [asset('uploads/' . $news->image_url)] : [asset('images/news-placeholder.jpg')],
    'datePublished' => $news->created_at->toIso8601String(),
    'dateModified' => $news->updated_at->toIso8601String(),
    'author' => [
        '@type' => 'Person',
        'name' => $news->user ? $news->user->name : ($news->video_author_name ?? config('app.name'))
    ],
    'publisher' => [
        '@type' => 'Organization',
        'name' => config('app.name'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('images/logo.png'),
            'width' => '192',
            'height' => '192'
        ]
    ],
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => route('news.show', $news->slug)
    ]
];

// Добавляем видео как часть статьи
$newsArticleSchema['video'] = $videoNewsSchema;
@endphp

<script type="application/ld+json">
    @json($videoNewsSchema, JSON_UNESCAPED_UNICODE)
</script>

<script type="application/ld+json">
    @json($newsArticleSchema, JSON_UNESCAPED_UNICODE)
</script>

<script type="application/ld+json">
    @json($breadcrumbSchema, JSON_UNESCAPED_UNICODE)
</script>

<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "WebSite",
    "name": "{{ config('app.name') }}",
    "url": "{{ url('/') }}",
    "potentialAction": {
        "@type": "SearchAction",
        "target": "{{ route('news.index') }}?search={search_term_string}",
        "query-input": "required name=search_term_string"
    }
}
</script>
