@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-8">
            <h1><i class="fas fa-rss text-warning mr-2"></i> Проверка RSS-фидов</h1>
            <p class="text-muted">Результаты проверки корректности всех RSS-фидов сайта</p>
        </div>
        <div class="col-md-4 text-right">
            <a href="{{ route('feed.validate') }}" class="btn btn-primary">
                <i class="fas fa-sync mr-1"></i> Перепроверить
            </a>
            <a href="{{ route('feeds.refresh-yandex') }}" class="btn btn-outline-primary ml-2">
                <i class="fas fa-sync-alt mr-1"></i> Обновить фид Яндекса
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            @if($success)
                <div class="alert alert-success">
                    <i class="fas fa-check-circle mr-1"></i> Все RSS-фиды корректны и готовы к использованию!
                </div>
            @else
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle mr-1"></i> Обнаружены проблемы в некоторых RSS-фидах. Подробности ниже.
                </div>
            @endif
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Результаты проверки RSS-фидов</h3>
                </div>
                <div class="card-body p-0">
                    <table class="table table-striped table-bordered">
                        <thead>
                            <tr>
                                <th>Фид</th>
                                <th>URL</th>
                                <th>Валидный XML</th>
                                <th>Структура RSS</th>
                                <th>Размер контента</th>
                                <th>Статус</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($results as $name => $result)
                                <tr>
                                    <td><strong>{{ ucfirst($name) }}</strong></td>
                                    <td>
                                        <small class="text-muted">{{ $result['url'] }}</small>
                                    </td>
                                    <td class="text-center">
                                        @if($result['valid_xml'])
                                            <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                        @else
                                            <span class="badge badge-danger"><i class="fas fa-times"></i></span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        @if($result['valid_rss'])
                                            <span class="badge badge-success"><i class="fas fa-check"></i></span>
                                        @else
                                            <span class="badge badge-danger"><i class="fas fa-times"></i></span>
                                        @endif
                                    </td>
                                    <td>
                                        {{ number_format($result['length']) }} байт
                                        @if(!$result['has_minimum_length'])
                                            <span class="badge badge-warning ml-1">Слишком мало</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($result['error'])
                                            <span class="badge badge-danger">Ошибка</span>
                                            <small class="d-block text-danger">{{ $result['error'] }}</small>
                                        @else
                                            <span class="badge badge-success">Корректен</span>
                                        @endif
                                    </td>
                                    <td>
                                        <a href="{{ $result['url'] }}" target="_blank" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i> Открыть
                                        </a>
                                        
                                        @if($name === 'yandex')
                                            <a href="{{ route('feeds.refresh-yandex') }}" class="btn btn-sm btn-outline-primary">
                                                <i class="fas fa-sync-alt"></i> Обновить
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h3 class="card-title">Рекомендации по использованию RSS в Яндекс.Вебмастер</h3>
                </div>
                <div class="card-body">
                    <ol>
                        <li>
                            <strong>Сообщите Яндексу про RSS-канал:</strong>
                            <p>Добавьте сайт в <a href="https://webmaster.yandex.ru/" target="_blank">Яндекс.Вебмастер</a> и укажите URL вашего основного RSS-фида.</p>
                        </li>
                        <li>
                            <strong>Укажите URL RSS-канала в robots.txt:</strong>
                            <pre><code>Sitemap: {{ route('rss.recipes') }}</code></pre>
                        </li>
                        <li>
                            <strong>Требования к RSS-каналу:</strong>
                            <ul>
                                <li>Соответствие спецификации RSS 2.0</li>
                                <li>Кодировка UTF-8</li>
                                <li>Поля title, link, description, pubDate должны быть заполнены для каждого item</li>
                                <li>Рекомендуется включать полный текст записи в поля description или yandex:full-text</li>
                            </ul>
                        </li>
                        <li>
                            <strong>Поддержка актуальности:</strong>
                            <p>Обновляйте RSS-фид при добавлении новых рецептов и статей.</p>
                        </li>
                    </ol>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
