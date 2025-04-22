/**
 * Управление согласием на использование cookie
 */
document.addEventListener('DOMContentLoaded', function() {
    const cookieConsent = document.getElementById('cookie-consent');
    const acceptCookies = document.getElementById('accept-cookies');
    const declineCookies = document.getElementById('decline-cookies');
    
    if (!cookieConsent) return;
    
    // Проверяем, есть ли согласие на использование cookie
    if (!localStorage.getItem('cookieConsent')) {
        cookieConsent.style.display = 'block';
    }
    
    // Обработчик принятия cookie
    acceptCookies.addEventListener('click', function() {
        localStorage.setItem('cookieConsent', 'accepted');
        cookieConsent.style.display = 'none';
    });
    
    // Обработчик отказа от cookie
    declineCookies.addEventListener('click', function() {
        localStorage.setItem('cookieConsent', 'declined');
        cookieConsent.style.display = 'none';
        // Дополнительно можно отключить аналитику
        // ym(96182066, "disable");
    });
});
