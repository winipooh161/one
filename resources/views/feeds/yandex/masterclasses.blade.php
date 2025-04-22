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
            <category id="1">Кулинарные мастер-классы</category>
            <category id="2" parentId="1">Выпечка</category>
            <category id="3" parentId="1">Основные блюда</category>
            <category id="4" parentId="1">Салаты и закуски</category>
            <category id="5" parentId="1">Супы</category>
            <category id="6" parentId="1">Десерты</category>
        </categories>
        <offers>
            @foreach($recipes as $recipe)
                <offer id="{{ $recipe->id }}" available="true">
                    <name>Мастер-класс: {{ $recipe->title }}</name>
                    <url>{{ route('recipes.show', $recipe->slug) }}</url>
                    <price>{{ rand(500, 2000) }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ $recipe->categories->isNotEmpty() ? ($recipe->categories->first()->id % 5) + 2 : 1 }}</categoryId>
                    <picture>{{ asset($recipe->image_url) }}</picture>
                    <description>
                        <![CDATA[
                        Кулинарный мастер-класс по приготовлению "{{ $recipe->title }}". 
                        {{ strip_tags($recipe->description) }}
                        
                        Вы научитесь готовить это блюдо с нуля под руководством опытного шеф-повара. 
                        Все ингредиенты включены в стоимость. 
                        Продолжительность: {{ $recipe->cooking_time ?? 60 }} минут.
                        ]]>
                    </description>
                    <store>false</store>
                    <pickup>true</pickup>
                    <delivery>false</delivery>
                    <param name="Тип мероприятия">Мастер-класс</param>
                    <param name="Продолжительность">{{ $recipe->cooking_time ?? 60 }} мин</param>
                    <param name="Сложность">{{ $recipe->difficulty_text ?? 'Средняя' }}</param>
                    <param name="Размер группы">до 10 человек</param>
                    <param name="Все материалы включены">Да</param>
                    <schedule>
                        <date-time>{{ now()->addDay()->setTime(12, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                        <date-time>{{ now()->addDay()->setTime(18, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                        <date-time>{{ now()->addDays(2)->setTime(12, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                        <date-time>{{ now()->addDays(2)->setTime(18, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                    </schedule>
                </offer>
            @endforeach
        </offers>
    </shop>
</yml_catalog>
