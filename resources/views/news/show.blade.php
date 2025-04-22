@extends('layouts.news')

@section('title', $news->title)
@section('description', Str::limit($news->short_description, 160))

@section('schema_org')
    @if($news->video_iframe)
        @include('schema_org.video_news_schema', ['news' => $news])
    @else
        @include('schema_org.news_schema')
    @endif
@endsection

@section('seo')
    @if($news->video_iframe)
        @include('seo.video_news_seo', ['news' => $news])
    @else
        @include('seo.news_seo')
    @endif
@endsection

@section('breadcrumbs')
<li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <a href="{{ route('news.index') }}" itemprop="item">
        <span itemprop="name">Новости</span>
    </a>
    <meta itemprop="position" content="2" />
</li>
<li class="breadcrumb-item active" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
    <span itemprop="name">{{ $news->title }}</span>
    <meta itemprop="position" content="3" />
</li>
@endsection

@section('content')
<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <article class="news-article card shadow-sm mb-4" itemscope itemtype="https://schema.org/NewsArticle">
                <meta itemprop="datePublished" content="{{ $news->created_at->toIso8601String() }}">
                <meta itemprop="dateModified" content="{{ $news->updated_at->toIso8601String() }}">
                
                <div class="card-body">
                    <h1 class="mb-3 fw-bold" itemprop="headline">{{ $news->title }}</h1>
                    
                    <div class="d-flex mb-3 text-muted">
                        <div class="me-3">
                            <i class="far fa-calendar-alt me-1"></i> 
                            <time datetime="{{ $news->created_at->toIso8601String() }}">{{ $news->created_at->format('d.m.Y') }}</time>
                        </div>
                        <div class="me-3">
                            <i class="far fa-eye me-1"></i> {{ $news->views }}
                        </div>
                        @if($news->user)
                        <div>
                            <i class="far fa-user me-1"></i> 
                            <span itemprop="author">{{ $news->user->name }}</span>
                        </div>
                        @endif
                    </div>
                    
                    @if($news->image_url && !$news->video_iframe)
                    <div class="news-featured-image mb-4">
                        <img src="{{ asset('uploads/' . $news->image_url) }}" 
                             class="img-fluid rounded" 
                             alt="{{ $news->title }}" 
                             itemprop="image">
                    </div>
                    @endif
                    
                    @if($news->video_iframe)
                    <div class="video-container ratio ratio-16x9 mb-4">
                        {!! $news->video_iframe !!}
                    </div>
                    @endif
                    
                    <div class="news-content mb-4" itemprop="articleBody">
                        {!! $news->content !!}
                    </div>
                    
                    @if($news->tags && $news->tags->count() > 0)
                    <div class="news-tags mb-4">
                        <h5>Теги:</h5>
                        <div class="d-flex flex-wrap gap-2">
                            @foreach($news->tags as $tag)
                                <a href="{{ route('news.tag', $tag->slug) }}" class="badge bg-secondary text-decoration-none">
                                    {{ $tag->name }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                    @endif
                    
                    <div class="share-buttons mb-4">
                        <h5>Поделиться:</h5>
                        <div class="d-flex gap-2">
                            <a href="https://vk.com/share.php?url={{ urlencode(route('news.show', $news->slug)) }}" 
                               class="btn btn-sm btn-outline-primary" target="_blank" rel="nofollow">
                                <i class="fab fa-vk"></i> ВКонтакте
                            </a>
                            <a href="https://t.me/share/url?url={{ urlencode(route('news.show', $news->slug)) }}&text={{ urlencode($news->title) }}" 
                               class="btn btn-sm btn-outline-info" target="_blank" rel="nofollow">
                                <i class="fab fa-telegram"></i> Telegram
                            </a>
                            <a href="https://wa.me/?text={{ urlencode($news->title . ' ' . route('news.show', $news->slug)) }}"
                               class="btn btn-sm btn-outline-success" target="_blank" rel="nofollow">
                                <i class="fab fa-whatsapp"></i> WhatsApp
                            </a>
                        </div>
                    </div>
                </div>
            </article>
            
            <!-- Навигация между новостями -->
            <div class="news-navigation d-flex justify-content-between mb-4">
                @if($prevNews)
                <a href="{{ route('news.show', $prevNews->slug) }}" class="btn btn-outline-primary">
                    <i class="fas fa-chevron-left me-2"></i> Предыдущая новость
                </a>
                @else
                <div></div>
                @endif

                @if($nextNews)
                <a href="{{ route('news.show', $nextNews->slug) }}" class="btn btn-outline-primary">
                    Следующая новость <i class="fas fa-chevron-right ms-2"></i>
                </a>
                @else
                <div></div>
                @endif
            </div>

            <!-- Раздел комментариев -->
            <div class="comments-section card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h3 class="h5 mb-0">
                        <i class="far fa-comments me-2"></i>
                        Комментарии <span class="badge bg-secondary">{{ $comments->count() }}</span>
                    </h3>
                </div>
                <div class="card-body">
                    @auth
                    <!-- Форма для добавления комментария -->
                    <div class="comment-form-wrapper mb-4">
                        <form action="{{ route('news.comments.store') }}" method="POST" id="comment-form">
                            @csrf
                            <input type="hidden" name="news_id" value="{{ $news->id }}">
                            <div class="mb-3">
                                <label for="comment-content" class="form-label">Ваш комментарий</label>
                                <textarea class="form-control" id="comment-content" name="content" rows="3" required></textarea>
                            </div>
                            <div class="d-flex justify-content-between">
                                <span class="text-muted small">Будьте вежливы и соблюдайте правила комментирования</span>
                                <button type="submit" class="btn btn-primary">
                                    <i class="far fa-paper-plane me-1"></i> Отправить
                                </button>
                            </div>
                        </form>
                    </div>
                    @else
                    <!-- Сообщение для неавторизованных пользователей -->
                    <div class="alert alert-info mb-4">
                        <i class="fas fa-info-circle me-2"></i>
                        <a href="{{ route('login') }}" class="alert-link">Авторизуйтесь</a> или <a href="{{ route('register') }}" class="alert-link">зарегистрируйтесь</a>, 
                        чтобы оставить комментарий
                    </div>
                    @endauth

                    <!-- Список комментариев -->
                    <div id="comments-list">
                        @if($comments->count() > 0)
                            @foreach($comments as $comment)
                                <div class="comment-item card mb-3">
                                    <div class="card-body">
                                        <div class="d-flex">
                                            <img src="{{ $comment->user->avatar ? asset($comment->user->avatar) : asset('images/default-avatar.png') }}" 
                                                alt="{{ $comment->user->name }}" 
                                                class="rounded-circle me-3" 
                                                width="50" height="50">
                                            <div class="w-100">
                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                    <h6 class="card-subtitle mb-0 fw-bold">{{ $comment->user->name }}</h6>
                                                    <small class="text-muted">{{ $comment->created_at->diffForHumans() }}</small>
                                                </div>
                                                <p class="card-text mb-2">{{ $comment->content }}</p>
                                                @if(auth()->check() && (auth()->id() === $comment->user_id || auth()->user()->isAdmin()))
                                                <div class="text-end">
                                                    <form action="{{ route('news.comments.destroy', $comment->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('DELETE')
                                                        <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены?')">
                                                            <i class="far fa-trash-alt"></i> Удалить
                                                        </button>
                                                    </form>
                                                </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        @else
                            <div class="text-center py-5">
                                <div class="empty-comments-icon mb-3">
                                    <i class="far fa-comment-dots fa-3x text-muted"></i>
                                </div>
                                <p class="text-muted">Комментариев пока нет. Будьте первым!</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <!-- Сайдбар с похожими новостями -->
            @if(isset($relatedNews) && $relatedNews->count() > 0)
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0 h6">
                        <i class="fas fa-newspaper me-2"></i>Похожие новости
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($relatedNews as $item)
                    <a href="{{ route('news.show', $item->slug) }}" class="list-group-item list-group-item-action d-flex gap-2">
                        @if($item->image_url)
                        <img src="{{ asset('uploads/' . $item->image_url) }}" 
                             alt="{{ $item->title }}" 
                             class="related-news-img"
                             width="70" height="50" style="object-fit: cover; border-radius: 4px;">
                        @endif
                        <div>
                            <p class="mb-1">{{ Str::limit($item->title, 60) }}</p>
                            <small class="text-muted">
                                <i class="far fa-calendar-alt me-1"></i> {{ $item->created_at->format('d.m.Y') }}
                            </small>
                        </div>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif
            
            <!-- Блок с последними новостями -->
            @if(isset($latestNews) && $latestNews->count() > 0)
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0 h6">
                        <i class="fas fa-clock me-2"></i>Последние новости
                    </h5>
                </div>
                <div class="list-group list-group-flush">
                    @foreach($latestNews as $item)
                    <a href="{{ route('news.show', $item->slug) }}" class="list-group-item list-group-item-action">
                        <div class="d-flex justify-content-between">
                            <span>{{ Str::limit($item->title, 50) }}</span>
                            @if(!empty($item->video_iframe))
                                <span class="badge bg-danger ms-1">
                                    <i class="fas fa-play-circle"></i>
                                </span>
                            @endif
                        </div>
                        <small class="text-muted">{{ $item->created_at->format('d.m.Y') }}</small>
                    </a>
                    @endforeach
                </div>
            </div>
            @endif

            <!-- Форма подписки на обновления -->
            <div class="card mb-4 shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0 h6">
                        <i class="fas fa-bell me-2"></i>Подписаться на обновления
                    </h5>
                </div>
                <div class="card-body">
                    <form id="sidebar-subscribe-form">
                        <div class="mb-3">
                            <label for="subscribe-email" class="form-label">Email для получения новостей</label>
                            <input type="email" class="form-control" id="subscribe-email" placeholder="example@mail.com" required>
                        </div>
                        <div class="form-check mb-3">
                            <input class="form-check-input" type="checkbox" id="subscribe-terms" required>
                            <label class="form-check-label small" for="subscribe-terms">
                                Я согласен получать новости и обновления
                            </label>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="far fa-envelope me-1"></i> Подписаться
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('styles')
<style>
/* Стили для новостного контента */
.news-article {
    border-radius: 8px;
    overflow: hidden;
}

.news-content {
    font-size: 1.05rem;
    line-height: 1.7;
}

.news-content h2 {
    font-size: 1.5rem;
    margin-top: 1.5rem;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 1px solid rgba(0, 0, 0, 0.1);
}

.news-content h3 {
    font-size: 1.3rem;
    margin-top: 1.3rem;
    margin-bottom: 0.8rem;
}

.news-content p {
    margin-bottom: 1rem;
}

.news-content img {
    max-width: 100%;
    height: auto;
    margin: 1rem 0;
    border-radius: 8px;
    box-shadow: 0 3px 6px rgba(0, 0, 0, 0.1);
}

.news-content ul, .news-content ol {
    margin-bottom: 1.5rem;
    padding-left: 1.5rem;
}

.news-content li {
    margin-bottom: 0.5rem;
}

.news-content blockquote {
    border-left: 4px solid #6c757d;
    padding: 0.5rem 1rem;
    margin: 1.5rem 0;
    background-color: rgba(0, 0, 0, 0.03);
    font-style: italic;
}

.animate-element {
    opacity: 0;
    transform: translateY(20px);
    transition: opacity 0.5s ease, transform 0.5s ease;
}

.animated {
    opacity: 1;
    transform: translateY(0);
}

/* Стили для контейнера видео */
.video-container {
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
    background-color: #000;
    margin-bottom: 1.5rem;
}

.video-container iframe {
    width: 100%;
    height: 100%;
    border: none;
}

/* Стили для комментариев */
.comment-item {
    transition: all 0.3s ease;
    border-left: 3px solid transparent;
}

.comment-item:hover {
    border-left-color: #007bff;
}

.related-news-img {
    transition: transform 0.3s ease;
}

.list-group-item:hover .related-news-img {
    transform: scale(1.05);
}

/* Адаптивные стили */
@media (max-width: 768px) {
    .news-content {
        font-size: 1rem;
    }
    
    .news-content h2 {
        font-size: 1.3rem;
    }
    
    .news-content h3 {
        font-size: 1.15rem;
    }
    
    .video-container {
        height: 300px;
    }
}
</style>
@endsection

@section('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Анимация появления контента
    const animateElements = document.querySelectorAll('.news-content p, .news-content h2, .news-content h3, .news-content ul, .news-content img');
    
    const observer = new IntersectionObserver((entries) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                entry.target.classList.add('animated');
                observer.unobserve(entry.target);
            }
        });
    }, { threshold: 0.1 });
    
    animateElements.forEach(element => {
        element.classList.add('animate-element');
        observer.observe(element);
    });
    
    // Обработчик формы комментариев
    const commentForm = document.getElementById('comment-form');
    if (commentForm) {
        commentForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const commentText = document.getElementById('comment-content').value;
            if (!commentText.trim()) return;
            
            const formData = new FormData(this);
            
            // Показываем индикатор загрузки
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span> Отправка...';
            
            // Отправляем AJAX запрос
            fetch(this.action, {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error('Ошибка сети');
                }
                return response.json();
            })
            .then(data => {
                // Возвращаем кнопку в исходное состояние
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                // Очищаем форму
                this.reset();
                
                // Показываем уведомление об успехе
                const alert = document.createElement('div');
                alert.className = 'alert alert-success alert-dismissible fade show';
                alert.innerHTML = '<i class="fas fa-check-circle me-2"></i> Ваш комментарий добавлен! <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                this.parentNode.insertBefore(alert, this);
                
                // Если есть данные комментария, добавляем его в список
                if (data.comment) {
                    const commentsList = document.getElementById('comments-list');
                    
                    // Если это первый комментарий, очищаем сообщение "комментариев пока нет"
                    if (commentsList.querySelector('.empty-comments-icon')) {
                        commentsList.innerHTML = '';
                    }
                    
                    // Создаем HTML для нового комментария
                    const commentItem = document.createElement('div');
                    commentItem.className = 'comment-item card mb-3';
                    commentItem.innerHTML = `
                        <div class="card-body">
                            <div class="d-flex">
                                <img src="${data.userAvatar || '{{ asset("images/default-avatar.png") }}'}" 
                                    alt="${data.userName}" 
                                    class="rounded-circle me-3" 
                                    width="50" height="50">
                                <div class="w-100">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <h6 class="card-subtitle mb-0 fw-bold">${data.userName}</h6>
                                        <small class="text-muted">только что</small>
                                    </div>
                                    <p class="card-text mb-2">${data.comment.content}</p>
                                    <div class="text-end">
                                        <form action="{{ route('news.comments.destroy', '') }}/${data.comment.id}" method="POST" class="d-inline">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" onclick="return confirm('Вы уверены?')">
                                                <i class="far fa-trash-alt"></i> Удалить
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Добавляем комментарий в начало списка
                    commentsList.insertBefore(commentItem, commentsList.firstChild);
                    
                    // Анимация появления комментария
                    commentItem.style.opacity = '0';
                    commentItem.style.transform = 'translateY(20px)';
                    setTimeout(() => {
                        commentItem.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
                        commentItem.style.opacity = '1';
                        commentItem.style.transform = 'translateY(0)';
                    }, 10);
                }
                
                // Автоматически закрываем уведомление через 3 секунды
                setTimeout(() => {
                    alert.classList.remove('show');
                    setTimeout(() => alert.remove(), 150);
                }, 3000);
            })
            .catch(error => {
                // Возвращаем кнопку в исходное состояние
                submitBtn.disabled = false;
                submitBtn.innerHTML = originalBtnText;
                
                // Показываем уведомление об ошибке
                const alert = document.createElement('div');
                alert.className = 'alert alert-danger alert-dismissible fade show';
                alert.innerHTML = '<i class="fas fa-exclamation-circle me-2"></i> Произошла ошибка при добавлении комментария. <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>';
                this.parentNode.insertBefore(alert, this);
            });
        });
    }
    
    // Форма подписки
    const subscribeForm = document.getElementById('sidebar-subscribe-form');
    if (subscribeForm) {
        subscribeForm.addEventListener('submit', function(e) {
            e.preventDefault();
            const submitBtn = this.querySelector('button[type="submit"]');
            const originalBtnText = submitBtn.innerHTML;
            submitBtn.disabled = true;
            submitBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status" aria-hidden="true"></span> Обработка...';
            
            // Имитация отправки формы (можно заменить на реальный AJAX запрос)
            setTimeout(() => {
                this.innerHTML = `
                    <div class="alert alert-success mb-0">
                        <i class="fas fa-check-circle me-2"></i>
                        Спасибо! Вы успешно подписались на обновления.
                    </div>
                `;
            }, 1000);
        });
    }
    
    // Увеличение изображений при клике
    const newsImages = document.querySelectorAll('.news-content img');
    newsImages.forEach(img => {
        img.classList.add('img-fluid', 'rounded', 'mb-3');
        img.style.cursor = 'pointer';
        img.addEventListener('click', function() {
            // Создаем модальное окно для просмотра изображения
            const modal = document.createElement('div');
            modal.className = 'modal fade';
            modal.innerHTML = `
                <div class="modal-dialog modal-dialog-centered modal-lg">
                    <div class="modal-content">
                        <div class="modal-header">
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                        </div>
                        <div class="modal-body text-center">
                            <img src="${this.src}" class="img-fluid" alt="${this.alt}">
                        </div>
                    </div>
                </div>
            `;
            
            document.body.appendChild(modal);
            const modalInstance = new bootstrap.Modal(modal);
            modalInstance.show();
            
            modal.addEventListener('hidden.bs.modal', function() {
                this.remove();
            });
        });
    });
});
</script>
@endsection
