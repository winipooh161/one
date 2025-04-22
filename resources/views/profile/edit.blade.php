@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card">
                <div class="card-header">
                    <h3 class="mb-0">Редактирование профиля</h3>
                </div>
                
                <div class="card-body">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PATCH')
                        
                        <div class="row mb-3">
                            <label for="name" class="col-md-4 col-form-label text-md-end">Имя</label>
                            
                            <div class="col-md-6">
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name', $user->name) }}" required autocomplete="name" autofocus>
                                
                                @error('name')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="email" class="col-md-4 col-form-label text-md-end">Email</label>
                            
                            <div class="col-md-6">
                                <input id="email" type="email" class="form-control @error('email') is-invalid @enderror" name="email" value="{{ old('email', $user->email) }}" required autocomplete="email">
                                
                                @error('email')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="bio" class="col-md-4 col-form-label text-md-end">О себе</label>
                            
                            <div class="col-md-6">
                                <textarea id="bio" class="form-control @error('bio') is-invalid @enderror" name="bio" rows="4">{{ old('bio', $user->bio) }}</textarea>
                                
                                @error('bio')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <div class="form-text">Расскажите о себе и своих кулинарных увлечениях</div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            <label for="avatar" class="col-md-4 col-form-label text-md-end">Аватар</label>
                            
                            <div class="col-md-6">
                                @if($user->avatar)
                                    <div class="mb-2">
                                        <img src="{{ asset('storage/'.$user->avatar) }}" alt="Current avatar" class="img-thumbnail" style="max-height: 100px;">
                                    </div>
                                @endif
                                
                                <input id="avatar" type="file" class="form-control @error('avatar') is-invalid @enderror" name="avatar" accept="image/*">
                                
                                @error('avatar')
                                    <span class="invalid-feedback" role="alert">
                                        <strong>{{ $message }}</strong>
                                    </span>
                                @enderror
                                <div class="form-text">Максимальный размер: 2MB. Поддерживаемые форматы: JPEG, PNG, GIF</div>
                            </div>
                        </div>
                        
                        <div class="row mb-0">
                            <div class="col-md-6 offset-md-4">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Сохранить изменения
                                </button>
                                <a href="{{ route('profile.show') }}" class="btn btn-secondary ms-2">
                                    <i class="fas fa-times me-1"></i> Отмена
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
