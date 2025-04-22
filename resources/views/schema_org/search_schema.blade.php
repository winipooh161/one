@php
// Подготавливаем Schema.org данные для страницы поиска
$searchAction = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'url' => url('/'),
    'name' => config('app.name'),
    'description' => 'Кулинарный сайт с пошаговыми рецептами и фотографиями',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => url('/search?query={search_term_string}'),
        'query-input' => 'required name=search_term_string'
    ]
];

// Проверяем наличие поискового запроса
$query = isset($query) ? $query : (request()->input('query') ?? request()->input('q') ?? '');

// Если у нас есть результаты поиска и строка запроса, добавляем разметку ItemList
$searchResults = null;
if (isset($recipes) && method_exists($recipes, 'total') && $recipes->total() > 0 && !empty($query)) {
    $searchResults = [
        '@context' => 'https://schema.org',
        '@type' => 'SearchResultsPage',
        'mainEntity' => [
            '@type' => 'ItemList',
            'name' => 'Результаты поиска: ' . $query,
            'description' => 'Результаты поиска рецептов по запросу: ' . $query,
            'numberOfItems' => $recipes->total(),
            'itemListOrder' => 'https://schema.org/ItemListOrderDescending',
            'itemListElement' => []
        ],
        'url' => url()->current() . '?' . http_build_query(request()->all()),
        'dateModified' => now()->toIso8601String()
    ];
    
    foreach ($recipes as $index => $recipe) {
        // Определяем текущую позицию для пагинации
        $position = ($recipes->currentPage() - 1) * $recipes->perPage() + $index + 1;
        
        // Формируем данные по рецепту для Schema.org
        $recipeSchema = [
            '@type' => 'Recipe',
            'name' => $recipe->title,
            'url' => route('recipes.show', $recipe->slug),
            'datePublished' => $recipe->created_at->toIso8601String(),
            'dateModified' => $recipe->updated_at->toIso8601String(),
            'description' => Str::limit(strip_tags($recipe->description), 200)
        ];
        
        // Добавляем изображение, если оно есть
        if (!empty($recipe->image_url)) {
            $recipeSchema['image'] = [
                '@type' => 'ImageObject',
                'url' => asset($recipe->image_url ?: 'images/placeholder.jpg'),
                'width' => '800',
                'height' => '600',
                'caption' => $recipe->title
            ];
        }
        
        // Добавляем время приготовления, если оно указано
        if ($recipe->cooking_time) {
            $recipeSchema['cookTime'] = 'PT' . $recipe->cooking_time . 'M';
            $recipeSchema['totalTime'] = 'PT' . $recipe->cooking_time . 'M';
        }
        
        // Добавляем категорию, если она есть
        if ($recipe->categories->isNotEmpty()) {
            $recipeSchema['recipeCategory'] = $recipe->categories->first()->name;
        }
        
        // Добавляем автора, если он есть
        if (isset($recipe->user) && $recipe->user) {
            $recipeSchema['author'] = [
                '@type' => 'Person',
                'name' => $recipe->user->name
            ];
        } else {
            $recipeSchema['author'] = [
                '@type' => 'Organization',
                'name' => config('app.name')
            ];
        }
        
        // Добавляем рецепт в список результатов поиска
        $searchResults['mainEntity']['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $position,
            'item' => $recipeSchema
        ];
    }
}

// Хлебные крошки для страницы поиска
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
            'name' => 'Поиск',
            'item' => route('search')
        ]
    ]
];

// Добавляем поисковый запрос в хлебные крошки, если он есть
if (!empty($query)) {
    $breadcrumbSchema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => 'Запрос: ' . $query,
        'item' => url()->current() . '?' . http_build_query(['query' => $query])
    ];
}

// Добавляем данные для FAQ, если есть популярные поисковые запросы
$faqSchema = null;
$popularQuestions = [
    'Как быстро приготовить ужин?' => 'Для быстрого ужина рекомендуем рецепты с пометкой "до 30 минут". Также популярные быстрые блюда: омлет, паста с соусом, бутерброды с авокадо.',
    'Какие рецепты подходят для начинающих?' => 'Для начинающих кулинаров хорошо подходят простые рецепты с минимумом ингредиентов и четкими инструкциями. Попробуйте начать с базовых блюд: яичница, макароны, простые супы.',
    'Что приготовить из курицы?' => 'Из курицы можно приготовить множество блюд: жаркое, суп, плов, котлеты, запеченную курицу с овощами, курицу в соусе, шашлык.',
    'Какие есть вегетарианские рецепты?' => 'Популярные вегетарианские блюда: овощные рагу, фалафель, хумус, тофу с овощами, овощные супы-пюре, салаты, запеканки из овощей.'
];

if (!empty($query)) {
    // FAQ схема для страницы с результатами поиска
    $faqSchema = [
        '@context' => 'https://schema.org',
        '@type' => 'FAQPage',
        'mainEntity' => []
    ];
    
    // Добавляем связанные с запросом вопросы
    foreach ($popularQuestions as $question => $answer) {
        $faqSchema['mainEntity'][] = [
            '@type' => 'Question',
            'name' => $question,
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => $answer
            ]
        ];
    }
}
@endphp

<!-- Основная разметка WebSite с поисковым действием -->
<script type="application/ld+json">
    @json($searchAction, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<!-- Разметка для результатов поиска, если они есть -->
@if($searchResults)
<script type="application/ld+json">
    @json($searchResults, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>
@endif

<!-- Разметка для хлебных крошек -->
<script type="application/ld+json">
    @json($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<!-- Разметка FAQ для поисковой страницы -->
@if($faqSchema)
<script type="application/ld+json">
    @json($faqSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>
@endif
