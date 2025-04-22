@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Настройки ВКонтакте</h3>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul class="mb-0">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <!-- Предупреждение о блокировке сервиса -->
                    @if(isset($serviceAvailable) && !$serviceAvailable)
                        <div class="alert alert-danger">
                            <h5><i class="fas fa-ban mr-2"></i> Сервис ВКонтакте заблокирован или недоступен</h5>
                            <p>
                                К сожалению, сервис ВКонтакте в данный момент недоступен или заблокирован. 
                                Используйте альтернативные способы для получения токена:
                            </p>
                            <ol>
                                <li>Используйте <a href="https://vkhost.github.io/" target="_blank">VK Host</a> для получения токена напрямую</li>
                                <li>Используйте <a href="{{ route('admin.settings.vk.token-help') }}">подробную инструкцию</a> с альтернативными методами</li>
                            </ol>
                        </div>
                    @endif
                    
                    <!-- Статус подключения -->
                    <div class="mb-4">
                        <h5>Статус подключения</h5>
                        <div class="alert {{ $connectionStatus['success'] ? 'alert-success' : 'alert-warning' }}">
                            @if($connectionStatus['success'])
                                <i class="fas fa-check-circle mr-2"></i> Подключение к ВКонтакте настроено корректно
                            @else
                                <i class="fas fa-exclamation-triangle mr-2"></i> Обнаружены проблемы с подключением
                                <ul class="mt-2 mb-0">
                                    @foreach($connectionStatus['errors'] as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                    
                    <!-- Форма для ручного обновления настроек -->
                    <form action="{{ route('admin.settings.vk.update') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="vk_access_token">Access Token</label>
                            <input type="text" id="vk_access_token" name="vk_access_token" class="form-control" 
                                   value="{{ old('vk_access_token', $settings['token']) }}" 
                                   placeholder="Введите токен доступа ВКонтакте">
                            <small class="form-text text-muted">
                                Токен доступа сообщества можно получить через 
                                <a href="https://vkhost.github.io/" target="_blank">VK Host</a> или 
                                <a href="{{ route('admin.settings.vk.token-help') }}">следуя инструкции</a>
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="vk_owner_id">ID Группы</label>
                            <div class="input-group">
                                <div class="input-group-prepend">
                                    <span class="input-group-text">-</span>
                                </div>
                                <input type="text" id="vk_owner_id" name="vk_owner_id" class="form-control" 
                                       value="{{ old('vk_owner_id', ltrim($settings['owner_id'] ?? '', '-')) }}" 
                                       placeholder="ID группы без знака минус">
                            </div>
                            <small class="form-text text-muted">
                                ID вашей группы ВКонтакте. В системе будет автоматически добавлен знак "-" для публикации от имени группы.
                            </small>
                        </div>
                        
                        <hr>
                        <h5>Настройки OAuth (опционально)</h5>
                        <p class="text-muted">Эти настройки необходимы для получения токена через OAuth.</p>
                        
                        <div class="form-group">
                            <label for="vk_client_id">Client ID (ID приложения)</label>
                            <input type="text" id="vk_client_id" name="vk_client_id" class="form-control" 
                                   value="{{ old('vk_client_id', $settings['client_id']) }}" 
                                   placeholder="ID приложения">
                        </div>
                        
                        <div class="form-group">
                            <label for="vk_client_secret">Client Secret (защищенный ключ)</label>
                            <input type="text" id="vk_client_secret" name="vk_client_secret" class="form-control" 
                                   value="{{ old('vk_client_secret', $settings['client_secret']) }}" 
                                   placeholder="Защищенный ключ приложения">
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Сохранить настройки
                            </button>
                            
                            @if($settings['client_id'] && $settings['client_secret'] && (!isset($serviceAvailable) || $serviceAvailable))
                                <a href="{{ route('admin.oauth.vk.redirect') }}" class="btn btn-info ml-2">
                                    <i class="fab fa-vk mr-1"></i> Авторизоваться через ВКонтакте
                                </a>
                            @endif
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Инструкция по настройке</h3>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle mr-2"></i> Как получить токен для группы ВКонтакте</h5>
                        <ol class="mb-0">
                            <li>Используйте сервис <a href="https://vkhost.github.io/" target="_blank">VK Host</a> для получения токена напрямую</li>
                            <li>Выберите вкладку "Настройки" и отметьте права: <code>wall</code>, <code>photos</code>, <code>groups</code>, <code>offline</code></li>
                            <li>Нажмите "Получить токен" и скопируйте полученный токен из URL</li>
                            <li>Вставьте токен в поле "Access Token" и сохраните настройки</li>
                        </ol>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <h5><i class="fas fa-exclamation-triangle mr-2"></i> Важно!</h5>
                        <p class="mb-0">Токен доступа не должен передаваться третьим лицам. Он обеспечивает полный доступ к управлению вашим сообществом ВКонтакте.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // При сохранении настроек показываем индикатор загрузки
        $('form').on('submit', function() {
            $(this).find('button[type="submit"]').html('<i class="fas fa-spinner fa-spin mr-1"></i> Сохранение...').prop('disabled', true);
        });
    });
</script>
@endpush
