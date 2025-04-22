@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Управление Telegram ботом</h5>
                    <div>
                        <a href="{{ route('admin.telegram.status') }}" class="btn btn-sm btn-info check-status-btn">
                            <i class="fas fa-sync-alt"></i> Проверить статус
                        </a>
                        <form action="{{ route('admin.telegram.clear-cache') }}" method="POST" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-secondary">
                                <i class="fas fa-broom"></i> Очистить кэш
                            </button>
                        </form>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif
                    @if (session('warning'))
                        <div class="alert alert-warning">{{ session('warning') }}</div>
                    @endif
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-info text-white">
                                    <i class="fas fa-robot"></i> Информация о боте
                                </div>
                                <div class="card-body" id="bot-info-container">
                                    @if ($botInfo)
                                        <div class="bot-info">
                                            <p><strong>Имя:</strong> {{ $botInfo['first_name'] }}</p>
                                            <p><strong>Username:</strong> @{{ $botInfo['username'] }}</p>
                                            <p><strong>ID:</strong> {{ $botInfo['id'] }}</p>
                                            @if (isset($botInfo['can_join_groups']))
                                                <p><strong>Может вступать в группы:</strong> {{ $botInfo['can_join_groups'] ? 'Да' : 'Нет' }}</p>
                                            @endif
                                            @if (isset($botInfo['can_read_all_group_messages']))
                                                <p><strong>Может читать все сообщения в группах:</strong> {{ $botInfo['can_read_all_group_messages'] ? 'Да' : 'Нет' }}</p>
                                            @endif
                                        </div>
                                    @else
                                        <div class="alert alert-warning">
                                            Не удалось получить информацию о боте. Проверьте настройки или нажмите "Проверить статус".
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-primary text-white">
                                    <i class="fas fa-tachometer-alt"></i> Статистика
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4 mb-3">
                                            <div class="bg-light p-3 rounded">
                                                <h2>{{ $totalChats }}</h2>
                                                <p class="mb-0">Всего чатов</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="bg-light p-3 rounded">
                                                <h2>{{ $activeChats }}</h2>
                                                <p class="mb-0">Активных чатов</p>
                                            </div>
                                        </div>
                                        <div class="col-md-4 mb-3">
                                            <div class="bg-light p-3 rounded">
                                                <h2>{{ $totalMessages }}</h2>
                                                <p class="mb-0">Сообщений</p>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-success text-white">
                                    <i class="fas fa-chart-line"></i> Активность за неделю
                                </div>
                                <div class="card-body">
                                    <canvas id="activity-chart" height="200"></canvas>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card mb-3">
                                <div class="card-header bg-danger text-white">
                                    <i class="fas fa-comments"></i> Последние сообщения
                                </div>
                                <div class="card-body">
                                    @if ($lastMessages->isEmpty())
                                        <p class="text-muted">Сообщений пока нет</p>
                                    @else
                                        <ul class="list-group">
                                            @foreach ($lastMessages as $message)
                                                <li class="list-group-item d-flex justify-content-between align-items-center">
                                                    <div>
                                                        <span class="badge {{ $message->direction == 'incoming' ? 'bg-primary' : 'bg-success' }} mr-2">
                                                            {{ $message->direction == 'incoming' ? 'Входящее' : 'Исходящее' }}
                                                        </span>
                                                        {{ Str::limit($message->text, 50) }}
                                                    </div>
                                                    <small class="text-muted">{{ $message->created_at->format('d.m.Y H:i') }}</small>
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-header bg-secondary text-white">
                                    <i class="fas fa-tools"></i> Управление ботом
                                </div>
                                <div class="card-body">
                                    <div class="row">
                                        <div class="col-md-3 mb-3">
                                            <a href="{{ route('admin.telegram.users') }}" class="btn btn-primary btn-block">
                                                <i class="fas fa-users"></i> Пользователи
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="{{ route('admin.telegram.broadcast') }}" class="btn btn-info btn-block">
                                                <i class="fas fa-broadcast-tower"></i> Рассылка
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="{{ route('admin.telegram.commands') }}" class="btn btn-success btn-block">
                                                <i class="fas fa-terminal"></i> Команды
                                            </a>
                                        </div>
                                        <div class="col-md-3 mb-3">
                                            <a href="{{ route('admin.telegram.settings') }}" class="btn btn-warning btn-block">
                                                <i class="fas fa-cog"></i> Настройки
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // График активности
    const activityData = @json($dailyStats);
    const dates = Object.keys(activityData);
    const counts = Object.values(activityData);
    
    new Chart(document.getElementById('activity-chart'), {
        type: 'line',
        data: {
            labels: dates,
            datasets: [{
                label: 'Количество сообщений',
                data: counts,
                borderColor: 'rgba(40, 167, 69, 1)',
                backgroundColor: 'rgba(40, 167, 69, 0.1)',
                borderWidth: 2,
                tension: 0.1,
                fill: true
            }]
        },
        options: {
            responsive: true,
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    }
                }
            }
        }
    });
    
    // Проверка статуса бота
    document.querySelector('.check-status-btn').addEventListener('click', function(e) {
        e.preventDefault();
        
        const infoContainer = document.getElementById('bot-info-container');
        infoContainer.innerHTML = '<div class="text-center"><i class="fas fa-spinner fa-spin"></i> Проверка статуса...</div>';
        
        fetch(this.getAttribute('href'))
            .then(response => response.json())
            .then(data => {
                if (data.status === 'ok') {
                    let html = `
                        <div class="bot-info">
                            <p><strong>Имя:</strong> ${data.bot.first_name}</p>
                            <p><strong>Username:</strong> @${data.bot.username}</p>
                            <p><strong>ID:</strong> ${data.bot.id}</p>`;
                    
                    if (data.bot.hasOwnProperty('can_join_groups')) {
                        html += `<p><strong>Может вступать в группы:</strong> ${data.bot.can_join_groups ? 'Да' : 'Нет'}</p>`;
                    }
                    
                    if (data.bot.hasOwnProperty('can_read_all_group_messages')) {
                        html += `<p><strong>Может читать все сообщения в группах:</strong> ${data.bot.can_read_all_group_messages ? 'Да' : 'Нет'}</p>`;
                    }
                    
                    html += `</div>`;
                    infoContainer.innerHTML = html;
                } else {
                    infoContainer.innerHTML = `<div class="alert alert-danger">${data.message}</div>`;
                }
            })
            .catch(error => {
                infoContainer.innerHTML = `<div class="alert alert-danger">Ошибка при проверке статуса бота</div>`;
                console.error('Ошибка:', error);
            });
    });
});
</script>
@endpush
