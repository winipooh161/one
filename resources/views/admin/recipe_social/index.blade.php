@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Публикация рецептов в социальные сети</h3>
                </div>
                <div class="card-body">
                    <ul class="nav nav-tabs" id="myTab" role="tablist">
                        <li class="nav-item" role="presentation">
                            <a class="nav-link active" id="unpublished-tab" data-toggle="tab" href="#unpublished" role="tab" aria-controls="unpublished" aria-selected="true">
                                Неопубликованные рецепты
                            </a>
                        </li>
                        <li class="nav-item" role="presentation">
                            <a class="nav-link" id="published-tab" data-toggle="tab" href="#published" role="tab" aria-controls="published" aria-selected="false">
                                Опубликованные рецепты
                            </a>
                        </li>
                    </ul>
                    <div class="tab-content" id="myTabContent">
                        <div class="tab-pane fade show active" id="unpublished" role="tabpanel" aria-labelledby="unpublished-tab">
                            <div class="table-responsive mt-4">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Название</th>
                                            <th>Категории</th>
                                            <th>Дата создания</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($recipes as $recipe)
                                            <tr>
                                                <td>{{ $recipe->id }}</td>
                                                <td>{{ $recipe->title }}</td>
                                                <td>
                                                    @foreach($recipe->categories as $category)
                                                        <span class="badge bg-primary">{{ $category->name }}</span>
                                                    @endforeach
                                                </td>
                                                <td>{{ $recipe->created_at->format('d.m.Y H:i') }}</td>
                                                <td>
                                                    <div class="btn-group">
                                                        <a href="{{ route('admin.recipe-social.preview', $recipe->id) }}" class="btn btn-sm btn-primary">
                                                            <i class="fas fa-eye"></i> Предпросмотр
                                                        </a>
                                                        <div class="btn-group">
                                                            <button type="button" class="btn btn-sm btn-secondary dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
                                                                <i class="fas fa-share-alt"></i> Опубликовать
                                                            </button>
                                                            <div class="dropdown-menu">
                                                                <form action="{{ route('admin.recipes.social.telegram', $recipe->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fab fa-telegram text-info"></i> В Telegram
                                                                    </button>
                                                                </form>
                                                                <form action="{{ route('admin.recipes.social.vk', $recipe->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fab fa-vk text-primary"></i> Во ВКонтакте
                                                                    </button>
                                                                </form>
                                                                @if(isset($zenEnabled) && $zenEnabled)
                                                                <form action="{{ route('admin.recipes.social.zen', $recipe->id) }}" method="POST" class="d-inline">
                                                                    @csrf
                                                                    <button type="submit" class="dropdown-item">
                                                                        <i class="fas fa-rss text-warning"></i> В Дзен
                                                                    </button>
                                                                </form>
                                                                @endif
                                                            </div>
                                                        </div>
                                                    </div>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">Нет неопубликованных рецептов</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                                
                                <div class="mt-3">
                                    {{ $recipes->links() }}
                                </div>
                            </div>
                        </div>
                        <div class="tab-pane fade" id="published" role="tabpanel" aria-labelledby="published-tab">
                            <div class="table-responsive mt-4">
                                <table class="table table-bordered table-striped">
                                    <thead>
                                        <tr>
                                            <th>ID</th>
                                            <th>Название</th>
                                            <th>Платформы</th>
                                            <th>Дата публикации</th>
                                            <th>Действия</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse($publishedRecipes as $recipe)
                                            <tr>
                                                <td>{{ $recipe->id }}</td>
                                                <td>{{ $recipe->title }}</td>
                                                <td>
                                                    @foreach($recipe->socialPosts as $post)
                                                        @if($post->telegram_status)
                                                            <span class="badge bg-info">
                                                                <i class="fab fa-telegram"></i> Telegram
                                                            </span>
                                                        @endif
                                                        @if($post->vk_status)
                                                            <span class="badge bg-primary">
                                                                <i class="fab fa-vk"></i> ВКонтакте
                                                            </span>
                                                        @endif
                                                        @if($post->platform == 'zen')
                                                            <span class="badge bg-warning">
                                                                <i class="fas fa-rss"></i> Дзен
                                                            </span>
                                                        @endif
                                                    @endforeach
                                                </td>
                                                <td>
                                                    @if($recipe->socialPosts->isNotEmpty())
                                                        {{ $recipe->socialPosts->sortByDesc('created_at')->first()->created_at->format('d.m.Y H:i') }}
                                                    @else
                                                        -
                                                    @endif
                                                </td>
                                                <td>
                                                    <a href="{{ route('recipes.show', $recipe->slug) }}" class="btn btn-sm btn-info" target="_blank">
                                                        <i class="fas fa-external-link-alt"></i> Смотреть на сайте
                                                    </a>
                                                </td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-center">Нет опубликованных рецептов</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
