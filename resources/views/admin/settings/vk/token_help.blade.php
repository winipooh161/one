@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Инструкция по получению токена ВКонтакте</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.settings.vk') }}" class="btn btn-sm btn-default">
                            <i class="fas fa-arrow-left"></i> Назад к настройкам
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-danger">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        <strong>Внимание! Обнаружены проблемы с доступом к API ВКонтакте.</strong>
                        <p class="mb-0">В связи с возможной блокировкой сервиса OAuth ВКонтакте, рекомендуем использовать альтернативный метод получения токена через VK Host.</p>
                    </div>
                    
                    <h5>Способ 1: Получение токена через VK Host (рекомендуется)</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <ol>
                                <li>Перейдите на сайт <a href="https://vkhost.github.io/" target="_blank">https://vkhost.github.io/</a></li>
                                <li>Выберите вкладку "Настройки" (Settings)</li>
                                <li>В разделе "Права доступа" включите следующие права:
                                    <ul>
                                        <li>wall (для публикации на стене)</li>
                                        <li>photos (для загрузки фото)</li>
                                        <li>groups (для доступа к группам)</li>
                                        <li>offline (для бессрочного токена)</li>
                                    </ul>
                                </li>
                                <li>Нажмите кнопку "Получить токен" (Get token)</li>
                                <li>Авторизуйтесь в ВКонтакте, если потребуется</li>
                                <li>Из адресной строки скопируйте часть URL после <code>access_token=</code> и до символа <code>&</code></li>
                            </ol>
                            <div class="alert alert-info">
                                <i class="fas fa-lightbulb mr-1"></i> Пример части URL с токеном:<br>
                                <code>https://oauth.vk.com/blank.html#access_token=<strong>vk1.a.AbCdEfGhIjKl...</strong>&expires_in=0&user_id=123456</code>
                            </div>
                        </div>
                    </div>
                    
                    <h5>Способ 2: Прямая авторизация по API (если первый способ не работает)</h5>
                    <div class="card mb-4">
                        <div class="card-body">
                            <ol>
                                <li>Откройте в новой вкладке следующую ссылку:
                                    <div class="input-group mb-3">
                                        <input type="text" class="form-control" value="https://oauth.vk.com/authorize?client_id={{ env('VK_CLIENT_ID') }}&display=page&redirect_uri=https://oauth.vk.com/blank.html&scope=wall,photos,groups,offline&response_type=token&v=5.131" id="direct-auth-url" readonly>
                                        <div class="input-group-append">
                                            <button class="btn btn-outline-secondary copy-url-btn" type="button">
                                                <i class="fas fa-copy"></i> Копировать
                                            </button>
                                        </div>
                                    </div>
                                </li>
                                <li>Авторизуйтесь в ВКонтакте и предоставьте запрашиваемые разрешения</li>
                                <li>После авторизации вы будете перенаправлены на пустую страницу</li>
                                <li>Скопируйте часть URL из адресной строки после <code>access_token=</code> и до <code>&expires_in</code></li>
                            </ol>
                        </div>
                    </div>
                    
                    <div class="card mt-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Ввод полученного токена</h5>
                        </div>
                        <div class="card-body">
                            <form action="{{ route('admin.settings.vk.update') }}" method="POST">
                                @csrf
                                <div class="form-group">
                                    <label for="vk_access_token">Новый токен доступа</label>
                                    <input type="text" id="vk_access_token" name="vk_access_token" class="form-control" 
                                           placeholder="Вставьте полученный токен">
                                </div>
                                <div class="form-group">
                                    <label for="vk_owner_id">ID группы (без знака минус)</label>
                                    <input type="text" id="vk_owner_id" name="vk_owner_id" class="form-control" 
                                           value="{{ old('vk_owner_id', env('VK_OWNER_ID') ? ltrim(env('VK_OWNER_ID'), '-') : '') }}" 
                                           placeholder="Например: 226845372">
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-save mr-1"></i> Сохранить токен
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Копирование URL в буфер обмена
    $(document).ready(function() {
        $('.copy-url-btn').on('click', function() {
            var urlField = $('#direct-auth-url');
            urlField.select();
            document.execCommand('copy');
            
            $(this).html('<i class="fas fa-check"></i> Скопировано');
            setTimeout(() => {
                $(this).html('<i class="fas fa-copy"></i> Копировать');
            }, 2000);
        });
    });
</script>
@endpush
