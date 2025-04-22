@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-between mb-4">
        <div class="col-md-6 mb-3 mb-md-0">
            <h1 class="h2">Управление категориями</h1>
        </div>
        <div class="col-md-6 text-md-end">
            <a href="{{ route('admin.categories.create') }}" class="btn btn-primary">
                <i class="fas fa-plus-circle me-1"></i> Добавить категорию
            </a>
        </div>
    </div>

    @if(session('success'))
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        {{ session('success') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    @endif

    <div class="card">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-striped table-hover table-responsive-sm mb-0">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Название</th>
                            <th>Slug</th>
                            <th>Рецептов</th>
                            <th>Действия</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($categories as $category)
                        <tr>
                            <td data-label="ID">{{ $category->id }}</td>
                            <td data-label="Название">{{ $category->name }}</td>
                            <td data-label="Slug">
                                <span class="d-inline-block text-truncate" style="max-width: 150px;">
                                    {{ $category->slug }}
                                </span>
                            </td>
                            <td data-label="Рецептов">{{ $category->recipes_count }}</td>
                            <td data-label="Действия">
                                <div class="action-buttons">
                                    <a href="{{ route('categories.show', $category->slug) }}" 
                                       class="btn btn-sm btn-info mb-1 me-1" target="_blank">
                                       <i class="fas fa-eye"></i><span class="d-none d-md-inline"> Просмотр</span>
                                    </a>
                                    <a href="{{ route('admin.categories.edit', $category->id) }}" 
                                       class="btn btn-sm btn-primary mb-1 me-1">
                                       <i class="fas fa-edit"></i><span class="d-none d-md-inline"> Редактировать</span>
                                    </a>
                                    <form action="{{ route('admin.categories.destroy', $category->id) }}" 
                                          method="POST" 
                                          onsubmit="return confirm('Вы уверены, что хотите удалить эту категорию?');"
                                          class="d-inline-block mb-1">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-danger">
                                            <i class="fas fa-trash"></i><span class="d-none d-md-inline"> Удалить</span>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            
            <div class="d-flex justify-content-center p-3">
                {{ $categories->links() }}
            </div>
        </div>
    </div>
</div>
@endsection
