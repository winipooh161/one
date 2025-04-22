@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Управление ссылками на рецепты</h6>
        </div>
        <div class="card-body">
            <!-- Аналитика ссылок -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                        Всего ссылок</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $analytics['total'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-link fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                        Уникальных ссылок</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $analytics['unique'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-check-circle fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                        Дубликатов</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $analytics['dublicates'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-copy fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info shadow h-100 py-2">
                        <div class="card-body">
                            <div class="row no-gutters align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                        Уже обработано</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800">{{ $analytics['processed'] }}</div>
                                </div>
                                <div class="col-auto">
                                    <i class="fas fa-clipboard-check fa-2x text-gray-300"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Инструменты управления ссылками -->
            <div class="row mb-4">
                <div class="col-md-12">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Инструменты очистки и обработки</h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <form action="{{ route('admin.parser.processLinks') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="remove_duplicates">
                                        <button type="submit" class="btn btn-warning btn-block">
                                            <i class="fas fa-broom mr-2"></i> Удалить дубликаты
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <form action="{{ route('admin.parser.processLinks') }}" method="POST">
                                        @csrf
                                        <input type="hidden" name="action" value="remove_existing">
                                        <button type="submit" class="btn btn-info btn-block">
                                            <i class="fas fa-filter mr-2"></i> Удалить существующие в БД
                                        </button>
                                    </form>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <form action="{{ route('admin.parser.processLinks') }}" method="POST" onsubmit="return confirm('Вы уверены, что хотите очистить все ссылки?');">
                                        @csrf
                                        <input type="hidden" name="action" value="clear">
                                        <button type="submit" class="btn btn-danger btn-block">
                                            <i class="fas fa-trash mr-2"></i> Очистить все ссылки
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Статистика по доменам -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Распределение по доменам</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-bordered" width="100%" cellspacing="0">
                                    <thead>
                                        <tr>
                                            <th>Домен</th>
                                            <th>Количество ссылок</th>
                                            <th>Процент</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($analytics['domains'] as $domain => $count)
                                            <tr>
                                                <td>{{ $domain }}</td>
                                                <td>{{ $count }}</td>
                                                <td>{{ round(($count / $analytics['total']) * 100, 2) }}%</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="col-md-6">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Статус обработки</h6>
                        </div>
                        <div class="card-body">
                            <div class="chart-pie pt-4 pb-2">
                                <canvas id="statusChart"></canvas>
                            </div>
                            <div class="mt-4 text-center small">
                                <span class="mr-2">
                                    <i class="fas fa-circle text-warning"></i> Ожидают обработки
                                </span>
                                <span class="mr-2">
                                    <i class="fas fa-circle text-success"></i> Уже обработаны
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Примеры ссылок из файла -->
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">Примеры ссылок в файле (первые 10)</h6>
                    <div class="dropdown no-arrow">
                        <a href="{{ route('admin.parser.collectedLinks') }}" class="btn btn-sm btn-primary">
                            Просмотреть все ссылки
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="list-group">
                        @if(count($links) > 0)
                            @foreach(array_slice($links, 0, 10) as $link)
                                <div class="list-group-item d-flex justify-content-between align-items-center">
                                    <a href="{{ $link['url'] }}" target="_blank" class="text-truncate" style="max-width: 70%">
                                        {{ $link['url'] }}
                                    </a>
                                    <span class="badge badge-{{ $link['status'] == 'processed' ? 'success' : 'warning' }} badge-pill">
                                        {{ $link['status'] == 'processed' ? 'Обработана' : 'Ожидает' }}
                                    </span>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-3">
                                <p>Нет ссылок в файле</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>
@endsection
@section('scripts')
<script src="https://cdn.jsdelivr.net/npm/chart.js@2.9.4/dist/Chart.min.js"></script>
<script>
// Статус обработки - Pie Chart
var ctx = document.getElementById("statusChart");
var statusChart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ["Ожидают обработки", "Уже обработаны"],
        datasets: [{
            data: [
                {{ $analytics['by_status']['pending'] }}, 
                {{ $analytics['by_status']['processed'] }}
            ],
            backgroundColor: ['#f6c23e', '#1cc88a'],
            hoverBackgroundColor: ['#f4b619', '#17a673'],
            hoverBorderColor: "rgba(234, 236, 244, 1)",
        }],
    },
    options: {
        maintainAspectRatio: false,
        tooltips: {
            backgroundColor: "rgb(255,255,255)",
            bodyFontColor: "#858796",
            borderColor: '#dddfeb',
            borderWidth: 1,
            xPadding: 15,
            yPadding: 15,
            displayColors: false,
            caretPadding: 10,
        },
        legend: {
            display: false
        },
        cutoutPercentage: 70,
    },
});
</script>
@endsection
