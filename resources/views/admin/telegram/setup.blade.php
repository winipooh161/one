@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0">Настройка Telegram бота</h5>
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

                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Начало работы с Telegram ботом</h5>
                        <p>Следуйте инструкциям ниже для настройки и запуска Telegram бота для вашего сайта с рецептами.</p>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Шаг 1: Проверка конфигурации</h5>
                        </div>
                        <div class="card-body">
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">Токен бота:</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" value="{{ $botToken ?? 'Не настроен' }}" readonly>
                                    <small class="form-text text-muted">
                                        @if (empty($botToken))
                                            <span class="text-danger">Токен бота не настроен. Добавьте TELEGRAM_RECIPE_BOT_TOKEN в файл .env</span>
                                        @else
                                            Токен бота настроен правильно.
                                        @endif
                                    </small>
                                </div>
                            </div>
                            <div class="form-group row">
                                <label class="col-sm-3 col-form-label">URL вебхука:</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" value="{{ $webhookUrl ?? 'Не настроен' }}" readonly>
                                    <small class="form-text text-muted">
                                        @if (empty($webhookUrl))
                                            <span class="text-danger">URL вебхука не настроен. Добавьте TELEGRAM_RECIPE_WEBHOOK_URL в файл .env</span>
                                        @else
                                            URL вебхука настроен правильно.
                                        @endif
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Шаг 2: Создание и миграция таблиц</h5>
                        </div>
                        <div class="card-body">
                            @if ($needSetup)
                                <div class="alert alert-warning">
                                    <p>Необходимо создать таблицы для работы с Telegram ботом. Выполните следующие команды:</p>
                                    <code>php artisan make:migration create_telegram_chats_table</code><br>
                                    <code>php artisan make:migration create_telegram_messages_table</code><br>
                                    <code>php artisan migrate</code>
                                </div>
                                <p>Или используйте кнопку ниже для автоматической миграции:</p>
                                <form action="{{ route('admin.telegram.migrate') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-warning">
                                        <i class="fas fa-database"></i> Выполнить миграцию
                                    </button>
                                </form>
                            @else
                                <div class="alert alert-success">
                                    <p><i class="fas fa-check-circle"></i> Таблицы для Telegram бота созданы и готовы к использованию.</p>
                                </div>
                            @endif
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h5 class="mb-0">Шаг 3: Установка вебхука</h5>
                        </div>
                        <div class="card-body">
                            <p>После настройки токена и URL вебхука, необходимо установить вебхук для бота:</p>
                            <form action="{{ route('admin.telegram.set-webhook') }}" method="POST" class="mb-3">
                                @csrf
                                <button type="submit" class="btn btn-primary" {{ empty($botToken) || empty($webhookUrl) ? 'disabled' : '' }}>
                                    <i class="fas fa-link"></i> Установить вебхук
                                </button>
                            </form>
                            
                            <p>Если вам нужно удалить вебхук (например, для отладки):</p>
                            <form action="{{ route('admin.telegram.delete-webhook') }}" method="POST">
                                @csrf
                                <button type="submit" class="btn btn-danger" {{ empty($botToken) ? 'disabled' : '' }}>
                                    <i class="fas fa-unlink"></i> Удалить вебхук
                                </button>
                            </form>
                        </div>
                    </div>

                    <div class="card mb-4">
                        <div class="card-header bg-success text-white">
                            <h5 class="mb-0">Готово!</h5>
                        </div>
                        <div class="card-body">
                            <p>Когда все шаги выполнены, вы можете перейти к управлению ботом:</p>
                            <a href="{{ route('admin.telegram.index') }}" class="btn btn-success">
                                <i class="fas fa-tachometer-alt"></i> Перейти к панели управления ботом
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
