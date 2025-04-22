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
            <category id="1">Кулинарные курсы</category>
            @foreach($categories as $index => $category)
                <category id="{{ $index + 2 }}" parentId="1">{{ $category->name }}</category>
            @endforeach
        </categories>
        <offers>
            @foreach($categories->take(20) as $index => $category)
                <offer id="course-{{ $category->id }}" available="true">
                    <name>Курс "{{ $category->name }}: от начинающего до профи"</name>
                    <url>{{ route('categories.show', $category->slug) }}</url>
                    <price>{{ rand(2000, 15000) }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ $index + 2 }}</categoryId>
                    <picture>{{ $category->image ?? asset('images/categories/default.jpg') }}</picture>
                    <description>
                        <![CDATA[
                        Профессиональный кулинарный курс по направлению "{{ $category->name }}".
                        
                        В программе курса:
                        - Основные техники и секреты приготовления
                        - {{ $category->recipes_count }} практических занятий с пошаговыми рецептами
                        - Подбор продуктов и ингредиентов
                        - Секреты шеф-поваров
                        - Сертификат по окончании курса
                        
                        Продолжительность: 4 недели. Занятия 2 раза в неделю по 3 часа.
                        Все ингредиенты включены в стоимость.
                        ]]>
                    </description>
                    <store>false</store>
                    <pickup>true</pickup>
                    <delivery>false</delivery>
                    <param name="Тип обучения">Кулинарные курсы</param>
                    <param name="Продолжительность">4 недели</param>
                    <param name="Интенсивность">2 раза в неделю</param>
                    <param name="Количество уроков">8</param>
                    <param name="Сертификат">Да</param>
                    <param name="Формат">Офлайн</param>
                </offer>
            @endforeach
            
            @foreach($recipes->take(50) as $recipe)
                <offer id="recipe-{{ $recipe->id }}" available="true">
                    <name>Мини-курс: Как приготовить "{{ $recipe->title }}"</name>
                    <url>{{ route('recipes.show', $recipe->slug) }}</url>
                    <price>{{ rand(1000, 3000) }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ $recipe->categories->isNotEmpty() ? ($recipe->categories->first()->id % count($categories)) + 2 : 2 }}</categoryId>
                    <picture>{{ asset($recipe->image_url) }}</picture>
                    <description>
                        <![CDATA[
                        Интенсивный мини-курс по приготовлению "{{ $recipe->title }}".
                        
                        {{ strip_tags($recipe->description) }}
                        
                        В программе:
                        - Особенности выбора ингредиентов
                        - Пошаговый мастер-класс приготовления
                        - Секреты идеальной подачи блюда
                        - Тонкости и лайфхаки от шеф-повара
                        
                        Продолжительность: {{ $recipe->cooking_time ? $recipe->cooking_time * 3 : 90 }} минут.
                        ]]>
                    </description>
                    <store>false</store>
                    <pickup>true</pickup>
                    <delivery>false</delivery>
                    <param name="Тип обучения">Мини-курс</param>
                    <param name="Продолжительность">{{ $recipe->cooking_time ? $recipe->cooking_time * 3 : 90 }} минут</param>
                    <param name="Сложность">{{ $recipe->difficulty_text ?? 'Средняя' }}</param>
                    <param name="Формат">Онлайн и офлайн</param>
                </offer>
            @endforeach
        </offers>
    </shop>
</yml_catalog>
