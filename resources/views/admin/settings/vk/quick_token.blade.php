@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Быстрое добавление токена ВКонтакте</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.settings.vk') }}" class="btn btn-sm btn-default">
                            <i class="fas fa-arrow-left"></i> Назад к настройкам
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i> 
                        <strong>Токен получен!</strong> Вы успешно получили токен доступа ВКонтакте. Сохраните его в настройках, чтобы использовать для публикации контента.
                    </div>
                    
                    <form action="{{ route('admin.settings.vk.update') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="vk_access_token">Токен доступа</label>
                            <div class="input-group">
                                <input type="text" id="vk_access_token" name="vk_access_token" class="form-control" 
                                       value="vk1.a.ougA-7lMbo_858Gf55P-lekhlXiQlnBc74g8Y0W25JXi9zlo-DuN97tmdzgQST6KZ87TpwodU5rC2CZ-pIIolQGnd9vy3y3u4ZN-gz8NmWx_EfjbgnNxSCY0BpQeVCmAJYNE5KzLwaGM000HlJ-oaO0Wk0nYxugZ4xDpi10quva0KWJxZACgYJi0FhhCwHEvPJ7nTrdf1J4EYBmcqpDG5A">
                                <div class="input-group-append">
                                    <button type="button" class="btn btn-outline-secondary copy-btn">
                                        <i class="fas fa-copy"></i> Копировать
                                    </button>
                                </div>
                            </div>
                            <small class="text-muted">Срок действия: 24 часа (до {{ now()->addHours(24)->format('d.m.Y H:i') }})</small>
                        </div>
                        
                        <div class="form-group">
                            <label for="vk_owner_id">ID группы</label>
                            <input type="text" id="vk_owner_id" name="vk_owner_id" class="form-control" 
                                   value="{{ old('vk_owner_id', env('VK_OWNER_ID') ? ltrim(env('VK_OWNER_ID'), '-') : '') }}" 
                                   placeholder="Введите ID группы (без знака минус)">
                            <small class="text-muted">Введите ID группы, для которой вы получили токен (без знака минус).</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Сохранить настройки
                            </button>
                            <a href="{{ route('admin.settings.vk') }}" class="btn btn-secondary ml-2">
                                <i class="fas fa-times mr-1"></i> Отмена
                            </a>
                        </div>
                    </form>
                    
                    <div class="alert alert-info mt-4">
                        <h5><i class="fas fa-info-circle mr-2"></i> Информация о токене:</h5>
                        <ul class="mb-0">
                            <li>ID пользователя: 426293750</li>
                            <li>Срок действия: 86400 секунд (24 часа)</li>
                            <li>Токен содержит все необходимые права для публикации постов в группе</li>
                        </ul>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        <strong>Важно!</strong> Токен доступа даёт полные права на управление указанной группой. 
                        Храните его в безопасности и не передавайте третьим лицам.
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
        // Функция копирования токена в буфер обмена
        $('.copy-btn').on('click', function() {
            var tokenField = $('#vk_access_token');
            tokenField.select();
            document.execCommand('copy');
            
            // Показываем подтверждение копирования
            $(this).html('<i class="fas fa-check"></i> Скопировано');
            setTimeout(() => {
                $(this).html('<i class="fas fa-copy"></i> Копировать');
            }, 2000);
        });
    });
</script>
@endpush
