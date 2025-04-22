<?php echo '<?xml version="1.0" encoding="UTF-8"?>'; ?>
<yml_catalog date="{{ now()->format('Y-m-d\TH:i:s') }}">
    <shop>
        <name>{{ config('app.name') }}</name>
        <company>{{ config('app.name') }}</company>
        <url>{{ url('/') }}</url>
        <currencies>
            <currency id="RUR" rate="1"/>
        </currencies>
        <categories>
            <category id="1">Кулинарные мероприятия</category>
            <category id="2" parentId="1">Кулинарные мастер-классы</category>
            <category id="3" parentId="1">Дегустации</category>
            <category id="4" parentId="1">Семейные мастер-классы</category>
            <category id="5" parentId="1">Корпоративные мероприятия</category>
            <category id="6" parentId="1">Детские праздники</category>
        </categories>
        <offers>
            @foreach($recipes as $recipe)
                @php
                    $categoryId = $recipe->categories->isNotEmpty() ? $recipe->categories->first()->id : 2;
                    
                    $price = match($categoryId) {
                        1 => 3000,
                        2 => 2500,
                        3 => 1500,
                        4 => 2000,
                        5 => 5000,
                        6 => 1800,
                        default => 2000
                    };
                    
                    $activityType = match($categoryId) {
                        1 => 'Кулинарное мероприятие',
                        2 => 'Кулинарный мастер-класс',
                        3 => 'Дегустация блюд',
                        4 => 'Семейный мастер-класс',
                        5 => 'Корпоративное мероприятие',
                        6 => 'Детский праздник',
                        default => 'Кулинарный мастер-класс'
                    };
                    
                    $nextWeek = now()->addWeek();
                    $saturday = $nextWeek->startOfWeek()->addDays(5);
                    $sunday = $nextWeek->startOfWeek()->addDays(6);
                @endphp
                
                <offer id="activity-{{ $recipe->id }}" available="true">
                    <name>{{ $activityType }}: {{ $recipe->title }}</name>
                    <description>
                        <![CDATA[{{ $recipe->description }}]]>
                    </description>
                    <picture>{{ $recipe->image_url }}</picture>
                    <url>{{ route('recipes.show', $recipe->slug) }}</url>
                    <price>{{ $price }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ $categoryId }}</categoryId>
                    <schedule>
                        <dateTime>{{ $saturday->format('Y-m-d') }} 12:00-14:00</dateTime>
                        <dateTime>{{ $sunday->format('Y-m-d') }} 14:00-16:00</dateTime>
                    </schedule>
                    <delivery>false</delivery>
                    <pickup>true</pickup>
                    <store>true</store>
                </offer>
            @endforeach
        </offers>
    </shop>
</yml_catalog>
