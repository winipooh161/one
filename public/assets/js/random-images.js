/**
 * Функционал для обработки ошибок загрузки изображений и
 * установки случайных изображений
 */
// Функция для выбора случайного изображения из папки defolt
window.getRandomDefaultImage = function() {
    // Массив доступных изображений в папке defolt
    const defaultImages = [
        '/images/defolt/default1.jpg',
        '/images/defolt/default2.jpg',
        '/images/defolt/default3.jpg',
        '/images/defolt/default4.jpg',
        '/images/defolt/default5.jpg',
        '/images/defolt/default6.jpg',
        '/images/defolt/default7.jpg',
        '/images/defolt/default8.jpg',
        '/images/defolt/default9.jpg',
        '/images/defolt/default10.jpg',
        '/images/defolt/default11.jpg'
    ];
    // Выбираем случайное изображение из массива
    const randomIndex = Math.floor(Math.random() * defaultImages.length);
    return defaultImages[randomIndex];
};

// Функция для обработки ошибок загрузки изображений
window.handleImageError = function(img) {
    // Получаем случайное изображение
    const randomImage = window.getRandomDefaultImage();
    // Устанавливаем его в качестве источника
    img.src = randomImage;
    // Сбрасываем обработчик ошибок, чтобы избежать рекурсии
    img.onerror = null;
};

// Инициализация всех изображений при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    // Глобальный обработчик ошибок загрузки изображений
    
    // Исправляем пути с дублированием
    document.querySelectorAll('img[src*="/images/images/"]').forEach(function(img) {
        img.src = img.src.replace('/images/images/', '/images/');
    });
    
    // Находим все изображения на странице
    const images = document.querySelectorAll('img');
    
    // Для каждого изображения добавляем обработчик ошибок
    images.forEach(img => {
        if (!img.hasAttribute('data-no-random') && !img.hasAttribute('data-skip-error-handler')) {
            img.addEventListener('error', function() {
                window.handleImageError(this);
            });
        }
    });
    
    // Остальной код обработчика
    const imgErrorHandler = function(event) {
        const img = event.target;
        const isCategory = img.closest('.category-card') !== null || img.classList.contains('category-img');
        const placeholderPath = isCategory ? '/images/category-placeholder.jpg' : '/images/placeholder.jpg';
        
        if (!img.src.includes('placeholder')) {
            console.log('Заменяем неудачное изображение:', img.src);
            img.src = placeholderPath;
        }
        
        // Предотвращаем повторные ошибки
        img.onerror = null;
    };
});
