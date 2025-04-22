<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('app.name') }} - Новые рецепты</title>
        <link>{{ url('/') }}</link>
        <description>Актуальные и новые рецепты на сайте {{ config('app.name') }}</description>
        <language>ru</language>
        <pubDate>{{ now()->toRssString() }}</pubDate>
        <lastBuildDate>{{ $recipes->first()->created_at->toRssString() }}</lastBuildDate>
        <atom:link href="{{ route('feeds.recipes') }}" rel="self" type="application/rss+xml" />
        
        @foreach($recipes as $recipe)
        <item>
            <title>{{ $recipe->title }}</title>
            <link>{{ route('recipes.show', $recipe->slug) }}</link>
            <guid>{{ route('recipes.show', $recipe->slug) }}</guid>
            <pubDate>{{ $recipe->created_at->toRssString() }}</pubDate>
            <description>{{ Str::limit(strip_tags($recipe->description), 150) }}</description>
            <content:encoded><![CDATA[
                @if($recipe->image_url)
                <p><img src="{{ asset($recipe->image_url) }}" alt="{{ $recipe->title }}" /></p>
                @endif
                <p>{{ $recipe->description }}</p>
                <p><strong>Время приготовления:</strong> {{ $recipe->cooking_time ?? 'Не указано' }} минут</p>
                <p><strong>Категории:</strong> {{ $recipe->categories->pluck('name')->implode(', ') }}</p>
            ]]></content:encoded>
            <author>{{ $recipe->user ? $recipe->user->email : config('mail.from.address') }} ({{ $recipe->user ? $recipe->user->name : config('app.name') }})</author>
            @if($recipe->categories->isNotEmpty())
            <category>{{ $recipe->categories->first()->name }}</category>
            @endif
        </item>
        @endforeach
    </channel>
</rss>
