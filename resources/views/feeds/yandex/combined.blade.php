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
            <!-- Мастер-классы -->
            <category id="1">Кулинарные мастер-классы</category>
            <category id="2" parentId="1">Выпечка</category>
            <category id="3" parentId="1">Основные блюда</category>
            <category id="4" parentId="1">Салаты и закуски</category>
            <category id="5" parentId="1">Супы</category>
            <category id="6" parentId="1">Десерты</category>
            
            <!-- Образование -->
            <category id="100">Кулинарные курсы</category>
            @foreach($categories as $index => $category)
                <category id="{{ 100 + $index + 1 }}" parentId="100">{{ $category->name }}</category>
            @endforeach
            
            <!-- Услуги -->
            <category id="200">Кулинарные услуги</category>
            <category id="201" parentId="200">Приготовление блюд на заказ</category>
            <category id="202" parentId="200">Кейтеринг</category>
            <category id="203" parentId="200">Банкетные блюда</category>
            <category id="204" parentId="200">Домашняя кухня</category>
            <category id="205" parentId="200">Выездное обслуживание</category>
            
            <!-- Активности -->
            <category id="300">Кулинарные мероприятия</category>
            <category id="301" parentId="300">Кулинарные мастер-классы</category>
            <category id="302" parentId="300">Дегустации</category>
            <category id="303" parentId="300">Семейные мастер-классы</category>
            <category id="304" parentId="300">Корпоративные мероприятия</category>
            <category id="305" parentId="300">Детские праздники</category>
        </categories>
        
        <offers>
            <!-- МАСТЕР-КЛАССЫ -->
            @foreach($recipes->take(30) as $recipe)
                <offer id="masterclass-{{ $recipe->id }}" available="true">
                    <name>Мастер-класс: {{ $recipe->title }}</name>
                    <url>{{ route('recipes.show', $recipe->slug) }}</url>
                    <price>{{ rand(500, 2000) }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ ($recipe->categories->isNotEmpty() ? ($recipe->categories->first()->id % 5) + 2 : 2) }}</categoryId>
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
                        <date-time>{{ $schedules['weekday']->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                        <date-time>{{ $schedules['weekday']->copy()->setTime(18, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                        <date-time>{{ $schedules['weekend']->copy()->setTime(12, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                        <date-time>{{ $schedules['weekend']->copy()->setTime(18, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                    </schedule>
                </offer>
            @endforeach
            
            <!-- КУРСЫ (ОБРАЗОВАНИЕ) -->
            @foreach($categories->take(10) as $index => $category)
                <offer id="course-{{ $category->id }}" available="true">
                    <name>Курс "{{ $category->name }}: от начинающего до профи"</name>
                    <url>{{ route('categories.show', $category->slug) }}</url>
                    <price>{{ rand(2000, 15000) }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ 100 + $index + 1 }}</categoryId>
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
            
            <!-- МИНИ-КУРСЫ (ОБРАЗОВАНИЕ) -->
            @foreach($recipes->take(20) as $recipe)
                <offer id="minicourse-{{ $recipe->id }}" available="true">
                    <name>Мини-курс: Как приготовить "{{ $recipe->title }}"</name>
                    <url>{{ route('recipes.show', $recipe->slug) }}</url>
                    <price>{{ rand(1000, 3000) }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ 100 + ($recipe->categories->isNotEmpty() ? min(5, ($recipe->categories->first()->id % 5) + 1) : 1) }}</categoryId>
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
            
            <!-- УСЛУГИ -->
            @foreach($recipes->take(30) as $recipe)
                @php
                    $serviceTypes = [
                        ['id' => 201, 'name' => 'Приготовление на заказ', 'price' => rand(500, 1500)],
                        ['id' => 202, 'name' => 'Кейтеринг', 'price' => rand(1500, 4000)],
                        ['id' => 203, 'name' => 'Банкетное блюдо', 'price' => rand(2000, 5000)],
                        ['id' => 204, 'name' => 'Домашняя кухня', 'price' => rand(400, 1200)],
                        ['id' => 205, 'name' => 'Выездное обслуживание', 'price' => rand(3000, 7000)]
                    ];
                    
                    $serviceType = $serviceTypes[$recipe->id % count($serviceTypes)];
                @endphp
                
                <offer id="service-{{ $recipe->id }}" available="true">
                    <name>{{ $serviceType['name'] }}: {{ $recipe->title }}</name>
                    <url>{{ route('recipes.show', $recipe->slug) }}</url>
                    <price>{{ $serviceType['price'] }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ $serviceType['id'] }}</categoryId>
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
                    <param name="Тип услуги">{{ $serviceType['name'] }}</param>
                    <param name="Порций">{{ $recipe->servings ?? '4-6' }}</param>
                    <param name="Время приготовления">{{ $recipe->cooking_time ?? 60 }} минут</param>
                    <param name="Индивидуальные пожелания">Принимаются</param>
                    <param name="Предварительный заказ">Обязателен</param>
                </offer>
            @endforeach
            
            <!-- АКТИВНОСТИ -->
            @foreach($recipes->take(20) as $recipe)
                @php
                    $activityTypes = [
                        ['id' => 301, 'name' => 'Кулинарный мастер-класс', 'price' => rand(500, 1500), 'age' => '12+'],
                        ['id' => 302, 'name' => 'Дегустация', 'price' => rand(1200, 3000), 'age' => '18+'],
                        ['id' => 303, 'name' => 'Семейный мастер-класс', 'price' => rand(2000, 4000), 'age' => '6+'],
                        ['id' => 304, 'name' => 'Корпоративное мероприятие', 'price' => rand(5000, 10000), 'age' => '18+'],
                        ['id' => 305, 'name' => 'Детский праздник', 'price' => rand(3000, 7000), 'age' => '6+']
                    ];
                    
                    $activityType = $activityTypes[$recipe->id % count($activityTypes)];
                @endphp
                
                <offer id="activity-{{ $recipe->id }}" available="true">
                    <name>{{ $activityType['name'] }}: {{ $recipe->title }}</name>
                    <url>{{ route('recipes.show', $recipe->slug) }}</url>
                    <price>{{ $activityType['price'] }}</price>
                    <currencyId>RUR</currencyId>
                    <categoryId>{{ $activityType['id'] }}</categoryId>
                    <picture>{{ asset($recipe->image_url) }}</picture>
                    <description>
                        <![CDATA[
                        {{ $activityType['name'] }} по приготовлению "{{ $recipe->title }}".
                        
                        {{ strip_tags($recipe->description) }}
                        
                        Участники мероприятия:
                        - Научатся готовить популярное блюдо под руководством шеф-повара
                        - Узнают секреты правильного подбора ингредиентов
                        - Освоят профессиональные техники приготовления
                        - Получат удовольствие от процесса и результата
                        - Заберут приготовленное блюдо с собой
                        
                        Продолжительность: {{ $recipe->cooking_time ? $recipe->cooking_time + 30 : 90 }} минут
                        Все необходимые материалы и инструменты предоставляются.
                        ]]>
                    </description>
                    <store>false</store>
                    <pickup>true</pickup>
                    <delivery>false</delivery>
                    <param name="Тип мероприятия">{{ $activityType['name'] }}</param>
                    <param name="Продолжительность">{{ $recipe->cooking_time ? $recipe->cooking_time + 30 : 90 }} минут</param>
                    <param name="Возрастное ограничение">{{ $activityType['age'] }}</param>
                    <param name="Количество участников">до {{ rand(5, 15) }} человек</param>
                    <param name="Материалы включены">Да</param>
                    <schedule>
                        <date-time>{{ $schedules['weekend']->copy()->setTime(10, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                        <date-time>{{ $schedules['weekend']->copy()->setTime(15, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                        <date-time>{{ $schedules['nextweek']->copy()->setTime(18, 0)->format('Y-m-d\TH:i:s') }}</date-time>
                    </schedule>
                </offer>
            @endforeach
        </offers>
    </shop>
</yml_catalog>

<?xml version="1.0" encoding="UTF-8"?>
<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">
    @foreach($recipes as $recipe)
    <url>
        <loc>https://im-edok.ru/recipes/{{ $recipe->slug }}</loc>
        <lastmod>{{ $recipe->updated_at->format('Y-m-d\TH:i:sP') }}</lastmod>
        <changefreq>weekly</changefreq>
        <priority>0.8</priority>
        <recipe>
            <recipe-name>{{ $recipe->title }}</recipe-name>
            
            @if($recipe->servings)
            <recipe-yield>{{ $recipe->servings }} порции</recipe-yield>
            @endif
            
            @if($recipe->cooking_time)
            <recipe-time>{{ $recipe->cooking_time }} минут</recipe-time>
            @endif
            
            @foreach($recipe->ingredients as $ingredient)
            <recipe-ingredient>{{ $ingredient->quantity }} {{ $ingredient->name }}</recipe-ingredient>
            @endforeach
            
            <recipe-instructions>{{ strip_tags($recipe->instructions) }}</recipe-instructions>
        </recipe>
    </url>
    @endforeach
</urlset>
