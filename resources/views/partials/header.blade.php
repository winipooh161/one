<!-- Основная навигация -->
        <style>
            .navbar {
  position: fixed !important;
  z-index: 99999;
  width: 100%;
}
        </style>
        <nav class="navbar navbar-expand-md navbar-light bg-white shadow-sm" itemscope itemtype="https://schema.org/SiteNavigationElement">
            <div class="container">
                <a class="navbar-brand d-flex align-items-center" href="{{ url('/') }}" itemprop="url">
                    <i class="fas fa-utensils me-2 text-primary" aria-hidden="true"></i>
                    <span itemprop="name">{{ config('app.name', 'Laravel') }}</span>
                </a>
                <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Переключить навигацию">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <!-- Left Side Of Navbar -->
                    <ul class="navbar-nav me-auto" itemscope itemtype="https://schema.org/ItemList">
                        <li class="nav-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a class="nav-link {{ request()->routeIs('recipes.index') ? 'active' : '' }}" href="{{ route('recipes.index') }}" itemprop="url">
                                <i class="fas fa-book-open me-1" aria-hidden="true"></i> 
                                <span itemprop="name">{{ __('Рецепты') }}</span>
                                <meta itemprop="position" content="1" />
                            </a>
                        </li>
                        <li class="nav-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a class="nav-link {{ request()->routeIs('categories.index') ? 'active' : '' }}" href="{{ route('categories.index') }}" itemprop="url">
                                <i class="fas fa-tags me-1" aria-hidden="true"></i> 
                                <span itemprop="name">{{ __('Категории') }}</span>
                                <meta itemprop="position" content="2" />
                            </a>
                        </li>
                        <li class="nav-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a class="nav-link {{ request()->routeIs('search') ? 'active' : '' }}" href="{{ route('search') }}" itemprop="url">
                                <i class="fas fa-search me-1" aria-hidden="true"></i> 
                                <span itemprop="name">{{ __('Поиск') }}</span>
                                <meta itemprop="position" content="3" />
                            </a>
                        </li>
                        <li class="nav-item" itemprop="itemListElement" itemscope itemtype="https://schema.org/ListItem">
                            <a class="nav-link {{ request()->routeIs('news.*') ? 'active' : '' }}" href="{{ route('news.index') }}" itemprop="url">
                                <span itemprop="name">Новости</span>
                                <meta itemprop="position" content="4" />
                            </a>
                        </li>
                    </ul>
                    
                    <!-- Поисковая строка в хедере -->
                    <form class="d-flex mx-auto my-2 my-lg-0 header-search-form position-relative" action="{{ route('search') }}" method="GET" role="search" aria-label="Поиск по сайту">
                        <div class="input-group">
                            <input id="global-search-input" class="form-control" type="search" name="query" placeholder="Найти рецепт..." aria-label="Поисковый запрос" 
                                   autocomplete="off" value="{{ request('query') ?? '' }}">
                            <button class="btn btn-outline-primary" type="submit" aria-label="Искать">
                                <i class="fas fa-search" aria-hidden="true"></i>
                            </button>
                        </div>
                        <div id="search-autocomplete" class="autocomplete-results d-none position-absolute w-100 mt-1 shadow-sm" style="z-index: 1050; top: 100%;" aria-live="polite">
                            <!-- Автодополнения будут добавлены JS-ом -->
                        </div>
                    </form>
                    
                    <!-- Right Side Of Navbar -->
                    <ul class="navbar-nav ms-auto">
                        <!-- Authentication Links -->
                        @guest
                            @if (Route::has('login'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('login') }}" rel="nofollow">
                                        <i class="fas fa-sign-in-alt me-1" aria-hidden="true"></i> {{ __('Войти') }}
                                    </a>
                                </li>
                            @endif
                            
                            @if (Route::has('register'))
                                <li class="nav-item">
                                    <a class="nav-link" href="{{ route('register') }}" rel="nofollow">
                                        <i class="fas fa-user-plus me-1" aria-hidden="true"></i> {{ __('Регистрация') }}
                                    </a>
                                </li>
                            @endif
                        @else
                            <li class="nav-item">
                                <a class="nav-link {{ request()->routeIs('admin.*') ? 'active' : '' }}" href="{{ route('admin.recipes.index') }}" rel="nofollow">
                                    <i class="fas fa-cog me-1" aria-hidden="true"></i> {{ __('Админка') }}
                                </a>
                            </li>
                            @auth
                                @if(auth()->user()->isAdmin())
                                    <li class="nav-item">
                                        <a class="nav-link {{ request()->routeIs('admin.parser.*') ? 'active' : '' }}" href="{{ route('admin.parser.index') }}" rel="nofollow">
                                            <i class="fas fa-code me-1" aria-hidden="true"></i> {{ __('Парсер') }}
                                        </a>
                                    </li>
                                @endif
                                
                                <li class="nav-item dropdown">
                                    <a id="navbarDropdown" class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false" v-pre>
                                        <i class="fas fa-user-circle me-1" aria-hidden="true"></i> {{ Auth::user()->name }}
                                        @if(auth()->user()->isAdmin())
                                            <span class="badge bg-danger">Admin</span>
                                        @endif
                                    </a>
                                    <div class="dropdown-menu dropdown-menu-end" aria-labelledby="navbarDropdown">
                                        @if(auth()->user()->isAdmin())
                                        <a class="dropdown-item" href="{{ route('admin.recipes.index') }}" rel="nofollow">
                                            <i class="fas fa-cogs" aria-hidden="true"></i> Админ-панель
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="{{ route('admin.recipes.index') }}" rel="nofollow">
                                            <i class="fas fa-utensils" aria-hidden="true"></i> Управление рецептами
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.categories.index') }}" rel="nofollow">
                                            <i class="fas fa-list" aria-hidden="true"></i> Управление категориями
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.parser.index') }}" rel="nofollow">
                                            <i class="fas fa-robot" aria-hidden="true"></i> Парсер рецептов
                                        </a>
                                        <a class="dropdown-item" href="{{ route('admin.social-posts.index') }}" rel="nofollow">
                                            <i class="fas fa-share-alt" aria-hidden="true"></i> Постинг в соцсети
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        @elseif(auth()->user()->can('manage-recipes'))
                                        <a class="dropdown-item" href="{{ route('admin.recipes.index') }}" rel="nofollow">
                                            <i class="fas fa-utensils" aria-hidden="true"></i> Управление рецептами
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        @endif
                                        <a class="dropdown-item" href="{{ route('profile.show') }}" rel="nofollow">
                                            <i class="fas fa-user" aria-hidden="true"></i> Профиль
                                        </a>
                                        <div class="dropdown-divider"></div>
                                        <a class="dropdown-item" href="{{ route('logout') }}" rel="nofollow"
                                           onclick="event.preventDefault();
                                                     document.getElementById('logout-form').submit();">
                                            <i class="fas fa-sign-out-alt" aria-hidden="true"></i> {{ __('Logout') }}
                                        </a>
                                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                                            @csrf
                                        </form>
                                    </div>
                                </li>
                            @endauth
                        @endguest
                    </ul>
                </div>
            </div>
        </nav>
        <!-- Скрипт для корректной работы мобильного меню -->
        <script>
            document.addEventListener('DOMContentLoaded', function() {
                // Находим все ссылки внутри навигационного меню
                const navLinks = document.querySelectorAll('.navbar-nav .nav-link');
                const navbarMenu = document.getElementById('navbarSupportedContent');
                const navbarToggler = document.querySelector('.navbar-toggler');
                // Добавляем обработчик событий для каждой ссылки
                navLinks.forEach(function(link) {
                    link.addEventListener('click', function() {
                        // Проверяем, открыто ли меню (имеет класс 'show')
                        if (navbarMenu && navbarMenu.classList.contains('show')) {
                            // Закрываем меню, удаляя класс 'show'
                            navbarMenu.classList.remove('show');
                            // Также меняем атрибут aria-expanded у кнопки бургера
                            if (navbarToggler) {
                                navbarToggler.setAttribute('aria-expanded', 'false');
                            }
                        }
                    });
                });
                // Обработчик для закрытия меню по клику вне меню
                document.addEventListener('click', function(event) {
                    // Если меню открыто и клик был не по меню и не по кнопке бургера
                    if (navbarMenu && navbarMenu.classList.contains('show') && 
                        !navbarMenu.contains(event.target) && 
                        !navbarToggler.contains(event.target)) {
                        navbarMenu.classList.remove('show');
                        if (navbarToggler) {
                            navbarToggler.setAttribute('aria-expanded', 'false');
                        }
                    }
                });
                // Дополнительная проверка кнопки бургера
                if (navbarToggler) {
                    navbarToggler.addEventListener('click', function() {
                        if (navbarMenu) {
                            navbarMenu.classList.toggle('show');
                            const isExpanded = navbarMenu.classList.contains('show');
                            navbarToggler.setAttribute('aria-expanded', isExpanded ? 'true' : 'false');
                        }
                    });
                }
                
                // Модификация поиска для автодополнения
                const searchInput = document.getElementById('global-search-input');
                const searchResults = document.getElementById('search-autocomplete');
                
                if (searchInput && searchResults) {
                    searchInput.addEventListener('input', debounce(function() {
                        const query = searchInput.value.trim();
                        
                        if (query.length < 2) {
                            searchResults.classList.add('d-none');
                            return;
                        }
                        
                        fetch('/api/search/autocomplete?q=' + encodeURIComponent(query))
                            .then(response => response.json())
                            .then(data => {
                                if (data && data.length > 0) {
                                    searchResults.innerHTML = '';
                                    searchResults.classList.remove('d-none');
                                    
                                    data.forEach(item => {
                                        const link = document.createElement('a');
                                        link.href = item.url;
                                        link.className = 'list-group-item list-group-item-action';
                                        link.innerHTML = `
                                            <div class="d-flex align-items-center">
                                                <img src="${item.image || '/images/placeholder.jpg'}" class="rounded me-2" alt="${item.title}" width="40" height="40" style="object-fit: cover;">
                                                <div>
                                                    <div class="fw-bold">${item.title}</div>
                                                    <small class="text-muted">${item.category || ''}</small>
                                                </div>
                                            </div>
                                        `;
                                        searchResults.appendChild(link);
                                    });
                                } else {
                                    searchResults.classList.add('d-none');
                                }
                            })
                            .catch(error => {
                                console.error('Ошибка автодополнения:', error);
                                searchResults.classList.add('d-none');
                            });
                    }, 300));
                    
                    // Скрываем результаты при клике вне поля поиска
                    document.addEventListener('click', function(event) {
                        if (!searchInput.contains(event.target) && !searchResults.contains(event.target)) {
                            searchResults.classList.add('d-none');
                        }
                    });
                    
                    // Debounce функция для задержки запроса
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
                }
            });
        </script>
