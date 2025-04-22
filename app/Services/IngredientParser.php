<?php

namespace App\Services;

class IngredientParser
{
    /**
     * Разбирает строку или массив с ингредиентами и преобразует в структурированный массив
     *
     * @param string|array $ingredientsText Текст с ингредиентами (разделенный переносами строк) или массив ингредиентов
     * @return array Массив структурированных ингредиентов
     */
    public function parseIngredients($ingredientsText): array
    {
        // Если передан массив, используем его напрямую
        if (is_array($ingredientsText)) {
            $lines = $ingredientsText;
        } else {
            // Иначе разбиваем строку на массив строк
            $lines = explode("\n", $ingredientsText);
        }

        $structuredIngredients = [];

        foreach ($lines as $line) {
            // Если элемент уже массив, используем его
            if (is_array($line)) {
                $structuredIngredients[] = $line;
                continue;
            }
            
            $line = trim($line);
            if (empty($line)) continue;

            $structuredIngredients[] = $this->parseIngredientLine($line);
        }

        return $structuredIngredients;
    }
    
    /**
     * Парсинг строки ингредиента для выделения названия, количества и единицы измерения
     *
     * @param string $line Строка с ингредиентом
     * @return array Структурированный ингредиент
     */
    public function parseIngredientLine(string $line): array
    {
        $result = [
            'name' => trim($line),
            'quantity' => null,
            'unit' => 'по вкусу'
        ];
        
        // Форматы записи ингредиентов
        
        // 0. Формат: "Название\nЧисло\nЕдиница измерения" (каждое на новой строке)
        if (preg_match('/^([^0-9]+)$/iu', $line, $matches) && 
            !preg_match('/по\s+вкусу/iu', $line)) {
            // Если строка содержит только название (без чисел и "по вкусу")
            // Это может быть случай, когда значение и единица измерения находятся в отдельных элементах
            $result['name'] = trim($matches[1]);
            return $result;
        }
        
        // 1. Формат: "Название - 500 г" или "Название - 2 штуки"
        if (preg_match('/^(.*?)[\s—–-]+\s*(\d+(?:[.,]?\d+)?)\s*(г|кг|мл|л|шт\.?|ст\.л\.?|ч\.л\.?|стакан|пучок|зубчик|щепотка|банка|упаковка|пачка|чайные ложки|столовые ложки|штук[аи]?)\.?$/iu', $line, $matches)) {
            $result['name'] = trim($matches[1]);
            $result['quantity'] = str_replace(',', '.', $matches[2]);
            $result['unit'] = mb_strtolower(trim($matches[3]));
            
            // Приведение сложных единиц к стандартному виду
            $result['unit'] = $this->normalizeUnit($result['unit']);
        } 
        // 2. Формат: "Название - по вкусу"
        elseif (preg_match('/^(.*?)[\s—–-]+\s*(по\s+вкусу)$/iu', $line, $matches)) {
            $result['name'] = trim($matches[1]);
            $result['unit'] = 'по вкусу';
        }
        // 3. Формат: "Название - 1½ чайные ложки" (обработка дробей с символами)
        elseif (preg_match('/^(.*?)[\s—–-]+\s*(\d*[½¼¾⅓⅔]+)\s*(.*?)$/iu', $line, $matches)) {
            $result['name'] = trim($matches[1]);
            $result['quantity'] = $this->convertFractionToDecimal($matches[2]);
            $result['unit'] = mb_strtolower(trim($matches[3]));
            
            // Стандартизация единиц измерения
            $result['unit'] = $this->normalizeUnit($result['unit']);
        }
        // 4. Только число без единиц измерения
        elseif (preg_match('/^(.*?)[\s—–-]+\s*(\d+(?:[.,]\d+)?)$/iu', $line, $matches)) {
            $result['name'] = trim($matches[1]);
            $result['quantity'] = str_replace(',', '.', $matches[2]);
            $result['unit'] = 'шт.';
        }
        // 5. Формат с числом в начале строки "500 г Мука" (исправляем порядок распознавания)
        elseif (preg_match('/^(\d+(?:[.,]?\d+)?)\s*(г|кг|мл|л|шт\.?|ст\.л\.?|ч\.л\.?|стакан|пучок|зубчик|щепотка|банка|упаковка|пачка|чайные ложки|столовые ложки|штук[аи]?)\.?\s+(.*)$/iu', $line, $matches)) {
            $result['name'] = trim($matches[3]);
            $result['quantity'] = str_replace(',', '.', $matches[1]);
            $result['unit'] = mb_strtolower(trim($matches[2]));
            
            // Приведение сложных единиц к стандартному виду
            $result['unit'] = $this->normalizeUnit($result['unit']);
        }
        // 6. Формат с дробями в начале строки "1½ ч.л. Соль"
        elseif (preg_match('/^(\d*[½¼¾⅓⅔]+)\s*(.*?)\s+(.*)$/iu', $line, $matches)) {
            $result['name'] = trim($matches[3]);
            $result['quantity'] = $this->convertFractionToDecimal($matches[1]);
            $result['unit'] = mb_strtolower(trim($matches[2]));
            
            // Стандартизация единиц измерения
            $result['unit'] = $this->normalizeUnit($result['unit']);
        }
        // 7. Только название продукта и единица измерения без количества
        elseif (preg_match('/^(.*?)\s+(г|кг|мл|л|шт\.?|ст\.л\.?|ч\.л\.?|стакан|пучок|зубчик|щепотка|банка|упаковка|пачка)$/iu', $line, $matches)) {
            $result['name'] = trim($matches[1]);
            $result['unit'] = mb_strtolower(trim($matches[2]));
            $result['unit'] = $this->normalizeUnit($result['unit']);
        }
        // 8. Число и единица измерения без разделителей "500г Сахар"
        elseif (preg_match('/^(\d+(?:[.,]?\d+)?)(г|кг|мл|л|шт|ст\.л|ч\.л)\s+(.*)$/iu', $line, $matches)) {
            $result['name'] = trim($matches[3]);
            $result['quantity'] = str_replace(',', '.', $matches[1]);
            $result['unit'] = mb_strtolower(trim($matches[2]));
            $result['unit'] = $this->normalizeUnit($result['unit']);
        }
        
        return $result;
    }
    
    /**
     * Нормализует единицы измерения
     *
     * @param string $unit Исходная единица измерения
     * @return string Нормализованная единица измерения
     */
    private function normalizeUnit(string $unit): string
    {
        if (stripos($unit, 'чайн') !== false || stripos($unit, 'ч.л') !== false) {
            return 'ч.л.';
        } elseif (stripos($unit, 'столов') !== false || stripos($unit, 'ст.л') !== false) {
            return 'ст.л.';
        } elseif (stripos($unit, 'штук') !== false || $unit == 'шт') {
            return 'шт.';
        } elseif (stripos($unit, 'зубчик') !== false) {
            return 'зубчик';
        } elseif (stripos($unit, 'долек') !== false || stripos($unit, 'дольк') !== false) {
            return 'долька';
        } elseif (stripos($unit, 'щепотк') !== false || stripos($unit, 'щепот') !== false) {
            return 'щепотка';
        }
        
        return $unit;
    }
    
    /**
     * Преобразует символы дробей в десятичные значения
     * 
     * @param string $fractionStr Строка с дробью
     * @return float Десятичное значение
     */
    private function convertFractionToDecimal(string $fractionStr): float
    {
        // Преобразование символов дробей в числовые значения
        $fractionMap = [
            '½' => 0.5,
            '¼' => 0.25,
            '¾' => 0.75,
            '⅓' => 0.33,
            '⅔' => 0.67
        ];
        
        $numericPart = 0;
        
        // Извлекаем целое число, если оно есть
        if (preg_match('/^(\d+)/', $fractionStr, $numMatch)) {
            $numericPart = intval($numMatch[1]);
            $fractionStr = substr($fractionStr, strlen($numMatch[1]));
        }
        
        // Добавляем дробную часть
        foreach ($fractionMap as $symbol => $value) {
            if (strpos($fractionStr, $symbol) !== false) {
                $numericPart += $value;
                break;
            }
        }
        
        return $numericPart;
    }
    
    /**
     * Преобразует текстовый список ингредиентов в JSON формат
     *
     * @param string $ingredientsText Текстовый список ингредиентов
     * @return string JSON строка со структурированными ингредиентами
     */
    public function convertToJson(string $ingredientsText): string
    {
        $structuredIngredients = $this->parseIngredients($ingredientsText);
        return json_encode($structuredIngredients, JSON_UNESCAPED_UNICODE);
    }
    
    /**
     * Преобразует текстовый список ингредиентов в JSON формат с расширенными возможностями
     *
     * @param string $ingredientsText Текстовый список ингредиентов
     * @param array $options Дополнительные опции парсинга
     * @return array Массив структурированных ингредиентов для JSON преобразования
     */
    public function parseToStructuredData(string $ingredientsText, array $options = []): array
    {
        $lines = explode("\n", $ingredientsText);
        $ingredients = [];
        $groupName = $options['default_group'] ?? null;
        $currentGroup = null;
        
        foreach ($lines as $index => $line) {
            $line = trim($line);
            if (empty($line)) continue;
            
            // Проверяем, является ли строка заголовком группы
            // (строка заканчивается двоеточием или следующая строка пустая)
            if (preg_match('/^(.+):$/', $line, $matches) || 
                ($index < count($lines) - 1 && trim($lines[$index + 1]) === '')) {
                $groupName = trim(str_replace(':', '', $matches[1] ?? $line));
                $currentGroup = [
                    'name' => $groupName,
                    'items' => []
                ];
                $ingredients[] = $currentGroup;
                continue;
            }
            
            // Парсим ингредиент
            $parsedIngredient = $this->parseIngredientLine($line);
            
            // Добавляем индекс для сохранения порядка
            $parsedIngredient['index'] = $index;
            
            // Добавляем ингредиент в текущую группу или в основной список
            if ($currentGroup !== null) {
                $currentGroup['items'][] = $parsedIngredient;
                // Обновляем последнюю добавленную группу в общем списке
                $ingredients[count($ingredients) - 1] = $currentGroup;
            } else {
                // Если группа не определена, добавляем в основной список
                $ingredients[] = $parsedIngredient;
            }
        }
        
        return [
            'version' => '2.0',
            'format' => 'structured',
            'count' => count($ingredients),
            'ingredients' => $ingredients
        ];
    }
    
    /**
     * Расширенный метод извлечения ингредиентов с поддержкой дополнительных атрибутов
     * 
     * @param string $line Строка с ингредиентом
     * @param array $options Дополнительные опции парсинга
     * @return array Структурированный ингредиент с дополнительными атрибутами
     */
    public function parseExtendedIngredient(string $line, array $options = []): array
    {
        // Базовый парсинг ингредиента
        $ingredient = $this->parseIngredientLine($line);
        
        // Дополнительные атрибуты
        $ingredient['optional'] = preg_match('/(по\s+желанию|опционально|если\s+есть)/iu', $line) === 1;
        
        // Определение состояния продукта (нарезанный, тертый, и т.д.)
        $states = [
            'нарезан' => 'chopped',
            'измельчен' => 'minced',
            'тертый' => 'grated',
            'вареный' => 'boiled',
            'жареный' => 'fried',
            'запеченный' => 'baked',
            'замороженный' => 'frozen',
            'свежий' => 'fresh'
        ];
        
        foreach ($states as $ru => $en) {
            if (preg_match('/\b' . $ru . '[а-я]*\b/iu', $line)) {
                $ingredient['state'] = $en;
                break;
            }
        }
        
        // Распознавание примечаний
        if (preg_match('/\(([^)]+)\)/', $line, $notes)) {
            $ingredient['notes'] = trim($notes[1]);
        }
        
        // Определение приоритета ингредиента (основной/вспомогательный)
        $ingredient['priority'] = 'normal';
        if ($ingredient['optional']) {
            $ingredient['priority'] = 'low';
        } elseif (preg_match('/(основ|главн)/iu', $line)) {
            $ingredient['priority'] = 'high';
        }
        
        return $ingredient;
    }
    
    /**
     * Преобразует структурированные данные в JSON
     * 
     * @param array $structuredData Массив структурированных данных
     * @return string JSON представление структурированных данных
     */
    public function toJson(array $structuredData): string
    {
        return json_encode($structuredData, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Преобразует данные из JSON в удобный для отображения формат
     * 
     * @param string $jsonData JSON строка с данными ингредиентов
     * @return array Массив с данными ингредиентов для отображения
     */
    public function fromJson(string $jsonData): array
    {
        $data = json_decode($jsonData, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return [];
        }
        
        return $data;
    }
}
