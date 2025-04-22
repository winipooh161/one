@extends('layouts.app')

@section('title', 'Нет подключения к интернету')
@section('description', 'Вы сейчас находитесь в автономном режиме')

@section('content')
<div class="container my-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow border-0">
                <div class="card-body text-center py-5">
                    <div class="mb-4">
                        <i class="fas fa-wifi-slash text-muted" style="font-size: 4rem;"></i>
                    </div>
                    <h1 class="h3 mb-3">Отсутствует подключение к интернету</h1>
                    <p class="text-muted mb-4">
                        В данный момент вы находитесь в автономном режиме. Некоторые функции могут быть недоступны до восстановления соединения.
                    </p>
                    
                    <button id="check-connection" class="btn btn-primary px-4 mb-3">
                        <i class="fas fa-sync-alt me-2"></i> Проверить соединение
                    </button>
                </div>
            </div>
            
            <!-- Отображение кэшированных рецептов -->
            <div class="mt-4" id="offline-recipes-container">
                <h2 class="h4 mb-3">Недавно просмотренные рецепты</h2>
                <div class="card shadow-sm">
                    <div class="card-body">
                        <div id="offline-recipes-list" class="list-group list-group-flush">
                            <div class="text-center py-4 text-muted">
                                <i class="fas fa-spinner fa-spin me-2"></i> Загрузка сохраненных рецептов...
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Проверка соединения
        const checkConnectionButton = document.getElementById('check-connection');
        if (checkConnectionButton) {
            checkConnectionButton.addEventListener('click', function() {
                if (navigator.onLine) {
                    window.location.href = '{{ route("home") }}';
                } else {
                    alert('Вы все еще находитесь в автономном режиме. Пожалуйста, проверьте ваше интернет-соединение.');
                }
            });
        }
        
        // Загрузка кэшированных рецептов из localStorage
        const offlineRecipesList = document.getElementById('offline-recipes-list');
        if (offlineRecipesList) {
            try {
                const recentRecipes = JSON.parse(localStorage.getItem('recent_recipes') || '[]');
                
                if (recentRecipes.length === 0) {
                    offlineRecipesList.innerHTML = `
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-cookie-bite me-2"></i> Нет сохраненных рецептов
                        </div>
                    `;
                } else {
                    offlineRecipesList.innerHTML = '';
                    
                    recentRecipes.forEach(recipe => {
                        const recipeItem = document.createElement('a');
                        recipeItem.className = 'list-group-item list-group-item-action d-flex align-items-center';
                        recipeItem.href = '/recipes/' + recipe.slug;
                        
                        const imageContainer = document.createElement('div');
                        imageContainer.className = 'me-3';
                        imageContainer.style.width = '60px';
                        imageContainer.style.height = '60px';
                        imageContainer.style.overflow = 'hidden';
                        imageContainer.style.borderRadius = '8px';
                        
                        const image = document.createElement('img');
                        image.src = recipe.image_url || '/images/placeholder.jpg';
                        image.alt = recipe.title;
                        image.style.width = '100%';
                        image.style.height = '100%';
                        image.style.objectFit = 'cover';
                        
                        imageContainer.appendChild(image);
                        
                        const contentDiv = document.createElement('div');
                        contentDiv.className = 'flex-grow-1';
                        
                        const title = document.createElement('h5');
                        title.className = 'mb-1';
                        title.textContent = recipe.title;
                        
                        const description = document.createElement('p');
                        description.className = 'mb-0 small text-muted';
                        description.textContent = recipe.description || 'Нет описания';
                        
                        const timeInfo = document.createElement('div');
                        timeInfo.className = 'text-muted small';
                        timeInfo.innerHTML = `<i class="far fa-clock me-1"></i> ${recipe.cooking_time || '?'} мин.`;
                        
                        contentDiv.appendChild(title);
                        contentDiv.appendChild(description);
                        
                        recipeItem.appendChild(imageContainer);
                        recipeItem.appendChild(contentDiv);
                        recipeItem.appendChild(timeInfo);
                        
                        offlineRecipesList.appendChild(recipeItem);
                    });
                }
            } catch (e) {
                console.error('Error loading offline recipes:', e);
                offlineRecipesList.innerHTML = `
                    <div class="text-center py-4 text-muted">
                        <i class="fas fa-exclamation-circle me-2"></i> Ошибка загрузки сохраненных рецептов
                    </div>
                `;
            }
        }
    });
</script>
@endsection
