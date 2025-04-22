<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use App\Services\IngredientParser;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use App\Models\Step;
use Illuminate\Support\Facades\Auth;

class Recipe extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'title',
        'slug',
        'description',
        'ingredients',
        'instructions',
        'image_url',
        'cooking_time',
        'servings',
        'calories',
        'proteins',
        'fats',
        'carbs',
        'source_url',
        'is_published',
        'views',
        'additional_data',
        'user_id',
        'difficulty',
        'moderation_status',
        'moderation_message',
        'preparation_time', // Дополнительные поля для микроразметки
        'result_photo',
    ];

    // Константы для статусов модерации
    const MODERATION_STATUS_PENDING = 'pending';
    const MODERATION_STATUS_APPROVED = 'approved';
    const MODERATION_STATUS_REJECTED = 'rejected';

    // Константы для статусов рецептов
    const STATUS_DRAFT = 'draft';
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    protected $casts = [
        'is_published' => 'boolean',
        'additional_data' => 'array',
        'published_at' => 'datetime',
    ];

    protected static function boot()
    {
        parent::boot();

        static::creating(function ($recipe) {
            if (empty($recipe->slug)) {
                // Создаем базовый slug из заголовка
                $baseSlug = Str::slug($recipe->title);
                
                // Если slug получился пустым (например, для не-латинских символов)
                if (empty($baseSlug)) {
                    // Используем транслитерацию или добавляем timestamp
                    $baseSlug = 'recipe-' . time();
                }
                
                // Создаем уникальный slug на основе базового
                $recipe->slug = self::createUniqueSlug($baseSlug);
            }
        });
    }

    /**
     * Создает уникальный slug для рецепта
     *
     * @param string $baseSlug
     * @return string
     */
    public static function createUniqueSlug($baseSlug) 
    {
        $slug = $baseSlug;
        $count = 1;
        
        // Ограничиваем длину slug для предотвращения ошибок в базе данных
        if (strlen($slug) > 80) {
            $slug = substr($slug, 0, 80);
        }
        
        // Проверяем уникальность, если не уникален - добавляем число
        while (self::where('slug', $slug)->exists()) {
            $slug = $baseSlug . '-' . $count++;
            
            // Если счетчик стал слишком большим, добавляем timestamp для гарантии уникальности
            if ($count > 10) {
                $slug = $baseSlug . '-' . time() . rand(100, 999);
                break;
            }
        }
        
        return $slug;
    }

    // Преобразует текстовое представление ингредиентов в массив
    public function getIngredientsArrayAttribute()
    {
        if (!isset($this->attributes['ingredients'])) {
            return [];
        }
        
        if (is_array($this->attributes['ingredients'])) {
            return $this->attributes['ingredients'];
        }
        
        if (is_string($this->attributes['ingredients'])) {
            return explode("\n", $this->attributes['ingredients']);
        }
        
        return [];
    }

    // Преобразует текстовое представление инструкций в массив
    public function getInstructionsArrayAttribute()
    {
        if (!isset($this->attributes['instructions'])) {
            return [];
        }
        
        if (is_array($this->attributes['instructions'])) {
            return $this->attributes['instructions'];
        }
        
        if (is_string($this->attributes['instructions'])) {
            return explode("\n", $this->attributes['instructions']);
        }
        
        return [];
    }
    
    /**
     * Категории, к которым относится рецепт
     */
    public function categories()
    {
        return $this->belongsToMany(Category::class, 'category_recipe', 'recipe_id', 'category_id');
    }

    /**
     * Основная категория рецепта (для совместимости)
     * Возвращает первую категорию из связанных категорий
     */
    public function category()
    {
        return $this->belongsToMany(Category::class)->limit(1);
    }
    
    /**
     * Похожие рецепты (из тех же категорий)
     */
    public function relatedRecipes($limit = 3)
    {
        // Получаем ID категорий текущего рецепта
        $categoryIds = $this->categories->pluck('id')->toArray();
        
        // Если у рецепта нет категорий, используем базовый запрос
        if (empty($categoryIds)) {
            return self::where('id', '!=', $this->id)
                ->where('is_published', true)
                ->inRandomOrder()
                ->limit($limit)
                ->get();
        }
        
        // Начинаем основной запрос
        $query = self::select('recipes.*')
            ->where('recipes.id', '!=', $this->id)
            ->where('recipes.is_published', true);
        
        // Добавляем связь с категориями и подсчитываем совпадения
        $query->leftJoin('category_recipe', 'recipes.id', '=', 'category_recipe.recipe_id')
            ->whereIn('category_recipe.category_id', $categoryIds)
            ->groupBy('recipes.id')
            ->orderByRaw('COUNT(DISTINCT category_recipe.category_id) DESC');
        
        // Если в названии есть ключевые слова (более 3 символов), ищем и по ним
        $titleWords = array_filter(
            explode(' ', $this->title), 
            function($word) { return strlen($word) > 3; }
        );
        
        if (!empty($titleWords)) {
            $query->orderByRaw(
                'CASE WHEN ' . 
                implode(' OR ', array_map(function($word) {
                    return "recipes.title LIKE '%" . addslashes($word) . "%'";
                }, $titleWords)) . 
                ' THEN 1 ELSE 0 END DESC'
            );
        }
        
        // Получаем похожие рецепты (сначала по категориям, затем если не хватает - добираем случайные)
        $related = $query->limit($limit)->get();
        
        // Если недостаточно похожих рецептов, добавляем случайные
        if ($related->count() < $limit) {
            $existingIds = $related->pluck('id')->push($this->id)->toArray();
            $randomRecipes = self::where('is_published', true)
                ->whereNotIn('id', $existingIds)
                ->inRandomOrder()
                ->limit($limit - $related->count())
                ->get();
            
            return $related->concat($randomRecipes);
        }
        
        return $related;
    }

    /**
     * Получить структурированные ингредиенты рецепта
     */
    public function getStructuredIngredientsAttribute()
    {
        if (empty($this->additional_data)) {
            // Если additional_data отсутствует, пробуем получить структурированные ингредиенты на лету
            if ($this->ingredients) {
                $parser = new IngredientParser();
                return $parser->parseIngredients($this->ingredients);
            }
            return [];
        }
        
        $data = is_array($this->additional_data) 
            ? $this->additional_data 
            : json_decode($this->additional_data, true);
        
        // Если есть structured_ingredients, возвращаем их
        if (isset($data['structured_ingredients'])) {
            return $data['structured_ingredients'];
        }
        
        // Если нет, но есть текст ингредиентов, парсим на лету
        if ($this->ingredients) {
            $parser = new IngredientParser();
            return $parser->parseIngredients($this->ingredients);
        }
        
        return [];
    }

    /**
     * Получить ингредиенты для отображения в правильном формате
     * 
     * @return array
     */
    public function getIngredientsForDisplay()
    {
        try {
            // Если есть связанные ингредиенты в базе данных
            if ($this->relationLoaded('ingredients') && $this->ingredients->count() > 0) {
                return $this->ingredients->map(function($ingredient) {
                    return [
                        'name' => $ingredient->name,
                        'quantity' => $ingredient->quantity,
                        'unit' => $ingredient->unit,
                    ];
                })->toArray();
            }
            
            // Если есть структурированные ингредиенты в additional_data
            if (!empty($this->structured_ingredients)) {
                $result = [];
                foreach ($this->structured_ingredients as $group) {
                    if (isset($group['items'])) {
                        // Обрабатываем группы ингредиентов
                        foreach ($group['items'] as $item) {
                            $result[] = [
                                'name' => $item['name'] ?? '',
                                'quantity' => $item['quantity'] ?? '',
                                'unit' => $item['unit'] ?? '',
                            ];
                        }
                    } else {
                        // Обрабатываем одиночные ингредиенты
                        $result[] = [
                            'name' => $group['name'] ?? '',
                            'quantity' => $group['quantity'] ?? '',
                            'unit' => $group['unit'] ?? '',
                        ];
                    }
                }
                return $result;
            }
            
            // Получаем ингредиенты из аксессора, который уже обрабатывает тип данных
            $ingredients = $this->getIngredientsAttribute();
            if (!empty($ingredients)) {
                // Если это простой массив строк, форматируем его правильно
                if (is_array($ingredients)) {
                    return array_filter($ingredients, function($line) {
                        return !empty(trim($line));
                    });
                }
            }
        } catch (\Exception $e) {
            \Log::error("Error in getIngredientsForDisplay: " . $e->getMessage());
        }
        
        // Если ничего не сработало или произошла ошибка, возвращаем пустой массив
        return [];
    }

    /**
     * Получить ингредиенты в виде массива
     *
     * @return array
     */
    public function getIngredientsAttribute()
    {
        // Проверяем, является ли 'ingredients' уже массивом
        if (isset($this->attributes['ingredients'])) {
            if (is_array($this->attributes['ingredients'])) {
                return $this->attributes['ingredients'];
            }
            
            // Если это строка, разбиваем по переносам строк
            if (is_string($this->attributes['ingredients'])) {
                return explode("\n", $this->attributes['ingredients']);
            }
            
            // Если это JSON, пробуем его декодировать
            try {
                $decoded = json_decode($this->attributes['ingredients'], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
            } catch (\Exception $e) {
                // Игнорируем ошибки декодирования
            }
        }
        
        return []; // Вернуть пустой массив вместо null
    }

    /**
     * Получить инструкции в виде массива
     *
     * @return array
     */
    public function getInstructionsAttribute()
    {
        // Возвращает массив инструкций с текстом и изображениями
        if (isset($this->attributes['instructions'])) {
            if (is_array($this->attributes['instructions'])) {
                return $this->attributes['instructions'];
            }
            
            if (is_string($this->attributes['instructions'])) {
                $decoded = json_decode($this->attributes['instructions'], true);
                if (is_array($decoded)) {
                    return $decoded;
                }
                
                // Если не удалось декодировать как JSON, возвращаем как текст
                return $this->attributes['instructions'];
            }
        }
        
        return []; // Вернуть пустой массив, если не удалось декодировать
    }

    // Преобразует текстовое представление инструкций в массив
    public function getInstructionsArray(): array
    {
        if (empty($this->instructions)) {
            return [];
        }

        // Разбиваем текст инструкций по переводам строки
        $instructions = preg_split('/\r\n|\r|\n/', $this->instructions);
        
        // Удаляем пустые строки и лишние пробелы
        return array_map('trim', array_filter($instructions, function($line) {
            return !empty(trim($line));
        }));
    }

    /**
     * Получить комментарии к рецепту
     */
    public function comments()
    {
        return $this->hasMany(Comment::class)->where('is_approved', true)->orderBy('created_at', 'desc');
    }

    /**
     * Связь с публикациями в социальных сетях
     */
    public function socialPosts()
    {
        return $this->hasMany(SocialPost::class);
    }

    public function steps()
    {
        return $this->hasMany(Step::class)->orderBy('order');
    }

    /**
     * Преобразование сложности в числовое значение
     */
    public function setDifficultyAttribute($value)
    {
        // Преобразование из строки в число, если введена строка
        if (is_string($value) && !is_numeric($value)) {
            switch (strtolower($value)) {
                case 'easy':
                    $value = 1;
                    break;
                case 'medium':
                    $value = 2;
                    break;
                case 'hard':
                    $value = 3;
                    break;
                default:
                    $value = 2; // По умолчанию - средняя сложность
            }
        }

        $this->attributes['difficulty'] = $value;
    }

    /**
     * Получение текстового представления сложности
     */
    public function getDifficultyTextAttribute()
    {
        switch ($this->difficulty) {
            case 1:
                return 'Легко';
            case 2:
                return 'Средне';
            case 3:
                return 'Сложно';
            default:
                return 'Средне';
        }
    }

    /**
     * Возвращает текстовое описание сложности рецепта.
     * 
     * @return string
     */
    public function getDifficultyLabel(): string
    {
        if (!isset($this->difficulty)) {
            return 'Средне';
        }
        
        return match ((int) $this->difficulty) {
            1 => 'Легко',
            2 => 'Средне',
            3 => 'Сложно',
            default => 'Средне',
        };
    }

    /**
     * Получить текущий статус модерации
     */
    public function getModerationStatusAttribute($value)
    {
        return $value ?: self::MODERATION_STATUS_PENDING;
    }

    /**
     * Проверяет, ожидает ли рецепт модерации
     */
    public function isPendingModeration()
    {
        return $this->moderation_status === self::MODERATION_STATUS_PENDING;
    }

    /**
     * Проверяет, одобрен ли рецепт
     */
    public function isApproved()
    {
        return $this->moderation_status === self::MODERATION_STATUS_APPROVED;
    }

    /**
     * Проверяет, отклонен ли рецепт
     */
    public function isRejected()
    {
        return $this->moderation_status === self::MODERATION_STATUS_REJECTED;
    }

    /**
     * Генерирует уникальный слаг для рецепта
     *
     * @param string $title Название рецепта
     * @param int|null $id ID рецепта (при обновлении)
     * @return string Уникальный слаг
     */
    public static function generateUniqueSlug($title, $id = null)
    {
        // Генерируем базовый слаг из названия
        $baseSlug = Str::slug($title, '-');
        
        // Если слаг пустой (например, только спецсимволы), генерируем случайный
        if (empty($baseSlug)) {
            $baseSlug = 'recipe-' . substr(md5(uniqid(mt_rand(), true)), 0, 8);
        }
        
        // Проверяем, существует ли уже такой слаг
        $slug = $baseSlug;
        $count = 1;
        
        // Создаем запрос для проверки уникальности
        $query = static::where('slug', $slug);
        if ($id) {
            $query->where('id', '!=', $id); // Исключаем текущий рецепт при обновлении
        }
        
        // Пока слаг не уникален, добавляем к нему счетчик
        while ($query->exists()) {
            $slug = $baseSlug . '-' . $count++;
            $query = static::where('slug', $slug);
            if ($id) {
                $query->where('id', '!=', $id);
            }
        }
        
        return $slug;
    }

    /**
     * Получить читаемое название статуса
     *
     * @return string
     */
    public function getStatusNameAttribute()
    {
        switch ($this->status) {
            case self::STATUS_DRAFT:
                return 'Черновик';
            case self::STATUS_PENDING:
                return 'На модерации';
            case self::STATUS_APPROVED:
                return 'Одобрен';
            case self::STATUS_REJECTED:
                return 'Отклонен';
            default:
                return $this->status;
        }
    }

    /**
     * Получить класс CSS для статуса
     *
     * @return string
     */
    public function getStatusClassAttribute()
    {
        switch ($this->status) {
            case self::STATUS_DRAFT:
                return 'bg-secondary';
            case self::STATUS_PENDING:
                return 'bg-warning';
            case self::STATUS_APPROVED:
                return 'bg-success';
            case self::STATUS_REJECTED:
                return 'bg-danger';
            default:
                return 'bg-info';
        }
    }

    /**
     * Отношение к оценкам рецепта
     */
    public function ratings()
    {
        return $this->hasMany(Rating::class);
    }

    /**
     * Отношение к ингредиентам
     */
    public function ingredients()
    {
        return $this->hasMany(Ingredient::class);
    }

    /**
     * Получить теги для рецепта.
     */
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'recipe_tag', 'recipe_id', 'tag_id');
    }

    /**
     * Получить массив ингредиентов для микроразметки Schema.org
     *
     * @return array
     */
    public function getIngredientsArray()
    {
        // Проверяем структурированные ингредиенты
        if (!empty($this->structured_ingredients)) {
            $ingredients = [];
            
            foreach ($this->structured_ingredients as $group) {
                if (isset($group['items'])) {
                    // Обрабатываем группы ингредиентов
                    foreach ($group['items'] as $item) {
                        $ingredient = '';
                        if (!empty($item['quantity'])) {
                            $ingredient .= $item['quantity'] . ' ';
                        }
                        if (!empty($item['unit'])) {
                            $ingredient .= $item['unit'] . ' ';
                        }
                        $ingredient .= $item['name'];
                        $ingredients[] = trim($ingredient);
                    }
                } else {
                    // Обрабатываем одиночные ингредиенты
                    $ingredient = '';
                    if (!empty($group['quantity'])) {
                        $ingredient .= $group['quantity'] . ' ';
                    }
                    if (!empty($group['unit'])) {
                        $ingredient .= $group['unit'] . ' ';
                    }
                    $ingredient .= $group['name'];
                    $ingredients[] = trim($ingredient);
                }
            }
            return $ingredients;
        }
        
        // Если у нас есть отношение к ингредиентам
        try {
            if (method_exists($this, 'ingredients') && $this->relationLoaded('ingredients')) {
                return $this->ingredients->map(function($ingredient) {
                    $result = '';
                    if ($ingredient->quantity) {
                        $result .= $ingredient->quantity . ' ';
                    }
                    if ($ingredient->unit) {
                        $result .= $ingredient->unit . ' ';
                    }
                    $result .= $ingredient->name;
                    return trim($result);
                })->toArray();
            }
        } catch (\Exception $e) {
            \Log::error('Error fetching ingredients: ' . $e->getMessage());
        }
        
        // Если у нас есть строковое представление ингредиентов
        if (isset($this->attributes['ingredients']) && is_string($this->attributes['ingredients'])) {
            return explode("\n", $this->attributes['ingredients']);
        }
        
        // Если ингредиенты уже в виде массива
        if (is_array($this->ingredients)) {
            return $this->ingredients;
        }
        
        // Возвращаем пустой массив, если ничего не сработало
        return [];
    }

    /**
     * Получить массив инструкций для микроразметки Schema.org
     *
     * @return array
     */
    public function getSchemaInstructionsArray()
    {
        // Разбиваем инструкции по переносу строки
        $instructions = preg_split('/\r\n|\r|\n/', $this->instructions);
        
        // Очищаем от пустых строк
        return array_values(array_filter($instructions, function($instruction) {
            return !empty(trim($instruction));
        }));
    }

    /**
     * Получить данные Schema.org Recipe для этого рецепта
     *
     * @return array
     */
    public function getSchemaOrgData()
    {
        $schema = [
            "@context" => "https://schema.org",
            "@type" => "Recipe",
            "name" => $this->title,
            "image" => asset($this->image_url),
            "author" => [
                "@type" => "Person",
                "name" => $this->user ? $this->user->name : config('app.name')
            ],
            "datePublished" => $this->created_at->toIso8601String(),
            "dateModified" => $this->updated_at->toIso8601String(),
            "description" => strip_tags(Str::limit($this->description, 300)),
            "url" => route('recipes.show', $this->slug),
            "mainEntityOfPage" => [
                "@type" => "WebPage",
                "@id" => route('recipes.show', $this->slug)
            ],
            "publisher" => [
                "@type" => "Organization",
                "name" => config('app.name'),
                "logo" => [
                    "@type" => "ImageObject",
                    "url" => asset('images/logo.png')
                ]
            ],
        ];
        
        // Добавляем категорию, если есть
        if ($this->categories->isNotEmpty()) {
            $schema["recipeCategory"] = $this->categories->first()->name;
        }
        
        // Добавляем кухню, если указана
        if (isset($this->cuisine) && $this->cuisine) {
            $schema["recipeCuisine"] = $this->cuisine;
        } else {
            $schema["recipeCuisine"] = "Русская кухня";
        }
        
        // Добавляем время приготовления
        if ($this->prep_time) {
            $schema["prepTime"] = "PT" . $this->prep_time . "M";
        }
        if ($this->cooking_time) {
            $schema["cookTime"] = "PT" . $this->cooking_time . "M";
        }
        // Общее время (готовка + подготовка, или +15 минут если только готовка указана)
        if (isset($this->total_time)) {
            $schema["totalTime"] = "PT" . $this->total_time . "M";
        } elseif ($this->cooking_time) {
            $schema["totalTime"] = "PT" . ($this->cooking_time + ($this->prep_time ?? 15)) . "M";
        }
        
        // Количество порций
        if ($this->servings) {
            $schema["recipeYield"] = $this->servings . " " . trans_choice('порция|порции|порций', $this->servings);
        }
        
        // Добавляем информацию о питательной ценности, если она есть
        if ($this->calories || $this->proteins || $this->fats || $this->carbs) {
            $schema["nutrition"] = [
                "@type" => "NutritionInformation"
            ];
            if ($this->calories) {
                $schema["nutrition"]["calories"] = $this->calories . " ккал";
            }
            if ($this->proteins) {
                $schema["nutrition"]["proteinContent"] = $this->proteins . " г";
            }
            if ($this->fats) {
                $schema["nutrition"]["fatContent"] = $this->fats . " г";
            }
            if ($this->carbs) {
                $schema["nutrition"]["carbohydrateContent"] = $this->carbs . " г";
            }
        }
        
        // Добавляем ингредиенты
        $ingredientsArray = $this->getIngredientsArray();
        if (!empty($ingredientsArray)) {
            $schema["recipeIngredient"] = $ingredientsArray;
        }
        
        // Добавляем инструкции
        $instructionsArray = $this->getSchemaInstructionsArray();
        if (!empty($instructionsArray)) {
            $schema["recipeInstructions"] = [];
            foreach ($instructionsArray as $index => $instruction) {
                $schema["recipeInstructions"][] = [
                    "@type" => "HowToStep",
                    "position" => $index + 1,
                    "text" => trim($instruction)
                ];
            }
        }
        
        // Добавляем рейтинг, если есть
        if (isset($this->additional_data['rating']) && $this->additional_data['rating']['count'] > 0) {
            $schema["aggregateRating"] = [
                "@type" => "AggregateRating",
                "ratingValue" => $this->additional_data['rating']['value'],
                "ratingCount" => $this->additional_data['rating']['count'],
                "bestRating" => "5",
                "worstRating" => "1"
            ];
        }
        
        // Ключевые слова для SEO
        $keywordsArray = [];
        $keywordsArray[] = strtolower($this->title);
        $keywordsArray[] = "рецепт " . strtolower($this->title);
        $keywordsArray[] = "как приготовить " . strtolower($this->title);
        if ($this->categories->isNotEmpty()) {
            foreach ($this->categories as $category) {
                $keywordsArray[] = strtolower($category->name);
                $keywordsArray[] = $this->title . " " . strtolower($category->name);
            }
        }
        $schema["keywords"] = implode(", ", array_unique($keywordsArray));
        
        return $schema;
    }

    /**
     * Проверить, оценил ли текущий пользователь рецепт
     *
     * @return bool|int False если не оценил, или значение оценки
     */
    public function getUserRating()
    {
        if (!Auth::check() || !isset($this->additional_data['user_ratings'])) {
            return false;
        }

        $userId = Auth::id();
        if (isset($this->additional_data['user_ratings'][$userId])) {
            return $this->additional_data['user_ratings'][$userId];
        }

        return false;
    }

    /**
     * Получить средний рейтинг рецепта
     *
     * @return float|bool Средний рейтинг или false
     */
    public function getAverageRating()
    {
        if (isset($this->additional_data['rating']) && $this->additional_data['rating']['count'] > 0) {
            return $this->additional_data['rating']['value'];
        }

        return false;
    }

    /**
     * Получить данные о питательной ценности в виде массива
     *
     * @return array
     */
    public function getNutritionAttribute()
    {
        $nutrition = isset($this->attributes['nutrition']) ? $this->attributes['nutrition'] : '';
        $decoded = json_decode($nutrition, true);
        return is_array($decoded) ? $decoded : []; // Вернуть пустой массив, если не удалось декодировать
    }

    /**
     * Получить URL изображения рецепта
     *
     * @return string
     */
    public function getImageUrlAttribute()
    {
        if (empty($this->attributes['image_url'])) {
            return 'images/placeholder.jpg';
        }
        
        $image = $this->attributes['image_url'];
        
        // Если это уже полный URL или начинается с правильных путей
        if (Str::startsWith($image, ['http://', 'https://'])) {
            return $image;
        }
        
        // Удаляем дублирование пути /images/ если оно есть
        if (Str::startsWith($image, 'images/')) {
            return '/' . $image;
        }
        
        if (Str::startsWith($image, '/images/')) {
            return $image;
        }
        
        // Проверяем, содержит ли путь 'recipes/'
        if (Str::contains($image, 'recipes/')) {
            // Извлекаем имя файла и добавляем корректный путь
            $filename = basename($image);
            return '/images/recipes/' . $filename;
        }
        
        // Для других случаев
        return '/images/recipes/' . basename($image);
    }

    /**
     * Получить строку времени приготовления в формате ISO 8601
     *
     * @return string|null
     */
    public function getIsoTotalTimeAttribute()
    {
        if ($this->cooking_time) {
            return 'PT' . $this->cooking_time . 'M';
        }
        return null;
    }

    /**
     * Получить строку времени подготовки в формате ISO 8601
     *
     * @return string|null
     */
    public function getIsoPrepTimeAttribute()
    {
        if ($this->prep_time) {
            return 'PT' . $this->prep_time . 'M';
        }
        return null;
    }

    /**
     * Получить пользователя, создавшего рецепт
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Добавляем метод для получения URL изображения рецепта
    public function getImageUrl()
    {
        if ($this->image_url) {
            return asset($this->image_url);
        }
        // Возвращаем стандартное изображение, если не задано свое
        return asset('images/default.png');
    }
}