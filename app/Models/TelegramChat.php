<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramChat extends Model
{
    use HasFactory;

    protected $fillable = [
        'chat_id',
        'type',
        'username',
        'first_name',
        'last_name',
        'last_activity_at',
        'is_active',
        'additional_data'
    ];

    protected $casts = [
        'last_activity_at' => 'datetime',
        'is_active' => 'boolean',
        'additional_data' => 'array'
    ];

    /**
     * Получить все сообщения из этого чата
     */
    public function messages()
    {
        return $this->hasMany(TelegramMessage::class, 'chat_id', 'chat_id');
    }

    /**
     * Добавить запрос в историю поиска пользователя
     * 
     * @param string $query Поисковый запрос
     * @param int $limit Максимальное количество запросов в истории
     * @return array Обновленная история поиска
     */
    public function addSearchQuery($query, $limit = 20)
    {
        $data = $this->additional_data ?: [];
        
        if (!isset($data['search_history'])) {
            $data['search_history'] = [];
        }
        
        // Добавляем запрос в начало массива
        array_unshift($data['search_history'], [
            'query' => $query,
            'date' => now()->toDateTimeString()
        ]);
        
        // Ограничиваем историю указанным количеством элементов
        $data['search_history'] = array_slice($data['search_history'], 0, $limit);
        
        // Обновляем модель
        $this->additional_data = $data;
        $this->save();
        
        return $data['search_history'];
    }
    
    /**
     * Получить историю поиска пользователя
     * 
     * @param int $limit Максимальное количество возвращаемых запросов
     * @return array История поиска
     */
    public function getSearchHistory($limit = 10)
    {
        $data = $this->additional_data ?: [];
        
        if (!isset($data['search_history'])) {
            return [];
        }
        
        return array_slice($data['search_history'], 0, $limit);
    }
    
    /**
     * Добавить рецепт в список просмотренных пользователем
     * 
     * @param int $recipeId ID рецепта
     * @param int $limit Максимальное количество хранимых ID
     * @return array Обновленный список просмотренных рецептов
     */
    public function addViewedRecipe($recipeId, $limit = 100)
    {
        $data = $this->additional_data ?: [];
        
        if (!isset($data['viewed_recipes'])) {
            $data['viewed_recipes'] = [];
        }
        
        // Добавляем ID в начало массива, если его там еще нет
        if (!in_array($recipeId, $data['viewed_recipes'])) {
            array_unshift($data['viewed_recipes'], $recipeId);
        } else {
            // Если ID уже есть, удаляем его и добавляем в начало (для поддержания порядка)
            $data['viewed_recipes'] = array_diff($data['viewed_recipes'], [$recipeId]);
            array_unshift($data['viewed_recipes'], $recipeId);
        }
        
        // Ограничиваем количество хранимых ID
        $data['viewed_recipes'] = array_slice($data['viewed_recipes'], 0, $limit);
        
        // Обновляем модель
        $this->additional_data = $data;
        $this->save();
        
        return $data['viewed_recipes'];
    }
    
    /**
     * Получить список просмотренных пользователем рецептов
     * 
     * @param int $limit Максимальное количество возвращаемых ID
     * @return array Список ID просмотренных рецептов
     */
    public function getViewedRecipes($limit = null)
    {
        $data = $this->additional_data ?: [];
        
        if (!isset($data['viewed_recipes'])) {
            return [];
        }
        
        return $limit ? array_slice($data['viewed_recipes'], 0, $limit) : $data['viewed_recipes'];
    }
    
    /**
     * Очистить историю просмотренных рецептов
     * 
     * @return bool Успешность операции
     */
    public function clearViewedRecipes()
    {
        $data = $this->additional_data ?: [];
        $data['viewed_recipes'] = [];
        $this->additional_data = $data;
        return $this->save();
    }
    
    /**
     * Сохранить предпочтения пользователя по категориям
     * 
     * @param array $preferences Массив предпочтений
     * @return bool Успешность операции
     */
    public function savePreferences($preferences)
    {
        $data = $this->additional_data ?: [];
        $data['preferences'] = $preferences;
        $this->additional_data = $data;
        return $this->save();
    }
    
    /**
     * Получить предпочтения пользователя
     * 
     * @return array Предпочтения пользователя
     */
    public function getPreferences()
    {
        $data = $this->additional_data ?: [];
        return $data['preferences'] ?? [];
    }

    /**
     * Получить полное имя пользователя
     */
    public function getFullNameAttribute()
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Получить отображаемое имя (полное имя или username)
     */
    public function getDisplayNameAttribute()
    {
        return $this->full_name ?: ('@' . $this->username) ?: "Чат {$this->chat_id}";
    }

    /**
     * Проверить, активен ли чат (был активен в последние 30 дней)
     */
    public function isActive()
    {
        return $this->last_activity_at && $this->last_activity_at->diffInDays(now()) < 30;
    }
}
