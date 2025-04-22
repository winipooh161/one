@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Пользователи Telegram бота</h5>
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

                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>ID</th>
                                    <th>Имя</th>
                                    <th>Тип</th>
                                    <th>Последняя активность</th>
                                    <th>Статус</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($chats as $chat)
                                    <tr>
                                        <td>{{ $chat->chat_id }}</td>
                                        <td>
                                            {{ $chat->display_name }}
                                            @if($chat->username)
                                                <br><small class="text-muted">@{{ $chat->username }}</small>
                                            @endif
                                        </td>
                                        <td>
                                            @if($chat->type == 'private')
                                                <span class="badge badge-primary">Личный чат</span>
                                            @elseif($chat->type == 'group')
                                                <span class="badge badge-success">Группа</span>
                                            @elseif($chat->type == 'supergroup')
                                                <span class="badge badge-info">Супергруппа</span>
                                            @elseif($chat->type == 'channel')
                                                <span class="badge badge-warning">Канал</span>
                                            @else
                                                <span class="badge badge-secondary">{{ $chat->type }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $chat->last_activity_at ? $chat->last_activity_at->format('d.m.Y H:i') : 'Н/Д' }}</td>
                                        <td>
                                            @if($chat->isActive())
                                                <span class="badge badge-success">Активен</span>
                                            @else
                                                <span class="badge badge-danger">Неактивен</span>
                                            @endif
                                        </td>
                                        <td>
                                            <a href="{{ route('admin.telegram.users.show', $chat) }}" class="btn btn-sm btn-info">
                                                <i class="fas fa-eye"></i> Детали
                                            </a>
                                            <button type="button" class="btn btn-sm btn-primary send-message-btn" data-toggle="modal" data-target="#sendMessageModal" data-chat-id="{{ $chat->chat_id }}" data-chat-name="{{ $chat->display_name }}">
                                                <i class="fas fa-paper-plane"></i> Сообщение
                                            </button>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center">Пользователи не найдены</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    {{ $chats->links() }}
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для отправки сообщения -->
<div class="modal fade" id="sendMessageModal" tabindex="-1" role="dialog" aria-labelledby="sendMessageModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="sendMessageModalLabel">Отправить сообщение</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="sendMessageForm" action="" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="form-group">
                        <label for="recipient">Получатель:</label>
                        <input type="text" class="form-control" id="recipient" readonly>
                    </div>
                    <div class="form-group">
                        <label for="message">Сообщение:</label>
                        <textarea class="form-control" id="message" name="message" rows="5" required></textarea>
                        <small class="form-text text-muted">Поддерживается форматирование Markdown.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="submit" class="btn btn-primary">Отправить</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        $('.send-message-btn').click(function() {
            const chatId = $(this).data('chat-id');
            const chatName = $(this).data('chat-name');
            
            $('#recipient').val(chatName + ' (ID: ' + chatId + ')');
            $('#sendMessageForm').attr('action', '{{ url("/admin/telegram/users") }}/' + chatId + '/send');
        });
    });
</script>
@endpush
