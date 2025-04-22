@php
// Подготавливаем Schema.org данные для рецепта
$ingredients = [];
if (is_array($recipe->ingredients)) {
    foreach ($recipe->ingredients as $ingredient) {
        $ingredients[] = $ingredient['name'] . ' - ' . $ingredient['quantity'] . ' ' . ($ingredient['unit'] ?? '');
    }
} elseif (is_object($recipe->ingredients) && $recipe->ingredients->count() > 0) {
    foreach ($recipe->ingredients as $ingredient) {
        $ingredients[] = ($ingredient->quantity ? $ingredient->quantity . ' ' . ($ingredient->unit ?? '') . ' ' : '') . $ingredient->name;
    }
} else {
    $ingredients = is_string($recipe->ingredients) ? explode("\n", $recipe->ingredients) : [];
    $ingredients = array_filter($ingredients, function($item) {
        return !empty(trim($item));
    });
}

$instructions = [];
if (is_array($recipe->instructions)) {
    foreach ($recipe->instructions as $index => $instruction) {
        $instructionText = is_array($instruction) ? ($instruction['text'] ?? $instruction) : $instruction;
        $instructionImage = is_array($instruction) && isset($instruction['image']) ? asset($instruction['image']) : null;
        
        $stepData = [
            "@type" => "HowToStep",
            "position" => $index + 1,
            "text" => $instructionText
        ];
        
        if ($instructionImage) {
            $stepData["image"] = $instructionImage;
        }
        
        $instructions[] = $stepData;
    }
} elseif (is_string($recipe->instructions)) {
    $instructionSteps = explode("\n", $recipe->instructions);
    foreach ($instructionSteps as $index => $step) {
        if (!empty(trim($step))) {
            $instructions[] = [
                "@type" => "HowToStep",
                "position" => $index + 1,
                "text" => trim($step)
            ];
        }
    }
}

// Рейтинг рецепта
$ratingValue = 0;
$ratingCount = 0;
if (isset($recipe->additional_data['rating'])) {
    $ratingValue = $recipe->additional_data['rating']['value'] ?? 0;
    $ratingCount = $recipe->additional_data['rating']['count'] ?? 0;
}

// Время приготовления в формате ISO
$prepTime = 'PT' . ($recipe->prep_time ?? 10) . 'M';
$cookTime = 'PT' . ($recipe->cooking_time ?? 20) . 'M';
$totalTime = 'PT' . (($recipe->prep_time ?? 10) + ($recipe->cooking_time ?? 20)) . 'M';

// Создаем основной объект Recipe
$recipeSchema = [
    '@context' => 'https://schema.org',
    '@type' => 'Recipe',
    'mainEntityOfPage' => [
        '@type' => 'WebPage',
        '@id' => route('recipes.show', $recipe->slug)
    ],
    'name' => $recipe->title,
    'author' => [
        '@type' => 'Person',
        'name' => $recipe->user ? $recipe->user->name : config('app.name')
    ],
    'datePublished' => $recipe->created_at->toIso8601String(),
    'dateModified' => $recipe->updated_at->toIso8601String(),
    'description' => $recipe->description,
    'prepTime' => $prepTime,
    'cookTime' => $cookTime,
    'totalTime' => $totalTime,
    'keywords' => $recipe->categories->pluck('name')->implode(', '),
    'recipeYield' => $recipe->servings ? $recipe->servings . ' ' . trans_choice('порция|порции|порций', $recipe->servings) : '4 порции',
    'recipeCategory' => $recipe->categories->isNotEmpty() ? $recipe->categories->first()->name : 'Основные блюда',
    'recipeCuisine' => 'Русская кухня', // Можно добавить динамически, если у вас есть такое поле
    'recipeIngredient' => $ingredients,
    'recipeInstructions' => $instructions
];

// Добавляем информацию о питательной ценности при наличии
if ($recipe->calories || $recipe->proteins || $recipe->fats || $recipe->carbs) {
    $nutrition = [
        '@type' => 'NutritionInformation'
    ];
    
    if ($recipe->calories) $nutrition['calories'] = $recipe->calories . ' ккал';
    if ($recipe->proteins) $nutrition['proteinContent'] = $recipe->proteins . ' г';
    if ($recipe->fats) $nutrition['fatContent'] = $recipe->fats . ' г';
    if ($recipe->carbs) $nutrition['carbohydrateContent'] = $recipe->carbs . ' г';
    
    $recipeSchema['nutrition'] = $nutrition;
}

// Добавляем изображения
if ($recipe->image_url) {
    // Основное изображение
    $recipeSchema['image'] = [
        '@type' => 'ImageObject',
        'url' => asset($recipe->image_url),
        'width' => 800,
        'height' => 600,
        'caption' => $recipe->title
    ];
    
    // Дополнительные изображения, если есть
    if (isset($recipe->additional_data) && is_array($recipe->additional_data)) {
        $additionalImages = [];
        
        // Ищем дополнительные изображения в различных полях
        if (isset($recipe->additional_data['slider_images']) && is_array($recipe->additional_data['slider_images'])) {
            foreach($recipe->additional_data['slider_images'] as $img) {
                $additionalImages[] = asset($img);
            }
        }
        
        if (isset($recipe->additional_data['saved_images']) && is_array($recipe->additional_data['saved_images'])) {
            foreach($recipe->additional_data['saved_images'] as $img) {
                if (isset($img['saved_path'])) {
                    $additionalImages[] = asset($img['saved_path']);
                }
            }
        }
        
        // Если есть дополнительные изображения, добавляем их в массив
        if (!empty($additionalImages)) {
            $additionalImages = array_unique($additionalImages);
            // Преобразуем основное изображение в массив изображений
            if (count($additionalImages) > 0) {
                $recipeSchema['image'] = array_merge([$recipeSchema['image']], array_map(function($url) {
                    return [
                        '@type' => 'ImageObject',
                        'url' => $url,
                        'width' => 800,
                        'height' => 600
                    ];
                }, $additionalImages));
            }
        }
    }
}

// Добавляем рейтинг, если он есть
if ($ratingCount > 0) {
    $recipeSchema['aggregateRating'] = [
        '@type' => 'AggregateRating',
        'ratingValue' => $ratingValue,
        'ratingCount' => $ratingCount,
        'bestRating' => 5,
        'worstRating' => 1
    ];
}

// Добавляем данные по отзывам, если они есть
if (isset($recipe->comments) && $recipe->comments->count() > 0) {
    $recipeSchema['review'] = [];
    foreach($recipe->comments->take(5) as $comment) {
        $reviewRating = isset($comment->rating) && $comment->rating > 0 ? $comment->rating : 5;
        
        $recipeSchema['review'][] = [
            '@type' => 'Review',
            'reviewRating' => [
                '@type' => 'Rating',
                'ratingValue' => $reviewRating,
                'bestRating' => 5,
                'worstRating' => 1
            ],
            'author' => [
                '@type' => 'Person',
                'name' => $comment->user ? $comment->user->name : 'Пользователь'
            ],
            'datePublished' => $comment->created_at->toIso8601String(),
            'reviewBody' => $comment->content
        ];
    }
}

// Добавляем видео, если оно есть
if (isset($recipe->video_url) && !empty($recipe->video_url)) {
    $videoId = null;
    
    // Извлекаем ID видео из YouTube-ссылки
    if (preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $recipe->video_url, $matches)) {
        $videoId = $matches[1];
    }
    
    if ($videoId) {
        $recipeSchema['video'] = [
            '@type' => 'VideoObject',
            'name' => 'Видео: ' . $recipe->title,
            'description' => 'Видео-инструкция по приготовлению: ' . $recipe->title,
            'thumbnailUrl' => 'https://i.ytimg.com/vi/' . $videoId . '/hqdefault.jpg',
            'contentUrl' => $recipe->video_url,
            'embedUrl' => 'https://www.youtube.com/embed/' . $videoId,
            'uploadDate' => $recipe->created_at->toIso8601String()
        ];
    }
}
@endphp

<script type="application/ld+json">
    @json($recipeSchema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT)
</script>

{{-- BreadcrumbList Schema для улучшения навигации --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "BreadcrumbList",
    "itemListElement": [
        {
            "@type": "ListItem",
            "position": 1,
            "name": "Главная",
            "item": "{{ url('/') }}"
        },
        {
            "@type" => "ListItem",
            "position" => 2,
            "name" => "Рецепты",
            "item" => "{{ route('recipes.index') }}"
        },
        @if($recipe->categories->isNotEmpty())
        {
            "@type" => "ListItem",
            "position" => 3,
            "name" => "{{ $recipe->categories->first()->name }}",
            "item" => "{{ route('categories.show', $recipe->categories->first()->slug) }}"
        },
        {
            "@type" => "ListItem",
            "position" => 4,
            "name" => "{{ $recipe->title }}",
            "item" => "{{ route('recipes.show', $recipe->slug) }}"
        }
        @else
        {
            "@type" => "ListItem",
            "position" => 3,
            "name" => "{{ $recipe->title }}",
            "item" => "{{ route('recipes.show', $recipe->slug) }}"
        }
        @endif
    ]
}
</script>

{{-- Организация, предоставляющая рецепт --}}
<script type="application/ld+json">
{
    "@context": "https://schema.org",
    "@type": "Organization",
    "name": "{{ config('app.name') }}",
    "url": "{{ url('/') }}",
    "logo": "{{ asset('images/logo.png') }}",
    "sameAs": [
        "https://vk.com/imedokru",
        "https://t.me/imedokru"
    ]
}
</script>
