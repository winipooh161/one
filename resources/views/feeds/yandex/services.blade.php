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
            <category id="1">Кулинарные услуги</category>
            <category id="2" parentId="1">Приготовление блюд на заказ</category>
            <category id="3" parentId="1">Кейтеринг</category>
            <category id="4" parentId="1">Банкетные блюда</category>
            <category id="5" parentId="1">Домашняя кухня</category>
            <category id="6" parentId="1">Выездное обслуживание</category>
        </categories>
        <offers>
            @foreach($recipes as $recipe)
                @php
                    $categoryId = $recipe->categories->isNotEmpty() 
                        ? ($recipe->categories->first()->id % 5) + 2 
                        : 2;
                    
                    $price = match($categoryId) {
                        2 => rand(500, 1500),
                        3 => rand(1500, 4000),
                        4 => rand(2000, 5000),
                        5 => rand(400, 1200),
                        6 => rand(3000, 7000),
                        default => rand(500, 2000)
                    };
                    
                    $serviceType = match($categoryId) {
                        2 => 'Приготовление на заказ',
                        3 => 'Кейтеринг',
                        4 => 'Банкетное блюдо',
                        5 => 'Домашняя кухня',
                        6 => 'Выездное обслуживание',
                        default => 'Кулинарные услуги'
                    };
                @endphp
                
                <offer id="service-{{ $recipe->id }}" available="true">
                    <name>{{ $serviceType }}: {{ $recipe->title }}</name>
                    <url>{{ route('recipes.show', $recipe->slug) }}</url>
                    <price>{{ $price }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ $categoryId }}</categoryId>
                    <picture>{{ asset($recipe->image_url) }}</picture>
                    <description>
                        <![CDATA[
                        Профессиональное приготовление "{{ $recipe->title }}".
                        
                        {{ strip_tags($recipe->description) }}
                        
                        В стоимость входит:
                        - Подбор свежих продуктов
                        - Приготовление с заботой о деталях
                        - Доставка к указанному времени
                        - Гарантия высокого качества
                        
                        Порций: {{ $recipe->servings ?? '4-6' }}
                        ]]>
                    </description>
                    <store>false</store>
                    <pickup>true</pickup>
                    <delivery>true</delivery>
                    <param name="Тип услуги">{{ $serviceType }}</param>
                    <param name="Порций">{{ $recipe->servings ?? '4-6' }}</param>
                    <param name="Время приготовления">{{ $recipe->cooking_time ?? 60 }} минут</param>
                    <param name="Индивидуальные пожелания">Принимаются</param>
                    <param name="Предварительный заказ">Обязателен</param>
                </offer>
            @endforeach
        </offers>
    </shop>
</yml_catalog>
