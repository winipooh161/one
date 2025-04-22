@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Результаты проверки URL</h1>
    </div>

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

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Информация о проверенных URL</h6>
        </div>
        <div class="card-body">
            @if(isset($message))
                <div class="alert alert-info">
                    {{ $message }}
                </div>
            @endif

            @if(isset($total_urls) && $total_urls > 0)
                <div class="alert alert-success">
                    Готово к обработке: {{ $total_urls }} URL
                </div>
            @elseif(request()->has('total_urls') && request()->get('total_urls') > 0)
                <div class="alert alert-success">
                    Готово к обработке: {{ request()->get('total_urls') }} URL
                </div>
            @endif

            @if(isset($failed) && count($failed) > 0)
                <div class="alert alert-warning">
                    <h5>Не удалось обработать следующие URL:</h5>
                    <ul>
                        @foreach($failed as $fail)
                            <li>{{ $fail['url'] }} - {{ $fail['error'] }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Кнопки для продолжения или отмены процесса -->
            <div class="d-flex justify-content-between">
                <a href="{{ route('admin.parser.batch') }}" class="btn btn-secondary">Вернуться</a>
                @if((isset($total_urls) && $total_urls > 0) || (request()->has('total_urls') && request()->get('total_urls') > 0))
                    <a href="{{ route('admin.parser.processBatch') }}" class="btn btn-primary">
                        Начать обработку {{ isset($total_urls) ? $total_urls : request()->get('total_urls') }} URL
                    </a>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
