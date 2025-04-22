@php
// Подготавливаем Schema.org данные для страницы новостей
$newsSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'headline' => isset($searchTerm) ? "Поиск: $searchTerm - Кулинарные новости" : "Кулинарные новости и статьи",
    'description' => isset($searchTerm) ? "Результаты поиска новостей по запросу '$searchTerm'" : "Последние новости из мира кулинарии",
    'url' => url()->current(),
    'publisher' => [
        '@type' => 'Organization',
        'name' => config('app.name'),
        'logo' => [
            '@type' => 'ImageObject',
            'url' => asset('images/logo.png')
        ]
    ]
];

// Добавляем элементы новостей в список, если они существуют
// Проверяем, является ли $news коллекцией или отдельной новостью
if (isset($news)) {
    // Проверка, является ли $news коллекцией
    if (is_object($news) && method_exists($news, 'count') && $news->count() > 0) {
        // Это коллекция новостей (для страницы списка новостей)
        $newsSchema['mainEntity'] = [
            '@type' => 'ItemList',
            'itemListElement' => []
        ];
        
        foreach ($news as $index => $item) {
            // Дополнительная проверка на тип элемента
            if (is_object($item) && isset($item->slug) && isset($item->title)) {
                $newsSchema['mainEntity']['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => (int)$index + 1,
                    'url' => route('news.show', $item->slug),
                    'name' => $item->title
                ];
            }
        }
    } 
    // Проверка, является ли $news отдельной новостью (для страницы просмотра)
    elseif (is_object($news) && isset($news->slug)) {
        // Это отдельная новость, меняем тип схемы на NewsArticle
        $newsSchema['@type'] = 'NewsArticle';
        $newsSchema['headline'] = $news->title;
        $newsSchema['description'] = $news->short_description;
        
        if ($news->image_url) {
            $newsSchema['image'] = [asset('uploads/' . $news->image_url)];
        }
        
        $newsSchema['datePublished'] = $news->created_at->toIso8601String();
        $newsSchema['dateModified'] = $news->updated_at->toIso8601String();
        $newsSchema['author'] = [
            '@type' => 'Person',
            'name' => $news->user ? $news->user->name : config('app.name')
        ];
        
        $newsSchema['mainEntityOfPage'] = [
            '@type' => 'WebPage',
            '@id' => route('news.show', $news->slug)
        ];
    }
}

// Хлебные крошки для новостей
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
        ]
    ]
];

// Если есть поисковый запрос или категория, добавляем в хлебные крошки
if (isset($searchTerm) && !empty($searchTerm)) {
    $breadcrumbSchema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => "Поиск: $searchTerm",
        'item' => url()->full()
    ];
}

// Если это отдельная новость, добавляем её в хлебные крошки
if (isset($news) && is_object($news) && isset($news->slug) && isset($news->title) && !isset($news->count)) {
    $breadcrumbSchema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => count($breadcrumbSchema['itemListElement']) + 1,
        'name' => $news->title,
        'item' => route('news.show', $news->slug)
    ];
}
@endphp

<script type="application/ld+json">
    @json($newsSchema, JSON_UNESCAPED_UNICODE)
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
