<?php
/**
 * Прокси-скрипт для публикации постов в ВКонтакте
 * Запускается как отдельный процесс, что позволяет обойти ограничения веб-сервера
 * и решить проблемы с таймаутами при блокировке API
 */

// Увеличиваем лимиты времени и памяти
ini_set('max_execution_time', 60);
ini_set('memory_limit', '256M');

// Загружаем основные файлы Laravel
require_once __DIR__ . '/../../../vendor/autoload.php';

// Создаем функцию для логирования
function proxy_log($message, $data = []) {
    $logFile = __DIR__ . '/../../../storage/logs/vk_proxy.log';
    $timestamp = date('Y-m-d H:i:s');
    $logData = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    file_put_contents($logFile, "[$timestamp] $message\n$logData\n\n", FILE_APPEND);
}

// Функция для выполнения HTTP запроса с повторными попытками
function make_request($url, $method = 'GET', $params = [], $retries = 3, $timeout = 10) {
    $attempt = 0;
    
    while ($attempt < $retries) {
        $attempt++;
        proxy_log("Попытка $attempt/$retries запроса к $url");
        
        try {
            $ch = curl_init();
            if ($method === 'POST') {
                curl_setopt($ch, CURLOPT_POST, 1);
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
            } else {
                if (!empty($params)) {
                    $url .= '?' . http_build_query($params);
                }
            }
            
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, $timeout);
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_USERAGENT, 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36');
            
            $result = curl_exec($ch);
            $error = curl_error($ch);
            $info = curl_getinfo($ch);
            curl_close($ch);
            
            if ($error) {
                proxy_log("cURL ошибка в попытке $attempt", ['error' => $error, 'info' => $info]);
                sleep(1); // Пауза перед следующей попыткой
                continue;
            }
            
            // Проверяем, является ли результат валидным JSON
            $data = json_decode($result, true);
            if ($data === null && json_last_error() !== JSON_ERROR_NONE) {
                proxy_log("Невалидный JSON в попытке $attempt", ['result' => $result]);
                sleep(1);
                continue;
            }
            
            return $data;
        } catch (Exception $e) {
            proxy_log("Исключение в попытке $attempt", ['exception' => $e->getMessage()]);
            sleep(1);
        }
    }
    
    return ['error' => ['error_code' => 999, 'error_msg' => "Все $retries попыток запроса завершились неудачно"]];
}

// Функция для публикации поста в ВК
function publish_post_to_vk($post_data) {
    proxy_log("Запуск публикации поста через прокси", $post_data);
    
    // Обязательные поля
    $owner_id = $post_data['owner_id'] ?? null;
    $message = $post_data['message'] ?? '';
    $access_token = $post_data['access_token'] ?? null;
    $version = $post_data['version'] ?? '5.131';
    
    if (!$owner_id || !$access_token) {
        return ['error' => ['error_code' => 400, 'error_msg' => 'Отсутствуют обязательные параметры']];
    }
    
    // Формируем параметры запроса
    $params = [
        'owner_id' => $owner_id,
        'from_group' => 1,
        'message' => $message,
        'access_token' => $access_token,
        'v' => $version
    ];
    
    // Если есть вложения, добавляем их
    if (!empty($post_data['attachments'])) {
        $params['attachments'] = $post_data['attachments'];
    }
    
    // Выполняем запрос к API с повторными попытками
    $result = make_request('https://api.vk.com/method/wall.post', 'POST', $params, 5, 20);
    
    if (isset($result['response']['post_id'])) {
        $postId = $result['response']['post_id'];
        $groupId = abs((int)$owner_id);
        $post_url = "https://vk.com/wall-{$groupId}_{$postId}";
        
        proxy_log("Пост успешно опубликован", [
            'post_id' => $postId,
            'post_url' => $post_url
        ]);
        
        // Возвращаем информацию о публикации
        return [
            'success' => true,
            'post_id' => $postId,
            'post_url' => $post_url
        ];
    } else {
        proxy_log("Ошибка публикации", $result);
        return $result;
    }
}

// Обработка запроса
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json');
    
    // Получаем данные запроса
    $input = file_get_contents('php://input');
    $post_data = json_decode($input, true);
    
    // Проверяем секретный ключ для безопасности
    $secret_key = $_GET['key'] ?? '';
    $expected_key = md5(date('Y-m-d') . 'eats_vk_proxy');
    
    if ($secret_key !== $expected_key) {
        echo json_encode(['error' => 'Неверный ключ доступа']);
        exit;
    }
    
    // Публикуем пост
    $result = publish_post_to_vk($post_data);
    echo json_encode($result);
} else {
    // Если запрос не POST, возвращаем ошибку
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Метод не поддерживается']);
}
