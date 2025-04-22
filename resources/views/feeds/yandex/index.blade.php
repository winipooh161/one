@extends('layouts.app')

@section('title', 'Генератор фидов для Яндекса')
@section('description', 'Создание YML фидов для размещения кулинарных услуг в специальных блоках Яндекса')

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-md-12">
            <h1 class="mb-4">Генератор XML-фидов для Яндекса</h1>
            
            <div class="alert alert-info">
                <i class="fas fa-info-circle me-2"></i>
                Эти фиды позволяют привлекать бесплатный целевой трафик из специальных блоков с услугами и предложениями в поиске Яндекса.
                Загрузите ссылку на выбранный фид в формате YML в Яндекс.Вебмастер и дождитесь окончания проверки.
            </div>
            
            <div class="card mb-4 border-0 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">Доступные фиды</h4>
                </div>
                <div class="card-body">
                    <div class="row">
                        <!-- Объединенный фид -->
                        <div class="col-md-12 mb-4">
                            <div class="card h-100 border border-primary">
                                <div class="card-header bg-primary bg-opacity-10">
                                    <h5 class="card-title mb-0 text-primary">Объединенный фид для Яндекса</h5>
                                </div>
                                <div class="card-body">
                                    <p class="card-text">Единый файл, содержащий все типы фидов для Яндекса: мастер-классы, курсы, услуги и мероприятия.</p>
                                    <a href="{{ route('feeds.yandex.combined') }}" target="_blank" class="btn btn-primary">
                                        <i class="fas fa-download me-1"></i> Скачать объединенный XML
                                    </a>
                                </div>
                                <div class="card-footer bg-light">
                                    <small class="text-muted">Прямая ссылка для настройки: <code>{{ route('feeds.yandex.combined') }}</code></small>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Мастер-классы по кулинарии</h5>
                                    <p class="card-text text-muted">Фид в категории "Чем заняться". Представляет рецепты как кулинарные мастер-классы.</p>
                                    <a href="{{ route('feeds.yandex.masterclasses') }}" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Скачать XML
                                    </a>
                                </div>
                                <div class="card-footer bg-light">
                                    <small class="text-muted">Прямая ссылка для настройки: <code>{{ route('feeds.yandex.masterclasses') }}</code></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Кулинарные курсы</h5>
                                    <p class="card-text text-muted">Фид в категории "Образование". Представляет подборки рецептов как кулинарные курсы.</p>
                                    <a href="{{ route('feeds.yandex.education') }}" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Скачать XML
                                    </a>
                                </div>
                                <div class="card-footer bg-light">
                                    <small class="text-muted">Прямая ссылка для настройки: <code>{{ route('feeds.yandex.education') }}</code></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Кулинарные услуги</h5>
                                    <p class="card-text text-muted">Фид в категории "Исполнители". Представляет рецепты как услуги приготовления блюд.</p>
                                    <a href="{{ route('feeds.yandex.services') }}" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Скачать XML
                                    </a>
                                </div>
                                <div class="card-footer bg-light">
                                    <small class="text-muted">Прямая ссылка для настройки: <code>{{ route('feeds.yandex.services') }}</code></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6 mb-4">
                            <div class="card h-100">
                                <div class="card-body">
                                    <h5 class="card-title">Кулинарные активности</h5>
                                    <p class="card-text text-muted">Фид в категории "Чем заняться". Представляет приготовление блюд как развлечение.</p>
                                    <a href="{{ route('feeds.yandex.activities') }}" target="_blank" class="btn btn-outline-primary">
                                        <i class="fas fa-download me-1"></i> Скачать XML
                                    </a>
                                </div>
                                <div class="card-footer bg-light">
                                    <small class="text-muted">Прямая ссылка для настройки: <code>{{ route('feeds.yandex.activities') }}</code></small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-light">
                    <h4 class="mb-0">Инструкция по использованию</h4>
                </div>
                <div class="card-body">
                    <ol>
                        <li>Выберите нужный тип фида из списка выше.</li>
                        <li>Скопируйте URL фида или скачайте его на компьютер.</li>
                        <li>Войдите в <a href="https://webmaster.yandex.ru" target="_blank">Яндекс.Вебмастер</a>.</li>
                        <li>Перейдите в раздел "Фиды" и добавьте новый фид.</li>
                        <li>Укажите URL фида или загрузите скачанный файл.</li>
                        <li>Выберите соответствующий тип фида (Образование, Исполнители, Чем заняться).</li>
                        <li>Дождитесь проверки и индексации фида системой Яндекса.</li>
                        <li>После успешной проверки ваши рецепты начнут отображаться в специальных блоках выдачи.</li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

<?xml version="1.0" encoding="UTF-8"?>
<yml_catalog date="{{ now()->format('Y-m-d H:i') }}">
    <shop>
        <name>Кулинарный сайт im-edok.ru</name>
        <company>im-edok.ru</company>
        <url>https://im-edok.ru</url>
        <currencies>
            <currency id="RUB" rate="1"/>
        </currencies>
        <categories>
            <category id="1">Рецепты</category>
        </categories>
        <offers>
            @foreach($recipes as $recipe)
            <offer id="{{ $recipe->id }}" available="true">
                <url>https://im-edok.ru/recipes/{{ $recipe->slug }}</url>
                <name>{{ $recipe->title }}</name>
                <category id="1">Рецепты</category>
                
                @if($recipe->image)
                <picture>{{ $recipe->image_url }}</picture>
                @endif
                
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
            </offer>
            @endforeach
        </offers>
    </shop>
</yml_catalog>
