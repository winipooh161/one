// Основной JavaScript-файл приложения для статических ссылок
// Импортируйте jQuery перед использованием
window.$ = window.jQuery = require('jquery');

// Инициализация всех скриптов сайта
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация изображений
    initImageLoader();
    
    // Другие инициализации...
});

// Функция для загрузки изображений
function initImageLoader() {
    const images = document.querySelectorAll('img[data-src]');
    if (images.length > 0) {
        images.forEach(img => {
            const src = img.getAttribute('data-src');
            if (src) {
                img.addEventListener('load', function() {
                    img.classList.add('loaded');
                });
                img.addEventListener('error', function(e) {
                    handleImageError(e);
                });
                img.setAttribute('src', src);
            }
        });
    }
}

// Обработчик ошибок изображений
function handleImageError(e) {
    if (!e || !e.target) return;
    
    const img = e.target;
    const container = img.closest('.recipe-image-container');
    
    if (container) {
        container.classList.add('no-image');
        // Добавляем заместитель
        const placeholder = document.createElement('div');
        placeholder.className = 'placeholder-image';
        placeholder.innerHTML = `<span>Изображение недоступно</span>`;
        container.appendChild(placeholder);
    }
}
