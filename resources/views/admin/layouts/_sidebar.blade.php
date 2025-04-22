<aside class="main-sidebar sidebar-dark-primary elevation-4">
    <!-- Brand Logo -->
    <a href="{{ route('home') }}" class="brand-link">
        <img src="{{ asset('images/logo.png') }}" alt="Logo" class="brand-image img-circle elevation-3" style="opacity: .8">
        <span class="brand-text font-weight-light">{{ config('app.name') }}</span>
    </a>

    <!-- Sidebar -->
    <div class="sidebar">
        <!-- Sidebar user panel (optional) -->
        <div class="user-panel mt-3 pb-3 mb-3 d-flex">
            <div class="image">
                <img src="{{ asset('images/user-placeholder.jpg') }}" class="img-circle elevation-2" alt="User Image">
            </div>
            <div class="info">
                <a href="#" class="d-block">{{ auth()->user()->name }}</a>
            </div>
        </div>

        <!-- Sidebar Menu -->
        <nav class="mt-2">
            <ul class="nav nav-pills nav-sidebar flex-column" data-widget="treeview" role="menu" data-accordion="false">
                
                <!-- Рецепты -->
                <li class="nav-item">
                    <a href="{{ route('admin.recipes.index') }}" class="nav-link {{ request()->routeIs('admin.recipes.*') && !request()->routeIs('admin.recipes.moderation') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-utensils"></i>
                        <p>Рецепты</p>
                    </a>
                </li>
                
                <!-- Ссылка на модерацию (только для админов) -->
                @if(auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="{{ route('admin.moderation.index') }}" class="nav-link {{ request()->routeIs('admin.moderation.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tasks"></i>
                        <p>
                            Модерация
                            @php
                                $pendingCount = \App\Models\Recipe::where('is_published', false)->whereNotNull('user_id')->count();
                            @endphp
                            @if($pendingCount > 0)
                                <span class="badge badge-warning right">{{ $pendingCount }}</span>
                            @endif
                        </p>
                    </a>
                </li>
                @endif
                
                <!-- Категории (только для админов) -->
                @if(auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="{{ route('admin.categories.index') }}" class="nav-link {{ request()->routeIs('admin.categories.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-tags"></i>
                        <p>Категории</p>
                    </a>
                </li>
                @endif
                
                <!-- Публикация в соцсети (только для админов) -->
                @if(auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="{{ route('admin.recipe-social.index') }}" class="nav-link {{ request()->routeIs('admin.recipe-social.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-share-alt"></i>
                        <p>Публикация в соцсети</p>
                    </a>
                </li>
                @endif
                
                <!-- Парсер рецептов (только для админов) -->
                @if(auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="{{ route('admin.parser.index') }}" class="nav-link {{ request()->routeIs('admin.parser.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-spider"></i>
                        <p>Парсер рецептов</p>
                    </a>
                </li>
                @endif

                <!-- Генерация карты сайта (только для админов) -->
                @if(auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="{{ route('admin.sitemap.index') }}" class="nav-link {{ request()->routeIs('admin.sitemap.*') ? 'active' : '' }}">
                        <i class="nav-icon fas fa-sitemap"></i>
                        <p>Карта сайта</p>
                    </a>
                </li>
                @endif
                
                <!-- Настройки (только для админов) -->
                @if(auth()->user()->isAdmin())
                <li class="nav-item">
                    <a href="#" class="nav-link">
                        <i class="nav-icon fas fa-cogs"></i>
                        <p>
                            Настройки
                            <i class="right fas fa-angle-left"></i>
                        </p>
                    </a>
                    <ul class="nav nav-treeview">
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Основные настройки</p>
                            </a>
                        </li>
                        <li class="nav-item">
                            <a href="#" class="nav-link">
                                <i class="far fa-circle nav-icon"></i>
                                <p>Настройки SEO</p>
                            </a>
                        </li>
                    </ul>
                </li>
                @endif
                
                <!-- Разделитель -->
                <li class="nav-header">ДЕЙСТВИЯ</li>
                
                <!-- Перейти на сайт -->
                <li class="nav-item">
                    <a href="{{ route('home') }}" class="nav-link" target="_blank">
                        <i class="nav-icon fas fa-external-link-alt"></i>
                        <p>Перейти на сайт</p>
                    </a>
                </li>
                
                <!-- Выход -->
                <li class="nav-item">
                    <a href="#" class="nav-link" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="nav-icon fas fa-sign-out-alt"></i>
                        <p>Выход</p>
                    </a>
                    <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                        @csrf
                    </form>
                </li>
            </ul>
        </nav>
        <!-- /.sidebar-menu -->
    </div>
    <!-- /.sidebar -->
</aside>
