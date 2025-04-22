@extends('admin.layouts.app')

@section('title', 'Настройки OpenAI')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Настройки OpenAI API</h1>
    </div>

    @if (session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
    </div>
    @endif

    @if (session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        {{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Закрыть"></button>
    </div>
    @endif

    <div class="row">
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Настройки API</h5>
                </div>
                <div class="card-body">
                    <form action="{{ route('admin.settings.openai.update') }}" method="POST">
                        @csrf
                        <div class="mb-3">
                            <label for="api_key" class="form-label">API ключ</label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="api_key" name="api_key" value="{{ config('services.openai.api_key') }}">
                                <button class="btn btn-outline-secondary" type="button" id="toggleApiKey">
                                    <i class="fas fa-eye"></i>
                                </button>
                            </div>
                            <small class="text-muted">Получить API ключ можно на <a href="https://platform.openai.com/api-keys" target="_blank">странице API ключей OpenAI</a></small>
                        </div>

                        <div class="mb-3">
                            <label for="model" class="form-label">Модель</label>
                            <select class="form-select" id="model" name="model">
                                <option value="gpt-3.5-turbo" {{ config('services.openai.model') === 'gpt-3.5-turbo' ? 'selected' : '' }}>GPT-3.5 Turbo (быстрее и дешевле)</option>
                                <option value="gpt-4" {{ config('services.openai.model') === 'gpt-4' ? 'selected' : '' }}>GPT-4 (качественнее, но дороже)</option>
                                <option value="gpt-4-turbo-preview" {{ config('services.openai.model') === 'gpt-4-turbo-preview' ? 'selected' : '' }}>GPT-4 Turbo (улучшенная версия)</option>
                                <option value="gpt-4-1106-preview" {{ config('services.openai.model') === 'gpt-4-1106-preview' ? 'selected' : '' }}>GPT-4-1106 (ноябрь 2023)</option>
                            </select>
                        </div>

                        <div class="d-grid">
                            <button type="submit" class="btn btn-primary">Сохранить настройки</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Статус API</h5>
                </div>
                <div class="card-body">
                    <div class="mb-4">
                        <h6>Проверка подключения</h6>
                        <button class="btn btn-sm btn-outline-primary" id="testApiConnection">
                            <i class="fas fa-plug me-1"></i> Проверить подключение
                        </button>
                        <div id="apiTestResult" class="mt-2"></div>
                    </div>
                    
                    <div class="mb-3">
                        <h6>Советы по устранению проблем</h6>
                        <div class="list-group list-group-flush">
                            <div class="list-group-item">
                                <i class="fas fa-credit-card text-primary me-2"></i> 
                                <strong>Проблема с оплатой:</strong> Убедитесь, что вы <a href="https://platform.openai.com/account/billing" target="_blank">добавили платежный метод</a>
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-dollar-sign text-success me-2"></i> 
                                <strong>Недостаточно средств:</strong> <a href="https://platform.openai.com/account/billing" target="_blank">Пополните баланс</a> вашего аккаунта
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-chart-line text-warning me-2"></i> 
                                <strong>Лимит расходов:</strong> <a href="https://platform.openai.com/account/billing/limits" target="_blank">Проверьте лимиты</a> расходов
                            </div>
                            <div class="list-group-item">
                                <i class="fas fa-exclamation-triangle text-danger me-2"></i> 
                                <strong>Неверная модель:</strong> Убедитесь, что используете правильное имя модели:
                                <code>gpt-3.5-turbo</code> или <code>gpt-4</code> (без суффикса .0)
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<script>
    // Переключение видимости API ключа
    document.getElementById('toggleApiKey').addEventListener('click', function() {
        const apiKeyInput = document.getElementById('api_key');
        const icon = this.querySelector('i');
        
        if (apiKeyInput.type === 'password') {
            apiKeyInput.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            apiKeyInput.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    });
    
    // Проверка API подключения
    document.getElementById('testApiConnection').addEventListener('click', function() {
        const resultElement = document.getElementById('apiTestResult');
        resultElement.innerHTML = '<div class="alert alert-info">Проверка подключения...</div>';
        
        fetch('{{ route("admin.settings.openai.test") }}', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': '{{ csrf_token() }}'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                resultElement.innerHTML = `
                    <div class="alert alert-success">
                        <strong><i class="fas fa-check-circle me-1"></i> Успешно!</strong><br>
                        Подключение к API работает корректно.<br>
                        Модель: ${data.model || 'Не указана'}
                    </div>
                `;
            } else {
                resultElement.innerHTML = `
                    <div class="alert alert-danger">
                        <strong><i class="fas fa-exclamation-circle me-1"></i> Ошибка!</strong><br>
                        ${data.error || 'Не удалось подключиться к API.'}
                    </div>
                `;
            }
        })
        .catch(error => {
            resultElement.innerHTML = `
                <div class="alert alert-danger">
                    <strong><i class="fas fa-exclamation-triangle me-1"></i> Ошибка!</strong><br>
                    Произошла ошибка при проверке подключения: ${error.message}
                </div>
            `;
        });
    });
</script>
@endsection
