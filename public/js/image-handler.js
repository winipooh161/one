document.addEventListener('DOMContentLoaded', function() {
    // Функция для обработки ошибок загрузки изображений
    const imgErrorHandler = function(event) {
        const img = event.target;
        
        // Пропускаем изображения новостей, которые отмечены атрибутом data-no-random
        if (img.hasAttribute('data-no-random')) {
            console.log('Изображение с атрибутом data-no-random пропущено:', img.src);
            return;
        }
        
        const isCategory = img.closest('.category-card') !== null || img.classList.contains('category-img');
        const placeholderPath = isCategory ? '/images/category-placeholder.jpg' : '/images/placeholder.jpg';
        
        if (!img.src.includes('placeholder')) {
            console.log('Заменяем неудачное изображение:', img.src);
            img.src = placeholderPath;
        }
        
        // Предотвращаем повторные ошибки
        img.onerror = null;
    };
    
    // Применяем обработчик ко всем изображениям
    document.querySelectorAll('img').forEach(img => {
        if (!img.hasAttribute('data-skip-error-handler')) {
            img.addEventListener('error', imgErrorHandler);
        }
    });
});
