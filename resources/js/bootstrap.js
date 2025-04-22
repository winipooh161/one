/**
 * Bootstrap - подключаем только один раз 
 */
import * as bootstrap from 'bootstrap';
window.bootstrap = bootstrap;

/**
 * We'll load the axios HTTP library which allows us to easily issue requests
 * to our Laravel back-end. This library automatically handles sending the
 * CSRF token as a header based on the value of the "XSRF" token cookie.
 */

import axios from 'axios';
window.axios = axios;

window.axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';

/**
 * Echo exposes an expressive API for subscribing to channels and listening
 * for events that are broadcast by Laravel. Echo and event broadcasting
 * allows your team to easily build robust real-time web applications.
 */

// import Echo from 'laravel-echo';

// import Pusher from 'pusher-js';
// window.Pusher = Pusher;

// window.Echo = new Echo({
//     broadcaster: 'pusher',
//     key: import.meta.env.VITE_PUSHER_APP_KEY,
//     cluster: import.meta.env.VITE_PUSHER_APP_CLUSTER ?? 'mt1',
//     wsHost: import.meta.env.VITE_PUSHER_HOST ?? `ws-${import.meta.env.VITE_PUSHER_APP_CLUSTER}.pusher.com`,
//     wsPort: import.meta.env.VITE_PUSHER_PORT ?? 80,
//     wssPort: import.meta.env.VITE_PUSHER_PORT ?? 443,
//     forceTLS: (import.meta.env.VITE_PUSHER_SCHEME ?? 'https') === 'https',
//     enabledTransports: ['ws', 'wss'],
// });

/**
 * Функции для обработки изображений и улучшения пользовательского опыта
 */
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация всех компонентов Bootstrap отложена до полной загрузки DOM
    // Это поможет избежать ошибок, если элементы еще не созданы
    
    // Глобальная функция для обработки ошибок при загрузке изображений
    window.handleImageError = function(img) {
        // Определяем, является ли изображение категорией или рецептом
        const isCategory = img.closest('.category-card') !== null || img.classList.contains('category-img');
        const placeholderPath = isCategory ? '/images/category-placeholder.jpg' : '/images/placeholder.jpg';
        
        // Заменяем сломанное изображение на заглушку
        if (!img.src.includes('placeholder')) {
            img.src = placeholderPath;
        }
        
        // Предотвращаем повторные ошибки
        img.onerror = null;
    };
});
