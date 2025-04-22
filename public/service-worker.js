/**
 * Service Worker для PWA приложения Яедок
 * Обеспечивает кэширование ресурсов и функционирование в офлайн-режиме
 */

// Название и версия кэша - при обновлении ресурсов нужно обновить версию
const CACHE_NAME = 'yaedok-cache-v1.1';

// Ресурсы для предварительного кэширования
const PRECACHE_URLS = [
    '/',
    '/offline',
    '/css/app.css',
    '/js/app.js',
    '/js/pwa-install-widget.js',
    '/js/image-loader.js',
    '/images/logo.png',
    '/images/placeholder.jpg',
    '/images/category-placeholder.jpg',
    '/assets/fonts/fontawesome-webfont.woff2',
    '/favicon.ico',
    'https://fonts.bunny.net/css?family=Nunito:400,600,700&display=swap',
    'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css'
];

// Установка сервис-воркера и предварительное кэширование
self.addEventListener('install', event => {
    // Пропускаем фазу ожидания и сразу активируем
    self.skipWaiting();

    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            console.log('[Service Worker] Предварительное кэширование ресурсов');
            return cache.addAll(PRECACHE_URLS);
        })
    );
});

// Активация сервис-воркера и очистка старых кэшей
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.filter(cacheName => {
                    return cacheName.startsWith('yaedok-cache-') && cacheName !== CACHE_NAME;
                }).map(cacheName => {
                    console.log('[Service Worker] Удаление старого кэша: ' + cacheName);
                    return caches.delete(cacheName);
                })
            );
        }).then(() => {
            console.log('[Service Worker] Активирован и контролирует страницу.');
            return self.clients.claim();
        })
    );
});

// Стратегия кэширования: сначала проверяем кэш, при отсутствии - запрос к сети
self.addEventListener('fetch', event => {
    // Пропускаем не-GET запросы и запросы к API
    if (event.request.method !== 'GET' || 
        event.request.url.includes('/api/') ||
        event.request.url.includes('chrome-extension://')) {
        return;
    }

    // Обрабатываем HTML-запросы особым образом
    if (event.request.headers.get('accept') && 
        event.request.headers.get('accept').includes('text/html')) {
        event.respondWith(
            fetch(event.request)
                .then(response => {
                    // Если запрос успешен, кэшируем копию
                    const responseClone = response.clone();
                    caches.open(CACHE_NAME).then(cache => {
                        cache.put(event.request, responseClone);
                    });
                    return response;
                })
                .catch(() => {
                    // При ошибке сети ищем в кэше
                    return caches.match(event.request)
                        .then(response => {
                            // Если есть в кэше, возвращаем оттуда
                            if (response) {
                                return response;
                            }
                            // Иначе возвращаем офлайн-страницу
                            return caches.match('/offline');
                        });
                })
        );
        return;
    }

    // Для остальных ресурсов используем стратегию сеть с кэшированием
    event.respondWith(
        caches.open(CACHE_NAME).then(cache => {
            return cache.match(event.request)
                .then(cachedResponse => {
                    // Возвращаем из кэша, если есть
                    if (cachedResponse) {
                        // Делаем фоновое обновление кэша
                        fetch(event.request)
                            .then(networkResponse => {
                                cache.put(event.request, networkResponse.clone());
                            })
                            .catch(() => {});
                        return cachedResponse;
                    }

                    // Если нет в кэше, делаем запрос к сети
                    return fetch(event.request)
                        .then(networkResponse => {
                            // Кэшируем ответ от сети
                            cache.put(event.request, networkResponse.clone());
                            return networkResponse;
                        })
                        .catch(error => {
                            console.error('[Service Worker] Fetch error:', error);
                            // Для изображений возвращаем плейсхолдер
                            if (event.request.url.match(/\.(jpg|jpeg|png|gif|webp|svg)$/)) {
                                return caches.match('/images/placeholder.jpg');
                            }
                            throw error;
                        });
                });
        })
    );
});

// Обработка фоновой синхронизации (когда соединение восстановлено)
self.addEventListener('sync', event => {
    if (event.tag === 'sync-saved-recipes') {
        event.waitUntil(syncSavedRecipes());
    }
});

// Функция синхронизации сохраненных рецептов
async function syncSavedRecipes() {
    console.log('[Service Worker] Синхронизация сохраненных рецептов при восстановлении соединения');
    // Здесь можно реализовать функционал синхронизации данных
}
