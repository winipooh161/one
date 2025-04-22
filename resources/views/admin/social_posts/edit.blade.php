@extends('admin.layouts.app')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-md-12">
            <div class="card card-primary">
                <div class="card-header">
                    <h3 class="card-title">Редактировать пост</h3>
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
                    
                    <form action="{{ route('admin.social-posts.update', $socialPost->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')
                        
                        <div class="form-group">
                            <label for="title">Заголовок</label>
                            <input type="text" class="form-control @error('title') is-invalid @enderror" id="title" name="title" value="{{ old('title', $socialPost->title) }}" required>
                            @error('title')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        <div class="form-group">
                            <label for="content">Содержание</label>
                            <textarea class="form-control @error('content') is-invalid @enderror" id="content" name="content" rows="5" required>{{ old('content', $socialPost->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        
                        @if($socialPost->image_url)
                        <div class="form-group">
                            <label>Текущее изображение</label>
                            <div>
                                <img src="{{ $socialPost->image_url }}" alt="Текущее изображение" class="img-thumbnail" style="max-width: 200px; max-height: 200px;">
                            </div>
                        </div>
                        @endif
                        
                        <div class="form-group">
                            <label for="image">Новое изображение</label>
                            <div class="input-group">
                                <div class="custom-file">
                                    <input type="file" class="custom-file-input @error('image') is-invalid @enderror" id="image" name="image">
                                    <label class="custom-file-label" for="image">Выберите новый файл</label>
                                </div>
                            </div>
                            @error('image')
                                <div class="text-danger">{{ $message }}</div>
                            @enderror
                            <small class="form-text text-muted">Оставьте пустым, если не хотите менять изображение.</small>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-6">
                                <div class="card card-outline card-primary">
                                    <div class="card-header">
                                        <h3 class="card-title">ВКонтакте</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            @if($socialPost->isPublishedToVk())
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Опубликовано {{ $socialPost->vk_posted_at->format('d.m.Y H:i') }}
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> Не опубликовано
                                                </span>
                                                
                                                <div class="mt-2">
                                                    <form action="{{ route('admin.social-posts.publish-vk', $socialPost->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-primary">
                                                            <i class="fab fa-vk mr-1"></i> Опубликовать в ВК
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <div class="col-md-6">
                                <div class="card card-outline card-info">
                                    <div class="card-header">
                                        <h3 class="card-title">Телеграм</h3>
                                    </div>
                                    <div class="card-body">
                                        <div class="mb-2">
                                            @if($socialPost->isPublishedToTelegram())
                                                <span class="badge badge-success">
                                                    <i class="fas fa-check"></i> Опубликовано {{ $socialPost->telegram_posted_at->format('d.m.Y H:i') }}
                                                </span>
                                            @else
                                                <span class="badge badge-warning">
                                                    <i class="fas fa-clock"></i> Не опубликовано
                                                </span>
                                                
                                                <div class="mt-2">
                                                    <form action="{{ route('admin.social-posts.publish-telegram', $socialPost->id) }}" method="POST">
                                                        @csrf
                                                        <button type="submit" class="btn btn-info">
                                                            <i class="fab fa-telegram-plane mr-1"></i> Опубликовать в Телеграм
                                                        </button>
                                                    </form>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group mt-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save mr-1"></i> Обновить пост
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
