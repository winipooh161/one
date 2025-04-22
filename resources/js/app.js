import './bootstrap';
import * as bootstrap from 'bootstrap';
import $ from 'jquery';

// Глобальный доступ к bootstrap и jQuery
window.bootstrap = bootstrap;
window.$ = window.jQuery = $;

// Импортируем стили
import '../css/app.css';

// Функция для обработки кнопки "наверх"
function setupBackToTop() {
    const backToTopButton = document.getElementById('back-to-top');
    if (backToTopButton) {
        window.addEventListener('scroll', function() {
            if (window.pageYOffset > 300) {
                backToTopButton.style.display = 'flex';
            } else {
                backToTopButton.style.display = 'none';
            }
        });
        
        backToTopButton.addEventListener('click', function() {
            window.scrollTo({
                top: 0,
                behavior: 'smooth'
            });
        });
    }
}

// Функция для обработки изображений
function handleImages() {
    document.querySelectorAll('img:not([data-skip-error-handler])').forEach(img => {
        img.onerror = function() {
            if (!this.src.includes('placeholder')) {
                const isCategory = this.closest('.category-card') !== null || 
                                  this.classList.contains('category-img');
                this.src = isCategory ? '/images/category-placeholder.jpg' : '/images/placeholder.jpg';
                this.onerror = null;
            }
        };
        
        // Проверяем, если изображение уже загружено с ошибкой
        if (img.complete && img.naturalHeight === 0) {
            img.onerror();
        }
    });
}

// Функция для инициализации всех Bootstrap компонентов
function initBootstrapComponents() {
    // Проверяем, загружен ли Bootstrap правильно
    if (typeof bootstrap !== 'undefined') {
        // Инициализация всплывающих подсказок
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
        
        // Инициализация всплывающих окон
        var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
        popoverTriggerList.map(function (popoverTriggerEl) {
            return new bootstrap.Popover(popoverTriggerEl);
        });
        
        // Автоматическое закрытие уведомлений
        const alerts = document.querySelectorAll('.alert:not(.alert-permanent)');
        alerts.forEach(function(alert) {
            setTimeout(function() {
                const alertInstance = new bootstrap.Alert(alert);
                alertInstance.close();
            }, 5000);
        });
    } else {
        console.error('Bootstrap не загружен корректно!');
    }
}

// Анимация для элементов на главной странице
function animateHomePageElements() {
    if (document.querySelector('.homepage-header')) {
        // Анимация появления элементов при скролле
        const animateOnScroll = function() {
            const elements = document.querySelectorAll('.recipe-card, .category-card, .meal-type-card, .telegram-card');
            
            elements.forEach(element => {
                const elementPosition = element.getBoundingClientRect().top;
                const screenHeight = window.innerHeight;
                
                if (elementPosition < screenHeight - 100) {
                    element.classList.add('animated');
                }
            });
        };
        
        // Запускаем первую проверку после загрузки страницы
        setTimeout(animateOnScroll, 300);
        
        // Затем запускаем при скролле
        window.addEventListener('scroll', animateOnScroll);
    }
}

// Инициализация всех функций при загрузке страницы
document.addEventListener('DOMContentLoaded', function() {
    console.log('Инициализация приложения...');
    initBootstrapComponents();
    setupBackToTop();
    handleImages();
    animateHomePageElements();
    
    // Проверка сохраненной темы
    if (localStorage.getItem('darkMode') === 'true') {
        document.body.classList.add('dark-mode');
    }
});
