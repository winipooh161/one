<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    <sitemap>
        <loc>{{ url('sitemap-recipes.xml') }}</loc>
        <lastmod>{{ $now }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ url('sitemap-categories.xml') }}</loc>
        <lastmod>{{ $now }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ url('sitemap-static.xml') }}</loc>
        <lastmod>{{ $now }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ url('sitemap-pagination.xml') }}</loc>
        <lastmod>{{ $now }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ url('sitemap-users.xml') }}</loc>
        <lastmod>{{ $now }}</lastmod>
    </sitemap>
    <sitemap>
        <loc>{{ url('sitemap-news.xml') }}</loc>
        <lastmod>{{ $now }}</lastmod>
    </sitemap>
</sitemapindex>
