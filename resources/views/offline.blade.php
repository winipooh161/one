@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center p-5">
                    <i class="fas fa-wifi-slash fa-4x text-muted mb-4"></i>
                    <h1 class="h3 mb-4">Нет подключения к интернету</h1>
                    <p class="lead mb-4">Кажется, вы находитесь в автономном режиме. Проверьте подключение к интернету и попробуйте снова.</p>
                    
                    <div class="mt-4">
                        <button class="btn btn-primary btn-lg" onclick="window.location.reload()">
                            <i class="fas fa-sync-alt me-2"></i> Обновить страницу
                        </button>
                    </div>
                    
                    <div class="mt-5 offline-recipes">
                        <h4 class="mb-4">Ранее просмотренные рецепты</h4>
                        <div class="text-muted" id="offline-content">
                            <p>Здесь будут отображаться доступные офлайн рецепты.</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем, доступно ли локальное хранилище
    if (typeof(Storage) !== "undefined" && localStorage.getItem('recentRecipes')) {
        try {
            const recentRecipes = JSON.parse(localStorage.getItem('recentRecipes'));
            const offlineContent = document.getElementById('offline-content');
            
            if (recentRecipes && recentRecipes.length > 0) {
                let html = '<div class="row">';
                
                recentRecipes.forEach(recipe => {
                    html += `
                    <div class="col-md-6 mb-4">
                        <div class="card h-100">
                            <div class="card-body">
                                <h5 class="card-title">${recipe.title}</h5>
                                <p class="card-text small">${recipe.description || 'Нет описания'}</p>
                            </div>
                        </div>
                    </div>
                    `;
                });
                
                html += '</div>';
                offlineContent.innerHTML = html;
            }
        } catch (e) {
            console.error('Ошибка при загрузке офлайн рецептов:', e);
        }
    }
});
</script>
@endsection
