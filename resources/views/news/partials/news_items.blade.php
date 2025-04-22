@php
    // Отслеживаем уже отображенные новости
    $displayedIds = isset($displayedIds) ? $displayedIds : [];
@endphp

@forelse($news as $item)
    @if(!in_array($item->id, $displayedIds))
        @php
            // Добавляем ID в список отображенных
            $displayedIds[] = $item->id;
        @endphp
        
        <div class="col-lg-4 col-md-6 mb-4">
            <div class="card h-100 news-card {{ !empty($item->video_iframe) ? 'video-news-card' : '' }} shadow-hover">
                <a href="{{ route('news.show', $item->slug) }}" class="card-img-top-link position-relative">
                    <img src="{{ $item->getThumbnailUrl() }}" 
                         class="card-img-top" 
                         alt="{{ $item->title }}" 
                         loading="lazy">
                         
                    @if(!empty($item->video_iframe))
                    <div class="video-indicator">
                        <i class="fas fa-play-circle"></i>
                    </div>
                    @endif
                </a>
                <div class="card-body">
                    <h5 class="card-title">
                        <a href="{{ route('news.show', $item->slug) }}" class="text-dark text-decoration-none">
                            {{ $item->title }}
                        </a>
                    </h5>
                    <p class="card-text text-muted small mb-2">
                        <i class="far fa-calendar-alt me-1"></i> {{ $item->created_at->format('d.m.Y') }}
                        <i class="far fa-eye ms-2 me-1"></i> {{ $item->views }}
                        @if(isset($item->comments_count))
                        <i class="far fa-comment ms-2 me-1"></i> {{ $item->comments_count }}
                        @endif
                    </p>
                    <p class="card-text">{{ Str::limit($item->short_description, 100) }}</p>
                </div>
                <div class="card-footer bg-white border-0">
                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('news.show', $item->slug) }}" class="btn btn-outline-primary btn-sm">
                            Читать полностью
                        </a>
                        
                        <div>
                            @if(!empty($item->video_iframe))
                            <span class="badge bg-danger">
                                <i class="fas fa-play-circle"></i> Видео
                            </span>
                            @endif
                            
                            @if(isset($item->comments_count) && $item->comments_count > 0)
                            <span class="badge bg-secondary ms-1">
                                <i class="far fa-comments"></i> {{ $item->comments_count }}
                            </span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@empty
    <div class="col-12">
        <div class="alert alert-info">
            <i class="fas fa-info-circle me-2"></i> Новости не найдены. Пожалуйста, попробуйте другой поисковый запрос.
        </div>
    </div>
@endforelse

<style>
.news-card {
    transition: all 0.3s ease;
    border: 1px solid rgba(0,0,0,0.1);
}

.shadow-hover:hover {
    transform: translateY(-5px);
    box-shadow: 0 10px 20px rgba(0,0,0,0.1) !important;
}

.video-news-card {
    border-top: 3px solid #dc3545;
}

.video-indicator {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    color: white;
    font-size: 3rem;
    opacity: 0.8;
    text-shadow: 0 0 10px rgba(0,0,0,0.7);
    transition: all 0.3s ease;
}

.card-img-top-link {
    overflow: hidden;
}

.card-img-top {
    transition: transform 0.5s ease;
    height: 200px;
    object-fit: cover;
}

.card-img-top-link:hover .card-img-top {
    transform: scale(1.05);
}

.card-img-top-link:hover .video-indicator {
    font-size: 3.5rem;
    opacity: 1;
}

/* Анимация появления карточек */
@keyframes fadeInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.news-card {
    animation: fadeInUp 0.5s ease-out forwards;
}

.card-title a:hover {
    color: #0056b3 !important;
}
</style>
