/**
 * PWA Install Widget
 * Обрабатывает установку PWA на различных устройствах
 */
document.addEventListener('DOMContentLoaded', function() {
    // Инициализация виджета установки PWA
    initPwaInstallWidget();
});

/**
 * Инициализация виджета установки PWA
 */
function initPwaInstallWidget() {
    const installWidget = document.getElementById('pwa-install-widget');
    const installButton = document.getElementById('install-pwa');
    const closeButton = document.querySelector('.close-pwa-widget');
    
    if (!installWidget || !installButton) return;
    
    // Определяем устройство пользователя
    const deviceType = detectDeviceType();
    console.log('Обнаружено устройство:', deviceType);
    
    // Глобальная переменная для хранения события установки
    let deferredPrompt = null;
    
    // Настраиваем контент виджета для конкретного устройства
    setWidgetContent(deviceType, installButton);
    
    // Функция для закрытия виджета с сохранением в localStorage
    const dismissWidget = () => {
        hideWidget(installWidget);
        // Запоминаем, что пользователь закрыл виджет
        localStorage.setItem('pwaWidgetDismissed', Date.now());
    };
    
    // Закрытие по нажатию на кнопку закрытия
    if (closeButton) {
        closeButton.addEventListener('click', dismissWidget);
    }
    
    // Закрытие по клику вне виджета
    document.addEventListener('click', function(event) {
        if (installWidget.classList.contains('show') && 
            !installWidget.contains(event.target) && 
            event.target !== installWidget) {
            dismissWidget();
        }
    });
    
    // Закрытие по нажатию клавиши ESC
    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape' && installWidget.classList.contains('show')) {
            dismissWidget();
        }
    });
    
    // Закрытие свайпом вниз (для мобильных устройств)
    let touchStartY = 0;
    let touchEndY = 0;
    
    installWidget.addEventListener('touchstart', function(event) {
        touchStartY = event.changedTouches[0].screenY;
    }, false);
    
    installWidget.addEventListener('touchend', function(event) {
        touchEndY = event.changedTouches[0].screenY;
        // Если пользователь сделал свайп вниз
        if (touchEndY - touchStartY > 50) {
            dismissWidget();
        }
    }, false);
    
    // Автоматическое скрытие через 30 секунд
    let autoHideTimeout;
    
    // Функция сброса таймера автозакрытия
    const resetAutoHideTimer = () => {
        clearTimeout(autoHideTimeout);
        autoHideTimeout = setTimeout(() => {
            if (installWidget.classList.contains('show')) {
                dismissWidget();
            }
        }, 30000); // 30 секунд
    };
    
    // Сбрасываем таймер при взаимодействии с виджетом
    installWidget.addEventListener('mousemove', resetAutoHideTimer);
    installWidget.addEventListener('touchmove', resetAutoHideTimer);
    installWidget.addEventListener('click', resetAutoHideTimer);
    
    // Обработчик нажатия кнопки установки
    installButton.addEventListener('click', function(event) {
        event.preventDefault();
        
        if (deviceType === 'ios') {
            // Для iOS показываем инструкцию
            showIOSInstallInstructions();
            return;
        }
        
        // Для других устройств используем deferredPrompt если он доступен
        if (deferredPrompt) {
            console.log('Вызываем диалог установки из события');
            // Показываем диалог установки
            deferredPrompt.prompt();
            
            // Ожидаем ответа пользователя
            deferredPrompt.userChoice.then((choiceResult) => {
                if (choiceResult.outcome === 'accepted') {
                    console.log('Пользователь принял установку');
                    // Отправляем событие в аналитику
                    if (typeof ym !== 'undefined') {
                        ym(100639873, 'reachGoal', 'pwa_installed');
                    }
                } else {
                    console.log('Пользователь отклонил установку');
                }
                
                // Очищаем переменную
                deferredPrompt = null;
                
                // Скрываем виджет
                hideWidget(installWidget);
            });
        } else {
            // Если событие не доступно, показываем альтернативные инструкции
            showInstallInstructions(deviceType);
        }
    });
    
    // Перехватываем событие установки (работает на Android и десктопах с Chrome, Edge и др.)
    window.addEventListener('beforeinstallprompt', (e) => {
        // Предотвращаем автоматическое открытие подсказки
        e.preventDefault(); 
        
        // Сохраняем событие установки для дальнейшего использования
        deferredPrompt = e;
        console.log('Перехвачено событие beforeinstallprompt');
        
        // Показываем виджет установки (только если пользователь не закрывал его недавно)
        if (shouldShowWidget()) {
            showWidget(installWidget);
        }
        
        // Для Android и десктопа делаем кнопку установки активной
        installButton.classList.remove('disabled');
        installButton.disabled = false;
    });
    
    // Если это iOS, показываем виджет через 2 секунды после загрузки
    if (deviceType === 'ios' && shouldShowWidget() && !isStandaloneMode()) {
        setTimeout(() => {
            showWidget(installWidget);
            // Запускаем таймер автоматического скрытия
            resetAutoHideTimer();
            // Для iOS делаем кнопку активной
            installButton.classList.remove('disabled');
            installButton.disabled = false;
        }, 2000);
    }
    
    // Устанавливаем обработчик события завершения установки
    window.addEventListener('appinstalled', (evt) => {
        // Скрываем виджет после установки
        hideWidget(installWidget);
        console.log('PWA было успешно установлено');
        
        // Отправляем событие в аналитику
        if (typeof ym !== 'undefined') {
            ym(100639873, 'reachGoal', 'pwa_installed_success');
        }
    });
    
    // Проверяем, запущено ли приложение в режиме standalone (уже установлено)
    if (isStandaloneMode()) {
        console.log('Приложение запущено в режиме standalone (уже установлено)');
        return;
    }
}

/**
 * Показать виджет установки
 */
function showWidget(widget) {
    if (!widget) return;
    
    widget.style.display = 'block';
    setTimeout(() => {
        widget.classList.add('show');
    }, 10);
    
    console.log('Показан виджет установки');
}

/**
 * Скрыть виджет установки
 */
function hideWidget(widget) {
    if (!widget) return;
    
    widget.classList.remove('show');
    setTimeout(() => {
        widget.style.display = 'none';
    }, 300);
    
    console.log('Скрыт виджет установки');
}

/**
 * Определить тип устройства пользователя
 */
function detectDeviceType() {
    const userAgent = navigator.userAgent || navigator.vendor || window.opera;
    
    // Проверяем iOS
    if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
        return 'ios';
    }
    
    // Проверяем Android
    if (/android/i.test(userAgent)) {
        return 'android';
    }
    
    // Проверяем Windows
    if (/Windows NT/.test(userAgent)) {
        return 'windows';
    }
    
    // Проверяем macOS
    if (/Macintosh|Mac OS X/.test(userAgent)) {
        return 'mac';
    }
    
    // Остальные устройства
    return 'other';
}

/**
 * Определить, запущено ли приложение в режиме standalone (установлено)
 */
function isStandaloneMode() {
    return (window.matchMedia('(display-mode: standalone)').matches) || 
           (window.navigator.standalone) || 
           document.referrer.includes('android-app://');
}

/**
 * Настроить контент виджета для определенного устройства
 */
function setWidgetContent(deviceType, installButton) {
    const contentElement = document.querySelector('.pwa-widget-content');
    const deviceIconElement = document.querySelector('.device-icon');
    
    if (!contentElement || !deviceIconElement) return;
    
    // Устанавливаем иконку устройства
    let deviceIcon = 'fas fa-mobile-alt';
    
    switch(deviceType) {
        case 'ios':
            deviceIcon = 'fab fa-apple';
            break;
        case 'android':
            deviceIcon = 'fab fa-android';
            break;
        case 'windows':
            deviceIcon = 'fab fa-windows';
            break;
        case 'mac':
            deviceIcon = 'fab fa-apple';
            break;
    }
    
    deviceIconElement.className = deviceIcon + ' device-icon';
    
    // Настраиваем контент в зависимости от устройства
    switch(deviceType) {
        case 'ios':
            contentElement.innerHTML = `
                <p>Установите наше приложение на ваш iPhone или iPad:</p>
                <ol>
                    <li>Нажмите на <strong><i class="fas fa-share"></i> Поделиться</strong> внизу экрана</li>
                    <li>В появившемся меню выберите <strong>На экран «Домой»</strong></li>
                    <li>Нажмите <strong>Добавить</strong> в правом верхнем углу</li>
                </ol>
                <p class="small text-muted">После установки приложение будет доступно с главного экрана вашего устройства</p>
            `;
            
            if (installButton) {
                installButton.innerHTML = '<i class="fas fa-info-circle me-2"></i> Показать инструкцию';
                // По умолчанию делаем кнопку неактивной, активируем после загрузки страницы
                installButton.classList.add('disabled');
                installButton.disabled = true;
            }
            break;
            
        case 'android':
            contentElement.innerHTML = `
                <p>Установите наше приложение на ваш Android:</p>
                <p>Нажмите кнопку "Установить приложение" ниже и подтвердите установку.</p>
                <p class="small text-muted">Приложение не занимает много места и работает даже без интернета!</p>
            `;
            
            if (installButton) {
                installButton.innerHTML = '<i class="fas fa-download me-2"></i> Установить приложение';
                // По умолчанию делаем кнопку неактивной, активируем после события
                installButton.classList.add('disabled');
                installButton.disabled = true;
            }
            break;
            
        case 'windows':
        case 'mac':
            contentElement.innerHTML = `
                <p>Установите наше приложение на ваш компьютер:</p>
                <p>Нажмите на кнопку "Установить приложение" ниже или на значок <i class="fas fa-plus"></i> в адресной строке.</p>
                <p class="small text-muted">Приложение будет доступно с рабочего стола и панели задач.</p>
            `;
            
            if (installButton) {
                installButton.innerHTML = '<i class="fas fa-download me-2"></i> Установить приложение';
                // По умолчанию делаем кнопку неактивной, активируем после события
                installButton.classList.add('disabled');
                installButton.disabled = true;
            }
            break;
            
        default:
            contentElement.innerHTML = `
                <p>Установите наше приложение для быстрого доступа:</p>
                <p>Нажмите на кнопку "Установить приложение" ниже.</p>
                <p class="small text-muted">Приложение будет работать даже без интернета!</p>
            `;
            
            if (installButton) {
                installButton.innerHTML = '<i class="fas fa-download me-2"></i> Установить приложение';
                // По умолчанию делаем кнопку неактивной, активируем после события
                installButton.classList.add('disabled');
                installButton.disabled = true;
            }
    }
}

/**
 * Показать инструкции по установке для iOS
 */
function showIOSInstallInstructions() {
    alert('Чтобы установить приложение на iOS:\n\n1. Нажмите кнопку "Поделиться" 📤 внизу экрана\n2. Прокрутите и выберите "На экран «Домой»"\n3. Нажмите "Добавить" в правом верхнем углу');
    
    // Также можно открыть модальное окно с красивыми инструкциями вместо alert
    // showInstallModal('ios');
}

/**
 * Показать инструкции по установке для конкретного устройства
 */
function showInstallInstructions(deviceType) {
    switch(deviceType) {
        case 'windows':
            alert('Для установки на Windows:\n\n1. Нажмите на значок "+" или "Установить" в адресной строке браузера\n2. Или нажмите на три точки ⋮ в правом верхнем углу, затем выберите "Установить приложение"');
            break;
        case 'mac':
            alert('Для установки на Mac:\n\n1. Используйте браузер Safari\n2. В меню выберите "Поделиться"\n3. Затем выберите "Добавить на рабочий стол"');
            break;
        case 'android':
            alert('Для установки на Android:\n\n1. Откройте меню браузера (три точки)\n2. Выберите "Установить приложение" или "Добавить на главный экран"');
            break;
        default:
            alert('Для установки приложения используйте современный браузер Chrome, Edge или Safari и выберите опцию "Установить приложение" в меню браузера.');
    }
    
    // Можно открыть страницу с подробными инструкциями
    // window.location.href = '/pwa/install';
}

/**
 * Проверить, нужно ли показывать виджет (не показываем, если недавно закрыт)
 */
function shouldShowWidget() {
    const lastDismissed = localStorage.getItem('pwaWidgetDismissed');
    if (!lastDismissed) return true;
    
    // Показываем снова через 3 дня после закрытия
    const dismissedTime = parseInt(lastDismissed);
    const now = Date.now();
    const threeDays = 3 * 24 * 60 * 60 * 1000;
    
    return (now - dismissedTime) > threeDays;
}
