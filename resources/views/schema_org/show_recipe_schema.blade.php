@php
// Получаем все необходимые данные для рецепта
$ingredients = [];

// Собираем ингредиенты в зависимости от формата хранения
if (isset($recipe->ingredients) && is_object($recipe->ingredients) && $recipe->ingredients->count() > 0) {
    foreach($recipe->ingredients as $ingredient) {
        $ingredients[] = ($ingredient->quantity ? $ingredient->quantity . ' ' . ($ingredient->unit ?? '') . ' ' : '') . $ingredient->name;
    }
} elseif (is_array($recipe->ingredients)) {
    foreach ($recipe->ingredients as $ingredient) {
        if (is_array($ingredient)) {
            $ingredients[] = ($ingredient['quantity'] ?? '') . ' ' . ($ingredient['unit'] ?? '') . ' ' . ($ingredient['name'] ?? '');
        } else {
            $ingredients[] = $ingredient;
        }
    }
} elseif (is_string($recipe->ingredients)) {
    $ingredients = array_filter(explode("\n", $recipe->ingredients), function($item) {
        return !empty(trim($item));
    });
}

// Подготавливаем инструкции
$instructions = [];
if (is_array($recipe->instructions)) {
    foreach ($recipe->instructions as $index => $instruction) {
        if (is_array($instruction)) {
            $instructions[] = [
                "@type" => "HowToStep",
                "position" => $index + 1,
                "text" => $instruction['text'] ?? $instruction
            ];
        } else {
            $instructions[] = [
                "@type" => "HowToStep",
                "position" => $index + 1,
                "text" => $instruction
            ];
        }
    }
} elseif (is_string($recipe->instructions)) {
    $steps = array_filter(explode("\n", $recipe->instructions), function($item) {
        return !empty(trim($item));
    });
    foreach ($steps as $index => $step) {
        $instructions[] = [
            "@type" => "HowToStep",
            "position" => $index + 1,
            "text" => trim($step)
        ];
    }
}

// Подготавливаем информацию о времени
$prepTime = 'PT' . ($recipe->prep_time ?? 10) . 'M';
$cookTime = 'PT' . ($recipe->cooking_time ?? 20) . 'M';
$totalTime = 'PT' . (($recipe->prep_time ?? 10) + ($recipe->cooking_time ?? 20)) . 'M';

// Оценка рецепта
$ratingValue = isset($recipe->rating) ? $recipe->rating : (isset($recipe->additional_data['rating']['value']) ? $recipe->additional_data['rating']['value'] : 5);
$ratingCount = isset($recipe->ratings_count) ? $recipe->ratings_count : (isset($recipe->additional_data['rating']['count']) ? $recipe->additional_data['rating']['count'] : 1);

// Формируем основную схему рецепта
$recipeSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Recipe',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => route('recipes.show', $recipe->slug)
    ],
    'name' => $recipe->title,
    'headline' => $recipe->title,
    'description' => strip_tags($recipe->description),
    'datePublished' => $recipe->created_at->toIso8601String(),
    'dateModified' => $recipe->updated_at->toIso8601String(),
    'author' => [
        '@type' => 'Person',
        'name' => $recipe->user ? $recipe->user->name : config('app.name')
    ],
    'image' => [
        '@type' => 'ImageObject',
        'url' => asset($recipe->image_url ?: 'images/placeholder.jpg'),
        'width' => 800,
        'height' => 600
    ],
    'prepTime' => $prepTime,
    'cookTime' => $cookTime,
    'totalTime' => $totalTime,
    'recipeYield' => $recipe->servings . ' ' . trans_choice('порция|порции|порций', $recipe->servings ?: 4),
    'recipeIngredient' => $ingredients,
    'recipeInstructions' => $instructions,
    'aggregateRating' => [
        '@type' => 'AggregateRating',
        'ratingValue' => $ratingValue,
        'ratingCount' => $ratingCount,
        'bestRating' => '5',
        'worstRating' => '1'
    ]
];

// Добавляем категорию, если есть
if ($recipe->categories->isNotEmpty()) {
    $recipeSchema['recipeCategory'] = $recipe->categories->first()->name;
}

// Добавляем пищевую ценность, если есть данные
if ($recipe->calories || $recipe->proteins || $recipe->fats || $recipe->carbs) {
    $recipeSchema['nutrition'] = [
        '@type' => 'NutritionInformation'
    ];
    
    if ($recipe->calories) $recipeSchema['nutrition']['calories'] = $recipe->calories . ' ккал';
    if ($recipe->proteins) $recipeSchema['nutrition']['proteinContent'] = $recipe->proteins . ' г';
    if ($recipe->fats) $recipeSchema['nutrition']['fatContent'] = $recipe->fats . ' г';
    if ($recipe->carbs) $recipeSchema['nutrition']['carbohydrateContent'] = $recipe->carbs . ' г';
}

// Формируем схему хлебных крошек
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
            'name' => 'Рецепты',
            'item' => route('recipes.index')
        ]
    ]
];

// Добавляем категорию в хлебные крошки, если она есть
if ($recipe->categories->isNotEmpty()) {
    $breadcrumbSchema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $recipe->categories->first()->name,
        'item' => route('categories.show', $recipe->categories->first()->slug)
    ];
    
    $breadcrumbSchema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => 4,
        'name' => $recipe->title,
        'item' => route('recipes.show', $recipe->slug)
    ];
} else {
    $breadcrumbSchema['itemListElement'][] = [
        '@type' => 'ListItem',
        'position' => 3,
        'name' => $recipe->title,
        'item' => route('recipes.show', $recipe->slug)
    ];
}

// Добавляем информацию об организации
$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => config('app.name'),
    'url' => url('/'),
    'logo' => asset('images/logo.png'),
    'sameAs' => [
        'https://vk.com/imedokru',
        'https://t.me/imedokru',
        'https://dzen.ru/imedok'
    ]
];
@endphp

<script type="application/ld+json">
    @json($recipeSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<script type="application/ld+json">
    @json($breadcrumbSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<script type="application/ld+json">
    @json($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>
