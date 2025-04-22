<!doctype html>
<html amp lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <script async src="https://cdn.ampproject.org/v0.js"></script>
    <script async custom-element="amp-social-share" src="https://cdn.ampproject.org/v0/amp-social-share-0.1.js"></script>
    <script async custom-element="amp-analytics" src="https://cdn.ampproject.org/v0/amp-analytics-0.1.js"></script>
    
    <title>{{ $metaTitle }}</title>
    <meta name="description" content="{{ $metaDescription }}">
    <link rel="canonical" href="{{ route('recipes.show', $recipe->slug) }}">
    
    <meta name="viewport" content="width=device-width,minimum-scale=1,initial-scale=1">
    
    <style amp-boilerplate>body{-webkit-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-moz-animation:-amp-start 8s steps(1,end) 0s 1 normal both;-ms-animation:-amp-start 8s steps(1,end) 0s 1 normal both;animation:-amp-start 8s steps(1,end) 0s 1 normal both}@-webkit-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-moz-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-ms-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@-o-keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}@keyframes -amp-start{from{visibility:hidden}to{visibility:visible}}</style><noscript><style amp-boilerplate>body{-webkit-animation:none;-moz-animation:none;-ms-animation:none;animation:none}</style></noscript>
    
    <style amp-custom>
        /* Базовые стили */
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; line-height: 1.6; padding: 0; margin: 0; color: #333; }
        header { background: #fff; box-shadow: 0 2px 4px rgba(0,0,0,.1); padding: 0.5rem 1rem; }
        .container { max-width: 800px; margin: 0 auto; padding: 0 15px; }
        h1 { font-size: 1.8rem; margin-top: 1.5rem; }
        h2 { font-size: 1.4rem; margin-top: 1.5rem; }
        img { max-width: 100%; height: auto; }
        .logo { display: flex; align-items: center; }
        .logo-icon { margin-right: 0.5rem; color: #d9534f; }
        .recipe-meta { display: flex; flex-wrap: wrap; font-size: 0.9rem; color: #666; margin: 1rem 0; }
        .recipe-meta div { margin-right: 1rem; display: flex; align-items: center; }
        .recipe-meta svg { width: 1rem; height: 1rem; margin-right: 0.3rem; }
        .recipe-description { margin: 1rem 0; font-style: italic; }
        .recipe-ingredients { background: #f9f9f9; padding: 1rem; border-radius: 0.25rem; }
        .recipe-ingredients ul { padding-left: 1.2rem; }
        .recipe-instructions { counter-reset: step; }
        .recipe-instructions li { margin-bottom: 0.8rem; }
        .recipe-instructions li::before { counter-increment: step; content: counter(step) ". "; font-weight: bold; }
        .recipe-footer { margin-top: 2rem; padding-top: 1rem; border-top: 1px solid #eee; }
        .social-share { display: flex; margin-top: 1rem; }
        .social-share .share-btn { margin-right: 0.5rem; }
    </style>
</head>
<body>
    <header>
        <div class="container">
            <div class="logo">
                <span class="logo-icon">📝</span>
                <a href="{{ url('/') }}">{{ config('app.name', 'Laravel') }}</a>
            </div>
        </div>
    </header>

    <main class="container">
        <article class="recipe">
            <h1>{{ $recipe->title }}</h1>
            
            <amp-img src="{{ asset($recipe->image_url) }}" 
                     width="800" 
                     height="533" 
                     layout="responsive" 
                     alt="{{ $recipe->title }}"></amp-img>
            
            <div class="recipe-meta">
                <div>
                    <svg viewBox="0 0 24 24" width="24" height="24"><path d="M12 2C6.5 2 2 6.5 2 12s4.5 10 10 10 10-4.5 10-10S17.5 2 12 2zm0 18c-4.4 0-8-3.6-8-8s3.6-8 8-8 8 3.6 8 8-3.6 8-8 8zm-1-13h2v6h-2zm0 8h2v2h-2z"/></svg>
                    {{ $recipe->cooking_time }} мин.
                </div>
                <div>
                    <svg viewBox="0 0 24 24" width="24" height="24"><path d="M12 6c1.1 0 2-.9 2-2s-.9-2-2-2-2 .9-2 2 .9 2 2 2zm0 2c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2zm0 6c-1.1 0-2 .9-2 2s.9 2 2 2 2-.9 2-2-.9-2-2-2z"/></svg>
                    {{ $recipe->servings }} порц.
                </div>
                <div>
                    <svg viewBox="0 0 24 24" width="24" height="24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                    {{ $recipe->views }} просмотров
                </div>
                @if($recipe->categories->count() > 0)
                <div>
                    <svg viewBox="0 0 24 24" width="24" height="24"><path d="M17.63 5.84C17.27 5.33 16.67 5 16 5H5c-1.1 0-2 .9-2 2v10c0 1.1.9 2 2 2h11c.67 0 1.27-.33 1.63-.84L22 12l-4.37-6.16z"/></svg>
                    {{ $recipe->categories->first()->name }}
                </div>
                @endif
            </div>
            
            <div class="recipe-description">
                {{ $recipe->description }}
            </div>
            
            <div class="recipe-ingredients">
                <h2>Ингредиенты</h2>
                <ul>
                    @foreach($recipe->ingredients as $ingredient)
                    <li>{{ $ingredient }}</li>
                    @endforeach
                </ul>
            </div>
            
            <div class="recipe-instructions">
                <h2>Приготовление</h2>
                <ol>
                    @if(is_array($recipe->instructions))
                        @foreach($recipe->instructions as $step)
                            <li>{{ $step }}</li>
                        @endforeach
                    @else
                        @foreach(explode("\n", $recipe->instructions) as $step)
                            @if(trim($step))
                                <li>{{ trim($step) }}</li>
                            @endif
                        @endforeach
                    @endif
                </ol>
            </div>
            
            <div class="recipe-footer">
                <p>Автор: {{ $recipe->user->name ?? config('app.name') }}</p>
                <p>Опубликовано: {{ $recipe->created_at->format('d.m.Y') }}</p>
                
                <div class="social-share">
                    <amp-social-share type="facebook" width="40" height="40"></amp-social-share>
                    <amp-social-share type="twitter" width="40" height="40"></amp-social-share>
                    <amp-social-share type="pinterest" width="40" height="40"></amp-social-share>
                    <amp-social-share type="email" width="40" height="40"></amp-social-share>
                </div>
            </div>
        </article>
    </main>
    
    <footer class="container" style="margin-top: 2rem; padding: 1rem 0; border-top: 1px solid #eee; text-align: center; font-size: 0.9rem;">
        <p>&copy; {{ date('Y') }} {{ config('app.name') }}. Все права защищены.</p>
        <p>
            <a href="{{ route('recipes.show', $recipe->slug) }}">Посмотреть полную версию</a>
        </p>
    </footer>
    
    @if(config('services.analytics.tracking_id'))
    <amp-analytics type="gtag" data-credentials="include">
        <script type="application/json">
        {
            "vars" : {
                "gtag_id": "{{ config('services.analytics.tracking_id') }}",
                "config": {
                    "{{ config('services.analytics.tracking_id') }}": {
                        "groups": "default"
                    }
                }
            }
        }
        </script>
    </amp-analytics>
    @endif
</body>
</html>
