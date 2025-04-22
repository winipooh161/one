/**
 * Функционал для установки PWA (Progressive Web App)
 */
document.addEventListener('DOMContentLoaded', function() {
    // Проверяем поддержку функции установки PWA
    let deferredPrompt;
    const installButton = document.getElementById('install-pwa');
    
    if (!installButton) return;
    
    // Скрываем кнопку по умолчанию
    installButton.style.display = 'none';
    
    // Перехватываем событие установки
    window.addEventListener('beforeinstallprompt', (e) => {
        // Предотвращаем показ стандартного диалога установки
        e.preventDefault();
        // Сохраняем событие для использования позже
        deferredPrompt = e;
        // Показываем кнопку установки
        installButton.style.display = 'flex';
        
        // Добавляем обработчик клика для кнопки установки
        installButton.addEventListener('click', async () => {
            // Скрываем кнопку, чтобы избежать повторных кликов
            installButton.style.display = 'none';
            
            // Показываем диалог установки
            deferredPrompt.prompt();
            // Ожидаем выбор пользователя
            const { outcome } = await deferredPrompt.userChoice;
            
            // Логируем результат
            console.log(`Пользователь ${outcome === 'accepted' ? 'установил' : 'отклонил'} установку`);
            
            // Очищаем сохраненное событие
            deferredPrompt = null;
        });
    });
    
    // Если приложение уже установлено, скрываем кнопку
    window.addEventListener('appinstalled', () => {
        installButton.style.display = 'none';
        deferredPrompt = null;
        console.log('Приложение успешно установлено');
        
        // Отправляем аналитику об установке
        if (typeof(ym) !== 'undefined') {
            ym(96182066, 'reachGoal', 'app_installed');
        }
    });
});
