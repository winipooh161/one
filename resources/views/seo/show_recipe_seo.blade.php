{{-- SEO мета-теги для страницы рецепта --}}
<title>{{ $recipe->title }} - пошаговый рецепт с фото | {{ config('app.name') }}</title>

<meta name="description" content="{{ Str::limit(strip_tags($recipe->description), 160, '...') }}">
<meta name="keywords" content="{{ $recipe->title }}, пошаговый рецепт, кулинария, приготовление, {{ $recipe->categories->pluck('name')->implode(', ') }}, Яедок, я едок">

<link rel="canonical" href="{{ route('recipes.show', $recipe->slug) }}">

{{-- Open Graph теги --}}
<meta property="og:title" content="{{ $recipe->title }} - рецепт с фото | {{ config('app.name') }}">
<meta property="og:description" content="{{ Str::limit(strip_tags($recipe->description), 200, '...') }}">
<meta property="og:url" content="{{ route('recipes.show', $recipe->slug) }}">
<meta property="og:type" content="article">
<meta property="og:image" content="{{ asset($recipe->image_url ?: 'images/placeholder.jpg') }}">
<meta property="og:site_name" content="{{ config('app.name') }}">
<meta property="article:published_time" content="{{ $recipe->created_at->toIso8601String() }}">
<meta property="article:modified_time" content="{{ $recipe->updated_at->toIso8601String() }}">
@if($recipe->categories->isNotEmpty())
<meta property="article:section" content="{{ $recipe->categories->first()->name }}">
@endif

{{-- Twitter Card теги --}}
<meta name="twitter:card" content="summary_large_image">
<meta name="twitter:title" content="{{ $recipe->title }}">
<meta name="twitter:description" content="{{ Str::limit(strip_tags($recipe->description), 200, '...') }}">
<meta name="twitter:image" content="{{ asset($recipe->image_url ?: 'images/placeholder.jpg') }}">

{{-- Дополнительные мета-теги --}}
@if($recipe->cooking_time)
<meta name="recipe:cooking_time" content="{{ $recipe->cooking_time }} минут">
@endif
@if($recipe->servings)
<meta name="recipe:yield" content="{{ $recipe->servings }} порций">
@endif
@if($recipe->calories)
<meta name="recipe:calories" content="{{ $recipe->calories }} ккал">
@endif

<!-- Метатеги для деталей рецепта - эти метатеги дублируют функциональность recipe_seo.blade.php -->
@include('seo.recipe_seo', ['recipe' => $recipe, 'seo' => app('App\Services\SeoService')])

<!-- Расширенные метатеги для детальной страницы рецепта -->
@php
// Извлекаем ключевые ингредиенты для метатегов
$keyIngredients = [];
if (isset($recipe->ingredients) && is_object($recipe->ingredients) && $recipe->ingredients->count() > 0) {
    $keyIngredients = $recipe->ingredients->take(5)->pluck('name')->toArray();
} elseif (is_array($recipe->ingredients)) {
    $keyIngredients = array_slice($recipe->ingredients, 0, 5);
} elseif (is_string($recipe->ingredients) && !empty($recipe->ingredients)) {
    $keyIngredients = array_slice(explode("\n", $recipe->ingredients), 0, 5);
}

// Обеспечиваем безопасное преобразование элементов $keyIngredients в строки
if (!empty($keyIngredients)) {
    $keyIngredients = array_map(function($item) {
        if (is_string($item)) {
            return $item;
        } elseif (is_array($item)) {
            return json_encode($item);
        } elseif (is_object($item) && method_exists($item, '__toString')) {
            return (string)$item;
        } elseif (is_scalar($item)) {
            return (string)$item;
        }
        return '';
    }, $keyIngredients);
}

// Добавляем микроданные для статьи с рецептом
$publishedDate = $recipe->created_at->toIso8601String();
$modifiedDate = $recipe->updated_at->toIso8601String();
@endphp

<!-- Дополнительные метатеги для продвинутой индексации -->
<meta name="article:published_time" content="{{ $publishedDate }}">
<meta name="article:modified_time" content="{{ $modifiedDate }}">

@if(!empty($keyIngredients))
<meta name="recipe:ingredient" content="{{ implode(', ', $keyIngredients) }}">
@endif

@if($recipe->calories)
<meta name="nutrition:calories" content="{{ $recipe->calories }} ккал">
@endif

@if($recipe->proteins)
<meta name="nutrition:protein" content="{{ $recipe->proteins }} г">
@endif

@if($recipe->fats)
<meta name="nutrition:fat" content="{{ $recipe->fats }} г">
@endif

@if($recipe->carbs)
<meta name="nutrition:carbohydrate" content="{{ $recipe->carbs }} г">
@endif

@if($recipe->categories->isNotEmpty())
<meta name="article:section" content="{{ $recipe->categories->first()->name }}">
@endif

@if($recipe->categories->isNotEmpty())
@foreach($recipe->categories as $category)
<meta name="article:tag" content="{{ $category->name }}">
@endforeach
@endif

<!-- Дополнительная разметка для социальных сетей -->
<meta property="og:image:alt" content="Фото рецепта: {{ $recipe->title }}">
<meta name="twitter:label1" content="Время приготовления">
<meta name="twitter:data1" content="{{ $recipe->cooking_time ?? 30 }} мин">
<meta name="twitter:label2" content="Калорийность">
<meta name="twitter:data2" content="{{ $recipe->calories ?? 'Не указана' }}">
