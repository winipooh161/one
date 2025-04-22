@extends('admin.layouts.app')

@section('title', 'Управление фидами')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Управление XML-фидами</h3>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        XML-фиды используются для экспорта данных в поисковые системы, агрегаторы и другие сервисы.
                        Здесь вы можете управлять различными фидами вашего сайта.
                    </p>
                    
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif
                    
                    @if(session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif
                    
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Название</th>
                                    <th>Описание</th>
                                    <th>Статус</th>
                                    <th>Последнее обновление</th>
                                    <th>Размер файла</th>
                                    <th>Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($feeds as $feed)
                                <tr>
                                    <td>{{ $feed['name'] }}</td>
                                    <td>{{ $feed['description'] }}</td>
                                    <td>
                                        @if($feed['file_exists'])
                                            <span class="badge bg-success">Файл создан</span>
                                        @else
                                            <span class="badge bg-warning">Динамический</span>
                                        @endif
                                    </td>
                                    <td>{{ $feed['last_updated'] }}</td>
                                    <td>{{ $feed['file_size'] }}</td>
                                    <td class="d-flex">
                                        <a href="{{ $feed['url'] }}" target="_blank" class="btn btn-sm btn-info me-2">
                                            <i class="fas fa-eye"></i> Просмотр
                                        </a>
                                        <form action="{{ $feed['refresh_route'] }}" method="POST">
                                            @csrf
                                            <button type="submit" class="btn btn-sm btn-primary">
                                                <i class="fas fa-sync-alt"></i> Обновить
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
