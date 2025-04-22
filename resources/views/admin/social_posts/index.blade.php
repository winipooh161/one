@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Посты для социальных сетей</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.social-posts.create') }}" class="btn btn-primary btn-sm">
                            <i class="fas fa-plus"></i> Создать новый пост
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th width="5%">ID</th>
                                    <th width="35%">Заголовок</th>
                                    <th width="20%">ВКонтакте</th>
                                    <th width="20%">Телеграм</th>
                                    <th width="10%">Создан</th>
                                    <th width="10%">Действия</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($posts as $post)
                                <tr>
                                    <td>{{ $post->id }}</td>
                                    <td>{{ $post->title }}</td>
                                    <td>
                                        @if($post->isPublishedToVk())
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Опубликовано {{ $post->vk_posted_at->format('d.m.Y H:i') }}
                                            </span>
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Не опубликовано
                                            </span>
                                            <form method="POST" action="{{ route('admin.social-posts.publish-vk', $post->id) }}" class="d-inline mt-1">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-primary">
                                                    <i class="fab fa-vk"></i> Опубликовать
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>
                                        @if($post->isPublishedToTelegram())
                                            <span class="badge badge-success">
                                                <i class="fas fa-check"></i> Опубликовано {{ $post->telegram_posted_at->format('d.m.Y H:i') }}
                                            </span>
                                        @else
                                            <span class="badge badge-warning">
                                                <i class="fas fa-clock"></i> Не опубликовано
                                            </span>
                                            <form method="POST" action="{{ route('admin.social-posts.publish-telegram', $post->id) }}" class="d-inline mt-1">
                                                @csrf
                                                <button type="submit" class="btn btn-sm btn-info">
                                                    <i class="fab fa-telegram"></i> Опубликовать
                                                </button>
                                            </form>
                                        @endif
                                    </td>
                                    <td>{{ $post->created_at->format('d.m.Y H:i') }}</td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="{{ route('admin.social-posts.edit', $post->id) }}" class="btn btn-sm btn-warning">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" action="{{ route('admin.social-posts.destroy', $post->id) }}" class="d-inline" onsubmit="return confirm('Вы уверены? Это действие невозможно отменить.')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-danger">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="6" class="text-center">Постов еще нет. <a href="{{ route('admin.social-posts.create') }}">Создать первый пост</a></td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    
                    <div class="mt-3">
                        {{ $posts->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
