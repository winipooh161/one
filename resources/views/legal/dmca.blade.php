@extends('layouts.app')

@section('meta_tags')
    <title>Правообладателям (DMCA) | {{ config('app.name') }}</title>
    <meta name="description" content="Информация для правообладателей. Форма для отправки запросов на удаление контента, нарушающего авторские права.">
    <link rel="canonical" href="{{ route('legal.dmca') }}" />
@endsection

@section('content')
<div class="container py-5">
    <div class="row">
        <div class="col-lg-8 mx-auto">
            <div class="card border-0 shadow-sm">
                <div class="card-body p-lg-5">
                    <h1 class="mb-4">Правообладателям (DMCA)</h1>
                    
                    <div class="mb-4">
                        <p>{{ config('app.name') }} уважает интеллектуальную собственность других лиц и просит пользователей делать то же самое. Если вы считаете, что ваша работа была скопирована таким образом, который представляет собой нарушение авторских прав, пожалуйста, следуйте процедуре, описанной ниже.</p>
                    </div>
                    
                    <div class="mb-5">
                        <h2 class="h5 mb-3">Процедура подачи уведомления о нарушении</h2>
                        <p>Для эффективной обработки вашего уведомления о нарушении авторских прав в соответствии с Законом о защите авторских прав в цифровую эпоху (DMCA), пожалуйста, заполните форму ниже или отправьте нам письменное уведомление, которое должно содержать следующую информацию:</p>
                        
                        <ol>
                            <li>Физическую или электронную подпись лица, уполномоченного действовать от имени владельца авторских прав;</li>
                            <li>Идентификацию произведения, защищенного авторским правом, права на которое, как утверждается, были нарушены;</li>
                            <li>Идентификацию материала, который, как утверждается, нарушает авторские права, и информацию, достаточную для нас, чтобы определить его местонахождение;</li>
                            <li>Контактную информацию заявителя, включая адрес, номер телефона и, если возможно, адрес электронной почты;</li>
                            <li>Заявление о том, что заявитель добросовестно полагает, что использование материала не разрешено владельцем авторских прав, его агентом или законом;</li>
                            <li>Заявление о том, что информация в уведомлении точна и, под страхом наказания за лжесвидетельство, что лицо, подающее уведомление, уполномочено действовать от имени владельца авторских прав.</li>
                        </ol>
                    </div>
                    
                    <div class="mb-5">
                        <h2 class="h5 mb-3">Форма запроса DMCA</h2>
                        
                        @if(session('success'))
                            <div class="alert alert-success mb-4">
                                <i class="fas fa-check-circle me-2"></i> {{ session('success') }}
                            </div>
                        @endif
                        
                        @if($errors->any())
                            <div class="alert alert-danger mb-4">
                                <i class="fas fa-exclamation-circle me-2"></i> Пожалуйста, исправьте следующие ошибки:
                                <ul class="mb-0 mt-2">
                                    @foreach($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        
                        <form action="{{ route('legal.dmca.submit') }}" method="POST">
                            @csrf
                            
                            <div class="mb-3">
                                <label for="name" class="form-label">Ваше имя <span class="text-danger">*</span></label>
                                <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="email" class="form-label">Ваш email <span class="text-danger">*</span></label>
                                <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="mb-3">
                                <label for="content_url" class="form-label">URL страницы с нарушением <span class="text-danger">*</span></label>
                                <input type="url" class="form-control @error('content_url') is-invalid @enderror" id="content_url" name="content_url" value="{{ old('content_url') }}" required placeholder="https://eats/...">
                                @error('content_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Укажите точный URL страницы, где размещен контент, нарушающий ваши авторские права.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="original_url" class="form-label">URL оригинального контента</label>
                                <input type="url" class="form-control @error('original_url') is-invalid @enderror" id="original_url" name="original_url" value="{{ old('original_url') }}" placeholder="https://...">
                                @error('original_url')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Если возможно, укажите URL, где размещен оригинальный контент.</div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Описание нарушения <span class="text-danger">*</span></label>
                                <textarea class="form-control @error('description') is-invalid @enderror" id="description" name="description" rows="6" required>{{ old('description') }}</textarea>
                                @error('description')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                                <div class="form-text">Пожалуйста, подробно опишите, какой именно контент нарушает ваши права, и как вы можете доказать, что вы являетесь правообладателем.</div>
                            </div>
                            
                            <div class="mb-4 form-check">
                                <input type="checkbox" class="form-check-input @error('confirmation') is-invalid @enderror" id="confirmation" name="confirmation" {{ old('confirmation') ? 'checked' : '' }} required>
                                <label class="form-check-label" for="confirmation">Я подтверждаю, что добросовестно полагаю, что использование материала не разрешено владельцем авторских прав, его агентом или законом, и что информация в этом уведомлении точна. <span class="text-danger">*</span></label>
                                @error('confirmation')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Отправить запрос</button>
                            </div>
                        </form>
                    </div>
                    
                    <div class="mt-4">
                        <p class="small text-muted">После получения вашего уведомления мы рассмотрим его и примем соответствующие меры в соответствии с применимым законодательством. Обратите внимание, что подача ложных заявлений может привести к юридической ответственности.</p>
                        <p class="small text-muted">Если у вас есть вопросы относительно процедуры DMCA, пожалуйста, свяжитесь с нами по адресу: <a href="mailto:w1nishko@yandex.ru">w1nishko@yandex.ru</a></p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
