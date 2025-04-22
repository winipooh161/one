<?xml version="1.0" encoding="UTF-8"?>
<rss version="2.0" xmlns:content="http://purl.org/rss/1.0/modules/content/" xmlns:atom="http://www.w3.org/2005/Atom">
    <channel>
        <title>{{ config('app.name') }} - Категории рецептов</title>
        <link>{{ route('categories.index') }}</link>
        <description>Все категории рецептов на сайте {{ config('app.name') }}</description>
        <language>ru</language>
        <pubDate>{{ now()->toRssString() }}</pubDate>
        <lastBuildDate>{{ $categories->first()->updated_at->toRssString() }}</lastBuildDate>
        <atom:link href="{{ route('feeds.categories') }}" rel="self" type="application/rss+xml" />
        
        @foreach($categories as $category)
        <item>
            <title>{{ $category->name }}</title>
            <link>{{ route('categories.show', $category->slug) }}</link>
            <guid>{{ route('categories.show', $category->slug) }}</guid>
            <pubDate>{{ $category->created_at->toRssString() }}</pubDate>
            <description>{{ Str::limit(strip_tags($category->description ?? 'Рецепты в категории ' . $category->name), 150) }}</description>
            <content:encoded><![CDATA[
                @if($category->image_path)
                <p><img src="{{ asset($category->image_path) }}" alt="{{ $category->name }}" /></p>
                @endif
                <p>{{ $category->description ?? 'Рецепты в категории ' . $category->name }}</p>
                <p>В этой категории {{ $category->recipes_count }} {{ trans_choice('рецепт|рецепта|рецептов', $category->recipes_count) }}.</p>
                <p>Перейдите по <a href="{{ route('categories.show', $category->slug) }}">ссылке</a>, чтобы увидеть все рецепты в этой категории.</p>
            ]]></content:encoded>
        </item>
        @endforeach
    </channel>
</rss>
