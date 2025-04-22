@extends('admin.layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    Собранные ссылки на рецепты
                    <span class="badge badge-primary">{{ $linksCount ?? 0 }}</span>
                </div>

                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if (session('error'))
                        <div class="alert alert-danger">
                            {{ session('error') }}
                        </div>
                    @endif

                    <div class="mb-4">
                        <a href="{{ route('admin.parser.collect_links') }}" class="btn btn-secondary">Назад к форме сбора</a>
                        <form method="POST" action="{{ route('admin.parser.remove_duplicate_links') }}" class="d-inline">
                            @csrf
                            <button type="submit" class="btn btn-warning">Удалить дубликаты</button>
                        </form>
                        <form method="POST" action="{{ route('admin.parser.clear_links_file') }}" class="d-inline" onsubmit="return confirm('Вы уверены, что хотите удалить все ссылки?');">
                            @csrf
                            <button type="submit" class="btn btn-danger">Очистить все ссылки</button>
                        </form>
                    </div>

                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>URL</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($links as $index => $link)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>
                                            <a href="{{ $link }}" target="_blank">{{ $link }}</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="2" class="text-center">Ссылки не найдены</td>
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
@endsection
