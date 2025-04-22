@extends('admin.layouts.app')

@section('content')
<div class="container">
    <h1 class="mb-4 h2"><i class="fas fa-check-circle text-primary me-2"></i> Модерация рецептов</h1>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show">
            <button type="button" class="close" data-dismiss="alert">&times;</button>
            {{ session('success') }}
        </div>
    @endif

    <div class="card mb-4">
        <div class="card-header bg-white">
            <h5 class="mb-0">Ожидают проверки: {{ $recipes->total() }}</h5>
        </div>
        <div class="card-body p-0">
            @if($recipes->count() > 0)
                <div class="table-responsive">
                    <table class="table table-striped table-hover table-responsive-sm mb-0">
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Фото</th>
                                <th>Название</th>
                                <th>Автор</th>
                                <th>Дата</th>
                                <th>Действия</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recipes as $recipe)
                            <tr>
                                <td data-label="ID">{{ $recipe->id }}</td>
                                <td data-label="Фото">
                                    <img src="{{ $recipe->getImageUrl() }}" alt="{{ $recipe->title }}" 
                                         class="img-thumbnail" style="max-width: 60px; max-height: 60px;">
                                </td>
                                <td data-label="Название">
                                    <strong>{{ $recipe->title }}</strong>
                                    <div class="small text-muted">{{ Str::limit($recipe->description, 40) }}</div>
                                </td>
                                <td data-label="Автор">{{ $recipe->user->name ?? 'Н/Д' }}</td>
                                <td data-label="Дата">{{ $recipe->created_at->format('d.m.Y') }}</td>
                                <td data-label="Действия">
                                    <div class="action-buttons">
                                        <a href="{{ route('admin.moderation.show', $recipe->id) }}" 
                                           class="btn btn-sm btn-info mb-1 me-1">
                                           <i class="fas fa-eye"></i><span class="d-none d-md-inline"> Просмотр</span>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-success mb-1 me-1 btn-approve" 
                                                data-id="{{ $recipe->id }}">
                                           <i class="fas fa-check"></i><span class="d-none d-md-inline"> Одобрить</span>
                                        </button>
                                        <button type="button" class="btn btn-sm btn-danger mb-1 btn-reject" 
                                                data-id="{{ $recipe->id }}">
                                           <i class="fas fa-times"></i><span class="d-none d-md-inline"> Отклонить</span>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="card-footer">
                    <div class="d-flex justify-content-center">
                        {{ $recipes->links() }}
                    </div>
                </div>
            @else
                <div class="alert alert-info mb-0 rounded-0">
                    <i class="fas fa-info-circle me-2"></i> Нет рецептов для модерации.
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Модальное окно для отклонения рецепта -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="rejectModalLabel">Отклонить рецепт</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form id="rejectForm">
                    <div class="form-group">
                        <label for="rejection_reason">Причина отклонения:</label>
                        <textarea class="form-control" id="rejection_reason" name="rejection_reason" rows="3" required></textarea>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">Отмена</button>
                <button type="button" class="btn btn-danger" id="confirmReject">Отклонить</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    $(document).ready(function() {
        // Обработка нажатия на кнопку "Отклонить"
        $('.btn-reject').click(function() {
            var recipeId = $(this).data('id');
            $('#rejectForm').attr('action', '/admin/moderation/' + recipeId + '/reject');
            $('#rejectModal').modal('show');
        });
        
        // Обработка нажатия на кнопку "Одобрить"
        $('.btn-approve').click(function() {
            var recipeId = $(this).data('id');
            if(confirm('Вы уверены, что хотите одобрить этот рецепт?')) {
                window.location.href = '/admin/moderation/' + recipeId + '/approve';
            }
        });
        
        // Оптимизация модального окна для мобильных устройств
        function adjustModalForMobile() {
            if (window.innerWidth < 768) {
                $('.modal-dialog').css({
                    'max-width': '95%',
                    'margin': '10px auto'
                });
            } else {
                $('.modal-dialog').css({
                    'max-width': '500px',
                    'margin': '1.75rem auto'
                });
            }
        }
        
        // Вызываем функцию при загрузке и изменении размера окна
        adjustModalForMobile();
        $(window).on('resize', adjustModalForMobile);
        
        // Обработка отправки формы отклонения
        $('#confirmReject').click(function() {
            var reason = $('#rejection_reason').val();
            if (!reason.trim()) {
                alert('Пожалуйста, укажите причину отклонения');
                return;
            }
            
            var form = $('#rejectForm');
            var action = form.attr('action');
            
            $.ajax({
                url: action,
                type: 'POST',
                data: {
                    '_token': '{{ csrf_token() }}',
                    'reason': reason
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                },
                error: function() {
                    alert('Произошла ошибка при отправке запроса');
                }
            });
        });
    });
</script>
@endpush
@endsection
