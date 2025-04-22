<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <!-- Добавляем CSRF токен -->
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - Админ-панель</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css" rel="stylesheet">
   

    <style>
        body {
            font-size: 0.9rem;
            background-color: #f8f9fa;
        }
        
        #sidebar-wrapper {
            min-height: 100vh;
            margin-left: -15rem;
            transition: margin 0.25s ease-out;
            z-index: 1040;
        }
        
        #sidebar-wrapper .sidebar-heading {
            padding: 0.875rem 1.25rem;
            font-size: 1.2rem;
        }
        
        #sidebar-wrapper .list-group {
            width: 15rem;
        }
        
        #page-content-wrapper {
            min-width: 100vw;
        }
        
        #wrapper.toggled #sidebar-wrapper {
            margin-left: 0;
        }
        
        @media (min-width: 768px) {
            #sidebar-wrapper {
                margin-left: 0;
            }
            
            #page-content-wrapper {
                min-width: 0;
                width: 100%;
            }
            
            #wrapper.toggled #sidebar-wrapper {
                margin-left: -15rem;
            }
        }
        
        /* Мобильная оптимизация */
        @media (max-width: 767.98px) {
            #sidebar-wrapper {
                position: fixed;
                top: 0;
                left: 0;
                height: 100%;
                box-shadow: 0 0.5rem 1rem rgba(0,0,0,.15);
                background-color: #fff;
            }
            
            .navbar {
                padding: 0.5rem 1rem;
            }
            
            .container-fluid {
                padding-left: 0px;
                padding-right: 0px;
            }
            
            /* Убираем горизонтальный скролл на мобильных */
            body {
                overflow-x: hidden;
            }
            
            /* Кнопки в мобильной версии */
            .btn {
                padding: 0.375rem 0.5rem;
                font-size: 0.875rem;
            }
            
            .btn-group {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            /* Адаптация таблиц */
            .table-responsive {
                border: 0;
            }
            
            /* Карточки и контейнеры */
            .card {
                margin-bottom: 1rem;
            }
            
            .card-body {
                padding: 1rem;
            }
            .container, .container-fluid, .container-lg, .container-md, .container-sm, .container-xl {
                padding-left: 5px;     padding-right: 5px;
            }
            /* Сайдбар в мобильной версии */
            #wrapper:not(.toggled) #sidebar-wrapper {
                margin-left: -15rem;
            }
            
            #wrapper.toggled .sidebar-overlay {
                position: fixed;
                top: 0;
                left: 0;
                right: 0;
                bottom: 0;
                background-color: rgba(0,0,0,0.4);
                z-index: 1030;
            }
            
            /* Улучшаем доступные для касания элементы */
            .list-group-item, .nav-link, .dropdown-item {
                padding-top: 0.75rem;
                padding-bottom: 0.75rem;
            }
        }
        
        .sidebar-heading {
            background-color: #343a40;
            color: white;
        }
        
        .list-group-item {
            border: none;
            padding: 0.75rem 1.25rem;
        }
        
        .list-group-item.active {
            background-color: #007bff;
            border-color: #007bff;
        }
        
        .submenu {
            padding-left: 2rem;
            background-color: #f8f9fa;
        }
        
        .submenu .list-group-item {
            padding: 0.5rem 1rem;
            font-size: 0.85rem;
        }
        
        .nav-item.dropdown .dropdown-menu {
            right: 0;
            left: auto;
        }
        
        .card-header {
            background-color: #f5f5f5;
            font-weight: 600;
        }
        
        /* Стили для открытых подменю */
        .has-submenu.menu-open + .submenu {
            display: block;
        }
        
        .submenu {
            display: none;
        }
        
        /* Иконка стрелки */
        .has-submenu::after {
            content: '\f105';
            font-family: 'Font Awesome 5 Free';
            font-weight: 900;
            float: right;
            transition: transform 0.3s;
        }
        
        .has-submenu.menu-open::after {
            transform: rotate(90deg);
        }
        
        /* Адаптивные таблицы для мобильных устройств */
        @media (max-width: 767.98px) {
            .table-responsive-sm thead {
                display: none;
            }
            
            .table-responsive-sm tbody tr {
                display: block;
                border: 1px solid #ddd;
                margin-bottom: 1rem;
            }
            
            .table-responsive-sm tbody td {
                display: block;
                border: none;
                position: relative;
                padding-left: 50%;
                text-align: right;
                min-height: 40px;
            }
            
            .table-responsive-sm tbody td::before {
                content: attr(data-label);
                position: absolute;
                left: 0.75rem;
                width: 45%;
                text-align: left;
                font-weight: bold;
            }
            
            /* Мобильный вид кнопок действий */
            .action-buttons {
                display: flex;
                flex-wrap: wrap;
                gap: 5px;
            }
            
            /* Улучшаем форму в мобильной версии */
            .form-row > [class*=col-] {
                padding-right: 5px;
                padding-left: 5px;
            }
        }
    </style>
    @stack('styles')
</head>
<body>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <div class="d-flex" id="wrapper">
        <!-- Overlay для мобильной версии -->
        <div class="sidebar-overlay d-md-none"></div>
        
        <!-- Sidebar -->
        <div class="bg-light border-right" id="sidebar-wrapper">
            <div class="sidebar-heading d-flex justify-content-between align-items-center">
                <span>{{ config('app.name') }}</span>
                <button type="button" class="close d-md-none" id="close-sidebar" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="list-group list-group-flush">
                <a href="{{ route('admin.recipes.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.recipes.*') ? 'active' : '' }}">
                    <i class="fas fa-utensils mr-2"></i> Рецепты
                </a>
                
                <a href="{{ route('admin.categories.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                    <i class="fas fa-list mr-2"></i> Категории
                </a>
                
                <a href="{{ route('admin.news.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.news.*') ? 'active' : '' }}">
                    <i class="fas fa-newspaper mr-2"></i> Новости
                </a>
                
                <div class="list-group-item list-group-item-action has-submenu {{ request()->routeIs('admin.telegram.*') ? 'menu-open' : '' }}">
                    <i class="fab fa-telegram-plane mr-2"></i> Telegram Бот
                </div>
                <div class="submenu">
                    <a href="{{ route('admin.telegram.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.telegram.index') ? 'active' : '' }}">
                        <i class="fas fa-tachometer-alt mr-2"></i> Обзор
                    </a>
                    <a href="{{ route('admin.telegram.users') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.telegram.users') ? 'active' : '' }}">
                        <i class="fas fa-users mr-2"></i> Пользователи
                    </a>
                    <a href="{{ route('admin.telegram.broadcast') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.telegram.broadcast') ? 'active' : '' }}">
                        <i class="fas fa-broadcast-tower mr-2"></i> Рассылка
                    </a>
                    <a href="{{ route('admin.telegram.commands') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.telegram.commands') ? 'active' : '' }}">
                        <i class="fas fa-terminal mr-2"></i> Команды
                    </a>
                    <a href="{{ route('admin.telegram.settings') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.telegram.settings') ? 'active' : '' }}">
                        <i class="fas fa-cog mr-2"></i> Настройки
                    </a>
                </div>
              
                <div class="list-group-item list-group-item-action has-submenu {{ request()->routeIs('admin.social-posts.*') || request()->routeIs('admin.recipe-social.*') ? 'menu-open' : '' }}">
                    <i class="fas fa-share-alt mr-2"></i> Соцсети
                </div>
                <div class="submenu">
                    <a href="{{ route('admin.social-posts.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.social-posts.*') ? 'active' : '' }}">
                        <i class="fas fa-paper-plane mr-2"></i> Публикации
                    </a>
                    <a href="{{ route('admin.recipe-social.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.recipe-social.*') ? 'active' : '' }}">
                        <i class="fas fa-rss mr-2"></i> Рецепты в соцсети
                    </a>
                </div>
                
                <div class="list-group-item list-group-item-action has-submenu {{ request()->routeIs('admin.moderation.*') ? 'menu-open' : '' }}">
                    <i class="fas fa-check-circle mr-2"></i> Модерация
                </div>
                <div class="submenu">
                    <a href="{{ route('admin.recipes.moderation') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.recipes.moderation') ? 'active' : '' }}">
                        <i class="fas fa-clipboard-check mr-2"></i> Рецепты
                    </a>
                    <a href="{{ route('admin.moderation.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.moderation.index') ? 'active' : '' }}">
                        <i class="fas fa-user-check mr-2"></i> Все на модерации
                    </a>
                </div>
                
                <div class="list-group-item list-group-item-action has-submenu {{ request()->routeIs('admin.parser.*') ? 'menu-open' : '' }}">
                    <i class="fas fa-sync mr-2"></i> Парсер
                </div>
                <div class="submenu">
                    <a href="{{ route('admin.parser.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.parser.index') ? 'active' : '' }}">
                        <i class="fas fa-download mr-2"></i> Одиночный парсинг
                    </a>
                    <a href="{{ route('admin.parser.batch') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.parser.batch') ? 'active' : '' }}">
                        <i class="fas fa-layer-group mr-2"></i> Пакетный парсинг
                    </a>
                  
                    <a href="{{ route('admin.parser.manage_links') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.parser.collectLinksForm') ? 'active' : '' }}">
                        <i class="fas fa-link mr-2"></i> Менеджер ссылок
                    </a>
                </div>
                
                <div class="list-group-item list-group-item-action has-submenu {{ request()->routeIs('admin.settings.*') ? 'menu-open' : '' }}">
                    <i class="fas fa-cogs mr-2"></i> Настройки
                </div>
                <div class="submenu">
                    <a href="{{ route('admin.settings.vk') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.settings.vk') ? 'active' : '' }}">
                        <i class="fab fa-vk mr-2"></i> ВКонтакте
                    </a>
                    <a href="{{ route('admin.sitemap.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.sitemap.*') ? 'active' : '' }}">
                        <i class="fas fa-sitemap mr-2"></i> Sitemap
                    </a>
                    <a href="{{ route('admin.feeds.index') }}" class="list-group-item list-group-item-action {{ request()->routeIs('admin.feeds.*') ? 'active' : '' }}">
                        <i class="fas fa-rss-square mr-2"></i> Фиды
                    </a>
                </div>
            </div>
        </div>
        <!-- /#sidebar-wrapper -->

        <!-- Page Content -->
        <div id="page-content-wrapper">
            <nav class="navbar navbar-expand-lg navbar-light bg-light border-bottom">
                <button class="btn btn-sm btn-primary" id="menu-toggle">
                    <i class="fas fa-bars"></i>
                </button>

                <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon"></span>
                </button>

                <div class="collapse navbar-collapse" id="navbarSupportedContent">
                    <ul class="navbar-nav ml-auto mt-2 mt-lg-0">
                        <li class="nav-item">
                            <a class="nav-link" href="{{ url('/') }}" target="_blank">Перейти на сайт</a>
                        </li>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                {{ Auth::user()->name }}
                            </a>
                            <div class="dropdown-menu dropdown-menu-right" aria-labelledby="navbarDropdown">
                                <a class="dropdown-item" href="{{ route('profile.show') }}">
                                    <i class="fas fa-user-circle mr-2"></i> Профиль
                                </a>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="{{ route('logout') }}" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                                    <i class="fas fa-sign-out-alt mr-2"></i> Выйти
                                </a>
                            </div>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </li>
                    </ul>
                </div>
            </nav>

            <div class="container-fluid py-4">
                @yield('content')
            </div>
        </div>
        <!-- /#page-content-wrapper -->
    </div>
    <!-- /#wrapper -->


    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.6.0/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Menu Toggle Script -->
    <script>
        $(document).ready(function() {
            // Переключение сайдбара
            $("#menu-toggle").click(function(e) {
                e.preventDefault();
                $("#wrapper").toggleClass("toggled");
            });
            
            // Закрытие сайдбара при клике на X
            $("#close-sidebar").click(function() {
                $("#wrapper").removeClass("toggled");
            });
            
            // Закрытие сайдбара при клике на оверлей
            $(".sidebar-overlay").click(function() {
                $("#wrapper").removeClass("toggled");
            });

            // Обработка нажатия на пункты с подменю
            $(".has-submenu").click(function() {
                // Закрываем все другие открытые подменю
                $(".has-submenu").not(this).removeClass("menu-open");
                $(".submenu").not($(this).next(".submenu")).slideUp(200);
                
                // Переключаем состояние текущего подменю
                $(this).toggleClass("menu-open");
                $(this).next(".submenu").slideToggle(200);
            });

            // Автоматическое открытие подменю для активных пунктов
            $(".list-group-item.active").closest(".submenu").show().prev(".has-submenu").addClass("menu-open");
            
            // Адаптивные таблицы - добавляем data-label к ячейкам
            $('.table-responsive-sm table').each(function() {
                var headers = [];
                $(this).find('thead th').each(function(index) {
                    headers[index] = $(this).text().trim();
                });
                
                $(this).find('tbody tr').each(function() {
                    $(this).find('td').each(function(index) {
                        $(this).attr('data-label', headers[index]);
                    });
                });
            });

            // Инициализация тултипов
            $(function () {
                $('[data-toggle="tooltip"]').tooltip();
            });
        });
    </script>
           @yield('scripts')
    @stack('scripts')
</body>
</html>
