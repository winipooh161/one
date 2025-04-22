@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">
                        Чат с {{ $chat->display_name }}
                        <span class="badge {{ $chat->isActive() ? 'badge-success' : 'badge-danger' }}">
                            {{ $chat->isActive() ? 'Активен' : 'Неактивен' }}
                        </span>
                    </h5>
                    <a href="{{ route('admin.telegram.users') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к списку пользователей
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    
                    <div class="chat-container mb-4">
                        <div class="mb-3">
                            <h6>История сообщений:</h6>
                        </div>
                        
                        <div class="messages-container bg-light p-3 rounded" style="max-height: 500px; overflow-y: auto;">
                            @if($messages->isEmpty())
                                <p class="text-muted text-center">Нет сообщений</p>
                            @else
                                @foreach($messages as $message)
                                    <div class="message mb-2 p-2 rounded {{ $message->isIncoming() ? 'bg-white' : 'bg-primary text-white' }} {{ $message->isIncoming() ? 'text-left' : 'text-right' }}">
                                        <div class="message-content">
                                            <p class="mb-1">{{ $message->text }}</p>
                                            <small class="text-{{ $message->isIncoming() ? 'muted' : 'light' }}">
                                                {{ $message->created_at->format('d.m.Y H:i:s') }} 
                                                <span class="badge badge-{{ $message->isIncoming() ? 'info' : 'light' }}">
                                                    {{ $message->isIncoming() ? 'Входящее' : 'Исходящее' }}
                                                </span>
                                            </small>
                                        </div>
                                    </div>
                                @endforeach
                            @endif
                        </div>
                    </div>
                    
                    <form action="{{ route('admin.telegram.send-message', $chat->chat_id) }}" method="POST">
                        @csrf
                        <div class="form-group">
                            <label for="message">Отправить сообщение:</label>
                            <textarea name="message" id="message" rows="3" class="form-control" required></textarea>
                            <small class="form-text text-muted">Поддерживается форматирование Markdown.</small>
                        </div>
                        <button type="submit" class="btn btn-primary">
                            <i class="fas fa-paper-plane"></i> Отправить
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
