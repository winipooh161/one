// Виджет для установки PWA на разных платформах

// Обнаружение устройства
function detectDevice() {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
    
    if (/android/i.test(userAgent)) {
        console.log('Обнаружено устройство: android');
        return 'android';
    }
    
    if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
        console.log('Обнаружено устройство: ios');
        return 'ios';
    }
    
    console.log('Обнаружено устройство: windows');
    return 'windows';
}

// Инициализация виджета установки PWA
function initPwaInstallWidget() {
    const deviceType = detectDevice();
    const widget = document.getElementById('pwa-install-widget');
    
    if (!widget) return;
    
    // Не показываем виджет, если приложение уже установлено
    if (window.matchMedia('(display-mode: standalone)').matches) {
        return;
    }
    
    // Проверяем, можно ли установить приложение
    let deferredPrompt;
    
    window.addEventListener('beforeinstallprompt', (e) => {
        e.preventDefault();
        deferredPrompt = e;
        
        // Показываем виджет
        setTimeout(() => {
            widget.classList.add('show');
        }, 3000);
        
        // Настраиваем инструкции в зависимости от устройства
        const deviceIcon = widget.querySelector('.device-icon');
        const installButton = widget.querySelector('.install-pwa-btn');
        
        if (deviceIcon) {
            deviceIcon.classList.add(deviceType);
        }
        
        // Кнопка закрытия
        const closeBtn = widget.querySelector('.close-pwa-widget');
        if (closeBtn) {
            closeBtn.addEventListener('touchstart', (e) => {
                e.preventDefault();
                widget.classList.remove('show');
                localStorage.setItem('pwa-widget-closed', Date.now().toString());
            });
            
            closeBtn.addEventListener('touchmove', (e) => {
                e.preventDefault();
            });
            
            closeBtn.addEventListener('click', () => {
                widget.classList.remove('show');
                localStorage.setItem('pwa-widget-closed', Date.now().toString());
            });
        }
        
        // Кнопка установки
        if (installButton && deferredPrompt) {
            installButton.addEventListener('click', async () => {
                deferredPrompt.prompt();
                const { outcome } = await deferredPrompt.userChoice;
                
                if (outcome === 'accepted') {
                    console.log('Пользователь установил приложение');
                    widget.classList.remove('show');
                    
                    // Отправляем аналитику
                    if (navigator.onLine) {
                        fetch('/pwa/track-install', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || ''
                            },
                            body: JSON.stringify({ device: deviceType })
                        }).catch(e => console.error('Ошибка отправки статистики установки:', e));
                    }
                }
                
                deferredPrompt = null;
            });
        }
    });
    
    // Проверяем, не скрыл ли пользователь виджет ранее
    const closed = localStorage.getItem('pwa-widget-closed');
    if (closed) {
        const closedTime = parseInt(closed);
        const now = Date.now();
        // Не показываем виджет в течение 3 дней после закрытия
        if (now - closedTime < 3 * 24 * 60 * 60 * 1000) {
            return;
        }
    }
}

// Запускаем при загрузке страницы
document.addEventListener('DOMContentLoaded', initPwaInstallWidget);
