@php
// Подготавливаем Schema.org данные для страницы категорий
$categoriesSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => 'Категории рецептов | Яедок',
    'description' => 'Полный каталог кулинарных категорий. Найдите рецепты по любой категории блюд.',
    'url' => route('categories.index'),
    'datePublished' => now()->subMonths(6)->toIso8601String(),
    'dateModified' => now()->toIso8601String(),
    'author' => [
        '@type' => 'Organization',
        'name' => config('app.name'),
        'url' => url('/')
    ],
    'image' => asset('images/categories-cover.jpg'),
    'mainEntity' => [
        '@type' => 'ItemList',
        'numberOfItems' => $categoriesCount ?? count($popularCategories),
        'itemListElement' => []
    ]
];

// Добавляем популярные категории в schema
if(isset($popularCategories) && $popularCategories->count() > 0) {
    foreach($popularCategories as $index => $category) {
        $categoriesSchema['mainEntity']['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Thing',
                'name' => $category->name,
                'url' => route('categories.show', $category->slug),
                'image' => asset($category->image_path ?: 'images/category-placeholder.jpg'),
                'description' => $category->description ?? 'Рецепты в категории ' . $category->name,
                'mainEntityOfPage' => route('categories.show', $category->slug)
            ]
        ];
    }
}

// Добавляем информацию о количестве рецептов в каждой категории, если они доступны
foreach($popularCategories as $index => $category) {
    if(isset($category->recipes_count) && $category->recipes_count > 0) {
        $categoriesSchema['mainEntity']['itemListElement'][$index]['item']['potentialAction'] = [
            '@type' => 'ViewAction',
            'target' => route('categories.show', $category->slug),
            'name' => 'Просмотреть ' . $category->recipes_count . ' ' . trans_choice('рецепт|рецепта|рецептов', $category->recipes_count) . ' в категории ' . $category->name
        ];
    }
}

// Хлебные крошки для страницы категорий
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
            'name' => 'Категории',
            'item' => route('categories.index')
        ]
    ]
];

// Добавляем информацию об организации/сайте
$siteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('app.name'),
    'url' => url('/'),
    'description' => 'Кулинарные рецепты с пошаговыми инструкциями',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => url('/search?query={search_term_string}'),
        'query-input' => 'required name=search_term_string'
    ]
];

// Добавляем информацию о специальных предложениях (вдохновение дня)
if(isset($featuredRecipes) && count($featuredRecipes) > 0) {
    $featuredRecipesSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Вдохновение дня - Рекомендуемые рецепты',
        'numberOfItems' => count($featuredRecipes),
        'itemListElement' => []
    ];
    
    foreach($featuredRecipes as $index => $recipe) {
        $featuredRecipesSchema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Recipe',
                'name' => $recipe->title,
                'url' => route('recipes.show', $recipe->slug),
                'image' => asset($recipe->image_url),
                'description' => Str::limit(strip_tags($recipe->description ?? ''), 150),
                'recipeCategory' => $recipe->categories->first() ? $recipe->categories->first()->name : null
            ]
        ];
    }
}
@endphp

<script type="application/ld+json">
    @json($categoriesSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<script type="application/ld+json">
    @json($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<script type="application/ld+json">
    @json($siteSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

@if(isset($featuredRecipes) && count($featuredRecipes) > 0)
<script type="application/ld+json">
    @json($featuredRecipesSchema ?? [], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>
@endif
