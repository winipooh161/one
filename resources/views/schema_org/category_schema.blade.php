@php
// Подготавливаем Schema.org данные для страницы отдельной категории

// Основные данные для страницы категории
$categorySchema = [
    '@context' => 'https://schema.org',
    '@type' => 'CollectionPage',
    'name' => $category->name,
    'description' => $category->description ?? 'Рецепты в категории ' . $category->name,
    'url' => route('categories.show', $category->slug),
    'datePublished' => $category->created_at->toIso8601String(),
    'dateModified' => $category->updated_at->toIso8601String(),
    'author' => [
        '@type' => 'Organization',
        'name' => config('app.name'),
        'url' => url('/')
    ],
    'mainEntity' => [
        '@type' => 'ItemList',
        'numberOfItems' => $recipes->total(),
        'itemListElement' => []
    ]
];

// Добавляем изображение категории, если оно есть
if($category->image_path) {
    $categorySchema['image'] = [
        '@type' => 'ImageObject',
        'url' => asset($category->image_path),
        'width' => 1200,
        'height' => 630
    ];
}

// Добавляем рецепты в схему
if($recipes->count() > 0) {
    foreach($recipes as $index => $recipe) {
        $recipeSchemaItem = [
            '@type' => 'ListItem',
            'position' => ($recipes->currentPage() - 1) * $recipes->perPage() + $index + 1,
            'item' => [
                '@type' => 'Recipe',
                'name' => $recipe->title,
                'url' => route('recipes.show', $recipe->slug),
                'description' => Str::limit(strip_tags($recipe->description), 150),
                'datePublished' => $recipe->created_at->toIso8601String(),
                'author' => [
                    '@type' => 'Person',
                    'name' => $recipe->user ? $recipe->user->name : config('app.name')
                ],
                'recipeCategory' => $category->name
            ]
        ];
        
        // Добавляем время приготовления, если оно указано
        if($recipe->cooking_time) {
            $recipeSchemaItem['item']['totalTime'] = 'PT' . $recipe->cooking_time . 'M';
        }
        
        // Добавляем изображение рецепта, если оно есть
        if($recipe->image_url) {
            $recipeSchemaItem['item']['image'] = asset($recipe->image_url);
        }
        
        // Добавляем количество порций, если оно указано
        if($recipe->servings) {
            $recipeSchemaItem['item']['recipeYield'] = $recipe->servings . ' ' . trans_choice('порция|порции|порций', $recipe->servings);
        } else {
            $recipeSchemaItem['item']['recipeYield'] = '4 порции';
        }
        
        $categorySchema['mainEntity']['itemListElement'][] = $recipeSchemaItem;
    }
}

// Хлебные крошки для страницы категории
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
        ],
        [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => $category->name,
            'item' => route('categories.show', $category->slug)
        ]
    ]
];

// Добавляем информацию о веб-сайте
$siteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'name' => config('app.name'),
    'url' => url('/'),
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => url('/search?query={search_term_string}'),
        'query-input' => 'required name=search_term_string'
    ]
];

// Добавление информации о популярных рецептах если они есть
$popularRecipesSchema = null;
if(isset($popularRecipes) && $popularRecipes->count() > 0) {
    $popularRecipesSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'ItemList',
        'name' => 'Популярные рецепты в категории ' . $category->name,
        'numberOfItems' => $popularRecipes->count(),
        'itemListElement' => []
    ];
    
    foreach($popularRecipes as $index => $recipe) {
        $popularRecipesSchema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'item' => [
                '@type' => 'Recipe',
                'name' => $recipe->title,
                'url' => route('recipes.show', $recipe->slug),
                'image' => asset($recipe->image_url),
                'description' => Str::limit(strip_tags($recipe->description), 100),
                'recipeCategory' => $category->name
            ]
        ];
    }
}
@endphp

<script type="application/ld+json">
    @json($categorySchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<script type="application/ld+json">
    @json($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<script type="application/ld+json">
    @json($siteSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

@if($popularRecipesSchema)
<script type="application/ld+json">
    @json($popularRecipesSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>
@endif
