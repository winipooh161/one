@php echo '<?xml version="1.0" encoding="UTF-8"?>'; @endphp
<!DOCTYPE yml_catalog SYSTEM "shops.dtd">
<yml_catalog date="{{ now()->format('Y-m-d H:i') }}">
    <shop>
        <name>{{ config('app.name') }}</name>
        <company>{{ config('app.name') }}</company>
        <url>{{ config('app.url') }}</url>
        <currencies>
            <currency id="RUB" rate="1"/>
        </currencies>
        
        <!-- Категории -->
        <categories>
            @foreach($categories as $category)
            <category id="{{ $category->id }}">{{ $category->name }}</category>
            @endforeach
        </categories>
        
        <!-- Предложения (рецепты) -->
        <offers>
            @foreach($recipes as $recipe)
            <offer id="{{ $recipe->id }}" available="true" type="vendor.model">
                <name>{{ $recipe->title }}</name>
                <url>{{ route('recipes.show', $recipe->slug) }}</url>
                <price>0</price>
                <currencyId>RUB</currencyId>
                <categoryId>{{ $recipe->categories->first()->id ?? '' }}</categoryId>
                @if($recipe->image_url)
                <picture>{{ asset($recipe->image_url) }}</picture>
                @endif
                <description><![CDATA[{{ strip_tags($recipe->description) }}]]></description>
                <vendor>{{ config('app.name') }}</vendor>
                <model>recipe-{{ $recipe->id }}</model>
                
                <!-- Параметры рецепта -->
                <param name="cooking_time">{{ $recipe->cooking_time ?? '' }} мин</param>
                <param name="servings">{{ $recipe->servings ?? '' }}</param>
                @if($recipe->calories)
                <param name="calories">{{ $recipe->calories }} ккал</param>
                @endif
                @if($recipe->proteins)
                <param name="proteins">{{ $recipe->proteins }} г</param>
                @endif
                @if($recipe->fats)
                <param name="fats">{{ $recipe->fats }} г</param>
                @endif
                @if($recipe->carbs)
                <param name="carbs">{{ $recipe->carbs }} г</param>
                @endif
            </offer>
            @endforeach
        </offers>
    </shop>
</yml_catalog>
