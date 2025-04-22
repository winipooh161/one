@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-10">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex justify-content-between align-items-center">
                        <h3>Пакетный парсинг рецептов</h3>
                        <div>
                            <a href="{{ route('admin.parser.collect_links') }}" class="btn btn-outline-success me-2">
                                <i class="fas fa-search"></i> Сбор ссылок
                            </a>
                            <a href="{{ route('admin.parser.index') }}" class="btn btn-outline-secondary">
                                <i class="fas fa-arrow-left"></i> Назад к обычному парсингу
                            </a>
                        </div>
                    </div>
                </div>

                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger" role="alert">
                            {{ session('error') }}
                        </div>
                    @endif

                    <form method="POST" action="{{ route('admin.parser.batchParse') }}">
                        @csrf
                        <div class="form-group mb-3">
                            <label for="urls">Список URL (каждый URL на отдельной строке)</label>
                            <div class="input-group mb-2">
                                <span class="input-group-text">Быстрые действия</span>
                                <button type="button" class="btn btn-outline-secondary" id="btn-clear-urls">
                                    <i class="fas fa-trash"></i> Очистить
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-filter-urls">
                                    <i class="fas fa-filter"></i> Удалить дубликаты
                                </button>
                                <button type="button" class="btn btn-outline-secondary" id="btn-sort-urls">
                                    <i class="fas fa-sort-alpha-down"></i> Сортировать
                                </button>
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    <i class="fas fa-bolt"></i> Примеры
                                </button>
                                <ul class="dropdown-menu">
                                    <li><button type="button" class="dropdown-item" id="btn-example-edaruBreakfast">Eda.ru - Завтраки (5 ссылок)</button></li>
                                    <li><button type="button" class="dropdown-item" id="btn-example-edaruSoup">Eda.ru - Супы (5 ссылок)</button></li>
                                </ul>
                            </div>
                            <textarea 
                                class="form-control @error('urls') is-invalid @enderror" 
                                id="urls" 
                                name="urls" 
                                rows="10" 
                                placeholder="https://example.com/recipe1&#10;https://example.com/recipe2&#10;https://example.com/recipe3"
                                required
                            >{{ old('urls') }}</textarea>
                            @error('urls')
                                <span class="invalid-feedback" role="alert">
                                    <strong>{{ $message }}</strong>
                                </span>
                            @enderror
                            <div class="d-flex justify-content-between align-items-center mt-2">
                                <div class="form-text">Вставьте список URL рецептов, каждый с новой строки</div>
                                <div id="url-counter" class="badge bg-secondary">0 URL</div>
                            </div>
                        </div>

                        <div class="form-group">
                            <label for="batch_size">Количество карточек в пакете</label>
                            <input type="number" name="batch_size" id="batch_size" class="form-control" value="15" min="1" max="100" required>
                        </div>
                        <div class="form-group">
                            <label for="batch_interval">Интервал между пакетами (в минутах)</label>
                            <input type="number" name="batch_interval" id="batch_interval" class="form-control" value="1" min="1" max="60" required>
                        </div>

                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sync-alt"></i> Начать пакетный парсинг
                            </button>
                        </div>
                    </form>

                    <div class="mt-4">
                        <div class="alert alert-info">
                            <h5><i class="fas fa-info-circle"></i> Инструкция по использованию:</h5>
                            <ol>
                                <li>Вставьте список URL рецептов, каждый с новой строки</li>
                                <li>Нажмите кнопку "Начать пакетный парсинг"</li>
                                <li>Система автоматически обработает каждый URL и создаст рецепты</li>
                                <li>После завершения вы увидите отчёт о результатах</li>
                            </ol>
                            <p>
                                <strong>Совет:</strong> Используйте сначала <a href="{{ route('admin.parser.collect_links') }}">страницу сбора ссылок</a> для автоматического извлечения URL рецептов с целой категории.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function() {
        const urlsTextarea = document.getElementById('urls');
        const urlCounter = document.getElementById('url-counter');
        
        // Функция подсчета URL
        function updateUrlCounter() {
            const urls = urlsTextarea.value.split('\n').filter(url => url.trim().length > 0);
            urlCounter.textContent = `${urls.length} URL`;
        }
        
        // Обновляем счетчик при изменении содержимого
        urlsTextarea.addEventListener('input', updateUrlCounter);
        
        // Инициализация счетчика
        updateUrlCounter();
        
        // Очистка текстового поля
        const btnClearUrls = document.getElementById('btn-clear-urls');
        if (btnClearUrls) {
            btnClearUrls.addEventListener('click', function() {
                if (confirm('Вы уверены, что хотите очистить список URL?')) {
                    urlsTextarea.value = '';
                    updateUrlCounter();
                }
            });
        }
        
        // Фильтрация дубликатов
        const btnFilterUrls = document.getElementById('btn-filter-urls');
        if (btnFilterUrls) {
            btnFilterUrls.addEventListener('click', function() {
                const urls = urlsTextarea.value.split('\n').filter(url => url.trim().length > 0);
                const uniqueUrls = [...new Set(urls)];
                urlsTextarea.value = uniqueUrls.join('\n');
                updateUrlCounter();
                
                const removedCount = urls.length - uniqueUrls.length;
                if (removedCount > 0) {
                    alert(`Удалено ${removedCount} дубликатов URL`);
                } else {
                    alert('Дубликаты URL не найдены');
                }
            });
        }
        
        // Сортировка URL
        const btnSortUrls = document.getElementById('btn-sort-urls');
        if (btnSortUrls) {
            btnSortUrls.addEventListener('click', function() {
                const urls = urlsTextarea.value.split('\n').filter(url => url.trim().length > 0);
                urls.sort();
                urlsTextarea.value = urls.join('\n');
            });
        }
        
        // Примеры URL с eda.ru
        const btnExampleEdaruBreakfast = document.getElementById('btn-example-edaruBreakfast');
        if (btnExampleEdaruBreakfast) {
            btnExampleEdaruBreakfast.addEventListener('click', function() {
                urlsTextarea.value = `https://eda.ru/recepty/zavtraki/zavtrak-so-steykom-125170
https://eda.ru/recepty/zavtraki/poleznij-zavtrak-22515
https://eda.ru/recepty/zavtraki/yaichnica-s-pomidorami-14631
https://eda.ru/recepty/zavtraki/omlet-po-ispanski-s-tomatami-10773
https://eda.ru/recepty/zavtraki/yaichnica-s-avokado-85844`;
                updateUrlCounter();
            });
        }
        
        const btnExampleEdaruSoup = document.getElementById('btn-example-edaruSoup');
        if (btnExampleEdaruSoup) {
            btnExampleEdaruSoup.addEventListener('click', function() {
                urlsTextarea.value = `https://eda.ru/recepty/supy/borsch-po-klasicheskomu-receptu-s-10180
https://eda.ru/recepty/supy/sup-s-frikadelkami-i-vermishelju-81903
https://eda.ru/recepty/supy/sup-harcho-klassicheskij-54961
https://eda.ru/recepty/supy/shhi-iz-svezhey-kapusty-131903
https://eda.ru/recepty/supy/kurinij-sup-s-lapshoj-131904`;
                updateUrlCounter();
            });
        }
        
        // Проверяем, есть ли сохраненные ссылки в localStorage
        const savedLinks = localStorage.getItem('batchParseLinks');
        if (savedLinks) {
            // Заполняем поле сохраненными ссылками
            urlsTextarea.value = savedLinks;
            // Очищаем хранилище
            localStorage.removeItem('batchParseLinks');
            // Обновляем счетчик URL
            updateUrlCounter();
            
            // Показываем уведомление
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-success alert-dismissible fade show mt-3';
            alertDiv.innerHTML = `
                <i class="fas fa-check-circle"></i> 
                Ссылки из страницы сбора были автоматически добавлены в форму.
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;
            const formElement = document.querySelector('form');
            formElement.insertAdjacentElement('afterend', alertDiv);
            
            // Автоматически скрываем уведомление через 5 секунд
            setTimeout(function() {
                alertDiv.remove();
            }, 5000);
        }
    });
</script>
@endsection
