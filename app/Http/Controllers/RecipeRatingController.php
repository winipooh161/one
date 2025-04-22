<?php

namespace App\Http\Controllers;

use App\Models\Recipe;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class RecipeRatingController extends Controller
{
    /**
     * Оценка рецепта пользователем
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\Models\Recipe  $recipe
     * @return \Illuminate\Http\Response
     */
    public function rate(Request $request, Recipe $recipe)
    {
        $request->validate([
            'rating' => 'required|integer|min:1|max:5',
        ]);

        $userId = Auth::id();
        $rating = (int) $request->input('rating');

        // Получаем текущие данные рейтинга и убеждаемся, что это массив
        $additionalData = $recipe->additional_data;
        
        // Проверяем, является ли $additionalData массивом
        if (!is_array($additionalData)) {
            $additionalData = [];
        }

        if (!isset($additionalData['rating'])) {
            $additionalData['rating'] = [
                'value' => 0,
                'count' => 0,
                'sum' => 0,
            ];
        }

        // Убедимся, что user_ratings - это массив
        if (!isset($additionalData['user_ratings']) || !is_array($additionalData['user_ratings'])) {
            $additionalData['user_ratings'] = [];
        }

        // Проверяем, голосовал ли уже этот пользователь
        $previousRating = null;
        $userIdStr = (string) $userId; // Преобразуем ID пользователя в строку для использования в качестве ключа
        
        if (isset($additionalData['user_ratings'][$userIdStr])) {
            $previousRating = $additionalData['user_ratings'][$userIdStr];
        }

        // Обновляем данные рейтинга
        if ($previousRating) {
            // Пользователь уже голосовал, корректируем предыдущую оценку
            $additionalData['rating']['sum'] = $additionalData['rating']['sum'] - $previousRating + $rating;
        } else {
            // Это первое голосование пользователя
            $additionalData['rating']['count']++;
            $additionalData['rating']['sum'] += $rating;
        }

        // Вычисляем новое среднее значение рейтинга
        if ($additionalData['rating']['count'] > 0) {
            $additionalData['rating']['value'] = round($additionalData['rating']['sum'] / $additionalData['rating']['count'], 1);
        }

        // Сохраняем рейтинг пользователя
        $additionalData['user_ratings'][$userIdStr] = $rating;

        // Обновляем данные рецепта
        $recipe->additional_data = $additionalData;
        $recipe->save();

        // Вместо JSON-ответа, перенаправляем на страницу рецепта с сообщением об успехе
        return redirect()->to($request->headers->get('referer'))
            ->with('success', 'Ваша оценка сохранена!');
    }
}
