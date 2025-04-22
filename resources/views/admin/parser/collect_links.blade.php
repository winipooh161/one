@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Сбор ссылок на рецепты</h3>
                </div>
                <div class="card-body">
                    @if(session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if(session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="row">
                        <!-- Форма запуска непрерывного сбора ссылок -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-primary text-white">
                                    <h4 class="mb-0">Непрерывный сбор ссылок</h4>
                                </div>
                                <div class="card-body">
                                    <form id="continuousCollectionForm">
                                        @csrf
                                        <div class="form-group">
                                            <label for="url">Стартовый URL:</label>
                                            <input type="url" class="form-control" id="url" name="url" required 
                                                placeholder="https://eda.ru/recepty" value="https://eda.ru/recepty">
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="strategy">Стратегия обхода:</label>
                                            <select class="form-control" id="strategy" name="strategy">
                                                <option value="combined" selected>Комбинированная</option>
                                                <option value="breadth">В ширину</option>
                                                <option value="depth">В глубину</option>
                                            </select>
                                        </div>
                                        
                                        <div class="form-group">
                                            <label for="additional_urls">Дополнительные URL (по одному на строке):</label>
                                            <textarea class="form-control" id="additional_urls" name="additional_urls" rows="4" 
                                            placeholder="https://eda.ru/recepty/zavtraki
https://eda.ru/recepty/supy
https://eda.ru/recepty/osnovnye-blyuda"></textarea>
                                        </div>
                                        
                                        <div class="form-group text-center">
                                            <button type="button" id="startCollectionBtn" class="btn btn-primary">
                                                <i class="fas fa-play"></i> Запустить сбор (классический)
                                            </button>
                                            <button type="button" id="startInfiniteScrollBtn" class="btn btn-success">
                                                <i class="fas fa-sync"></i> Запустить Infinite Scroll
                                            </button>
                                            <button type="button" id="stopCollectionBtn" class="btn btn-danger" style="display:none;">
                                                <i class="fas fa-stop"></i> Остановить сбор
                                            </button>
                                        </div>
                                    </form>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Отображение статуса сбора -->
                        <div class="col-md-6">
                            <div class="card">
                                <div class="card-header bg-info text-white">
                                    <h4 class="mb-0">Статус сбора ссылок</h4>
                                </div>
                                <div class="card-body">
                                    <div id="collection-status">
                                        <div class="status-item">
                                            <strong>Статус:</strong> <span id="status-active" class="badge badge-secondary">Не активен</span>
                                        </div>
                                        <div class="status-item">
                                            <strong>Собрано ссылок:</strong> <span id="status-total">{{ $totalLinks ?? 0 }}</span>
                                        </div>
                                        <div class="status-item">
                                            <strong>Текущий URL:</strong> <span id="status-current-url">-</span>
                                        </div>
                                        <div class="status-item">
                                            <strong>Смещение (offset):</strong> <span id="status-offset">0</span>
                                        </div>
                                        <div class="status-item">
                                            <strong>Ошибки:</strong> <span id="status-errors">0</span>
                                        </div>
                                        <div class="status-item">
                                            <strong>Пропущены дубликаты:</strong> <span id="status-duplicates">0</span>
                                        </div>
                                        <div class="status-item">
                                            <strong>Последняя активность:</strong> <span id="status-last-activity">-</span>
                                        </div>
                                        
                                        <div class="progress mt-3">
                                            <div id="collection-progress" class="progress-bar progress-bar-striped progress-bar-animated" 
                                                role="progressbar" style="width: 0%"></div>
                                        </div>
                                    </div>
                                    
                                    <div class="mt-3">
                                        <h5>Лог действий:</h5>
                                        <div id="collection-log" class="bg-dark text-light p-2" style="height: 200px; overflow-y: auto; font-family: monospace; font-size: 0.9rem;">
                                            Ожидание старта сбора...
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Здесь можно добавить дополнительные элементы управления -->
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('scripts')
<!-- Подключение jQuery (если не подключено в основном шаблоне) -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    let infiniteScrollActive = false;
    let statusInterval;
    let collectionInterval;
    let infiniteScrollInterval;
    let baseUrl = '';
    let currentPage = 1;
    let maxPages = 100000000; // Устанавливаем максимальное количество страниц для обработки

    // Запуск классического сбора (с использованием очереди URL)
    $('#startCollectionBtn').click(function() {
        const formData = new FormData(document.getElementById('continuousCollectionForm'));
        addLogEntry('Запуск классического сбора ссылок...');
        $.ajax({
            url: '{{ route("admin.parser.collect_links_continuous") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    $('#startCollectionBtn').hide();
                    $('#startInfiniteScrollBtn').hide();
                    $('#stopCollectionBtn').show();
                    updateStatus(response.status);
                    statusInterval = setInterval(fetchStatus, 3000);
                    collectionInterval = setInterval(processBatch, 30000);
                    addLogEntry('Классический сбор запущен. ' + response.message);
                } else {
                    addLogEntry('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                addLogEntry('Ошибка при запуске сбора: ' + xhr.responseText);
            }
        });
    });

    // Запуск infinite scroll сбора
    $('#startInfiniteScrollBtn').click(function() {
        const formData = new FormData(document.getElementById('continuousCollectionForm'));
        baseUrl = $('#url').val(); // Сохраняем базовый URL
        currentPage = 1; // Начинаем с первой страницы
        
        addLogEntry('Запуск infinite scroll сбора ссылок...');
        $.ajax({
            url: '{{ route("admin.parser.collect_links_continuous") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    infiniteScrollActive = true;
                    $('#startCollectionBtn').hide();
                    $('#startInfiniteScrollBtn').hide();
                    $('#stopCollectionBtn').show();
                    updateStatus(response.status);
                    statusInterval = setInterval(fetchStatus, 3000);
                    
                    // Запускаем автоматический infinite scroll с интервалом
                    infiniteScrollInterval = setInterval(function() {
                        if (infiniteScrollActive && currentPage < maxPages) {
                            processInfiniteScrollBatch();
                        } else {
                            clearInterval(infiniteScrollInterval);
                            addLogEntry('Infinite scroll: достигнуто максимальное количество страниц или сбор остановлен');
                        }
                    }, 5000); // Интервал 5 секунд между запросами
                    
                    addLogEntry('Infinite scroll сбор запущен. ' + response.message);
                } else {
                    addLogEntry('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                addLogEntry('Ошибка при запуске infinite scroll сбора: ' + xhr.responseText);
            }
        });
    });

    // Остановка сбора
    $('#stopCollectionBtn').click(function() {
        addLogEntry('Остановка сбора ссылок...');
        $.ajax({
            url: '{{ route("admin.parser.stop_collection") }}',
            type: 'POST',
            data: {_token: '{{ csrf_token() }}'},
            success: function(response) {
                if (response.success) {
                    clearInterval(collectionInterval);
                    clearInterval(statusInterval);
                    clearInterval(infiniteScrollInterval);
                    infiniteScrollActive = false;
                    $('#stopCollectionBtn').hide();
                    $('#startCollectionBtn').show();
                    $('#startInfiniteScrollBtn').show();
                    updateStatus(response.status);
                    addLogEntry('Сбор ссылок остановлен.');
                } else {
                    addLogEntry('Ошибка при остановке сбора: ' + response.message);
                }
            },
            error: function(xhr) {
                addLogEntry('Ошибка при остановке сбора: ' + xhr.responseText);
            }
        });
    });

    // Классический процесс обработки пакета
    function processBatch() {
        $.ajax({
            url: '{{ url("admin/parser/process-batch") }}',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    updateStatus(response.status);
                    addLogEntry(response.message);
                    // Если очередь пуста, останавливаем сбор
                    if (response.status.urls_queue && response.status.urls_queue.length === 0 && response.status.active) {
                        addLogEntry('Очередь URL пуста. Сбор завершен.');
                        $('#stopCollectionBtn').click();
                    }
                } else {
                    addLogEntry('Ошибка при обработке пакета: ' + response.message);
                }
            },
            error: function(xhr) {
                addLogEntry('Ошибка при обработке пакета: ' + xhr.responseText);
            }
        });
    }

    // Функция для вызова infinite scroll AJAX
    function processInfiniteScrollBatch() {
        $.ajax({
            url: '{{ route("admin.parser.process_infinite_scroll") }}',
            type: 'GET',
            data: { base_url: baseUrl },
            success: function(response) {
                if (response.success) {
                    currentPage = response.page || (currentPage + 1);
                    addLogEntry(response.message);
                    updateStatus(response.status);
                    
                    // Если достигли максимального числа страниц, останавливаем
                    if (currentPage >= maxPages) {
                        clearInterval(infiniteScrollInterval);
                        addLogEntry('Infinite scroll: достигнуто максимальное количество страниц (' + maxPages + ')');
                    }
                } else {
                    addLogEntry('Ошибка: ' + response.message);
                }
            },
            error: function(xhr) {
                addLogEntry('Ошибка при обработке infinite scroll: ' + xhr.responseText);
            }
        });
    }

    // Обработка infinite scroll при достижении конца контейнера лога (можете изменить селектор на контейнер с рецептами)
    $('#collection-log').on('scroll', function() {
        let container = $(this);
        if (infiniteScrollActive && container.scrollTop() + container.innerHeight() >= container[0].scrollHeight - 50) {
            processInfiniteScrollBatch();
        }
    });

    // Функция получения статуса
    function fetchStatus() {
        $.ajax({
            url: '{{ route("admin.parser.collection_status") }}',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    updateStatus(response.status);
                }
            }
        });
    }

    // Обновление элементов интерфейса статуса
    function updateStatus(status) {
        $('#status-active').text(status.active ? 'Активен' : 'Не активен')
            .removeClass('badge-secondary badge-success')
            .addClass(status.active ? 'badge-success' : 'badge-secondary');
        $('#status-total').text(status.total_collected);
        $('#status-current-url').text(status.current_url || '-');
        $('#status-offset').text(status.scroll_offset || 0);
        $('#status-errors').text(status.errors);
        $('#status-duplicates').text(status.duplicate_skipped);
        if (status.last_activity) {
            const lastActivityDate = new Date(status.last_activity * 1000);
            $('#status-last-activity').text(lastActivityDate.toLocaleTimeString());
        } else {
            $('#status-last-activity').text('-');
        }
        // Примерное обновление прогресс-бара
        if (status.active) {
            const progress = Math.floor(Math.random() * 100);
            $('#collection-progress').css('width', progress + '%');
        } else {
            $('#collection-progress').css('width', '0%');
        }
    }

    // Функция добавления записи в лог
    function addLogEntry(message) {
        const now = new Date();
        const timeStr = now.toLocaleTimeString();
        const logEntry = `[${timeStr}] ${message}`;
        const logElement = $('#collection-log');
        logElement.append('<div>' + logEntry + '</div>');
        logElement.scrollTop(logElement[0].scrollHeight);
    }
});
</script>
@endsection
