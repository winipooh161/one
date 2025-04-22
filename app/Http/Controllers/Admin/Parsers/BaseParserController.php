<?php

namespace App\Http\Controllers\Admin\Parsers;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use DOMDocument;
use DOMXPath;
use Exception;

abstract class BaseParserController extends Controller
{
    /**
     * Парсинг количества ингредиента и единицы измерения
     * 
     * @param string $quantityString Строка с количеством (например, "200 г", "2 штуки", "по вкусу")
     * @return array Массив с числовым значением и единицей измерения
     */
    protected function parseQuantity($quantityString)
    {
        $result = [
            'value' => null,
            'unit' => ''
        ];
        
        // Если пусто или "по вкусу", "по желанию", etc.
        if (empty($quantityString) || preg_match('/по\s+(вкусу|желанию)/i', $quantityString)) {
            $result['unit'] = 'по вкусу';
            return $result;
        }
        
        // Извлекаем числовое значение с поддержкой дробей типа 1/2
        if (preg_match('/(\d+)\s*[\/]\s*(\d+)/', $quantityString, $matches)) {
            // Обработка дробей вида "1/2"
            $result['value'] = (float)$matches[1] / (float)$matches[2];
        }
        // Обработка обычных чисел
        elseif (preg_match('/(\d+[.,]?\d*)/', $quantityString, $matches)) {
            $result['value'] = (float)str_replace(',', '.', $matches[1]);
        }
        
        // Определяем единицу измерения с расширенным списком паттернов
        $unitPatterns = [
            'г|грамм|гр\.?' => 'г',
            'кг|килограмм' => 'кг',
            'мл|миллилитр' => 'мл',
            'л|литр' => 'л',
            'ч\.?\s*л\.?|чайн(ая|ой|ую)?\s+лож(ка|ки|ку)' => 'ч.л.',
            'ст\.?\s*л\.?|столов(ая|ой|ую)?\s+лож(ка|ки|ку)' => 'ст.л.',
            'шт\.?|штук[аи]?|штук' => 'шт.',
            'стакан[а-я]*' => 'стакан',
            'пуч[ое]?к|пучок' => 'пучок',
            'зуб[а-я]+|зубчик[а-я]*' => 'зубчик',
            'долька|долек|дольки' => 'долька',
            'банк[аи]|банок' => 'банка',
            'упаковк[аи]|упаковок' => 'упаковка',
            'пачк[аи]|пачек' => 'пачка',
            'по\s+вкусу' => 'по вкусу',
            'щепотк[аи]|щепоток' => 'щепотка'
        ];
        
        foreach ($unitPatterns as $pattern => $standardUnit) {
            if (preg_match('/' . $pattern . '/iu', $quantityString)) {
                $result['unit'] = $standardUnit;
                break;
            }
        }
        
        // Если не удалось определить единицу измерения, но есть числовое значение
        if (empty($result['unit']) && !is_null($result['value'])) {
            $result['unit'] = 'шт.'; // По умолчанию считаем штуками
        }
        
        return $result;
    }

    /**
     * Вспомогательная функция для парсинга чисел
     */
    protected function parseNumber($value)
    {
        if (empty($value)) {
            return null;
        }
        
        // Дополнительный лог для отладки
        \Log::debug("Парсинг числового значения из строки: '{$value}'");
        
        // Очищаем строку от лишних символов перед парсингом
        $cleanValue = trim(strip_tags($value));
        
        // Выделяем первое число из строки с помощью регулярного выражения
        if (preg_match('/(\d+[.,]?\d*)/', $cleanValue, $matches)) {
            // Заменяем запятую на точку для правильного преобразования
            $numericValue = str_replace(',', '.', $matches[1]);
            
            // Преобразуем в число
            if (is_numeric($numericValue)) {
                $result = (float)$numericValue;
                \Log::debug("Успешно распарсено число: {$result}");
                return $result;
            }
        }
        
        // Если стандартное регулярное выражение не сработало, пробуем другие паттерны
        if (preg_match('/^(\d+[.,]?\d*)\s*(?:g|г|ккал|kkal|kcal)/i', $cleanValue, $matches)) {
            $numericValue = str_replace(',', '.', $matches[1]);
            if (is_numeric($numericValue)) {
                $result = (float)$numericValue;
                \Log::debug("Успешно распарсено число из единиц измерения: {$result}");
                return $result;
            }
        }
        
        \Log::debug("Не удалось распарсить число из строки: '{$value}'");
        return null;
    }

    /**
     * Преобразование CSS-селектора в XPath
     */
    protected function cssToXPath($selector)
    {
        // Проверяем, является ли селектор уже XPath-запросом
        if (strpos($selector, '/') === 0) {
            return $selector;
        }
        
        // Очень базовое преобразование CSS в XPath для наиболее распространенных случаев
        $xpathParts = [];
        $parts = explode(',', $selector);
        
        foreach ($parts as $part) {
            $part = trim($part);
            
            // Замена классов
            $part = preg_replace('/\.([\w-]+)/', '[contains(@class, "$1")]', $part);
            
            // Замена идентификаторов
            $part = preg_replace('/#([\w-]+)/', '[@id="$1"]', $part);
            
            // Замена пробелов на //
            $part = preg_replace('/\s+/', '//', $part);
            
            // Добавление начала пути '//'
            $xpathParts[] = '//' . $part;
        }
        
        return implode(' | ', $xpathParts);
    }

    /**
     * Генерирует уникальный слаг для рецепта
     */
    protected function generateUniqueSlug($baseSlug)
    {
        $slug = $baseSlug;
        $counter = 1;
        
        // Проверяем существование слага
        while (Recipe::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $counter;
            $counter++;
            
            // Предотвращаем бесконечный цикл
            if ($counter > 100) {
                $slug = $baseSlug . '-' . uniqid();
                break;
            }
        }
        
        return $slug;
    }
    
    /**
     * Определение расширения изображения из содержимого и URL
     */
    protected function getImageExtensionFromContent($imageContents, $imageUrl)
    {
        // Сначала пробуем определить из URL
        $parts = parse_url($imageUrl);
        $path = $parts['path'] ?? '';
        
        if (preg_match('/\.(jpe?g|png|gif|webp)$/i', $path, $matches)) {
            $ext = strtolower($matches[1]);
            return $ext == 'jpeg' ? 'jpg' : $ext;
        }
        
        // Если не удалось определить из URL, используем finfo для определения типа
        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->buffer($imageContents);
        
        switch ($mimeType) {
            case 'image/jpeg':
                return 'jpg';
            case 'image/png':
                return 'png';
            case 'image/gif':
                return 'gif';
            case 'image/webp':
                return 'webp';
            default:
                // Если тип не определен, используем jpg по умолчанию
                return 'jpg';
        }
    }
    
    /**
     * Парсинг времени в формате ISO 8601 Duration (PT1H30M)
     */
    protected function parseDuration($duration)
    {
        $minutes = 0;
        
        // Если это строка в формате PT1H30M
        if (preg_match('/PT(?:(\d+)H)?(?:(\d+)M)?(?:(\d+)S)?/', $duration, $matches)) {
            $hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $mins = isset($matches[2]) ? (int)$matches[2] : 0;
            $secs = isset($matches[3]) ? (int)$matches[3] : 0;
            
            $minutes = $hours * 60 + $mins + ceil($secs / 60);
        } 
        // Если это просто число
        elseif (is_numeric($duration)) {
            $minutes = (int)$duration;
        } 
        // Если это строка вида "1ч 30мин" или "1 час 30 минут"
        elseif (preg_match('/(\d+)\s*(?:h|ч|час|hours?)?\s*(?:и|and)?\s*(\d+)?\s*(?:m|мин|min|минут)?/i', $duration, $matches)) {
            $hours = isset($matches[1]) ? (int)$matches[1] : 0;
            $mins = isset($matches[2]) ? (int)$matches[2] : 0;
            $minutes = $hours * 60 + $mins;
        }
        
        return $minutes > 0 ? $minutes : null;
    }
}
