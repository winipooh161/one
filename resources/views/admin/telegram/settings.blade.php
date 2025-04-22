@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Настройки Telegram бота</h5>
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

                    <ul class="nav nav-tabs" id="settingsTabs" role="tablist">
                        <li class="nav-item">
                            <a class="nav-link active" id="basic-tab" data-toggle="tab" href="#basic" role="tab" aria-controls="basic" aria-selected="true">
                                <i class="fas fa-cog"></i> Основные настройки
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="webhook-tab" data-toggle="tab" href="#webhook" role="tab" aria-controls="webhook" aria-selected="false">
                                <i class="fas fa-link"></i> Настройки вебхука
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" id="advanced-tab" data-toggle="tab" href="#advanced" role="tab" aria-controls="advanced" aria-selected="false">
                                <i class="fas fa-cogs"></i> Дополнительные настройки
                            </a>
                        </li>
                    </ul>

                    <div class="tab-content p-3 border border-top-0 rounded-bottom" id="settingsTabsContent">
                        <!-- Основные настройки -->
                        <div class="tab-pane fade show active" id="basic" role="tabpanel" aria-labelledby="basic-tab">
                            <form action="{{ route('admin.telegram.update-settings') }}" method="POST">
                                @csrf
                                <div class="form-group row">
                                    <label for="botToken" class="col-sm-3 col-form-label">Токен бота:</label>
                                    <div class="col-sm-9">
                                        <div class="input-group">
                                            <input type="text" class="form-control" id="botToken" name="bot_token" value="{{ $botToken ?? '' }}" required>
                                            <div class="input-group-append">
                                                <button class="btn btn-outline-secondary" type="button" id="toggle-token">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                            </div>
                                        </div>
                                        <small class="form-text text-muted">Токен бота, полученный от @BotFather в Telegram</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="welcomeMessage" class="col-sm-3 col-form-label">Приветственное сообщение:</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="welcomeMessage" name="welcome_message" rows="3">Привет! Я бот для поиска рецептов. Просто напиши мне название блюда или ингредиенты, и я найду для тебя рецепты.</textarea>
                                        <small class="form-text text-muted">Сообщение, которое бот отправляет при первом запуске</small>
                                    </div>
                                </div>

                                <div class="form-group row">
                                    <label for="helpMessage" class="col-sm-3 col-form-label">Справочное сообщение:</label>
                                    <div class="col-sm-9">
                                        <textarea class="form-control" id="helpMessage" name="help_message" rows="3">Чтобы найти рецепт, просто отправь мне ключевые слова или ингредиенты. Например: "паста карбонара" или "что приготовить из курицы и грибов".</textarea>
                                        <small class="form-text text-muted">Сообщение, которое отправляется по команде /help</small>
                                    </div>
                                </div>

                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="enableLogging" name="enable_logging" checked>
                                    <label class="form-check-label" for="enableLogging">
                                        Включить логирование всех сообщений
                                    </label>
                                </div>

                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить настройки
                                </button>
                            </form>
                        </div>

                        <!-- Настройки вебхука -->
                        <div class="tab-pane fade" id="webhook" role="tabpanel" aria-labelledby="webhook-tab">
                            <div class="form-group row">
                                <label for="webhookUrl" class="col-sm-3 col-form-label">URL вебхука:</label>
                                <div class="col-sm-9">
                                    <input type="text" class="form-control" id="webhookUrl" value="{{ $webhookUrl ?? '' }}" readonly>
                                    <small class="form-text text-muted">URL, на который Telegram будет отправлять обновления</small>
                                </div>
                            </div>

                            @if ($webhookInfo)
                                <div class="card mb-4">
                                    <div class="card-header">
                                        <h6 class="mb-0">Текущие настройки вебхука</h6>
                                    </div>
                                    <div class="card-body">
                                        <dl class="row">
                                            <dt class="col-sm-3">URL:</dt>
                                            <dd class="col-sm-9">{{ $webhookInfo['url'] }}</dd>
                                            
                                            <dt class="col-sm-3">Имеет собственный сертификат:</dt>
                                            <dd class="col-sm-9">{{ $webhookInfo['has_custom_certificate'] ? 'Да' : 'Нет' }}</dd>
                                            
                                            <dt class="col-sm-3">Ожидание обновлений:</dt>
                                            <dd class="col-sm-9">{{ $webhookInfo['pending_update_count'] }} обновлений</dd>
                                            
                                            <dt class="col-sm-3">Последняя ошибка:</dt>
                                            <dd class="col-sm-9">{{ $webhookInfo['last_error_message'] ?? 'Нет ошибок' }}</dd>
                                            
                                            <dt class="col-sm-3">Последняя ошибка дата:</dt>
                                            <dd class="col-sm-9">
                                                @if (isset($webhookInfo['last_error_date']))
                                                    {{ date('d.m.Y H:i:s', $webhookInfo['last_error_date']) }}
                                                @else
                                                    Нет ошибок
                                                @endif
                                            </dd>
                                        </dl>
                                    </div>
                                </div>
                            @endif

                            <div class="d-flex">
                                <form action="{{ route('admin.telegram.set-webhook') }}" method="POST" class="mr-2">
                                    @csrf
                                    <button type="submit" class="btn btn-primary" {{ empty($botToken) || empty($webhookUrl) ? 'disabled' : '' }}>
                                        <i class="fas fa-link"></i> Установить вебхук
                                    </button>
                                </form>
                                
                                <form action="{{ route('admin.telegram.delete-webhook') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="btn btn-danger" {{ empty($botToken) ? 'disabled' : '' }}>
                                        <i class="fas fa-unlink"></i> Удалить вебхук
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Дополнительные настройки -->
                        <div class="tab-pane fade" id="advanced" role="tabpanel" aria-labelledby="advanced-tab">
                            <form action="{{ route('admin.telegram.update-settings') }}" method="POST">
                                @csrf
                                <input type="hidden" name="tab" value="advanced">
                                
                                <div class="form-group row">
                                    <label for="maxResults" class="col-sm-3 col-form-label">Максимальное кол-во результатов:</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control" id="maxResults" name="max_results" value="5" min="1" max="10">
                                        <small class="form-text text-muted">Максимальное количество рецептов, отображаемых в результатах поиска</small>
                                    </div>
                                </div>
                                
                                <div class="form-group row">
                                    <label for="minSearchScore" class="col-sm-3 col-form-label">Минимальный процент совпадения:</label>
                                    <div class="col-sm-9">
                                        <input type="number" class="form-control" id="minSearchScore" name="min_search_score" value="10" min="1" max="100">
                                        <small class="form-text text-muted">Минимальный процент совпадения для отображения рецепта в результатах поиска</small>
                                    </div>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="showCategories" name="show_categories" checked>
                                    <label class="form-check-label" for="showCategories">
                                        Показывать категории в результатах поиска
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="showIngredients" name="show_ingredients" checked>
                                    <label class="form-check-label" for="showIngredients">
                                        Показывать ингредиенты в результатах поиска
                                    </label>
                                </div>
                                
                                <div class="form-check mb-3">
                                    <input class="form-check-input" type="checkbox" id="showTags" name="show_tags" checked>
                                    <label class="form-check-label" for="showTags">
                                        Показывать теги в результатах поиска
                                    </label>
                                </div>
                                
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> Сохранить дополнительные настройки
                                </button>
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
$(document).ready(function() {
    // Показать/скрыть токен
    $('#toggle-token').click(function() {
        const tokenInput = $('#botToken');
        const icon = $(this).find('i');
        
        if (tokenInput.attr('type') === 'password') {
            tokenInput.attr('type', 'text');
            icon.removeClass('fa-eye').addClass('fa-eye-slash');
        } else {
            tokenInput.attr('type', 'password');
            icon.removeClass('fa-eye-slash').addClass('fa-eye');
        }
    });
    
    // Скрыть токен изначально
    $('#botToken').attr('type', 'password');
});
</script>
@endpush
