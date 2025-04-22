// Регистрация Service Worker для PWA
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/service-worker.js')
            .then(registration => {
                console.log('ServiceWorker registered: ', registration);
            })
            .catch(error => {
                console.log('ServiceWorker registration failed: ', error);
            });
    });
}

// Показ предложения об установке приложения
let deferredPrompt;

window.addEventListener('beforeinstallprompt', (e) => {
    // Показываем кнопку установки
    e.preventDefault();
    deferredPrompt = e;
    
    // Опционально: показать кнопку установки
    const installButton = document.getElementById('install-pwa');
    const installPrompt = document.querySelector('.pwa-install-prompt');
    
    if (installPrompt) {
        installPrompt.classList.remove('d-none');
    }
    
    if (installButton) {
        installButton.style.display = 'inline-block';
        installButton.addEventListener('click', installApp);
    }
});

function installApp() {
    if (!deferredPrompt) return;
    
    // Показываем диалог установки
    deferredPrompt.prompt();
    
    // Ждем выбора пользователя
    deferredPrompt.userChoice.then((choiceResult) => {
        if (choiceResult.outcome === 'accepted') {
            console.log('User accepted the install prompt');
        } else {
            console.log('User dismissed the install prompt');
        }
        deferredPrompt = null;
        
        // Скрываем кнопку установки
        const installPrompt = document.querySelector('.pwa-install-prompt');
        if (installPrompt) {
            installPrompt.classList.add('d-none');
        }
    });
}

// Определяем, установлено ли приложение
window.addEventListener('appinstalled', () => {
    // Скрываем кнопку установки
    const installPrompt = document.querySelector('.pwa-install-prompt');
    if (installPrompt) {
        installPrompt.classList.add('d-none');
    }
    console.log('PWA was installed');
});

// Проверяем режим отображения для уже установленных приложений
if (window.matchMedia('(display-mode: standalone)').matches) {
    console.log('Application is running in standalone mode');
    // Здесь можно скрыть элементы UI, которые не нужны в установленном приложении
}
