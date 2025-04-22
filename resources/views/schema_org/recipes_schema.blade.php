@php
// Подготавливаем Schema.org данные для списка рецептов
$listSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Список рецептов',
    'description' => 'Каталог кулинарных рецептов с пошаговыми инструкциями и фото',
    'itemListElement' => []
];

if(isset($recipes) && $recipes->count() > 0) {
    foreach($recipes as $index => $recipe) {
        $listSchema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => ($recipes->currentPage() - 1) * $recipes->perPage() + $index + 1,
            'item' => [
                '@type' => 'Recipe',
                'name' => $recipe->title,
                'url' => route('recipes.show', $recipe->slug),
                'image' => asset($recipe->image_url ?: 'images/placeholder.jpg'),
                'author' => [
                    '@type' => 'Person',
                    'name' => $recipe->user ? $recipe->user->name : config('app.name')
                ],
                'datePublished' => $recipe->created_at->toIso8601String(),
                'description' => Str::limit(strip_tags($recipe->description), 150),
                'recipeCategory' => $recipe->categories->isNotEmpty() ? $recipe->categories->first()->name : null,
                'recipeYield' => $recipe->servings ? $recipe->servings . ' ' . trans_choice('порция|порции|порций', $recipe->servings) : null,
                'cookTime' => 'PT' . $recipe->cooking_time . 'M',
            ]
        ];
    }
}

// Хлебные крошки для страницы списка рецептов
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
            'name' => 'Все рецепты',
            'item' => route('recipes.index')
        ]
    ]
];

// Если задана категория, добавляем ее в хлебные крошки
if (isset($category) && $category) {
    $breadcrumbSchema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $category->name,
        'item' => route('recipes.index', ['category' => $category->slug])
    ];
}

// Если выполняется поиск, добавляем это в хлебные крошки
if (isset($request) && ($request->has('search') || $request->has('q'))) {
    $searchTerm = trim($request->input('search', $request->input('q', '')));
    if (!empty($searchTerm)) {
        $breadcrumbSchema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => 3,
            'name' => 'Поиск: ' . $searchTerm,
            'item' => url()->current()
        ];
    }
}
@endphp

<script type="application/ld+json">
    @json($listSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<script type="application/ld+json">
    @json($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>
