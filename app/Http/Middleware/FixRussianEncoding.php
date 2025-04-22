<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\Support\Facades\Log;

class FixRussianEncoding
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Исправляем кодировку во всех входящих полях
        $input = $request->all();
        $fixed = $this->fixArrayEncoding($input);
        $request->replace($fixed);

        return $next($request);
    }

    /**
     * Рекурсивно исправляет кодировку во всех элементах массива
     */
    protected function fixArrayEncoding($array)
    {
        if (!is_array($array)) {
            return $array;
        }

        foreach ($array as $key => $value) {
            if (is_array($value)) {
                $array[$key] = $this->fixArrayEncoding($value);
            } elseif (is_string($value)) {
                $array[$key] = $this->fixEncoding($value);
            }
        }

        return $array;
    }

    /**
     * Исправляет кодировку строки
     */
    protected function fixEncoding($text)
    {
        if (empty($text)) {
            return '';
        }

        // Если найдены характерные признаки искаженного UTF-8 текста
        if (preg_match('/Г.{1,2}В.{1,2}/u', $text)) {
            try {
                // Преобразуем из UTF-8 в Windows-1251, а затем обратно в UTF-8
                $temp = mb_convert_encoding($text, 'Windows-1251', 'UTF-8');
                $fixed = mb_convert_encoding($temp, 'UTF-8', 'Windows-1251');
                
                // Проверяем, если результат содержит кириллицу, значит конвертация успешна
                if (preg_match('/[а-яА-ЯёЁ]/u', $fixed)) {
                    Log::debug("Исправлена кодировка строки: " . mb_substr($text, 0, 30) . "... -> " . mb_substr($fixed, 0, 30));
                    return $fixed;
                }
            } catch (\Exception $e) {
                Log::warning("Ошибка при исправлении кодировки: {$e->getMessage()}");
            }
        }

        // Если преобразование не удалось или не требовалось, возвращаем исходный текст
        return $text;
    }
}
