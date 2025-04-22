@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Создать новый пост</h3>
                    <div class="card-tools">
                        <a href="{{ route('admin.social-posts.index') }}" class="btn btn-default btn-sm">
                            <i class="fas fa-arrow-left"></i> Назад к списку
                        </a>
                    </div>
                </div>
                <div class="card-body">
                    @if ($errors->any())
                        <div class="alert alert-danger">
                            <ul>
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif
                    
                    <form action="{{ route('admin.social-posts.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="title">Заголовок</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title') }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Содержание</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="5" required>{{ old('content') }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="image">Изображение</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('image') is-invalid @enderror" id="image" name="image">
                                    <label class="custom-file-label" for="image">Выберите файл</label>
                                </div>
                            </div>
                            @error('image')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Рекомендуемый размер: 1200x630px. Максимальный размер: 2 МБ.</small>
                        </div>
                        
                        <div class="form-group">
                            <label>Опубликовать в соцсетях</label>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="publish_to_telegram" name="publish_to_telegram" value="1" {{ old('publish_to_telegram') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="publish_to_telegram">
                                    <i class="fab fa-telegram text-info"></i> Telegram
                                </label>
                            </div>
                            <div class="custom-control custom-checkbox">
                                <input type="checkbox" class="custom-control-input" id="publish_to_vk" name="publish_to_vk" value="1" {{ old('publish_to_vk') ? 'checked' : '' }}>
                                <label class="custom-control-label" for="publish_to_vk">
                                    <i class="fab fa-vk text-primary"></i> ВКонтакте
                                </label>
                            </div>
                            <small class="form-text text-muted">Выберите социальные сети для мгновенной публикации после сохранения поста</small>
                        </div>
                        
                        <div class="form-group">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Сохранить
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    $(document).ready(function () {
        bsCustomFileInput.init();
    });
</script>
@endpush
