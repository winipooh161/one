@extends('admin.layouts.app')

@section('title', 'Управление новостями')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Управление новостями</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <div class="btn-group me-2">
                <a href="{{ route('admin.articles.create') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fas fa-plus"></i> Создать новость
                </a>
            </div>
        </div>
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

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Изображение</th>
                    <th>Заголовок</th>
                    <th>Тип</th>
                    <th>Статус</th>
                    <th>Дата публикации</th>
                    <th>Автор</th>
                    <th>Действия</th>
                </tr>
            </thead>
            <tbody>
                @forelse($articles as $article)
                <tr>
                    <td>{{ $article->id }}</td>
                    <td>
                        @if($article->image)
                            <img src="{{ asset('storage/' . str_replace('articles/', 'articles/thumb_', $article->image)) }}" 
                                 alt="{{ $article->title }}" class="img-thumbnail" style="max-width: 80px;">
                        @else
                            <span class="text-muted">Нет изображения</span>
                        @endif
                    </td>
                    <td>
                        <strong>{{ Str::limit($article->title, 50) }}</strong>
                        @if($article->categories->count() > 0)
                            <div class="small text-muted mt-1">
                                @foreach($article->categories as $category)
                                    <span class="badge bg-secondary">{{ $category->name }}</span>
                                @endforeach
                            </div>
                        @endif
                    </td>
                    <td>
                        @switch($article->type)
                            @case('news')
                                <span class="badge bg-info">Новость</span>
                                @break
                            @case('article')
                                <span class="badge bg-primary">Статья</span>
                                @break
                            @case('guide')
                                <span class="badge bg-success">Руководство</span>
                                @break
                            @default
                                <span class="badge bg-secondary">{{ $article->type }}</span>
                        @endswitch
                    </td>
                    <td>
                        @if($article->status === 'published')
                            <span class="badge bg-success">Опубликовано</span>
                        @else
                            <span class="badge bg-warning text-dark">Черновик</span>
                        @endif
                    </td>
                    <td>{{ $article->published_at ? $article->published_at->format('d.m.Y H:i') : 'Не опубликовано' }}</td>
                    <td>{{ $article->user ? $article->user->name : 'Система' }}</td>
                    <td>
                        <div class="btn-group" role="group">
                            <a href="{{ route('articles.show', $article->slug) }}" class="btn btn-sm btn-outline-primary" target="_blank" title="Просмотреть на сайте">
                                <i class="fas fa-eye"></i>
                            </a>
                            <a href="{{ route('admin.articles.edit', $article) }}" class="btn btn-sm btn-outline-secondary" title="Редактировать">
                                <i class="fas fa-edit"></i>
                            </a>
                            <form action="{{ route('admin.articles.destroy', $article) }}" method="POST" class="d-inline" onsubmit="return confirm('Вы уверены, что хотите удалить эту новость?');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger" title="Удалить">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center py-4">
                        <div class="alert alert-info mb-0">
                            Новости не найдены. <a href="{{ route('admin.articles.create') }}">Создать новую?</a>
                        </div>
                    </td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="d-flex justify-content-center mt-4">
        {{ $articles->links() }}
    </div>
</div>
@endsection
