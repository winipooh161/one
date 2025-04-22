/**
 * Функционал автодополнения для поискового поля
 */
document.addEventListener('DOMContentLoaded', function() {
    // Отложенная загрузка скриптов для поиска
    setTimeout(function() {
        // Инициализация поиска
        const searchInput = document.getElementById('global-search-input');
        if (!searchInput) return;
        
        searchInput.addEventListener('input', debounce(function() {
            performSearch(this.value);
        }, 300));
        
        function debounce(func, wait) {
            let timeout;
            return function() {
                const context = this, args = arguments;
                clearTimeout(timeout);
                timeout = setTimeout(function() {
                    func.apply(context, args);
                }, wait);
            };
        }
        
        function performSearch(query) {
            if (query.length < 2) {
                document.querySelector('.autocomplete-results').classList.add('d-none');
                return;
            }
            
            fetch('/search/autocomplete?query=' + encodeURIComponent(query))
                .then(response => response.json())
                .then(data => {
                    updateAutocompleteResults(data, query);
                })
                .catch(error => console.error('Error:', error));
        }
        
        function updateAutocompleteResults(results, query) {
            const autocompleteContainer = document.querySelector('.autocomplete-results');
            
            if (!autocompleteContainer || results.length === 0) {
                if (autocompleteContainer) {
                    autocompleteContainer.classList.add('d-none');
                }
                return;
            }
            
            autocompleteContainer.innerHTML = '';
            autocompleteContainer.classList.remove('d-none');
            
            results.forEach(result => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item p-2';
                        
                // Подсветка совпадающего текста
                const regex = new RegExp(query, 'gi');
                const highlightedTitle = result.title.replace(regex, match => `<mark>${match}</mark>`);
                
                item.innerHTML = `
                    <a href="${result.url}" class="text-decoration-none text-dark d-block">
                        <div class="d-flex align-items-center">
                            <img src="${result.image}" class="rounded me-2" style="width: 40px; height: 40px; object-fit: cover;" alt="${result.title}">
                            <div>
                                <div>${highlightedTitle}</div>
                                <small class="text-muted">${result.category || ''}</small>
                            </div>
                        </div>
                    </a>
                `;
                   
                autocompleteContainer.appendChild(item);
            });
            
            // Добавляем пункт "Показать все результаты"
            const showAllItem = document.createElement('div');
            showAllItem.className = 'autocomplete-item show-all p-2 text-center';
            showAllItem.innerHTML = `<a href="/search?query=${encodeURIComponent(query)}" class="text-primary">Показать все результаты</a>`;
            autocompleteContainer.appendChild(showAllItem);
                
            // Добавляем обработчик для закрытия подсказок при клике вне элемента
            document.addEventListener('click', function(e) {
                if (!autocompleteContainer.contains(e.target) && e.target !== searchInput) {
                    autocompleteContainer.classList.add('d-none');
                }
            });
        }
    }, 1000);
});
