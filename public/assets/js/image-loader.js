// Код для обработки ошибок загрузки изображений и ленивой загрузки

// Функция-обработчик ошибок загрузки изображений
function handleImageError(e) {
    if (!e || !e.target) return;
    
    const img = e.target;
    const container = img.closest('.recipe-image-container');
    
    if (container) {
        // Добавляем класс, чтобы показать заместитель
        container.classList.add('no-image');
        
        // Создаем элемент заместителя
        const placeholder = document.createElement('div');
        placeholder.className = 'image-placeholder';
        placeholder.innerHTML = '<i class="fa fa-image"></i><span>Изображение не найдено</span>';
        
        // Убираем сломанное изображение и вставляем заместитель
        img.style.display = 'none';
        container.appendChild(placeholder);
    }
}

// Ленивая загрузка изображений
function initLazyLoading() {
    // Проверяем поддержку Intersection Observer
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                        
                        // Обрабатываем событие загрузки
                        img.addEventListener('load', () => {
                            img.classList.add('loaded');
                        });
                        
                        // Обрабатываем ошибки загрузки
                        img.addEventListener('error', handleImageError);
                    }
                    
                    // Перестаем наблюдать за изображением
                    imageObserver.unobserve(img);
                }
            });
        });
        
        // Начинаем наблюдение за всеми изображениями
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Запасной вариант для старых браузеров
        document.querySelectorAll('img[data-src]').forEach(img => {
            img.src = img.getAttribute('data-src');
            img.addEventListener('error', handleImageError);
        });
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    initLazyLoading();
    
    // Также обрабатываем обычные изображения
    document.querySelectorAll('img:not([data-src])').forEach(img => {
        img.addEventListener('error', handleImageError);
    });
});
