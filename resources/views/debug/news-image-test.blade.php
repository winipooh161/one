@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="card">
        <div class="card-header">
            <h5>Тестирование отображения изображений новостей</h5>
        </div>
        <div class="card-body">
            <h6>Тест отображения изображения первой новости</h6>
            @php
                $news = App\Models\News::first();
            @endphp
            
            @if($news)
                <div class="mb-3">
                    <h5>Информация о новости:</h5>
                    <p>ID: {{ $news->id }}<br>
                    Заголовок: {{ $news->title }}<br>
                    Путь к изображению: {{ $news->image_url }}</p>
                </div>
                
                <div class="mb-4 p-3 border">
                    <h6>1. Обычное отображение с атрибутом data-no-random:</h6>
                    <img src="{{ asset('storage/' . $news->image_url) }}" 
                         class="img-thumbnail mb-2" 
                         style="max-width: 300px;" 
                         alt="{{ $news->title }}"
                         data-no-random>
                    <p class="mt-2 text-muted">URL: {{ asset('storage/' . $news->image_url) }}</p>
                </div>
                
                <div class="mb-4 p-3 border">
                    <h6>2. Обычное отображение без атрибута data-no-random:</h6>
                    <img src="{{ asset('storage/' . $news->image_url) }}" 
                         class="img-thumbnail mb-2" 
                         style="max-width: 300px;" 
                         alt="{{ $news->title }}">
                    <p class="mt-2 text-muted">URL: {{ asset('storage/' . $news->image_url) }}</p>
                </div>
                
                <div class="mb-4 p-3 border">
                    <h6>3. Путь без префикса storage:</h6>
                    <img src="{{ asset($news->image_url) }}" 
                         class="img-thumbnail mb-2" 
                         style="max-width: 300px;" 
                         alt="{{ $news->title }}"
                         data-no-random>
                    <p class="mt-2 text-muted">URL: {{ asset($news->image_url) }}</p>
                </div>
                
                <div class="mb-4 p-3 border">
                    <h6>4. Проверка существования файла по разным путям:</h6>
                    <ul>
                        <li>Существует в storage/app/public/{{ $news->image_url }}? 
                            <strong>{{ file_exists(storage_path('app/public/' . $news->image_url)) ? 'Да' : 'Нет' }}</strong>
                        </li>
                        <li>Существует в public/storage/{{ $news->image_url }}? 
                            <strong>{{ file_exists(public_path('storage/' . $news->image_url)) ? 'Да' : 'Нет' }}</strong>
                        </li>
                        <li>Существует в public/{{ $news->image_url }}? 
                            <strong>{{ file_exists(public_path($news->image_url)) ? 'Да' : 'Нет' }}</strong>
                        </li>
                    </ul>
                </div>
            @else
                <div class="alert alert-warning">В базе данных не найдено ни одной новости.</div>
            @endif
        </div>
    </div>
</div>
@endsection
