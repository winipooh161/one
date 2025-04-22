<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9"
        xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">
    @foreach($recipes as $recipe)
    <url>
        <loc>{{ route('recipes.show', $recipe->slug) }}</loc>
        <lastmod>{{ $recipe->updated_at->toAtomString() }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        
        @if($recipe->image_url)
        <image:image>
            <image:loc>{{ asset('uploads/' . $recipe->image_url) }}</image:loc>
            <image:title>{{ $recipe->title }}</image:title>
        </image:image>
        @endif
    </url>
    @endforeach
</urlset>
