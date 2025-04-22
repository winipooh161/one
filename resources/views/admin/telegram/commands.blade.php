@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row mb-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Управление командами Telegram бота</h5>
                    <a href="{{ route('admin.telegram.index') }}" class="btn btn-sm btn-secondary">
                        <i class="fas fa-arrow-left"></i> Назад к обзору
                    </a>
                </div>
                <div class="card-body">
                    @if (session('success'))
                        <div class="alert alert-success">{{ session('success') }}</div>
                    @endif
                    @if (session('error'))
                        <div class="alert alert-danger">{{ session('error') }}</div>
                    @endif

                    <div class="alert alert-info">
                        <h5><i class="fas fa-info-circle"></i> Информация о командах</h5>
                        <p>Здесь вы можете настроить команды, которые будут отображаться пользователям в меню бота. Команды будут доступны через автозаполнение в чате Telegram.</p>
                    </div>

                    <form action="{{ route('admin.telegram.commands') }}" method="POST">
                        @csrf
                        <div class="table-responsive">
                            <table class="table table-bordered table-hover" id="commands-table">
                                <thead class="thead-dark">
                                    <tr>
                                        <th width="30%">Команда</th>
                                        <th width="60%">Описание</th>
                                        <th width="10%">Действие</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($commands as $index => $command)
                                        <tr class="command-row">
                                            <td>
                                                <input type="text" class="form-control" name="commands[{{ $index }}][command]" value="{{ $command['command'] }}" required>
                                            </td>
                                            <td>
                                                <input type="text" class="form-control" name="commands[{{ $index }}][description]" value="{{ $command['description'] }}" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm remove-command">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </td>
                                        </tr>
                                    @endforeach
                                    <tr id="empty-row" style="{{ count($commands) > 0 ? 'display: none;' : '' }}">
                                        <td colspan="3" class="text-center">Команды не добавлены</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3 mb-4">
                            <button type="button" class="btn btn-success" id="add-command">
                                <i class="fas fa-plus"></i> Добавить команду
                            </button>
                        </div>

                        <div class="border-top pt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> Сохранить команды
                            </button>
                        </div>
                    </form>
                </div>
            </div>

            <div class="card mt-4">
                <div class="card-header">
                    <h5 class="mb-0">Команды по умолчанию</h5>
                </div>
                <div class="card-body">
                    <p>Эти команды рекомендуется добавить в вашего бота:</p>
                    <ul>
                        <li><strong>/start</strong> - Начать работу с ботом</li>
                        <li><strong>/help</strong> - Получить справку по использованию бота</li>
                        <li><strong>/random</strong> - Получить случайный рецепт</li>
                        <li><strong>/popular</strong> - Показать популярные рецепты</li>
                        <li><strong>/category</strong> - Просмотр категорий рецептов</li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
$(document).ready(function() {
    let commandIndex = {{ count($commands) }};
    
    // Добавление новой команды
    $('#add-command').click(function() {
        $('#empty-row').hide();
        const newRow = `
            <tr class="command-row">
                <td>
                    <input type="text" class="form-control" name="commands[${commandIndex}][command]" placeholder="Например: start" required>
                </td>
                <td>
                    <input type="text" class="form-control" name="commands[${commandIndex}][description]" placeholder="Например: Начать работу с ботом" required>
                </td>
                <td>
                    <button type="button" class="btn btn-danger btn-sm remove-command">
                        <i class="fas fa-trash"></i>
                    </button>
                </td>
            </tr>
        `;
        $('#commands-table tbody').append(newRow);
        commandIndex++;
    });
    
    // Удаление команды
    $(document).on('click', '.remove-command', function() {
        $(this).closest('tr').remove();
        if ($('.command-row').length === 0) {
            $('#empty-row').show();
        }
    });
});
</script>
@endpush
