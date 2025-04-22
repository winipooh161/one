@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-3">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">Просмотр рецепта для модерации: {{ $recipe->title }}</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.moderation.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Назад к списку
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            @if($recipe->image)
                                <img src="{{ Storage::url($recipe->image) }}" alt="{{ $recipe->title }}" class="img-fluid mb-3">
                            @else
                                <div class="alert alert-info">Изображение отсутствует</div>
                            @endif
                            
                            <h4>Основная информация</h4>
                            <dl class="row">
                                <dt class="col-sm-4">Автор:</dt>
                                <dd class="col-sm-8">{{ $recipe->user->name }}</dd>
                                
                                <dt class="col-sm-4">Категория:</dt>
                                <dd class="col-sm-8">{{ $recipe->category ? $recipe->category->name : 'Не указана' }}</dd>
                                
                                <dt class="col-sm-4">Время приготовления:</dt>
                                <dd class="col-sm-8">{{ $recipe->cooking_time }} мин.</dd>
                                
                                <dt class="col-sm-4">Сложность:</dt>
                                <dd class="col-sm-8">{{ $recipe->difficulty }}</dd>
                                
                                <dt class="col-sm-4">Кол-во порций:</dt>
                                <dd class="col-sm-8">{{ $recipe->servings }}</dd>
                                
                                <dt class="col-sm-4">Дата создания:</dt>
                                <dd class="col-sm-8">{{ $recipe->created_at->format('d.m.Y H:i') }}</dd>
                            </dl>
                        </div>
                        <div class="col-md-6">
                            <h4>Описание</h4>
                            <div class="p-3 bg-light mb-3">
                                {!! nl2br(e($recipe->description)) !!}
                            </div>
                            
                            <h4>Ингредиенты</h4>
                            <ul class="list-group mb-3">
                                @if(is_string($recipe->ingredients))
                                    @foreach(explode("\n", $recipe->ingredients) as $ingredient)
                                        <li class="list-group-item">
                                            {{ $ingredient }}
                                        </li>
                                    @endforeach
                                @else
                                    @foreach($recipe->ingredients as $ingredient)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            {{ $ingredient->name }}
                                            <span class="badge bg-primary">{{ $ingredient->pivot->quantity }} {{ $ingredient->pivot->unit }}</span>
                                        </li>
                                    @endforeach
                                @endif
                            </ul>
                            
                            <!-- Добавляем блок инструкций -->
                            <h4>Инструкции</h4>
                            <div class="p-3 bg-light mb-3">
                                @if(is_string($recipe->instructions))
                                    {!! nl2br(e($recipe->instructions)) !!}
                                @elseif($recipe->steps && $recipe->steps->count() > 0)
                                    <ol class="list-group list-group-numbered">
                                        @foreach($recipe->steps as $step)
                                            <li class="list-group-item">
                                                {{ $step->content }}
                                            </li>
                                        @endforeach
                                    </ol>
                                @else
                                    <p class="text-danger">Инструкции отсутствуют. Это поле обязательно для одобрения рецепта.</p>
                                @endif
                            </div>
                        </div>
                    </div>
                    
                    <h4 class="mt-4">Шаги приготовления</h4>
                    <div class="row">
                        @foreach($recipe->steps->sortBy('order') as $step)
                            <div class="col-md-4 mb-3">
                                <div class="card">
                                    <div class="card-header bg-light">
                                        <strong>Шаг {{ $step->order }}</strong>
                                    </div>
                                    @if($step->image)
                                        <img src="{{ Storage::url($step->image) }}" class="card-img-top" alt="Шаг {{ $step->order }}">
                                    @endif
                                    <div class="card-body">
                                        <p class="card-text">{{ $step->description }}</p>
                                        @if($step->time)
                                            <div class="text-muted"><i class="far fa-clock"></i> {{ $step->time }} мин.</div>
                                        @endif
                                        @if($step->tips)
                                            <div class="alert alert-info mt-2">
                                                <i class="fas fa-lightbulb"></i> <strong>Совет:</strong> {{ $step->tips }}
                                            </div>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
                <!-- Добавляем кнопки модерации в футер карточки -->
                <div class="card-footer">
                    <div class="d-flex justify-content-end">
                        <a href="{{ route('admin.moderation.index') }}" class="btn btn-secondary mr-2">
                            <i class="fas fa-arrow-left"></i> Назад
                        </a>
                        <button type="button" class="btn btn-success mx-2 btn-approve" data-id="{{ $recipe->id }}">
                            <i class="fas fa-check"></i> Одобрить
                        </button>
                        <button type="button" class="btn btn-danger btn-reject" data-id="{{ $recipe->id }}">
                            <i class="fas fa-times"></i> Отклонить
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Модальное окно для отклонения рецепта -->
<div class="modal fade" id="rejectModal" tabindex="-1" role="dialog" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <form action="{{ route('admin.moderation.reject', $recipe) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title" id="rejectModalLabel">Отклонить рецепт</h5>
                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                        <span aria-hidden="true">&times;</span>
                    </button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label for="rejection_reason">Причина отказа в модерации:</label>
                        <textarea name="rejection_reason" id="rejection_reason" class="form-control @error('rejection_reason') is-invalid @enderror" rows="5" placeholder="Укажите подробную причину отказа...">{{ old('rejection_reason') }}</textarea>
                        @error('rejection_reason')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="form-text text-muted">
                            Эта информация будет отправлена автору рецепта. Пожалуйста, укажите конкретные причины, по которым рецепт не прошел модерацию, и что нужно исправить.
                        </small>
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

<!-- Модальное окно для отклонения -->
<div class="modal fade" id="rejectModal" tabindex="-1" aria-labelledby="rejectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
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
    // JavaScript для обработки кнопок модерации
    $(document).ready(function() {
        let recipeIdToReject = null;
        
        // Обработка нажатия на кнопку "Одобрить"
        $('.btn-approve').click(function() {
            const recipeId = $(this).data('id');
            
            if (!recipeId) {
                alert('Ошибка: ID рецепта не найден.');
                return;
            }
            
            if (confirm('Вы уверены, что хотите одобрить этот рецепт?')) {
                approveRecipe(recipeId);
            }
        });
        
        // Обработка нажатия на кнопку "Отклонить"
        $('.btn-reject').click(function() {
            recipeIdToReject = $(this).data('id');
            
            if (!recipeIdToReject) {
                alert('Ошибка: ID рецепта не найден.');
                return;
            }
            
            $('#rejectModal').modal('show');
        });
        
        // Обработка подтверждения отклонения в модальном окне
        $('#confirmReject').click(function() {
            const reason = $('#rejection_reason').val();
            
            if (!reason.trim()) {
                alert('Пожалуйста, укажите причину отклонения.');
                return;
            }
            
            rejectRecipe(recipeIdToReject, reason);
            $('#rejectModal').modal('hide');
        });
        
        // Функция для одобрения рецепта
        function approveRecipe(recipeId) {
            $.ajax({
                url: `/admin/moderation/${recipeId}/approve`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
                success: function(response) {
                    if (response.success) {
                        alert('Рецепт успешно одобрен!');
                        window.location.href = '{{ route("admin.moderation.index") }}';
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        alert('Ошибка: ' + xhr.responseJSON.message);
                    } else {
                        alert('Произошла ошибка при одобрении рецепта. Проверьте, заполнены ли все обязательные поля.');
                    }
                }
            });
        }
        
        // Функция для отклонения рецепта
        function rejectRecipe(recipeId, reason) {
            $.ajax({
                url: `/admin/moderation/${recipeId}/reject`,
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        alert('Рецепт отклонён.');
                        window.location.href = '{{ route("admin.moderation.index") }}';
                    } else {
                        alert('Ошибка: ' + response.message);
                    }
                },
                error: function(xhr) {
                    if (xhr.responseJSON && xhr.responseJSON.message) {
                        alert('Ошибка: ' + xhr.responseJSON.message);
                    } else {
                        alert('Произошла ошибка при отклонении рецепта.');
                    }
                }
            });
        }
    });
</script>
@endpush
@endsection
