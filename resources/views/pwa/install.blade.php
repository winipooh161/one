@extends('layouts.app')

@section('title', 'Установить приложение Яедок')
@section('description', 'Инструкция по установке приложения Яедок на ваше устройство для доступа к рецептам даже без интернета')
@section('keywords', 'установить приложение, приложение с рецептами, PWA, мобильное приложение')

@section('breadcrumbs')
    <li class="breadcrumb-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
        <a href="{{ route('pwa.install') }}" itemprop="item"><span itemprop="name">Установка приложения</span></a>
        <meta itemprop="position" content="2" />
    </li>
@endsection

@section('content')
<div class="container my-4 pwa-install-page">
    <h1 class="mb-4">Установка приложения Я едок</h1>
    
    <div class="row">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h2>Почему стоит установить наше приложение?</h2>
                    <ul class="list-unstyled">
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Быстрый доступ</strong> к любимым рецептам прямо с рабочего стола
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Работает без интернета</strong> - готовьте даже при отсутствии соединения
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Не занимает много места</strong> - приложение легкое и не требовательное к ресурсам
                        </li>
                        <li class="mb-3">
                            <i class="fas fa-check-circle text-success me-2"></i>
                            <strong>Бесплатно</strong> - никакой платы за скачивание и использование
                        </li>
                    </ul>
                </div>
            </div>

            <div class="row mb-4">
                <!-- iOS инструкция -->
                <div class="col-md-6 mb-4">
                    <div class="device-section card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fab fa-apple device-icon text-dark"></i>
                            <h3 class="device-name">iPhone / iPad</h3>
                            <div class="steps text-start">
                                <div class="step">
                                    <span class="badge bg-primary me-2">1</span> Откройте сайт в браузере <strong>Safari</strong>
                                </div>
                                <div class="step">
                                    <span class="badge bg-primary me-2">2</span> Нажмите на кнопку <strong><i class="fas fa-share"></i> Поделиться</strong> внизу экрана
                                </div>
                                <div class="step">
                                    <span class="badge bg-primary me-2">3</span> Прокрутите вниз и нажмите <strong>"На экран «Домой»"</strong>
                                </div>
                                <div class="step">
                                    <span class="badge bg-primary me-2">4</span> Нажмите <strong>"Добавить"</strong> в правом верхнем углу
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Android инструкция -->
                <div class="col-md-6 mb-4">
                    <div class="device-section card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fab fa-android device-icon text-success"></i>
                            <h3 class="device-name">Android</h3>
                            <div class="steps text-start">
                                <div class="step">
                                    <span class="badge bg-success me-2">1</span> Откройте сайт в браузере <strong>Chrome</strong>
                                </div>
                                <div class="step">
                                    <span class="badge bg-success me-2">2</span> Появится баннер установки или нажмите на три точки <strong><i class="fas fa-ellipsis-v"></i></strong> в правом верхнем углу
                                </div>
                                <div class="step">
                                    <span class="badge bg-success me-2">3</span> Выберите <strong>"Установить приложение"</strong> или <strong>"Добавить на главный экран"</strong>
                                </div>
                                <div class="step">
                                    <span class="badge bg-success me-2">4</span> Подтвердите установку
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Windows инструкция -->
                <div class="col-md-6 mb-4">
                    <div class="device-section card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fab fa-windows device-icon text-primary"></i>
                            <h3 class="device-name">Windows / Chrome</h3>
                            <div class="steps text-start">
                                <div class="step">
                                    <span class="badge bg-primary me-2">1</span> Откройте сайт в браузере <strong>Chrome или Edge</strong>
                                </div>
                                <div class="step">
                                    <span class="badge bg-primary me-2">2</span> Нажмите на значок установки <strong><i class="fas fa-plus"></i></strong> в адресной строке
                                </div>
                                <div class="step">
                                    <span class="badge bg-primary me-2">3</span> Или нажмите на кнопку <strong>"Установить"</strong> ниже
                                </div>
                                <div class="step">
                                    <span class="badge bg-primary me-2">4</span> Подтвердите установку в появившемся диалоге
                                </div>
                            </div>
                            <div class="mt-3" id="windows-install-container">
                                <button id="windows-install-pwa" class="btn btn-primary d-flex align-items-center mx-auto">
                                    <i class="fas fa-download me-2"></i> Установить приложение
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Mac инструкция -->
                <div class="col-md-6 mb-4">
                    <div class="device-section card h-100 border-0 shadow-sm">
                        <div class="card-body text-center">
                            <i class="fab fa-apple device-icon text-secondary"></i>
                            <h3 class="device-name">Mac OS</h3>
                            <div class="steps text-start">
                                <div class="step">
                                    <span class="badge bg-secondary me-2">1</span> Откройте сайт в браузере <strong>Safari</strong>
                                </div>
                                <div class="step">
                                    <span class="badge bg-secondary me-2">2</span> В меню выберите <strong>Safari > Настройки > Веб-сайты</strong>
                                </div>
                                <div class="step">
                                    <span class="badge bg-secondary me-2">3</span> Включите <strong>"Добавление в Dock"</strong> для этого сайта
                                </div>
                                <div class="step">
                                    <span class="badge bg-secondary me-2">4</span> Перезагрузите страницу и нажмите <strong>"Установить"</strong>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-lg-4">
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3>Особенности приложения</h3>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-primary text-white p-3 rounded-circle me-3">
                            <i class="fas fa-bolt"></i>
                        </div>
                        <div>
                            <h4 class="h6 mb-1">Быстрая загрузка</h4>
                            <p class="small text-muted mb-0">Мгновенный доступ к рецептам</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center mb-3">
                        <div class="bg-success text-white p-3 rounded-circle me-3">
                            <i class="fas fa-wifi-slash"></i>
                        </div>
                        <div>
                            <h4 class="h6 mb-1">Офлайн режим</h4>
                            <p class="small text-muted mb-0">Доступ к сохраненным рецептам без интернета</p>
                        </div>
                    </div>
                    <div class="d-flex align-items-center">
                        <div class="bg-info text-white p-3 rounded-circle me-3">
                            <i class="fas fa-sync"></i>
                        </div>
                        <div>
                            <h4 class="h6 mb-1">Автообновление</h4>
                            <p class="small text-muted mb-0">Всегда актуальная версия</p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card shadow-sm mb-4">
                <div class="card-body">
                    <h3>Поддерживаемые устройства</h3>
                    <ul class="list-unstyled">
                        <li class="mb-2"><i class="fab fa-android text-success me-2"></i> Смартфоны и планшеты Android</li>
                        <li class="mb-2"><i class="fab fa-apple me-2"></i> iPhone и iPad (iOS 14+)</li>
                        <li class="mb-2"><i class="fab fa-windows text-primary me-2"></i> ПК с Windows</li>
                        <li class="mb-2"><i class="fab fa-linux text-danger me-2"></i> Linux</li>
                        <li><i class="fab fa-chrome text-warning me-2"></i> Браузеры на основе Chromium</li>
                    </ul>
                </div>
            </div>
            
            <div class="card bg-light">
                <div class="card-body">
                    <h3 class="h5">Нужна помощь?</h3>
                    <p>Если у вас возникли проблемы с установкой, напишите нам:</p>
                    <a href="mailto:w1nishko@yandex.ru" class="btn btn-outline-primary w-100">
                        <i class="fas fa-envelope me-2"></i> Написать в поддержку
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2>Часто задаваемые вопросы</h2>
                    <div class="accordion" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne" aria-expanded="false" aria-controls="collapseOne">
                                    Что такое PWA-приложение?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" aria-labelledby="faqOne" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    PWA (Progressive Web Application) - это технология, которая позволяет веб-сайтам функционировать как приложения на вашем устройстве. Это дает возможность быстрого доступа к сайту с рабочего стола, работы в офлайн режиме и получения уведомлений.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo" aria-expanded="false" aria-controls="collapseTwo">
                                    Нужно ли скачивать приложение из App Store или Google Play?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" aria-labelledby="faqTwo" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Нет, PWA-приложение не нужно скачивать из магазинов приложений. Вы устанавливаете его прямо из браузера на ваше устройство. Это быстрее и безопаснее, чем традиционная загрузка приложений.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree" aria-expanded="false" aria-controls="collapseThree">
                                    Как обновить приложение до последней версии?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" aria-labelledby="faqThree" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    PWA-приложения обновляются автоматически, когда вы открываете их с подключением к интернету. Вам не нужно делать никаких дополнительных действий для получения последних обновлений.
                                </div>
                            </div>
                        </div>
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="faqFour">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseFour" aria-expanded="false" aria-controls="collapseFour">
                                    Как удалить приложение с устройства?
                                </button>
                            </h2>
                            <div id="collapseFour" class="accordion-collapse collapse" aria-labelledby="faqFour" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    На устройствах Android и Windows вы можете удалить приложение так же, как и любое другое: зажмите иконку и выберите "Удалить". На iOS найдите иконку приложения на домашнем экране, нажмите и удерживайте, затем нажмите на "X" в верхнем углу иконки.
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="container">
    <h1>Установка приложения</h1>
    <p>Следуйте приведенным ниже инструкциям для установки PWA на ваше устройство.</p>
    <!-- Здесь можно добавить рекомендации для разных устройств -->
    <ul>
        <li>Для Android: Нажмите на кнопку "Установить приложение".</li>
        <li>Для iOS: Используйте Safari и выберите "Добавить на главный экран".</li>
    </ul>
</div>
@endsection

@section('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // Обработчик установки PWA
        let deferredPrompt;
        const installButton = document.getElementById('windows-install-pwa');
        
        if (installButton) {
            // Скрываем кнопку установки по умолчанию
            installButton.style.display = 'none';
            
            // Обработка события beforeinstallprompt
            window.addEventListener('beforeinstallprompt', (e) => {
                // Предотвращаем стандартный браузерный диалог
                e.preventDefault();
                // Сохраняем событие
                deferredPrompt = e;
                // Показываем кнопку установки
                installButton.style.display = 'flex';
                
                // Добавляем обработчик нажатия
                installButton.addEventListener('click', async () => {
                    // Вызываем диалог установки
                    deferredPrompt.prompt();
                    // Ожидаем решения пользователя
                    const { outcome } = await deferredPrompt.userChoice;
                    console.log(`User ${outcome === 'accepted' ? 'accepted' : 'dismissed'} the install prompt`);
                    
                    // Отправляем событие в аналитику
                    if (typeof ym !== 'undefined') {
                        ym(100639873, 'reachGoal', outcome === 'accepted' ? 'pwa_install_accepted' : 'pwa_install_rejected');
                    }
                    
                    // Сбрасываем сохраненный промпт
                    deferredPrompt = null;
                    
                    // Скрываем кнопку
                    installButton.style.display = 'none';
                });
            });
            
            // При успешной установке
            window.addEventListener('appinstalled', (event) => {
                console.log('App was installed');
                installButton.style.display = 'none';
                
                // Отправляем событие в аналитику
                if (typeof ym !== 'undefined') {
                    ym(100639873, 'reachGoal', 'pwa_installed');
                }
            });
        }
        
        // Определяем текущее устройство и прокручиваем к соответствующей секции
        const userAgent = navigator.userAgent || navigator.vendor || window.opera;
        let deviceSection;
        
        if (/iPad|iPhone|iPod/.test(userAgent) && !window.MSStream) {
            deviceSection = document.querySelector('.fab.fa-apple').closest('.device-section');
        } else if (/android/i.test(userAgent)) {
            deviceSection = document.querySelector('.fab.fa-android').closest('.device-section');
        } else if (/Windows NT/.test(userAgent)) {
            deviceSection = document.querySelector('.fab.fa-windows').closest('.device-section');
        } else if (/Macintosh|Mac OS X/.test(userAgent)) {
            deviceSection = document.querySelector('.fab.fa-apple.text-secondary').closest('.device-section');
        }
        
        if (deviceSection) {
            // Добавляем визуальное выделение текущего устройства
            deviceSection.classList.add('border-primary');
            deviceSection.classList.add('bg-light');
            
            // Плавно прокручиваем к секции с задержкой
            setTimeout(() => {
                deviceSection.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }, 500);
        }
    });
</script>
@endsection
