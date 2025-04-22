<?php

namespace App\Services;

use App\Models\SocialPost;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Str;

class VkService
{
    protected $accessToken;
    protected $version;
    protected $ownerId;
    protected $clientId;
    protected $clientSecret;
    protected $redirectUri;
    protected $timeout = 5; // Уменьшаем таймаут до 5 секунд вместо стандартных 30

    /**
     * Инициализация сервиса
     */
    public function __construct()
    {
        $this->accessToken = env('VK_ACCESS_TOKEN');
        $this->version = env('VK_API_VERSION', '5.131');
        $this->ownerId = env('VK_OWNER_ID');
        
        // OAuth параметры
        $this->clientId = env('VK_CLIENT_ID');
        $this->clientSecret = env('VK_CLIENT_SECRET');
        $this->redirectUri = env('VK_REDIRECT_URI', url('/admin/oauth/vk/callback'));
        
        // Отладочный вывод для проверки настроек
        Log::debug('VkService инициализирован', [
            'access_token_exists' => !empty($this->accessToken),
            'access_token_length' => $this->accessToken ? strlen($this->accessToken) : 0,
            'version' => $this->version,
            'owner_id' => $this->ownerId,
            'oauth_configured' => (!empty($this->clientId) && !empty($this->clientSecret))
        ]);
    }

    /**
     * Проверка настроек ВКонтакте
     * 
     * @param bool $skipApiCheck Пропустить проверку API, если известно, что есть проблемы с соединением
     * @return array Результат проверки
     */
    public function checkSettings($skipApiCheck = false)
    {
        $result = [
            'success' => true,
            'errors' => []
        ];
        Log::debug('Проверка настроек ВКонтакте - параметры', [
            'access_token_exists' => !empty($this->accessToken),
            'access_token_sample' => $this->accessToken ? substr($this->accessToken, 0, 10) . '...' : null,
            'owner_id' => $this->ownerId,
            'env_access_token' => env('VK_ACCESS_TOKEN') ? 'Задан' : 'Не задан',
            'env_owner_id' => env('VK_OWNER_ID'),
            'skip_api_check' => $skipApiCheck
        ]);
        
        if (empty($this->accessToken)) {
            $result['success'] = false;
            $result['errors'][] = 'Не задан токен доступа ВКонтакте';
        }
        
        if (empty($this->ownerId)) {
            $result['success'] = false;
            $result['errors'][] = 'Не задан ID группы ВКонтакте';
        }
        
        // Если проверка API отключена, возвращаем результат без проверки соединения
        if ($skipApiCheck) {
            Log::info('Проверка API ВКонтакте пропущена', ['reason' => 'Явный пропуск проверки']);
            return $result;
        }
        
        // Если токен и ID группы заданы, проверяем связь с API
        if ($result['success']) {
            try {
                // Используем метод groups.getById для проверки доступа к группе
                $groupId = abs((int)$this->ownerId);
                $response = Http::timeout($this->timeout)->get('https://api.vk.com/method/groups.getById', [
                    'group_id' => $groupId,
                    'access_token' => $this->accessToken,
                    'v' => $this->version
                ]);
                
                $data = $response->json();
                Log::debug('Ответ API ВКонтакте при проверке настроек', $data);
                
                if (!isset($data['response'])) {
                    $result['success'] = false;
                    $error = isset($data['error']) ? $data['error']['error_msg'] : 'Неизвестная ошибка';
                    $result['errors'][] = 'Ошибка соединения с API ВКонтакте: ' . $error;
                    
                    Log::error('Ошибка проверки API ВКонтакте', [
                        'response' => $data,
                        'error_code' => $data['error']['error_code'] ?? 'unknown'
                    ]);
                } else {
                    Log::info('Успешное соединение с API ВКонтакте', [
                        'group_info' => $data['response']
                    ]);
                }
            } catch (\Exception $e) {
                // Если ошибка таймаута или сетевая ошибка, устанавливаем специальный флаг
                $isTimeoutError = strpos($e->getMessage(), 'timed out') !== false || 
                                  strpos($e->getMessage(), 'cURL error 28') !== false ||
                                  strpos($e->getMessage(), 'connection failed') !== false;
                                  
                if ($isTimeoutError) {
                    $result['is_timeout'] = true;
                    $result['errors'][] = 'Таймаут при обращении к API ВКонтакте. Возможно, сервис заблокирован или недоступен.';
                } else {
                    $result['errors'][] = 'Исключение при проверке API ВКонтакте: ' . $e->getMessage();
                }
                
                // Если это таймаут, можно продолжить работу в резервном режиме
                if ($isTimeoutError) {
                    Log::warning('Таймаут при проверке API ВКонтакте, продолжаем в резервном режиме', [
                        'error' => $e->getMessage()
                    ]);
                    $result['success'] = true; // Считаем, что настройки верны, но есть проблемы с соединением
                    $result['connection_issues'] = true;
                } else {
                    $result['success'] = false;
                    Log::error('Исключение при проверке API ВКонтакте: ' . $e->getMessage(), [
                        'trace' => $e->getTraceAsString()
                    ]);
                }
            }
        }
        
        return $result;
    }

    /**
     * Публикует пост в группу ВКонтакте
     * 
     * @param SocialPost $post Пост для публикации
     * @param bool $forcePublish Публиковать даже при проблемах с проверкой API
     * @return bool Успешность публикации
     */
    public function publishPost(SocialPost $post, $forcePublish = false)
    {
        try {
            Log::info('Начинаем публикацию в ВК', [
                'post_id' => $post->id,
                'has_image' => !empty($post->image_url),
                'force_publish' => $forcePublish
            ]);
            
            // Проверка настроек - если forcePublish=true, пропускаем проверку API
            $settingsCheck = $this->checkSettings($forcePublish);
            
            // Если есть проблемы с настройками (кроме проблем с соединением), выходим
            if (!$settingsCheck['success'] && !isset($settingsCheck['connection_issues'])) {
                Log::error('Ошибка конфигурации ВКонтакте', $settingsCheck['errors']);
                return false;
            }
            
            // Дополнительная проверка токена перед отправкой
            if (empty($this->accessToken)) {
                Log::error('Токен доступа не определен при публикации в ВК', [
                    'post_id' => $post->id,
                ]);
                return false;
            }

            // Используем ID группы с минусом для публикации от имени группы
            $ownerId = $this->ownerId;
            if (strpos($ownerId, '-') !== 0) {
                $ownerId = '-' . $ownerId;
            }
            
            $params = [
                'owner_id' => $ownerId,
                'from_group' => 1,
                'message' => $post->title . "\n\n" . $post->content,
                'access_token' => $this->accessToken,
                'v' => $this->version
            ];
            
            Log::debug('Параметры запроса к API ВК', [
                'owner_id' => $params['owner_id'],
                'from_group' => $params['from_group'],
                'message_length' => strlen($params['message']),
                'token_exists' => !empty($params['access_token']),
                'token_length' => strlen($params['access_token']),
                'token_sample' => substr($params['access_token'], 0, 10) . '...',
                'v' => $params['v']
            ]);

            // Если есть изображение, загружаем его (с форсированием, если нужно)
            if ($post->image_url) {
                $attachments = $this->uploadPhoto($post->image_url, $forcePublish);
                if ($attachments) {
                    $params['attachments'] = $attachments;
                    Log::info('Фото успешно загружено для публикации', [
                        'attachment' => $attachments
                    ]);
                } else {
                    // Проверим альтернативный URL изображения
                    $alternativeUrl = $this->getAlternativeImageUrl($post->image_url);
                    if ($alternativeUrl) {
                        Log::info('Пробуем альтернативный URL изображения', [
                            'original_url' => $post->image_url,
                            'alternative_url' => $alternativeUrl
                        ]);
                        
                        $attachments = $this->uploadPhoto($alternativeUrl, $forcePublish);
                        if ($attachments) {
                            $params['attachments'] = $attachments;
                            Log::info('Фото по альтернативному URL успешно загружено', [
                                'attachment' => $attachments
                            ]);
                        }
                    } else {
                        Log::warning('Не удалось загрузить изображение для публикации в ВК', [
                            'post_id' => $post->id,
                            'image_url' => $post->image_url
                        ]);
                    }
                }
            }

            // Выполняем запрос к API ВКонтакте с увеличенным таймаутом для публикации
            Log::debug('Отправка запроса на публикацию с параметрами', [
                'url' => 'https://api.vk.com/method/wall.post',
                'params_count' => count($params)
            ]);
            
            // Пробуем отправить запрос стандартным способом
            try {
                $response = Http::timeout($this->timeout * 2)
                              ->post('https://api.vk.com/method/wall.post', $params);
                
                $result = $response->json();
                Log::debug('Ответ API ВК на публикацию', $result);

                if (isset($result['response']['post_id'])) {
                    $this->processSuccessfulPost($post, $result);
                    return true;
                }
                
                // Если получена ошибка, и это блокировка или проблема с авторизацией, пробуем через прокси
                if (isset($result['error']) && 
                    ($result['error']['error_code'] == 5 || $result['error']['error_code'] == 27 || 
                     strpos($result['error']['error_msg'] ?? '', 'access_token') !== false ||
                     strpos($result['error']['error_msg'] ?? '', 'authorization') !== false)) {
                    
                    Log::warning('Ошибка API ВК - пробуем через прокси', [
                        'error_code' => $result['error']['error_code'],
                        'error_msg' => $result['error']['error_msg']
                    ]);
                    
                    return $this->publishPostViaProxy($post, $params);
                }

                if (isset($result['error'])) {
                    Log::error('Ошибка API ВК при публикации', [
                        'post_id' => $post->id,
                        'error_code' => $result['error']['error_code'] ?? 'unknown',
                        'error_msg' => $result['error']['error_msg'] ?? 'Неизвестная ошибка',
                        'request_params' => $result['error']['request_params'] ?? []
                    ]);
                } else {
                    Log::error('Неизвестная ошибка API ВК при публикации', [
                        'post_id' => $post->id,
                        'result' => $result
                    ]);
                }
                
                return false;
            } catch (\Exception $e) {
                Log::error('Исключение при публикации в ВК, переключаемся на прокси: ' . $e->getMessage(), [
                    'post_id' => $post->id
                ]);
                
                // При ошибке пробуем публикацию через прокси
                return $this->publishPostViaProxy($post, $params);
            }
        } catch (\Exception $e) {
            Log::error('Критическое исключение при публикации в ВК: ' . $e->getMessage(), [
                'post_id' => $post->id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Публикует пост через прокси-скрипт для обхода возможной блокировки API
     * 
     * @param SocialPost $post Пост для публикации
     * @param array $params Параметры публикации
     * @return bool Успешность публикации
     */
    protected function publishPostViaProxy(SocialPost $post, array $params)
    {
        try {
            Log::info('Пробуем публикацию через прокси', [
                'post_id' => $post->id
            ]);
            
            // Формируем параметры для прокси
            $proxyParams = [
                'owner_id' => $params['owner_id'],
                'message' => $params['message'],
                'access_token' => $this->accessToken,
                'version' => $this->version
            ];
            
            // Добавляем вложения, если есть
            if (isset($params['attachments'])) {
                $proxyParams['attachments'] = $params['attachments'];
            }
            
            // Формируем URL прокси с секретным ключом (простая защита)
            $secretKey = md5(date('Y-m-d') . 'eats_vk_proxy');
            $proxyUrl = url('/proxy/vk/wall-post.php?key=' . $secretKey);
            
            // Отправляем запрос к прокси
            $response = Http::timeout(30) // Увеличиваем таймаут для прокси
                          ->withHeaders([
                              'Content-Type' => 'application/json',
                              'Accept' => 'application/json'
                          ])
                          ->post($proxyUrl, $proxyParams);
            
            $result = $response->json();
            Log::debug('Ответ прокси на публикацию', $result);
            
            if (isset($result['success']) && $result['success'] === true) {
                // Сохраняем информацию об успешной публикации
                $post->update([
                    'vk_status' => true,
                    'vk_post_id' => $result['post_id'] ?? null,
                    'vk_posted_at' => now(),
                    'post_url' => $result['post_url'] ?? null
                ]);
                
                Log::info('Пост успешно опубликован через прокси', [
                    'post_id' => $post->id,
                    'vk_post_id' => $result['post_id'] ?? null,
                    'vk_post_url' => $result['post_url'] ?? null
                ]);
                
                return true;
            }
            
            if (isset($result['error'])) {
                Log::error('Ошибка прокси при публикации', [
                    'post_id' => $post->id,
                    'error' => $result['error']
                ]);
            } else {
                Log::error('Неизвестная ошибка прокси при публикации', [
                    'post_id' => $post->id,
                    'result' => $result
                ]);
            }
            
            return false;
        } catch (\Exception $e) {
            Log::error('Исключение при публикации через прокси: ' . $e->getMessage(), [
                'post_id' => $post->id,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * Обрабатывает результат успешной публикации поста
     * 
     * @param SocialPost $post Пост для публикации
     * @param array $result Результат от API
     */
    protected function processSuccessfulPost(SocialPost $post, array $result)
    {
        // Конструируем URL поста
        $groupId = abs((int)$this->ownerId);
        $postId = $result['response']['post_id'];
        $postUrl = "https://vk.com/wall-{$groupId}_{$postId}";
        
        // Обновляем информацию о посте
        $post->update([
            'vk_status' => true,
            'vk_post_id' => $postId,
            'vk_posted_at' => now(),
            'post_url' => $postUrl
        ]);
        
        Log::info('Пост успешно опубликован в ВК', [
            'post_id' => $post->id,
            'vk_post_id' => $postId,
            'vk_post_url' => $postUrl
        ]);
    }

    /**
     * Пытается найти альтернативный URL для изображения
     * 
     * @param string $originalUrl Исходный URL изображения
     * @return string|null Альтернативный URL или null
     */
    protected function getAlternativeImageUrl($originalUrl)
    {
        if (empty($originalUrl)) {
            return null;
        }

        // Заменяем домен im-edok.ru на app_url из конфигурации
        if (strpos($originalUrl, 'im-edok.ru') !== false) {
            $alternativeUrl = str_replace('im-edok.ru', parse_url(config('app.url'), PHP_URL_HOST), $originalUrl);
            Log::info('Сформирован альтернативный URL изображения', [
                'original' => $originalUrl,
                'alternative' => $alternativeUrl
            ]);
            
            // Проверим существование изображения
            try {
                $headers = @get_headers($alternativeUrl);
                if ($headers && strpos($headers[0], '200') !== false) {
                    return $alternativeUrl;
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка при проверке альтернативного URL: ' . $e->getMessage());
            }
        }

        // Пробуем другие варианты URL (без домена, только путь)
        $path = parse_url($originalUrl, PHP_URL_PATH);
        if ($path) {
            $baseUrl = rtrim(config('app.url'), '/');
            $localUrl = $baseUrl . '/' . ltrim($path, '/');
            try {
                $headers = @get_headers($localUrl);
                if ($headers && strpos($headers[0], '200') !== false) {
                    return $localUrl;
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка при проверке локального URL: ' . $e->getMessage());
            }
        }

        // Если изображение из рецепта, пробуем найти общедоступное изображение рецепта
        $matches = [];
        if (preg_match('/recipe[s]?_(\d+)_\d+/', $originalUrl, $matches)) {
            $recipeId = $matches[1];
            $baseUrl = rtrim(config('app.url'), '/');
            $defaultImage = $baseUrl . '/images/recipes/default_' . $recipeId . '.jpg';
            try {
                $headers = @get_headers($defaultImage);
                if ($headers && strpos($headers[0], '200') !== false) {
                    return $defaultImage;
                }
            } catch (\Exception $e) {
                Log::warning('Ошибка при проверке изображения по умолчанию: ' . $e->getMessage());
            }
        }

        return null;
    }
        
    /**
     * Загружает фото в ВКонтакте и возвращает строку attachments
     * 
     * @param string $imageUrl URL изображения
     * @param bool $forceUpload Загружать даже в случае проблем с соединением
     * @return string|null Строка attachments для API ВКонтакте или null в случае ошибки
     */
    protected function uploadPhoto($imageUrl, $forceUpload = false)
    {
        try {
            Log::info('Начинаем загрузку фото в ВК', [
                'image_url' => $imageUrl,
                'force_upload' => $forceUpload
            ]);

            // Проверяем наличие токена
            if (empty($this->accessToken)) {
                Log::error('Отсутствует токен доступа для загрузки фото');
                return null;
            }

            // Проверяем существование URL изображения
            $headers = get_headers($imageUrl, 1);
            if (!$headers || strpos($headers[0], '200') === false) {
                Log::error('Изображение недоступно по указанному URL', [
                    'image_url' => $imageUrl,
                    'headers' => $headers[0] ?? 'Не получены'
                ]);
                return null;
            }

            // Получаем ID группы (без минуса)
            $groupId = abs((int)$this->ownerId);
            
            // Параметры запроса для загрузки фото
            $params = [
                'group_id' => $groupId,
                'access_token' => $this->accessToken,
                'v' => $this->version
            ];
            
            Log::debug('Параметры запроса для получения сервера загрузки', [
                'params' => array_merge(
                    array_diff_key($params, ['access_token' => '']),
                    ['access_token' => substr($this->accessToken, 0, 10) . '...']
                )
            ]);
            
            // Шаг 1: Получаем сервер для загрузки с явным указанием всех параметров
            $serverResponse = Http::timeout($this->timeout)
                              ->get('https://api.vk.com/method/photos.getWallUploadServer', $params);
            
            $serverData = $serverResponse->json();
            Log::debug('Ответ API ВК при получении сервера для загрузки', $serverData);
            
            if (!isset($serverData['response']['upload_url'])) {
                // Если ошибка связана с авторизацией групп, пробуем альтернативный метод
                if (isset($serverData['error']) && 
                    ($serverData['error']['error_code'] == 27 || 
                     strpos($serverData['error']['error_msg'] ?? '', 'group auth') !== false)) {
                    Log::warning('Ошибка авторизации группы, пробуем без указания group_id', [
                        'error' => $serverData['error']
                    ]);
                    
                    // Пробуем получить upload_url без указания group_id
                    $params = [
                        'access_token' => $this->accessToken,
                        'v' => $this->version
                    ];
                    
                    $serverResponse = Http::timeout($this->timeout)
                                    ->get('https://api.vk.com/method/photos.getWallUploadServer', $params);
                    $serverData = $serverResponse->json();
                    
                    if (!isset($serverData['response']['upload_url'])) {
                        Log::error('Не удалось получить сервер для загрузки фото (альтернативный метод)', $serverData);
                        return null;
                    }
                } else {
                    Log::error('Не удалось получить сервер для загрузки фото', $serverData);
                    return null;
                }
            }

            // Шаг 2: Скачиваем изображение во временный файл
            $uploadUrl = $serverData['response']['upload_url'];
            $tempFile = tempnam(sys_get_temp_dir(), 'vk_img_');
            $imageContent = file_get_contents($imageUrl);
            if (!$imageContent) {
                Log::error('Не удалось скачать изображение', [
                    'image_url' => $imageUrl
                ]);
                return null;
            }
            file_put_contents($tempFile, $imageContent);
            
            // Шаг 3: Загружаем изображение на сервер ВК
            $uploadResponse = Http::timeout($this->timeout * 2) // Увеличенный таймаут для загрузки файла
                              ->attach(
                                'photo', 
                                file_get_contents($tempFile), 
                                'image.jpg'
                              )->post($uploadUrl);
            
            unlink($tempFile); // Удаляем временный файл
            
            $uploadData = $uploadResponse->json();
            Log::debug('Ответ при загрузке фото на сервер ВК', $uploadData);
            
            if (!isset($uploadData['photo']) || empty($uploadData['photo']) || $uploadData['photo'] === '[]') {
                Log::error('Ошибка при загрузке фото на сервер ВК', $uploadData);
                return null;
            }
            
            if (!isset($uploadData['server']) || !isset($uploadData['hash'])) {
                Log::error('Отсутствуют необходимые параметры в ответе при загрузке фото', $uploadData);
                return null;
            }
            
            // Шаг 4: Сохраняем фото в альбом группы с явным указанием всех параметров
            $saveParams = [
                'group_id' => $groupId, 
                'photo' => $uploadData['photo'],
                'server' => $uploadData['server'],
                'hash' => $uploadData['hash'],
                'access_token' => $this->accessToken,
                'v' => $this->version
            ];
            
            Log::debug('Параметры запроса для сохранения фото', [
                'params' => array_merge(
                    array_diff_key($saveParams, ['access_token' => '']),
                    ['access_token' => substr($this->accessToken, 0, 10) . '...']
                )
            ]);
            
            $saveResponse = Http::timeout($this->timeout)
                            ->get('https://api.vk.com/method/photos.saveWallPhoto', $saveParams);
            
            $saveData = $saveResponse->json();
            Log::debug('Ответ при сохранении фото в ВК', $saveData);
            
            if (!isset($saveData['response'][0]['owner_id']) || !isset($saveData['response'][0]['id'])) {
                Log::error('Ошибка при сохранении фото в ВК', $saveData);
                return null;
            }
            
            // Формируем строку attachments
            $photoOwnerId = $saveData['response'][0]['owner_id'];
            $photoId = $saveData['response'][0]['id'];
            $attachment = "photo{$photoOwnerId}_{$photoId}";
            
            Log::info('Фото успешно загружено в ВК', [
                'attachment' => $attachment
            ]);
            
            return $attachment;
            
        } catch (\Exception $e) {
            Log::error('Исключение при загрузке фото в ВК: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            // При таймауте можно попробовать загрузить ещё раз с другими параметрами
            $isTimeoutError = strpos($e->getMessage(), 'timed out') !== false || 
                             strpos($e->getMessage(), 'cURL error 28') !== false;
                             
            if ($isTimeoutError && !$forceUpload) {
                Log::info('Таймаут при загрузке фото, пробуем с альтернативным методом', [
                    'image_url' => $imageUrl
                ]);
                
                // Попробуем иной подход или реализуем mock-attachment если API недоступно
                return null;
            }
            
            return null;
        }
    }

    /**
     * Генерирует URL для OAuth авторизации в ВКонтакте
     *
     * @return string URL для авторизации
     */
    public function getOAuthUrl()
    {
        $state = Str::random(32);
        Session::put('vk_oauth_state', $state);
        
        $groupIds = str_replace('-', '', $this->ownerId);
        
        $params = [
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'group_ids' => $groupIds,
            'display' => 'page',
            'scope' => 'manage,photos,wall,docs,offline', // Обязательно offline для долгосрочного токена
            'response_type' => 'code',
            'v' => $this->version,
            'state' => $state
        ];
        
        $url = 'https://oauth.vk.com/authorize?' . http_build_query($params);
        
        Log::info('Сгенерирован URL для OAuth авторизации', [
            'url' => $url,
            'client_id' => $this->clientId,
            'redirect_uri' => $this->redirectUri,
            'group_ids' => $groupIds
        ]);
        
        return $url;
    }

    /**
     * Обменивает код авторизации на токен доступа
     *
     * @param string $code Код авторизации
     * @param string $state Защитное состояние
     * @return array Массив с результатом получения токена
     */
    public function exchangeCodeForToken($code, $state)
    {
        if ($state !== Session::get('vk_oauth_state')) {
            Log::warning('Несоответствие состояния VK OAuth', [
                'received' => $state,
                'expected' => Session::get('vk_oauth_state')
            ]);
            
            return [
                'success' => false,
                'error' => 'Ошибка безопасности: несоответствие состояния OAuth'
            ];
        }
        
        try {
            Log::info('Обмен кода на токен доступа', [
                'code_length' => strlen($code),
                'client_id' => $this->clientId,
                'redirect_uri' => $this->redirectUri
            ]);
            
            $response = Http::post('https://oauth.vk.com/access_token', [
                'client_id' => $this->clientId,
                'client_secret' => $this->clientSecret,
                'redirect_uri' => $this->redirectUri,
                'code' => $code
            ]);
            
            $data = $response->json();
            Log::debug('Ответ при обмене кода на токен', array_keys($data));
            
            if (isset($data['error'])) {
                Log::error('Ошибка получения токена VK', $data);
                
                return [
                    'success' => false,
                    'error' => $data['error_description'] ?? $data['error']
                ];
            }
            
            // Сохраняем группы и токены
            $groups = $data['groups'] ?? [];
            $tokens = [];
            
            foreach ($groups as $group) {
                $tokens[$group['group_id']] = $group['access_token'];
            }
            
            Log::info('Успешно получены токены для групп', [
                'group_count' => count($groups)
            ]);
            
            return [
                'success' => true,
                'tokens' => $tokens,
                'groups' => $groups
            ];
            
        } catch (\Exception $e) {
            Log::error('Исключение при получении токена VK: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString()
            ]);
            
            return [
                'success' => false,
                'error' => 'Ошибка при обмене кода на токен: ' . $e->getMessage()
            ];
        }
    }

    /**
     * Проверяет, не заблокирован ли сервис VK
     * 
     * @return bool Возвращает true, если сервис доступен
     */
    public function isServiceAvailable()
    {
        try {
            $response = Http::timeout($this->timeout)->get('https://api.vk.com/method/users.get', [
                'user_ids' => '1',
                'access_token' => $this->accessToken,
                'v' => $this->version
            ]);
            
            $data = $response->json();
            
            // Проверяем наличие ответа или специфической ошибки о блокировке
            if (isset($data['response']) || 
                (isset($data['error']) && $data['error']['error_code'] != 5 && 
                 !str_contains($data['error']['error_msg'] ?? '', 'blocked'))) {
                return true;
            }
            
            Log::warning('Сервис ВКонтакте недоступен', [
                'response' => $data
            ]);
            
            return false;
        } catch (\Exception $e) {
            // Специально обрабатываем таймауты - они означают, что сервис заблокирован или недоступен
            $isTimeoutError = strpos($e->getMessage(), 'timed out') !== false || 
                             strpos($e->getMessage(), 'cURL error 28') !== false;
                             
            if ($isTimeoutError) {
                Log::warning('Сервис ВКонтакте недоступен (таймаут)', [
                    'error' => $e->getMessage()
                ]);
            } else {
                Log::error('Ошибка при проверке доступности ВКонтакте: ' . $e->getMessage());
            }
            
            return false;
        }
    }

    /**
     * Получает тип авторизации (группа или пользователь)
     * 
     * @return string 'user' или 'group'
     */
    public function getAuthType()
    {
        // Проверяем формат токена
        $isUserToken = strpos($this->accessToken, 'vk1.a.') === 0;
        $isGroupToken = strpos($this->ownerId, '-') === 0 && 
                       !$isUserToken;
        
        Log::debug('Определение типа авторизации', [
            'isUserToken' => $isUserToken,
            'isGroupToken' => $isGroupToken,
            'ownerIdFormat' => $this->ownerId,
            'tokenFormat' => substr($this->accessToken, 0, 6) . '...'
        ]);
        
        return $isUserToken ? 'user' : 'group';
    }

    /**
     * Получает правильный ID для запросов при авторизации от имени пользователя
     *
     * @return string ID группы или пользователя в правильном формате
     */
    protected function getCorrectOwnerId()
    {
        $authType = $this->getAuthType();
        
        // Для пользовательского токена ID группы всегда должен быть со знаком минус
        if ($authType === 'user') {
            // Убеждаемся, что ID группы начинается с минуса
            if (strpos($this->ownerId, '-') !== 0) {
                return '-' . $this->ownerId;
            }
        }
        
        return $this->ownerId;
    }
}
