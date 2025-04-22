/**
 * Image Loader - оптимизированная загрузка изображений 
 * с поддержкой ленивой загрузки и обработкой ошибок
 */
document.addEventListener('DOMContentLoaded', function() {
    // Включаем ленивую загрузку для всех изображений с атрибутом data-src
    initLazyLoading();
    
    // Обработчик ошибок изображений
    setupImageErrorHandling();
    
    // Настраиваем обработку изображений для PWA и офлайн-режима
    setupPwaImageHandling();
});

/**
 * Инициализация ленивой загрузки изображений
 */
function initLazyLoading() {
    // Проверяем поддержку IntersectionObserver
    if ('IntersectionObserver' in window) {
        const lazyImages = document.querySelectorAll('img[data-src]');
        
        const imageObserver = new IntersectionObserver((entries, observer) => {
            entries.forEach(entry => {
                if (entry.isIntersecting) {
                    const img = entry.target;
                    const src = img.getAttribute('data-src');
                    
                    if (src) {
                        img.src = src;
                        img.removeAttribute('data-src');
                        
                        // Добавляем обработку загрузки изображения
                        img.addEventListener('load', () => {
                            img.classList.add('loaded');
                        });
                        
                        // Отключаем наблюдение после загрузки
                        observer.unobserve(img);
                    }
                }
            });
        }, { rootMargin: '50px 0px' });
        
        lazyImages.forEach(img => {
            imageObserver.observe(img);
        });
    } else {
        // Fallback для браузеров без поддержки IntersectionObserver
        loadAllImages();
    }
}

/**
 * Fallback-функция для загрузки всех изображений
 */
function loadAllImages() {
    const lazyImages = document.querySelectorAll('img[data-src]');
    
    lazyImages.forEach(img => {
        const src = img.getAttribute('data-src');
        if (src) {
            img.src = src;
            img.removeAttribute('data-src');
            
            img.addEventListener('load', () => {
                img.classList.add('loaded');
            });
        }
    });
}

/**
 * Настройка обработки ошибок загрузки изображений
 */
function setupImageErrorHandling() {
    document.querySelectorAll('img:not([data-skip-error-handler])').forEach(img => {
        img.addEventListener('error', handleImageError);
    });
}

/**
 * Обработчик ошибок загрузки изображений
 */
function handleImageError(event) {
    const img = event.target;
    const isCategory = img.closest('.category-card') !== null || img.classList.contains('category-img');
    const placeholderPath = isCategory ? '/images/category-placeholder.jpg' : '/images/placeholder.jpg';
    
    if (!img.src.includes('placeholder')) {
        console.log('Заменяем неудачное изображение:', img.src);
        img.src = placeholderPath;
    }
    
    // Предотвращаем повторные ошибки
    img.onerror = null;
}

/**
 * Настройка обработки изображений для PWA и офлайн-режима
 */
function setupPwaImageHandling() {
    // Проверяем статус сети
    if (!navigator.onLine) {
        handleOfflineImages();
    }
    
    // Обработчик изменения статуса сети
    window.addEventListener('online', () => {
        console.log('Соединение восстановлено, обновляем изображения');
        document.querySelectorAll('.offline-image-placeholder').forEach(placeholder => {
            const img = document.createElement('img');
            img.src = placeholder.getAttribute('data-src');
            img.alt = placeholder.getAttribute('data-alt') || '';
            img.className = placeholder.getAttribute('data-class') || '';
            
            img.addEventListener('load', () => {
                placeholder.parentNode.replaceChild(img, placeholder);
            });
            
            img.addEventListener('error', handleImageError);
        });
    });
    
    window.addEventListener('offline', () => {
        console.log('Соединение потеряно, переключаемся на офлайн-режим для изображений');
        handleOfflineImages();
    });
}

/**
 * Обработка изображений в офлайн-режиме
 */
function handleOfflineImages() {
    // Заменяем не загруженные изображения на плейсхолдеры
    document.querySelectorAll('img:not(.loaded)').forEach(img => {
        if (!img.complete || img.naturalHeight === 0) {
            const placeholder = document.createElement('div');
            placeholder.className = 'offline-image-placeholder ' + (img.className || '');
            placeholder.setAttribute('data-src', img.src);
            placeholder.setAttribute('data-alt', img.alt);
            placeholder.setAttribute('data-class', img.className);
            
            placeholder.innerHTML = `
                <div class="d-flex align-items-center justify-content-center h-100">
                    <div class="text-center text-muted">
                        <i class="fas fa-wifi-slash"></i>
                        <div class="mt-2 small">Офлайн</div>
                    </div>
                </div>
            `;
            
            if (img.parentNode) {
                img.parentNode.replaceChild(placeholder, img);
            }
        }
    });
}
