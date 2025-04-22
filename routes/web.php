<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\RecipeController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\SearchController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\LegalController;
use App\Http\Controllers\Admin\RecipeController as AdminRecipeController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\RecipeParserController;
use App\Http\Controllers\Admin\RecipeLinkCollectorController;

use App\Http\Controllers\HomeController;
use App\Http\Controllers\CommentController;
use App\Http\Controllers\ArticleController;
use App\Http\Controllers\PwaController;
use App\Http\Controllers\TelegramWebhookController;
use App\Http\Controllers\Admin\TelegramBotController;
use Telegram\Bot\Api as Telegram;
use App\Http\Controllers\SitemapController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "web" middleware group. Make something great!
|
*/

// Главная страница
Route::get('/', [HomeController::class, 'index'])->name('home');

// Страницы рецептов
Route::get('/recipes', [RecipeController::class, 'index'])->name('recipes.index');
Route::get('/recipes/{slug}', [RecipeController::class, 'show'])->name('recipes.show');
// Добавляем маршрут для AMP версии рецептов
Route::get('/recipes/{slug}/amp', [RecipeController::class, 'showAmp'])->name('recipes.amp');

// Маршрут для оценки рецептов
Route::post('/recipes/{recipe}/rate', [App\Http\Controllers\RecipeRatingController::class, 'rate'])->name('recipes.rate')->middleware('auth');

// Страницы категорий
Route::get('/categories', [CategoryController::class, 'index'])->name('categories.index');
Route::get('/categories/{slug}', [CategoryController::class, 'show'])->name('categories.show');

// Поиск
Route::get('/search', [SearchController::class, 'index'])->name('search');
Route::get('/search/autocomplete', [SearchController::class, 'autocomplete'])->name('search.autocomplete');
Route::post('/search/record-click', [SearchController::class, 'recordClick'])->name('search.record-click');
Route::get('/home/autocomplete', [HomeController::class, 'autocomplete'])->name('home.autocomplete');

// Правовые страницы
Route::get('/disclaimer', [LegalController::class, 'disclaimer'])->name('legal.disclaimer');
Route::get('/terms', [LegalController::class, 'terms'])->name('legal.terms');
Route::get('/dmca', [LegalController::class, 'dmca'])->name('legal.dmca');
Route::post('/dmca/submit', [LegalController::class, 'dmcaSubmit'])->name('legal.dmca.submit');
Route::get('/privacy', [LegalController::class, 'privacy'])->name('legal.privacy');

// Офлайн страница для PWA
Route::get('/offline', [PwaController::class, 'offline'])->name('offline');

// Маршруты для PWA
Route::prefix('pwa')->name('pwa.')->group(function () {
    Route::get('/install', [PwaController::class, 'install'])->name('install');
    Route::get('/install-info', [PwaController::class, 'installInfo'])->name('install-info');
    Route::post('/track-install', [PwaController::class, 'trackInstall'])->name('track-install');
});

// Аутентификация
Auth::routes();

// Профиль пользователя (доступно только авторизованным пользователям)
Route::middleware(['auth'])->group(function () {
    Route::get('/profile', [ProfileController::class, 'show'])->name('profile.show');
    Route::get('/profile/edit', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/change-password', [ProfileController::class, 'showChangePasswordForm'])->name('profile.change-password');
    Route::post('/profile/change-password', [ProfileController::class, 'changePassword']);
    Route::get('/user/{user}', [ProfileController::class, 'show'])->name('user.profile');
    
    // Добавляем маршрут для комментариев
    Route::post('/comments', [CommentController::class, 'store'])->name('comments.store');
    
    // Маршрут для публичного создания рецептов пользователями
    Route::get('/recipes/create', [App\Http\Controllers\RecipeController::class, 'create'])->name('recipes.create');
    Route::post('/recipes', [App\Http\Controllers\RecipeController::class, 'store'])->name('recipes.store');
    Route::get('/my-recipes', [App\Http\Controllers\RecipeController::class, 'myRecipes'])->name('recipes.my');
});

// Админка: базовые функции для всех пользователей
Route::middleware(['auth'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/', function () {
        return redirect()->route('admin.recipes.index');
    });
    
    // Управление рецептами для всех авторизованных пользователей
    // ВАЖНОЕ ИЗМЕНЕНИЕ: Добавляем маршрут модерации ДО ресурсного маршрута recipes
    Route::get('recipes/moderation', [AdminRecipeController::class, 'moderation'])->name('recipes.moderation');
    
    // Ресурсные маршруты
    Route::resource('recipes', AdminRecipeController::class);
    
    // Добавляем маршрут для генерации slug
    Route::post('recipes/generate-slug', [AdminRecipeController::class, 'generateSlug'])->name('recipes.generate-slug');
    
    // Функции только для администраторов
    Route::middleware(['role:admin'])->group(function () {
        Route::resource('categories', AdminCategoryController::class);
        
        // Функционал парсера только для администратора
        Route::get('/parser', [RecipeParserController::class, 'index'])->name('parser.index');
        Route::post('/parser/parse', [RecipeParserController::class, 'parse'])->name('parser.parse');
        Route::post('/parser/store', [RecipeParserController::class, 'store'])->name('parser.store');
        
        // Пакетный парсинг
        Route::get('/parser/batch', [RecipeParserController::class, 'batchIndex'])->name('parser.batch');
        Route::post('/parser/batch', [RecipeParserController::class, 'batchParse'])->name('parser.batchParse');
        
        // Удаляем дублирующиеся маршруты и оставляем только один вариант с правильным именем
        Route::get('/parser/process-batch', [RecipeParserController::class, 'processBatch'])->name('admin.parser.processBatch');
        
        // Маршруты для управления ссылками
        Route::get('/parser/manage-links', [RecipeLinkCollectorController::class, 'manageLinks'])->name('parser.manage_links');
        Route::post('/parser/process-links', [RecipeLinkCollectorController::class, 'processLinksFile'])->name('parser.processLinks');
        
        // Пакетный парсинг
        Route::get('/parser/batch', [RecipeParserController::class, 'batchIndex'])->name('parser.batch');
        Route::post('/parser/batch', [RecipeParserController::class, 'batchParse'])->name('parser.batchParse');
  
        // Сбор ссылок
        Route::get('/parser/process-batch', [RecipeParserController::class, 'processBatch'])
            ->name('parser.processBatch'); // Добавляем маршрут

    });
});

// Маршруты администратора
Route::prefix('admin')->name('admin.')->middleware(['auth', 'admin'])->group(function () {
    // Маршруты для постов в соцсети
    Route::resource('social-posts', App\Http\Controllers\Admin\SocialPostController::class)->except(['show']);
    Route::post('social-posts/{socialPost}/publish-vk', [App\Http\Controllers\Admin\SocialPostController::class, 'publishToVk'])
        ->name('social-posts.publish-vk');
    Route::post('social-posts/{socialPost}/publish-telegram', [App\Http\Controllers\Admin\SocialPostController::class, 'publishToTelegram'])
        ->name('social-posts.publish-telegram');
    
    // Маршруты для публикации рецептов в социальные сети
    Route::get('recipe-social', [App\Http\Controllers\Admin\RecipeSocialController::class, 'index'])
        ->name('recipe-social.index');
    Route::get('recipe-social/{id}/preview', [App\Http\Controllers\Admin\RecipeSocialController::class, 'preview'])
        ->name('recipe-social.preview');
    Route::post('recipe-social/{id}/publish', [App\Http\Controllers\Admin\RecipeSocialController::class, 'publish'])
        ->name('recipe-social.publish');
    
    // Маршруты для прямой публикации рецептов в социальные сети
    Route::post('recipes/{recipe}/social/telegram', [App\Http\Controllers\Admin\RecipeSocialController::class, 'publishToTelegram'])
        ->name('recipes.social.telegram');
    Route::post('recipes/{recipe}/social/vk', [App\Http\Controllers\Admin\RecipeSocialController::class, 'publishToVk'])
        ->name('recipes.social.vk');
    Route::post('recipes/{recipe}/social/zen', [App\Http\Controllers\Admin\RecipeSocialController::class, 'publishToZen'])
        ->name('recipes.social.zen');
    
    // Маршруты для управления Sitemap
    Route::prefix('sitemap')->name('sitemap.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\SitemapController::class, 'index'])->name('index');
        Route::post('/generate', [App\Http\Controllers\Admin\SitemapController::class, 'generate'])->name('generate');
    });
    
    // Маршруты для модерации рецептов
    Route::get('recipes/moderation', [AdminRecipeController::class, 'moderation'])->name('recipes.moderation');
    Route::post('recipes/{recipe}/approve', [AdminRecipeController::class, 'approve'])->name('recipes.approve');
    Route::delete('recipes/{recipe}/reject', [AdminRecipeController::class, 'reject'])->name('recipes.reject');
    
    // Управление фидами
    Route::get('/feeds', [App\Http\Controllers\Admin\FeedController::class, 'index'])->name('feeds.index');
    Route::post('/feeds/refresh', [App\Http\Controllers\Admin\FeedController::class, 'refresh'])->name('feeds.refresh');
    
    // Маршруты для OAuth авторизации ВКонтакте
    Route::prefix('oauth/vk')->name('oauth.vk.')->group(function() {
        Route::get('redirect', [App\Http\Controllers\Admin\VkOAuthController::class, 'redirect'])->name('redirect');
        Route::get('callback', [App\Http\Controllers\Admin\VkOAuthController::class, 'callback'])->name('callback');
    });
    
    // Маршруты настроек
    Route::prefix('settings')->name('settings.')->group(function() {
        Route::get('vk', [App\Http\Controllers\Admin\SettingsController::class, 'vk'])->name('vk');
        Route::post('vk', [App\Http\Controllers\Admin\SettingsController::class, 'updateVk'])->name('vk.update');
        // Добавляем маршрут для помощи с получением токена
        Route::get('vk/token-help', [App\Http\Controllers\Admin\SettingsController::class, 'vkTokenHelp'])->name('vk.token-help');
        // Добавляем новый маршрут для быстрого добавления токена
        Route::get('vk/quick-token', [App\Http\Controllers\Admin\SettingsController::class, 'quickToken'])->name('vk.quick-token');
    });
    
    // Маршруты для управления Telegram ботом
    Route::prefix('telegram')->name('telegram.')->group(function () {
        Route::get('/', [TelegramBotController::class, 'index'])->name('index');
        Route::get('/setup', [TelegramBotController::class, 'setup'])->name('setup');
        Route::get('/status', [TelegramBotController::class, 'checkStatus'])->name('status');
        Route::post('/clear-cache', [TelegramBotController::class, 'clearCache'])->name('clear-cache');
        
        // Управление пользователями
        Route::get('/users', [TelegramBotController::class, 'users'])->name('users');
        Route::get('/users/{chat}', [TelegramBotController::class, 'show'])->name('users.show');
        Route::post('/users/{chatId}/send', [TelegramBotController::class, 'sendMessage'])->name('send-message');
        
        // Массовая рассылка
        Route::get('/broadcast', [TelegramBotController::class, 'broadcast'])->name('broadcast');
        Route::post('/broadcast', [TelegramBotController::class, 'broadcast']);
        
        // Управление командами бота
        Route::get('/commands', [TelegramBotController::class, 'commands'])->name('commands');
        Route::post('/commands', [TelegramBotController::class, 'commands']);
        
        // Настройки бота
        Route::get('/settings', [TelegramBotController::class, 'settings'])->name('settings');
        Route::post('/settings', [TelegramBotController::class, 'updateSettings'])->name('update-settings');
        
        // Управление вебхуком
        Route::post('/webhook/set', [TelegramBotController::class, 'setWebhook'])->name('set-webhook');
        Route::post('/webhook/delete', [TelegramBotController::class, 'deleteWebhook'])->name('delete-webhook');
        
        // Просмотр логов
        Route::get('/logs', [TelegramBotController::class, 'logs'])->name('logs');
        
        // Миграции для Telegram
        Route::post('/migrate', [TelegramBotController::class, 'migrate'])->name('migrate');
    });
    

    
    // Добавляем маршруты для управления новостями в админке
    Route::resource('news', App\Http\Controllers\Admin\NewsController::class);
    
    // Маршрут для загрузки изображений через TinyMCE
    Route::post('tinymce/upload', [App\Http\Controllers\Admin\TinyMCEController::class, 'upload'])
        ->name('tinymce.upload');
        
    // Добавляем маршрут для извлечения метаданных видео
    Route::post('video/extract-metadata', [App\Http\Controllers\Admin\VideoMetadataController::class, 'extract'])
        ->name('video.extract-metadata');
});

// Маршруты для модерации рецептов
Route::prefix('admin/moderation')->name('admin.moderation.')->middleware(['auth', 'admin'])->group(function () {
    Route::get('/', [App\Http\Controllers\Admin\RecipeModerationController::class, 'index'])->name('index');
    Route::get('/{recipe}', [App\Http\Controllers\Admin\RecipeModerationController::class, 'show'])->name('show');
    Route::post('/{recipe}/approve', [App\Http\Controllers\Admin\RecipeModerationController::class, 'approve'])->name('approve');
    Route::delete('/{recipe}/reject', [App\Http\Controllers\Admin\RecipeModerationController::class, 'reject'])->name('reject');
    Route::post('/bulk-approve', [App\Http\Controllers\Admin\RecipeModerationController::class, 'bulkApprove'])->name('bulk-approve');
    Route::post('/bulk-reject', [App\Http\Controllers\Admin\RecipeModerationController::class, 'bulkReject'])->name('bulk-reject');
});

// AJAX маршрут для поиска похожих рецептов
Route::get('/admin/ajax/search-recipes', [App\Http\Controllers\Admin\AjaxController::class, 'searchRecipes'])
    ->name('admin.ajax.search-recipes')
    ->middleware(['auth', 'admin']);

// XML Feeds and Sitemap
Route::get('feeds/recipes', function () {
    return response()->view('feeds.recipes', [], 200)
                    ->header('Content-Type', 'text/xml');
})->name('feeds.recipes');

Route::get('feeds/categories', function () {
    return response()->view('feeds.categories', [], 200)
                    ->header('Content-Type', 'text/xml');
})->name('feeds.categories');

Route::get('sitemap.xml', function () {
    return response()->view('sitemap', [], 200)
                    ->header('Content-Type', 'text/xml');
})->name('sitemap');

// Sitemap Routes
Route::get('sitemap.xml', function () {
    return response()->view('sitemap', [], 200)
                    ->header('Content-Type', 'text/xml');
})->name('sitemap');

Route::get('sitemap-recipes.xml', function () {
    $filePath = public_path('sitemap-recipes.xml');
    if (file_exists($filePath)) {
        return response()->file($filePath, ['Content-Type' => 'text/xml']);
    }
    return redirect()->route('sitemap');
});

Route::get('sitemap-categories.xml', function () {
    $filePath = public_path('sitemap-categories.xml');
    if (file_exists($filePath)) {
        return response()->file($filePath, ['Content-Type' => 'text/xml']);
    }
    return redirect()->route('sitemap');
});

Route::get('sitemap-static.xml', function () {
    $filePath = public_path('sitemap-static.xml');
    if (file_exists($filePath)) {
        return response()->file($filePath, ['Content-Type' => 'text/xml']);
    }
    return redirect()->route('sitemap');
});

Route::get('sitemap-pagination.xml', function () {
    $filePath = public_path('sitemap-pagination.xml');
    if (file_exists($filePath)) {
        return response()->file($filePath, ['Content-Type' => 'text/xml']);
    }
    return redirect()->route('sitemap');
});

Route::get('sitemap-users.xml', function () {
    $filePath = public_path('sitemap-users.xml');
    if (file_exists($filePath)) {
        return response()->file($filePath, ['Content-Type' => 'text/xml']);
    }
    return redirect()->route('sitemap');
});

// Telegram routes
// Объединяем маршруты Telegram webhook (удаляем дублирование)
Route::match(['get', 'post'], '/telegram/webhook', [TelegramWebhookController::class, 'handle']);

// Перемещаем логику проверки статуса бота в контроллер для лучшей организации
Route::get('/telegram/check', [TelegramWebhookController::class, 'checkStatus']);

// Маршруты для новостей
Route::get('/news', [App\Http\Controllers\NewsController::class, 'index'])->name('news.index');
Route::get('/news/{slug}', [App\Http\Controllers\NewsController::class, 'show'])->name('news.show');
Route::get('/news/category/{category}', [App\Http\Controllers\NewsController::class, 'category'])->name('news.category');
Route::get('/news/tag/{tag}', [App\Http\Controllers\NewsController::class, 'tag'])->name('news.tag');
Route::get('/news/archive/{year}/{month?}', [App\Http\Controllers\NewsController::class, 'archive'])->name('news.archive');
Route::get('/news/author/{id}', [App\Http\Controllers\NewsController::class, 'author'])->name('news.author');

// Маршруты для комментариев к новостям
Route::middleware(['auth'])->group(function () {
    Route::post('/news/comments', [App\Http\Controllers\NewsCommentController::class, 'store'])->name('news.comments.store');
    Route::delete('/news/comments/{comment}', [App\Http\Controllers\NewsCommentController::class, 'destroy'])->name('news.comments.destroy');
    Route::post('/user/preferences', [App\Http\Controllers\ProfileController::class, 'savePreferences'])->name('user.preferences');
});

// Парсер рецептов

Route::prefix('admin/parser')->name('admin.parser.')->middleware(['auth', 'admin'])->group(function () {
    // Форма и ручной сбор ссылок
    Route::get('/collect-links', [RecipeLinkCollectorController::class, 'collectLinksForm'])->name('collect_links');
    Route::post('/collect-links', [RecipeLinkCollectorController::class, 'collectLinks'])->name('collect_links');
    Route::get('/collected-links', [RecipeLinkCollectorController::class, 'showCollectedLinks'])->name('collectedLinks');

    // Управление ссылками (оставляем для RecipeParserController, если требуется)
    Route::get('/manage-links', [RecipeParserController::class, 'manageLinks'])->name('manage_links');
    Route::post('/process-links', [RecipeParserController::class, 'processLinksFile'])->name('processLinks');
    Route::post('/clear-links-file', [RecipeLinkCollectorController::class, 'clearLinksFile'])->name('clear_links_file');
    Route::post('/remove-duplicate-links', [RecipeLinkCollectorController::class, 'removeDuplicateLinks'])->name('remove_duplicate_links');

    // Маршруты для непрерывного сбора ссылок
    Route::post('/collect-links-continuous', [RecipeLinkCollectorController::class, 'startContinuousCollection'])->name('collect_links_continuous');
    Route::get('/collection-status', [RecipeLinkCollectorController::class, 'getCollectionStatus'])->name('collection_status');
    Route::post('/stop-collection', [RecipeLinkCollectorController::class, 'stopCollection'])->name('stop_collection');
    Route::get('/process-infinite-scroll', [RecipeLinkCollectorController::class, 'processInfiniteScrollBatch'])->name('process_infinite_scroll');
    
    // Управление ссылками (используем обновленный метод в RecipeLinkCollectorController)
    Route::get('/manage-links', [RecipeLinkCollectorController::class, 'manageLinks'])->name('manage_links');
    Route::post('/process-selected-links', [RecipeLinkCollectorController::class, 'processSelectedLinks'])->name('process_selected_links');
    Route::post('/remove-processed-links', [RecipeLinkCollectorController::class, 'removeProcessedLinks'])->name('remove_processed_links');
    
    // Add this to the existing admin/parser route group
    Route::post('/check-valid-links', [RecipeLinkCollectorController::class, 'checkValidLinks'])->name('check_valid_links');
    
    // Добавляем маршрут для удаления существующих ссылок
    Route::post('/remove-existing-links', [RecipeLinkCollectorController::class, 'removeExistingLinks'])->name('remove_existing_links');
});

// SEO-ориентированные страницы
Route::get('/sitemap.xml', [SitemapController::class, 'index'])->name('sitemap');
Route::get('/sitemap/recipes.xml', [SitemapController::class, 'recipes'])->name('sitemap.recipes');
Route::get('/sitemap/categories.xml', [SitemapController::class, 'categories'])->name('sitemap.categories');


// Страницы для ошибок с кастомными шаблонами
Route::fallback(function () {
    return response()->view('errors.404', [], 404);
});

