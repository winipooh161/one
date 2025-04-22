@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Массовая рассылка сообщений</h5>
                    <a href="{{ route('admin.telegram.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к обзору
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Информация о рассылке</h5>
                        <p>Здесь вы можете отправить сообщение всем пользователям вашего бота или выбранной группе пользователей.</p>
                        <p><strong>Внимание!</strong> Массовая рассылка может занять продолжительное время в зависимости от количества получателей.</p>
                    </div>

                    <form action="{{ route('admin.telegram.broadcast') }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="recipients">Получатели:</label>
                            <select class="form-control" id="recipients" name="recipients" required>
                                <option value="all">Все пользователи ({{ count($allChats) }})</option>
                                <option value="active">Только активные пользователи ({{ $activeChatsCount }})</option>
                                <option value="selected">Выбранные пользователи</option>
                            </select>
                        </div>

                        <div id="selectedUsers" class="form-group" style="display: none;">
                            <label>Выберите пользователей:</label>
                            <div class="table-responsive">
                                <table class="table table-bordered table-sm">
                                    <thead class="thead-light">
                                        <tr>
                                            <th width="5%">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" id="selectAll">
                                                </div>
                                            </th>
                                            <th width="15%">ID</th>
                                            <th width="40%">Имя</th>
                                            <th width="20%">Тип</th>
                                            <th width="20%">Последняя активность</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($allChats as $chat)
                                            <tr>
                                                <td>
                                                    <div class="form-check">
                                                        <input class="form-check-input user-checkbox" type="checkbox" name="selected_chats[]" value="{{ $chat->chat_id }}">
                                                    </div>
                                                </td>
                                                <td>{{ $chat->chat_id }}</td>
                                                <td>{{ $chat->display_name }}</td>
                                                <td>{{ $chat->type }}</td>
                                                <td>{{ $chat->last_activity_at ? $chat->last_activity_at->format('d.m.Y H:i') : 'Н/Д' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="message">Текст сообщения:</label>
                            <textarea class="form-control" id="message" name="message" rows="6" required></textarea>
                            <small class="form-text text-muted">
                                Поддерживается форматирование Markdown. Используйте *текст* для жирного шрифта, _текст_ для курсива, [текст](URL) для ссылок.
                            </small>
                        </div>

                        <div class="form-group">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" id="confirmSend" required>
                                <label class="form-check-label" for="confirmSend">
                                    Я подтверждаю, что хочу отправить это сообщение выбранным получателям
                                </label>
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary" id="sendButton" disabled>
                            <i class="fas fa-paper-plane"></i> Отправить рассылку
                        </button>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Советы по рассылке</h5>
                </div>
                <div class="card-body">
                    <h6>Пример форматирования Markdown:</h6>
                    <pre>*Жирный текст*
_Курсивный текст_
[Текст ссылки](https://example.com)

• Маркированный список
• Второй пункт

1. Нумерованный список
2. Второй пункт</pre>

                    <h6 class="mt-3">Рекомендации по рассылке:</h6>
                    <ul>
                        <li>Отправляйте только важную и полезную информацию</li>
                        <li>Не злоупотребляйте рассылками, чтобы не вызвать негативную реакцию пользователей</li>
                        <li>Используйте форматирование для улучшения читаемости сообщения</li>
                        <li>Добавляйте призыв к действию в конце сообщения</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    // Показать/скрыть список пользователей в зависимости от выбора получателей
    $('#recipients').change(function() {
        if ($(this).val() === 'selected') {
            $('#selectedUsers').show();
        } else {
            $('#selectedUsers').hide();
        }
    });

    // Выбрать/снять выбор со всех чекбоксов
    $('#selectAll').change(function() {
        $('.user-checkbox').prop('checked', $(this).prop('checked'));
    });

    // Обработка подтверждения отправки
    $('#confirmSend').change(function() {
        $('#sendButton').prop('disabled', !$(this).prop('checked'));
    });

    // Предупреждение перед отправкой
    $('form').submit(function() {
        const recipients = $('#recipients').val();
        let count = 0;
        
        if (recipients === 'all') {
            count = {{ count($allChats) }};
        } else if (recipients === 'active') {
            count = {{ $activeChatsCount }};
        } else if (recipients === 'selected') {
            count = $('.user-checkbox:checked').length;
            if (count === 0) {
                alert('Пожалуйста, выберите хотя бы одного получателя.');
                return false;
            }
        }
        
        return confirm(`Вы собираетесь отправить сообщение ${count} получателям. Продолжить?`);
    });
});
</script>
@endpush
