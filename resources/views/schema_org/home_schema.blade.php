@php
// Подготавливаем Schema.org данные для главной страницы
$websiteSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'WebSite',
    'url' => url('/'),
    'name' => config('app.name'),
    'description' => 'Кулинарные рецепты с пошаговыми инструкциями для приготовления вкусных и разнообразных блюд',
    'potentialAction' => [
        '@type' => 'SearchAction',
        'target' => url('/search?query={search_term_string}'),
        'query-input' => 'required name=search_term_string'
    ]
];

// Организация
$organizationSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Organization',
    'name' => config('app.name'),
    'url' => url('/'),
    'logo' => asset('images/logo.png'),
    'sameAs' => [
        'https://vk.com/imedokru',
        'https://t.me/imedokru',
        'https://rutube.ru/channel/60757569/shorts/'
    ],
    'contactPoint' => [
        '@type' => 'ContactPoint',
        'telephone' => '+79044482283',
        'contactType' => 'customer service',
        'email' => 'w1nishko@yandex.ru'
    ]
];

// Данные кулинарного сайта
$recipeCollectionSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'ItemList',
    'name' => 'Популярные рецепты на ' . config('app.name'),
    'description' => 'Лучшие кулинарные рецепты с подробными инструкциями',
    'itemListElement' => []
];

// Если есть популярные рецепты, добавим их в список
if(isset($latestRecipes) && $latestRecipes->count() > 0) {
    foreach($latestRecipes as $index => $recipe) {
        $recipeCollectionSchema['itemListElement'][] = [
            '@type' => 'ListItem',
            'position' => $index + 1,
            'url' => route('recipes.show', $recipe->slug)
        ];
    }
}

// Добавляем FAQ для главной страницы
$faqSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'FAQPage',
    'mainEntity' => [
        [
            '@type' => 'Question',
            'name' => 'Как добавить свой рецепт на сайт?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Чтобы добавить свой рецепт, вам необходимо войти или зарегистрироваться на нашем сайте. После авторизации нажмите на кнопку "Добавить рецепт", заполните все необходимые поля и опубликуйте свой кулинарный шедевр!'
            ]
        ],
        [
            '@type' => 'Question',
            'name' => 'Могу ли я сохранять понравившиеся рецепты?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Да, после регистрации вы можете добавлять рецепты в избранное, нажав на значок звездочки на странице рецепта. Все сохраненные рецепты будут доступны в разделе "Избранное" в вашем профиле.'
            ]
        ],
        [
            '@type' => 'Question',
            'name' => 'Как найти рецепты по имеющимся ингредиентам?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Воспользуйтесь расширенным поиском на главной странице. На вкладке "По ингредиентам" введите имеющиеся у вас продукты, и система подберет подходящие рецепты.'
            ]
        ],
        [
            '@type' => 'Question',
            'name' => 'Как рассчитывается калорийность блюд?',
            'acceptedAnswer' => [
                '@type' => 'Answer',
                'text' => 'Калорийность рассчитывается автоматически на основе ингредиентов и их количества в рецепте. Обратите внимание, что это приблизительные значения, которые могут незначительно отличаться в зависимости от конкретных продуктов.'
            ]
        ]
    ]
];
@endphp

<script type="application/ld+json">
    @json($websiteSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

<script type="application/ld+json">
    @json($organizationSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

@if(isset($latestRecipes) && $latestRecipes->count() > 0)
<script type="application/ld+json">
    @json($recipeCollectionSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>
@endif

<script type="application/ld+json">
    @json($faqSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>
