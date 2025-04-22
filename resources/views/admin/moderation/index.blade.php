@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header bg-primary text-white">
                    <h3 class="card-title">
                        <i class="fas fa-tasks mr-2"></i> Модерация рецептов
                    </h3>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="small-box bg-warning">
                                <div class="inner">
                                    <h3>{{ $stats['pending'] }}</h3>
                                    <p>Ожидают модерации</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-clock"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-success">
                                <div class="inner">
                                    <h3>{{ $stats['approved_today'] }}</h3>
                                    <p>Одобрено сегодня</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-check"></i>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="small-box bg-danger">
                                <div class="inner">
                                    <h3>{{ $stats['rejected_today'] }}</h3>
                                    <p>Отклонено сегодня</p>
                                </div>
                                <div class="icon">
                                    <i class="fas fa-times"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Рецепты, требующие модерации</h3>
                    <div class="card-tools">
                        <button type="button" class="btn btn-tool" data-card-widget="collapse">
                            <i class="fas fa-minus"></i>
                        </button>
                    </div>
                </div>
                <div class="card-body">
                    <!-- Фильтры -->
                    <form action="{{ route('admin.moderation.index') }}" method="GET" class="mb-4">
                        <div class="row">
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Поиск:</label>
                                    <input type="text" name="search" class="form-control" placeholder="Название или описание..." value="{{ request('search') }}">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="form-group">
                                    <label>Автор:</label>
                                    <select name="user_id" class="form-control">
                                        <option value="">Все пользователи</option>
                                        @foreach($users as $user)
                                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>{{ $user->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Дата от:</label>
                                    <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Дата до:</label>
                                    <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="form-group">
                                    <label>Сортировка:</label>
                                    <select name="sort" class="form-control">
                                        <option value="newest" {{ request('sort') == 'newest' ? 'selected' : '' }}>Сначала новые</option>
                                        <option value="oldest" {{ request('sort') == 'oldest' ? 'selected' : '' }}>Сначала старые</option>
                                        <option value="title_asc" {{ request('sort') == 'title_asc' ? 'selected' : '' }}>По названию (А-Я)</option>
                                        <option value="title_desc" {{ request('sort') == 'title_desc' ? 'selected' : '' }}>По названию (Я-А)</option>
                                    </select>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-search"></i> Применить фильтры
                                </button>
                                <a href="{{ route('admin.moderation.index') }}" class="btn btn-default">
                                    <i class="fas fa-redo"></i> Сбросить
                                </a>
                            </div>
                        </div>
                    </form>

                    <!-- Массовые действия -->
                    <form id="bulk-action-form" action="" method="POST">
                        @csrf
                        
                        @if($recipes->isNotEmpty())
                        <div class="mb-3">
                            <button type="button" class="btn btn-success btn-bulk" data-action="approve">
                                <i class="fas fa-check"></i> Одобрить выбранные
                            </button>
                            <button type="button" class="btn btn-danger btn-bulk" data-action="reject">
                                <i class="fas fa-times"></i> Отклонить выбранные
                            </button>
                            <button type="button" class="btn btn-secondary" id="toggle-all">
                                <i class="fas fa-check-square"></i> Выбрать все
                            </button>
                        </div>
                        @endif

                        <!-- Таблица рецептов -->
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead>
                                    <tr>
                                        <th width="40px"></th>
                                        <th width="80px">Фото</th>
                                        <th>Название</th>
                                        <th>Автор</th>
                                        <th>Категории</th>
                                        <th>Дата создания</th>
                                        <th width="200px">Действия</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($recipes as $recipe)
                                    <tr>
                                        <td>
                                            <div class="icheck-primary">
                                                <input type="checkbox" name="recipe_ids[]" value="{{ $recipe->id }}" id="recipe_{{ $recipe->id }}">
                                                <label for="recipe_{{ $recipe->id }}"></label>
                                            </div>
                                        </td>
                                        <td>
                                            <img src="{{ $recipe->getImageUrl() }}" alt="{{ $recipe->title }}" class="img-thumbnail" style="max-height: 50px;">
                                        </td>
                                        <td>
                                            {{ $recipe->title }}
                                            <div class="text-muted small">{{ Str::limit($recipe->description, 50) }}</div>
                                        </td>
                                        <td>
                                            @if($recipe->user)
                                                <a href="{{ route('admin.moderation.index', ['user_id' => $recipe->user->id]) }}">
                                                    {{ $recipe->user->name }}
                                                </a>
                                            @else
                                                <span class="text-muted">Нет автора</span>
                                            @endif
                                        </td>
                                        <td>
                                            @forelse($recipe->categories as $category)
                                                <span class="badge badge-info">{{ $category->name }}</span>
                                            @empty
                                                <span class="text-muted">Нет категорий</span>
                                            @endforelse
                                        </td>
                                        <td>
                                            {{ $recipe->created_at->format('d.m.Y H:i') }}
                                            <div class="text-muted small">{{ $recipe->created_at->diffForHumans() }}</div>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <a href="{{ route('admin.moderation.show', $recipe->id) }}" class="btn btn-info btn-sm">
                                                    <i class="fas fa-eye"></i> Просмотр
                                                </a>
                                                <button type="button" class="btn btn-success btn-sm btn-approve" data-id="{{ $recipe->id }}" data-title="{{ $recipe->title }}">
                                                    <i class="fas fa-check"></i> Одобрить
                                                </button>
                                                <button type="button" class="btn btn-danger btn-sm btn-reject" data-id="{{ $recipe->id }}" data-title="{{ $recipe->title }}">
                                                    <i class="fas fa-times"></i> Отклонить
                                                </button>
                                            </div>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center">
                                            <div class="alert alert-info mb-0">
                                                <i class="fas fa-info-circle mr-2"></i> Нет рецептов, требующих модерации
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </form>
                    
                    <!-- Пагинация -->
                    <div class="mt-4">
                        {{ $recipes->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Модальное окно одобрения -->
    <div class="modal fade" id="approveModal" tabindex="-1" role="dialog" aria-labelledby="approveModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="" method="POST" id="approve-form">
                    @csrf
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title" id="approveModalLabel">Одобрение рецепта</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы собираетесь одобрить рецепт <strong id="approve-recipe-title"></strong>. Рецепт будет опубликован на сайте.</p>
                        
                        <div class="form-group">
                            <label for="edit-title">Название рецепта:</label>
                            <input type="text" class="form-control" id="edit-title" name="title">
                        </div>
                        
                        <div class="form-group">
                            <label for="edit-description">Описание:</label>
                            <textarea class="form-control" id="edit-description" name="description" rows="3"></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-success">Одобрить и опубликовать</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно отклонения -->
    <div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <form action="" method="POST" id="reject-form">
                    @csrf
                    @method('DELETE')
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title" id="rejectModalLabel">Отклонение рецепта</h5>
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <div class="modal-body">
                        <p>Вы собираетесь отклонить рецепт <strong id="reject-recipe-title"></strong>. Рецепт будет удален и не будет опубликован на сайте.</p>
                        
                        <div class="form-group">
                            <label for="reject-reason">Причина отклонения:</label>
                            <textarea class="form-control" id="reject-reason" name="reason" rows="3" required placeholder="Укажите причину отклонения рецепта. Эта информация будет отправлена автору."></textarea>
                            <small class="form-text text-muted">Причина отклонения будет отправлена автору рецепта.</small>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                        <button type="submit" class="btn btn-danger">Отклонить рецепт</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <!-- Модальное окно массового отклонения -->
    <div class="modal fade" id="bulkRejectModal" tabindex="-1" role="dialog" aria-labelledby="bulkRejectModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
                <div class="modal-header bg-danger text-white">
                    <h5 class="modal-title" id="bulkRejectModalLabel">Массовое отклонение рецептов</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <p>Вы собираетесь отклонить выбранные рецепты. Выбранные рецепты будут удалены и не будут опубликованы на сайте.</p>
                    
                    <div class="form-group">
                        <label for="bulk-reject-reason">Причина отклонения:</label>
                        <textarea class="form-control" id="bulk-reject-reason" name="bulk_reason" rows="3" required placeholder="Укажите причину отклонения рецептов. Эта информация будет отправлена авторам."></textarea>
                        <small class="form-text text-muted">Причина отклонения будет отправлена авторам рецептов.</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                    <button type="button" class="btn btn-danger" id="confirm-bulk-reject">Отклонить выбранные рецепты</button>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function() {
        // Индивидуальное одобрение
        $('.btn-approve').on('click', function() {
            var id = $(this).data('id');
            var title = $(this).data('title');
            
            $('#approve-recipe-title').text(title);
            $('#approve-form').attr('action', '{{ url("admin/moderation") }}/' + id + '/approve');
            $('#edit-title').val(title);
            
            // Загрузка описания через AJAX
            $.get('{{ url("admin/moderation") }}/' + id, function(data) {
                $('#edit-description').val(data.description);
            });
            
            $('#approveModal').modal('show');
        });
        
        // Индивидуальное отклонение
        $('.btn-reject').on('click', function() {
            var id = $(this).data('id');
            var title = $(this).data('title');
            
            $('#reject-recipe-title').text(title);
            $('#reject-form').attr('action', '{{ url("admin/moderation") }}/' + id + '/reject');
            
            $('#rejectModal').modal('show');
        });
        
        // Массовые действия
        $('.btn-bulk').on('click', function() {
            var action = $(this).data('action');
            var count = $('input[name="recipe_ids[]"]:checked').length;
            
            if (count === 0) {
                alert('Пожалуйста, выберите хотя бы один рецепт');
                return;
            }
            
            if (action === 'approve') {
                if (confirm('Вы уверены, что хотите одобрить ' + count + ' выбранных рецептов?')) {
                    $('#bulk-action-form').attr('action', '{{ route("admin.moderation.bulk-approve") }}');
                    $('#bulk-action-form').submit();
                }
            } else if (action === 'reject') {
                $('#bulkRejectModal').modal('show');
            }
        });
        
        // Подтверждение массового отклонения
        $('#confirm-bulk-reject').on('click', function() {
            var reason = $('#bulk-reject-reason').val();
            
            if (reason.length < 10) {
                alert('Пожалуйста, укажите причину отклонения (не менее 10 символов)');
                return;
            }
            
            $('#bulk-action-form').attr('action', '{{ route("admin.moderation.bulk-reject") }}');
            $('#bulk-action-form').append('<input type="hidden" name="bulk_reason" value="' + reason + '">');
            $('#bulk-action-form').submit();
        });
        
        // Выбрать все / Снять выбор
        $('#toggle-all').on('click', function() {
            var checkboxes = $('input[name="recipe_ids[]"]');
            var isChecked = checkboxes.first().prop('checked');
            
            checkboxes.prop('checked', !isChecked);
            $(this).find('i').toggleClass('fa-check-square fa-square');
        });
    });
</script>
@endpush
