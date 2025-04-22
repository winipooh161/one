<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    @foreach($news as $item)
    <url>
        <loc>{{ route('news.show', $item->slug) }}</loc>
        <lastmod>{{ $item->updated_at->toAtomString() }}</lastmod>
        <changefreq>daily</changefreq>
        <priority>0.8</priority>
        
        @if($item->image_url)
        <image:image>
            <image:loc>{{ asset('uploads/' . $item->image_url) }}</image:loc>
            <image:title>{{ $item->title }}</image:title>
        </image:image>
        @endif
    </url>
    @endforeach
</urlset>
