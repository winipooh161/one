/**
 * Инструменты отладки для проверки изображений на сайте
 */
(function() {
    // Добавляем кнопку отладки в нижний правый угол (видна только администраторам)
    function addDebugButton() {
        const debugBtn = document.createElement('button');
        debugBtn.innerHTML = '<i class="fas fa-bug"></i> Debug Images';
        debugBtn.className = 'btn btn-sm btn-danger position-fixed';
        debugBtn.style.bottom = '70px';
        debugBtn.style.right = '20px';
        debugBtn.style.zIndex = '9999';
        debugBtn.onclick = showImageDebugInfo;
        
        document.body.appendChild(debugBtn);
    }
    
    // Функция отображения информации о всех изображениях
    function showImageDebugInfo() {
        const images = document.querySelectorAll('img');
        let report = 'Image Debug Report:\n\n';
        
        images.forEach((img, i) => {
            const originalSrc = img.dataset.originalSrc || 'N/A';
            const currentSrc = img.src;
            const isPlaceholder = currentSrc.includes('placeholder.jpg');
            const status = isPlaceholder ? 'FAILED' : 'OK';
            const size = img.complete ? `${img.naturalWidth}x${img.naturalHeight}` : 'Not loaded';
            
            report += `[${i+1}] ${status} | Size: ${size}\n`;
            report += `   Current: ${currentSrc}\n`;
            if (originalSrc !== 'N/A') {
                report += `   Original: ${originalSrc}\n`;
            }
            report += '\n';
        });
        
        // Создаем модальное окно с отчетом
        const modal = document.createElement('div');
        modal.className = 'modal fade';
        modal.id = 'imageDebugModal';
        modal.innerHTML = `
            <div class="modal-dialog modal-lg">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Image Debug Report</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <pre style="max-height: 400px; overflow: auto;">${report}</pre>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-primary" onclick="window.retryLoadingImages()">Retry Failed Images</button>
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    </div>
                </div>
            </div>
        `;
        
        document.body.appendChild(modal);
        
        // Показываем модальное окно
        const bsModal = new bootstrap.Modal(document.getElementById('imageDebugModal'));
        bsModal.show();
    }
    
    // Запускаем добавление кнопки только для администраторов
    // Проверяем наличие элемента, указывающего на администратора
    if (document.querySelector('.badge.bg-danger')) {
        document.addEventListener('DOMContentLoaded', addDebugButton);
    }
})();
