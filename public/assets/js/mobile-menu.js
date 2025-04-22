/**
 * Инициализация бургер-меню для мобильной версии
 */
document.addEventListener('DOMContentLoaded', function() {
    const navbarToggler = document.querySelector('.navbar-toggler');
    if (navbarToggler) {
        navbarToggler.addEventListener('click', function() {
            const target = document.querySelector(this.getAttribute('data-bs-target'));
            if (target) {
                if (target.classList.contains('show')) {
                    target.classList.remove('show');
                } else {
                    target.classList.add('show');
                }
            }
        });
    }
});
