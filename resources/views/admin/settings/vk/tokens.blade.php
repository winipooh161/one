@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Токены доступа ВКонтакте</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.settings.vk') }}" class="btn btn-sm btn-default">
                            <i class="fas fa-arrow-left"></i> Назад к настройкам
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-success">
                        <i class="fas fa-check-circle mr-2"></i> Авторизация успешно завершена! Вы получили токены для следующих сообществ:
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th>ID группы</th>
                                    <th>Токен доступа</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($groups as $group)
                                <tr>
                                    <td>-{{ $group['group_id'] }}</td>
                                    <td>
                                        <div class="input-group">
                                            <input type="text" class="form-control token-field" value="{{ $group['access_token'] }}" readonly>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary copy-btn" type="button">
                                                    <i class="fas fa-copy"></i>
                                                </button>
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <form action="{{ route('admin.settings.vk.update') }}" method="POST">
                                            @csrf
                                            <input type="hidden" name="vk_access_token" value="{{ $group['access_token'] }}">
                                            <input type="hidden" name="vk_owner_id" value="{{ $group['group_id'] }}">
                                            <button type="submit" class="btn btn-primary">
                                                <i class="fas fa-save mr-1"></i> Сохранить как основной
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="alert alert-info mt-3">
                        <h5><i class="fas fa-info-circle mr-2"></i> Инструкция:</h5>
                        <ol class="mb-0">
                            <li>Выберите группу, для которой вы хотите использовать токен доступа</li>
                            <li>Нажмите "Сохранить как основной" для выбранной группы</li>
                            <li>Это обновит настройки ВКонтакте в вашем приложении</li>
                        </ol>
                    </div>
                    
                    <div class="alert alert-warning mt-3">
                        <i class="fas fa-exclamation-triangle mr-2"></i> 
                        <strong>Важно!</strong> Токен доступа сообщества дает полные права на управление группой. 
                        Не передавайте его третьим лицам.
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    // Скрипт для копирования токена в буфер обмена
    $(document).ready(function() {
        $('.copy-btn').on('click', function() {
            var tokenField = $(this).closest('.input-group').find('.token-field');
            tokenField.select();
            document.execCommand('copy');
            
            // Показываем уведомление о копировании
            $(this).html('<i class="fas fa-check"></i>');
            setTimeout(() => {
                $(this).html('<i class="fas fa-copy"></i>');
            }, 2000);
        });
    });
</script>
@endpush
