<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<rss version="2.0" 
    xmlns:content="http://purl.org/rss/1.0/modules/content/"
    xmlns:dc="http://purl.org/dc/elements/1.1/"
    xmlns:media="http://search.yahoo.com/mrss/"
    xmlns:atom="http://www.w3.org/2005/Atom"
    xmlns:georss="http://www.georss.org/georss">
    <channel>
        <title>{{ config('app.name') }} - Кулинарные рецепты</title>
        <link>{{ url('/') }}</link>
        <language>ru</language>
        
        @foreach($recipes as $recipe)
        <item>
            <title>{{ $recipe->title }}</title>
            <link>{{ route('recipes.show', $recipe->slug) }}</link>
            <pdalink>{{ route('recipes.show', $recipe->slug) }}</pdalink>
            <guid>{{ $recipe->id }}-{{ $recipe->slug }}</guid>
            <pubDate>{{ $recipe->published_at ? $recipe->published_at->format('D, d M Y H:i:s O') : $recipe->created_at->format('D, d M Y H:i:s O') }}</pubDate>
            <media:rating scheme="urn:simple">nonadult</media:rating>
            
            <!-- Настройки для публикации в Дзене -->
            <category>format-article</category>
            <category>index</category>
            <category>comment-all</category>
            
            <!-- Обложка статьи -->
            @if($recipe->image_url)
            <enclosure url="{{ url($recipe->image_url) }}" type="image/jpeg"/>
            @endif
            
            <!-- Описание для карточки в ленте -->
            <description><![CDATA[{{ Str::limit(strip_tags($recipe->description), 200) }}]]></description>
            
            <!-- Полный текст статьи -->
            <content:encoded><![CDATA[
                <h1>{{ $recipe->title }}</h1>
                
                @if($recipe->image_url)
                <figure>
                    <img src="{{ url($recipe->image_url) }}">
                    <figcaption>{{ $recipe->title }} - пошаговый рецепт приготовления</figcaption>
                </figure>
                @endif
                
                <p>{{ $recipe->description }}</p>
                
                @if($recipe->cooking_time || $recipe->servings || $recipe->calories)
                <p>
                    @if($recipe->cooking_time)<b>Время приготовления:</b> {{ $recipe->cooking_time }} мин.<br>@endif
                    @if($recipe->servings)<b>Количество порций:</b> {{ $recipe->servings }}<br>@endif
                    @if($recipe->calories)<b>Калорийность:</b> {{ $recipe->calories }} ккал@endif
                </p>
                @endif
                
                <h2 id="ingredients">Ингредиенты</h2>
                <ul>
                    @foreach(explode("\n", $recipe->ingredients) as $ingredient)
                        @if(trim($ingredient))
                        <li>{{ trim($ingredient) }}</li>
                        @endif
                    @endforeach
                </ul>
                
                <h2 id="cooking">Приготовление</h2>
                @php 
                    $instructions = explode("\n", $recipe->instructions);
                    $step = 1;
                @endphp
                
                @foreach($instructions as $instruction)
                    @if(trim($instruction))
                    <p><b>Шаг {{ $step }}.</b> {{ trim($instruction) }}</p>
                    @php $step++; @endphp
                    @endif
                @endforeach
                
                @if($recipe->additional_notes)
                <h3 id="notes">Советы к рецепту</h3>
                <p>{{ $recipe->additional_notes }}</p>
                @endif
                
                @if($recipe->categories->isNotEmpty())
                <p>
                    <b>Категории:</b> 
                    @foreach($recipe->categories as $category)
                    <a href="{{ route('categories.show', $category->slug) }}">{{ $category->name }}</a>{{ !$loop->last ? ', ' : '' }}
                    @endforeach
                </p>
                @endif
                
                <p>
                    <i>Приятного аппетита!</i>
                </p>
            ]]></content:encoded>
        </item>
        @endforeach
    </channel>
</rss>
