<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Category;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;

class SeoService
{
    protected $title;
    protected $description;
    protected $keywords;
    protected $canonical;
    protected $ogImage;
    protected $ogType = 'website';
    protected $schema = [];
    protected $meta = [];
    protected $ogTitle;
    protected $ogDescription;
    protected $index = true;
    protected $follow = true;
    protected $twitterCard = 'summary_large_image';

    /**
     * Задать заголовок страницы
     */
    public function setTitle($title)
    {
        $this->title = $title . ' | ' . config('app.name');
        return $this;
    }

    /**
     * Задать описание страницы
     */
    public function setDescription($description)
    {
        $this->description = $description;
        return $this;
    }

    /**
     * Задать ключевые слова
     */
    public function setKeywords($keywords)
    {
        if (is_array($keywords)) {
            $this->keywords = implode(', ', $keywords);
        } else {
            $this->keywords = $keywords;
        }
        return $this;
    }

    /**
     * Задать канонический URL
     */
    public function setCanonical($url)
    {
        $this->canonical = $url;
        return $this;
    }

    /**
     * Задать OG изображение
     */
    public function setOgImage($image)
    {
        $this->ogImage = $image;
        return $this;
    }

    /**
     * Задать OG тип
     */
    public function setOgType($type)
    {
        $this->ogType = $type;
        return $this;
    }

    /**
     * Добавить структурированные данные Schema.org
     */
    public function setSchema($schema)
    {
        $this->schema = $schema;
        return $this;
    }

    /**
     * Устанавливает директиву robots
     *
     * @param bool $index
     * @param bool $follow
     * @return $this
     */
    public function setRobots($index = true, $follow = true)
    {
        $this->index = $index;
        $this->follow = $follow;
        return $this;
    }

    /**
     * Устанавливает Twitter Card тип
     *
     * @param string $type
     * @return $this
     */
    public function setTwitterCard($type)
    {
        $this->twitterCard = $type;
        return $this;
    }

    /**
     * Получить заголовок
     */
    public function getTitle()
    {
        return $this->title;
    }

    /**
     * Получить описание
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * Получить ключевые слова
     */
    public function getKeywords()
    {
        return $this->keywords;
    }

    /**
     * Получить канонический URL
     */
    public function getCanonical()
    {
        return $this->canonical;
    }

    /**
     * Получить OG изображение
     */
    public function getOgImage()
    {
        return $this->ogImage;
    }

    /**
     * Получить OG тип
     */
    public function getOgType()
    {
        return $this->ogType;
    }

    /**
     * Получить структурированные данные Schema.org
     */
    public function getSchema()
    {
        return $this->schema;
    }

    /**
     * Получает директиву robots
     *
     * @return string
     */
    public function getRobots()
    {
        $robots = [];
        if ($this->index) {
            $robots[] = 'index';
        } else {
            $robots[] = 'noindex';
        }
        
        if ($this->follow) {
            $robots[] = 'follow';
        } else {
            $robots[] = 'nofollow';
        }
        
        return implode(', ', $robots);
    }

    /**
     * Создать Schema.org разметку для рецепта
     */
    public function createRecipeSchema($recipe)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'Recipe',
            'name' => $recipe->title,
            'description' => $recipe->description,
            'datePublished' => $recipe->created_at->toIso8601String(),
            'dateModified' => $recipe->updated_at->toIso8601String(),
            'author' => [
                '@type' => 'Person',
                'name' => $recipe->user->name ?? config('app.name')
            ],
            'image' => $recipe->getImageUrl(),
            'recipeCategory' => $recipe->category->name ?? '',
            'recipeCuisine' => $recipe->cuisine ?? 'Русская кухня',
            'keywords' => $recipe->tags->pluck('name')->implode(', '),
            'recipeYield' => $recipe->servings ?? '4 порции',
            'prepTime' => 'PT' . ($recipe->prep_time ?? 30) . 'M',
            'cookTime' => 'PT' . ($recipe->cook_time ?? 30) . 'M',
            'totalTime' => 'PT' . (($recipe->prep_time ?? 30) + ($recipe->cook_time ?? 30)) . 'M',
            'nutrition' => [
                '@type' => 'NutritionInformation',
                'calories' => $recipe->calories ?? '300 ккал'
            ],
            'recipeIngredient' => $recipe->ingredients->map(function($ingredient) {
                return $ingredient->name . ' - ' . $ingredient->quantity . ' ' . $ingredient->unit;
            })->toArray(),
            'recipeInstructions' => $recipe->steps->map(function($step, $index) {
                return [
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'text' => $step->description
                ];
            })->toArray(),
            'aggregateRating' => [
                '@type' => 'AggregateRating',
                'ratingValue' => $recipe->ratings_avg ?? 5,
                'ratingCount' => $recipe->ratings_count ?? 1
            ]
        ];

        return $schema;
    }

    /**
     * Генерирует SEO-заголовок для рецепта
     */
    public function getRecipeTitle(Recipe $recipe)
    {
        // Используем кэширование для ускорения генерации
        return Cache::remember('recipe_title_'.$recipe->id, 60*24, function() use ($recipe) {
            $title = $recipe->title;
            $category = $recipe->categories->first();
            
            if ($category) {
                $title .= ' | ' . $category->name;
            }
            
            if ($recipe->cooking_time) {
                $title .= ' | ' . $recipe->cooking_time . ' мин';
            }
            
            $title .= ' | ' . config('app.name');
            
            // Ограничиваем длину заголовка для поисковых систем
            return Str::limit($title, 65, '');
        });
    }
    
    /**
     * Генерирует SEO-описание для рецепта
     */
    public function getRecipeDescription(Recipe $recipe)
    {
        return Cache::remember('recipe_description_'.$recipe->id, 60*24, function() use ($recipe) {
            $description = $recipe->description;
            
            // Если описания нет или оно короткое, создаем описание на основе ингредиентов и инструкций
            if (empty($description) || strlen($description) < 50) {
                $ingredients = explode("\n", $recipe->ingredients);
                $ingredientsSample = array_slice($ingredients, 0, 3);
                $ingredientsCount = count($ingredients);
                
                $description = "Рецепт {$recipe->title} с подробной пошаговой инструкцией. ";
                
                if ($recipe->cooking_time) {
                    $description .= "Время приготовления: {$recipe->cooking_time} мин. ";
                }
                
                $description .= "Используется $ingredientsCount " . 
                               $this->pluralize($ingredientsCount, 'ингредиент', 'ингредиента', 'ингредиентов');
                
                if (!empty($ingredientsSample)) {
                    $description .= ", включая " . implode(', ', $ingredientsSample) . ". ";
                } else {
                    $description .= ". ";
                }
                
                if ($recipe->calories) {
                    $description .= "Калорийность: {$recipe->calories} ккал.";
                }
                
                // Добавляем информацию о сложности приготовления
                if ($recipe->cooking_time) {
                    if ($recipe->cooking_time <= 30) {
                        $description .= " Быстрый и легкий рецепт.";
                    } elseif ($recipe->cooking_time > 120) {
                        $description .= " Для особых случаев.";
                    }
                }
            }
            
            return Str::limit($description, 160, '');
        });
    }
    
    /**
     * Генерирует SEO-заголовок для категории
     */
    public function getCategoryTitle(Category $category)
    {
        return Cache::remember('category_title_'.$category->id, 60*24, function() use ($category) {
            $recipesCount = $category->recipes()->where('is_published', true)->count();
            $title = $category->name . ' - ' . $recipesCount . ' ' . 
                    $this->pluralize($recipesCount, 'рецепт', 'рецепта', 'рецептов') . 
                    ' | ' . config('app.name');
            
            return Str::limit($title, 65, '');
        });
    }
    
    /**
     * Генерирует SEO-описание для категории
     */
    public function getCategoryDescription(Category $category)
    {
        return Cache::remember('category_description_'.$category->id, 60*24, function() use ($category) {
            $description = $category->description;
            
            if (empty($description)) {
                $recipesCount = $category->recipes()->where('is_published', true)->count();
                $description = "Лучшие рецепты в категории {$category->name}. У нас вы найдете $recipesCount ";
                $description .= $this->pluralize($recipesCount, 'проверенный рецепт', 'проверенных рецепта', 'проверенных рецептов');
                $description .= " с подробными инструкциями и фото. Простое приготовление и отличный результат гарантированы!";
            }
            
            return Str::limit($description, 160, '');
        });
    }
    
    /**
     * Генерирует SEO-заголовок для поиска
     */
    public function getSearchTitle($query, $count = null)
    {
        $title = 'Поиск рецептов';
        
        if (!empty($query)) {
            $title = 'Рецепты "' . $query . '"';
            
            if ($count !== null) {
                $title .= ' - ' . $count . ' ' . $this->pluralize($count, 'рецепт', 'рецепта', 'рецептов');
            }
        }
        
        $title .= ' | ' . config('app.name');
        
        return Str::limit($title, 65, '');
    }
    
    /**
     * Генерирует SEO-описание для поиска
     */
    public function getSearchDescription($query, $count = null, $filters = [])
    {
        $description = 'Поиск кулинарных рецептов';
        
        if (!empty($query)) {
            $description = 'Результаты поиска по запросу "' . $query . '". ';
            
            if ($count !== null) {
                $description .= 'Найдено ' . $count . ' ' . 
                               $this->pluralize($count, 'рецепт', 'рецепта', 'рецептов') . ' ';
            }
            
            $description .= 'с подробными инструкциями и фото.';
            
            // Добавляем информацию о фильтрах
            if (!empty($filters)) {
                $filterDesc = [];
                
                if (!empty($filters['category'])) {
                    $filterDesc[] = 'категория: ' . $filters['category'];
                }
                
                if (!empty($filters['cooking_time'])) {
                    if ($filters['cooking_time'] <= 30) {
                        $filterDesc[] = 'быстрые рецепты до 30 минут';
                    } elseif ($filters['cooking_time'] <= 60) {
                        $filterDesc[] = 'рецепты до 1 часа';
                    } else {
                        $filterDesc[] = 'рецепты от ' . $filters['cooking_time'] . ' минут';
                    }
                }
                
                if (!empty($filterDesc)) {
                    $description .= ' Фильтры: ' . implode(', ', $filterDesc) . '.';
                }
            }
        } else {
            $description = 'Каталог кулинарных рецептов. Найдите идеальное блюдо с помощью нашего удобного поиска. Фильтры по категориям, времени приготовления и ингредиентам.';
        }
        
        return Str::limit($description, 160, '');
    }
    
    /**
     * Генерирует микроразметку Schema.org для рецепта
     */
    public function getRecipeSchema(Recipe $recipe)
    {
        return Cache::remember('recipe_schema_'.$recipe->id, 60*24, function() use ($recipe) {
            $schema = [
                '@context' => 'https://schema.org',
                '@type' => 'Recipe',
                'name' => $recipe->title,
                'author' => [
                    '@type' => 'Person',
                    'name' => $recipe->user ? $recipe->user->name : config('app.name')
                ],
                'datePublished' => $recipe->created_at->toIso8601String(),
                'dateModified' => $recipe->updated_at->toIso8601String(),
                'description' => $this->getRecipeDescription($recipe),
                'prepTime' => $recipe->cooking_time ? 'PT' . floor($recipe->cooking_time / 3) . 'M' : 'PT10M',
                'cookTime' => $recipe->cooking_time ? 'PT' . floor($recipe->cooking_time * 2/3) . 'M' : 'PT20M',
                'totalTime' => $recipe->cooking_time ? 'PT' . $recipe->cooking_time . 'M' : 'PT30M',
                'keywords' => $this->generateKeywords($recipe),
                'recipeCategory' => $recipe->categories->first() ? $recipe->categories->first()->name : 'Основные блюда',
                'recipeCuisine' => $this->detectCuisine($recipe),
                'recipeYield' => $recipe->servings ?: '4 порции',
                'url' => route('recipes.show', $recipe->slug),
                'mainEntityOfPage' => route('recipes.show', $recipe->slug),
            ];
            
            // Добавляем изображение
            if ($recipe->image_url) {
                $imageUrl = $recipe->getImageUrl();
                $schema['image'] = [
                    '@type' => 'ImageObject',
                    'url' => $imageUrl,
                    'width' => '800',
                    'height' => '600',
                    'caption' => $recipe->title
                ];
            }
            
            // Добавляем ингредиенты
            if (isset($recipe->ingredients) && $recipe->ingredients) {
                // Проверяем тип данных перед использованием explode
                if (is_string($recipe->ingredients)) {
                    $ingredients = explode("\n", $recipe->ingredients);
                } else {
                    // Если это уже массив, используем его как есть
                    $ingredients = $recipe->ingredients;
                }
                $schema['recipeIngredient'] = array_map(function($item) {
                    if (is_string($item)) {
                        return trim($item);
                    } elseif (is_object($item) && method_exists($item, '__toString')) {
                        return trim((string)$item);
                    } elseif (is_scalar($item)) {
                        return trim((string)$item);
                    }
                    return '';
                }, $ingredients);
                
                // Удаляем пустые строки
                $schema['recipeIngredient'] = array_filter($schema['recipeIngredient'], function($item) {
                    return !empty($item);
                });
                
                // Если массив стал пустым, удаляем его из схемы
                if (empty($schema['recipeIngredient'])) {
                    unset($schema['recipeIngredient']);
                }
            }
            
            // Добавляем инструкции
            if (isset($recipe->instructions) && $recipe->instructions) {
                // Проверяем тип данных перед использованием explode
                if (is_string($recipe->instructions)) {
                    $instructions = explode("\n", $recipe->instructions);
                } else {
                    // Если это уже массив, используем его как есть
                    $instructions = $recipe->instructions;
                }
                $schema['recipeInstructions'] = [];
                
                foreach ($instructions as $index => $step) {
                    $step = trim($step);
                    if (empty($step)) continue;
                    
                    $schema['recipeInstructions'][] = [
                        '@type' => 'HowToStep',
                        'text' => $step,
                        'position' => $index + 1,
                        'url' => route('recipes.show', $recipe->slug) . '#step-' . ($index + 1)
                    ];
                }
            }
            
            // Добавляем пищевую ценность
            if ($recipe->calories || $recipe->proteins || $recipe->fats || $recipe->carbs) {
                $schema['nutrition'] = [
                    '@type' => 'NutritionInformation'
                ];
                
                if ($recipe->calories) {
                    $schema['nutrition']['calories'] = $recipe->calories . ' ккал';
                }
                
                if ($recipe->proteins) {
                    $schema['nutrition']['proteinContent'] = $recipe->proteins . ' г';
                }
                
                if ($recipe->fats) {
                    $schema['nutrition']['fatContent'] = $recipe->fats . ' г';
                }
                
                if ($recipe->carbs) {
                    $schema['nutrition']['carbohydrateContent'] = $recipe->carbs . ' г';
                }
            }
            
            // Добавляем рейтинг 
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => '4.8',
                'ratingCount' => max(5, $recipe->views / 10), // Эмуляция рейтинга на основе просмотров
                'bestRating' => '5',
                'worstRating' => '1'
            ];
            
            // Добавляем хлебные крошки в schema
            $schema['breadcrumb'] = [
                '@type' => 'BreadcrumbList',
                'itemListElement' => [
                    [
                        '@type' => 'ListItem',
                        'position' => 1,
                        'name' => 'Главная',
                        'item' => url('/')
                    ],
                    [
                        '@type' => 'ListItem',
                        'position' => 2,
                        'name' => 'Рецепты',
                        'item' => route('recipes.index')
                    ]
                ]
            ];
            
            // Добавляем категорию в хлебные крошки, если она есть
            if ($recipe->categories->first()) {
                $schema['breadcrumb']['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $recipe->categories->first()->name,
                    'item' => route('categories.show', $recipe->categories->first()->slug)
                ];
                
                $schema['breadcrumb']['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => 4,
                    'name' => $recipe->title,
                    'item' => route('recipes.show', $recipe->slug)
                ];
            } else {
                $schema['breadcrumb']['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => $recipe->title,
                    'item' => route('recipes.show', $recipe->slug)
                ];
            }
            
            // Добавляем информацию о видео, если оно есть
            if (!empty($recipe->video_url)) {
                $schema['video'] = [
                    '@type' => 'VideoObject',
                    'name' => 'Приготовление ' . $recipe->title,
                    'description' => 'Видео-инструкция по приготовлению ' . $recipe->title,
                    'thumbnailUrl' => $recipe->getImageUrl(),
                    'contentUrl' => $recipe->video_url,
                    'embedUrl' => $recipe->video_url,
                    'uploadDate' => $recipe->created_at->toIso8601String(),
                    'duration' => 'PT5M', // примерная длительность
                    'interactionStatistic' => [
                        '@type' => 'InteractionCounter',
                        'interactionType' => 'https://schema.org/WatchAction',
                        'userInteractionCount' => max(10, $recipe->views)
                    ]
                ];
            }
            
            return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        });
    }
    
    /**
     * Генерирует структурированные данные для поисковой страницы
     */
    public function getSearchSchema($query, $recipes, $totalCount)
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'SearchResultsPage',
            'mainEntity' => [
                '@type' => 'ItemList',
                'itemListElement' => [],
                'numberOfItems' => $totalCount
            ],
            'url' => url()->current() . '?' . http_build_query(request()->query()),
            'name' => 'Результаты поиска по запросу "' . $query . '"',
            'description' => $this->getSearchDescription($query, $totalCount)
        ];
        
        // Добавляем рецепты в список
        $position = 1;
        foreach ($recipes as $recipe) {
            $schema['mainEntity']['itemListElement'][] = [
                '@type' => 'ListItem',
                'position' => $position++,
                'item' => [
                    '@type' => 'Recipe',
                    'name' => $recipe->title,
                    'url' => route('recipes.show', $recipe->slug),
                    'image' => $recipe->getImageUrl(),
                    'description' => Str::limit($recipe->description, 100),
                    'author' => [
                        '@type' => 'Person',
                        'name' => $recipe->user ? $recipe->user->name : config('app.name')
                    ],
                    'datePublished' => $recipe->created_at->toIso8601String(),
                    'cookTime' => $recipe->cooking_time ? 'PT' . $recipe->cooking_time . 'M' : null,
                    'recipeCategory' => $recipe->categories->first() ? $recipe->categories->first()->name : null
                ]
            ];
        }
        
        // Добавляем хлебные крошки
        $schema['breadcrumb'] = [
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Главная',
                    'item' => url('/')
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Поиск',
                    'item' => route('search')
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 3,
                    'name' => 'Поиск: ' . $query,
                    'item' => url()->current() . '?' . http_build_query(request()->query())
                ]
            ]
        ];
        
        return json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
    
    /**
     * Генерирует SEO-заголовок для страницы пользователя
     */
    public function getUserTitle($user)
    {
        $recipesCount = $user->recipes()->where('is_published', true)->count();
        $title = "Рецепты пользователя {$user->name} ({$recipesCount}) | " . config('app.name');
        return Str::limit($title, 65, '');
    }
    
    /**
     * Генерирует SEO-описание для страницы пользователя
     */
    public function getUserDescription($user)
    {
        $recipesCount = $user->recipes()->where('is_published', true)->count();
        $description = "Все рецепты от {$user->name}. ";
        $description .= "Просмотрите {$recipesCount} " . 
                       $this->pluralize($recipesCount, 'рецепт', 'рецепта', 'рецептов') . 
                       " с пошаговыми инструкциями и фото.";
                       
        return Str::limit($description, 160, '');
    }
    
    /**
     * Генерирует ключевые слова для рецепта
     */
    private function generateKeywords(Recipe $recipe)
    {
        $keywords = [];
        
        // Добавляем название рецепта и его части
        $keywords[] = $recipe->title;
        
        // Разбиваем название на слова длиной более 3 символов
        $titleWords = preg_split('/\s+/', $recipe->title, -1, PREG_SPLIT_NO_EMPTY);
        foreach ($titleWords as $word) {
            if (mb_strlen($word) >= 3) {
                $keywords[] = mb_strtolower($word);
            }
        }
        
        // Добавляем категории
        foreach ($recipe->categories as $category) {
            $keywords[] = $category->name;
        }
        
        // Добавляем основные ингредиенты (первые 5)
        $ingredients = explode("\n", $recipe->ingredients);
        $mainIngredients = array_slice($ingredients, 0, 5);
        foreach ($mainIngredients as $ingredient) {
            // Извлекаем только название ингредиента (без количества)
            preg_match('/(?:\d+\с*\w+\с*)?(.+)/i', $ingredient, $matches);
            if (isset($matches[1])) {
                $keywords[] = trim($matches[1]);
            }
        }
        
        // Добавляем общие ключевые слова
        $keywords[] = 'рецепт';
        $keywords[] = 'приготовление';
        $keywords[] = 'как приготовить';
        $keywords[] = 'пошаговый рецепт';
        $keywords[] = 'с фото';
        
        // Если есть время приготовления, добавляем соответствующее ключевое слово
        if ($recipe->cooking_time) {
            if ($recipe->cooking_time <= 30) {
                $keywords[] = 'быстрый рецепт';
                $keywords[] = 'рецепт быстрого приготовления';
            } elseif ($recipe->cooking_time >= 120) {
                $keywords[] = 'сложный рецепт';
            }
            $keywords[] = 'время приготовления ' . $recipe->cooking_time . ' минут';
        }
        
        // Если есть калории, добавляем ключевые слова о калорийности
        if ($recipe->calories) {
            if ($recipe->calories < 300) {
                $keywords[] = 'низкокалорийный рецепт';
                $keywords[] = 'диетический рецепт';
            }
            $keywords[] = 'калорийность ' . $recipe->calories . ' ккал';
        }
        
        // Добавляем сезонные ключевые слова
        $season = $this->getCurrentSeason();
        if ($season) {
            $keywords[] = $season . ' рецепт';
        }
        
        // Убираем дубликаты и объединяем, преобразуя все ключевые слова в нижний регистр
        $keywords = array_map('mb_strtolower', $keywords);
        return implode(', ', array_unique($keywords));
    }
    
    /**
     * Определяет кухню на основе названия и ингредиентов рецепта
     */
    private function detectCuisine(Recipe $recipe)
    {
        $title = mb_strtolower($recipe->title);
        $ingredients = mb_strtolower($recipe->ingredients);
        $description = mb_strtolower($recipe->description);
        $allText = $title . ' ' . $ingredients . ' ' . $description;
        
        $cuisineKeywords = [
            'Русская кухня' => ['борщ', 'щи', 'блины', 'пельмени', 'окрошка', 'квас', 'гречка', 'кисель', 'оливье', 'винегрет', 'сметана', 'творог', 'селедка'],
            'Итальянская кухня' => ['паста', 'пицца', 'лазанья', 'ризотто', 'карбонара', 'тирамису', 'панна котта', 'песто', 'моцарелла', 'пармезан', 'прошутто'],
            'Французская кухня' => ['багет', 'круассан', 'киш', 'фуа-гра', 'рататуй', 'крем-брюле', 'эклер', 'суфле', 'бешамель', 'лук-порей', 'дижонская'],
            'Японская кухня' => ['суши', 'роллы', 'васаби', 'темпура', 'мисо', 'саке', 'рамен', 'тофу', 'эдамаме', 'матча', 'дайкон', 'соба', 'удон'],
            'Китайская кухня' => ['вок', 'димсам', 'рисовая лапша', 'соевый соус', 'дим сам', 'тофу', 'пекинская утка', 'лапша', 'кунжут', 'имбирь', 'устричный соус'],
            'Мексиканская кухня' => ['тако', 'буррито', 'гуакамоле', 'начос', 'кесадилья', 'тортилья', 'сальса', 'текила', 'фахитас', 'перец чили', 'кукуруза'],
            'Индийская кухня' => ['карри', 'масала', 'нан', 'чапати', 'самоса', 'панир', 'чатни', 'гарам масала', 'куркума', 'кориандр', 'кардамон', 'корица'],
            'Грузинская кухня' => ['хачапури', 'сациви', 'хинкали', 'чахохбили', 'аджика', 'ткемали', 'чурчхела', 'шашлык', 'лобио', 'сулугуни'],
            'Средиземноморская кухня' => ['оливковое масло', 'тапас', 'хумус', 'фета', 'тахини', 'баклажан', 'табуле', 'питта', 'фалафель', 'долма']
        ];
        
        $matchedCuisines = [];
        
        foreach ($cuisineKeywords as $cuisine => $keywords) {
            $matchCount = 0;
            foreach ($keywords as $keyword) {
                if (Str::contains($allText, $keyword)) {
                    $matchCount++;
                }
            }
            
            if ($matchCount > 0) {
                $matchedCuisines[$cuisine] = $matchCount;
            }
        }
        
        if (empty($matchedCuisines)) {
            return 'Интернациональная кухня';
        }
        
        // Возвращаем кухню с наибольшим количеством совпадений
        arsort($matchedCuisines);
        return array_key_first($matchedCuisines);
    }
    
    /**
     * Получает текущий сезон
     */
    private function getCurrentSeason()
    {
        $month = date('n');
        
        if ($month >= 3 && $month <= 5) {
            return 'весенний';
        } elseif ($month >= 6 && $month <= 8) {
            return 'летний';
        } elseif ($month >= 9 && $month <= 11) {
            return 'осенний';
        } else {
            return 'зимний';
        }
    }
    
    /**
     * Вспомогательная функция для склонения существительных
     */
    private function pluralize($count, $one, $two, $many)
    {
        if ($count % 10 == 1 && $count % 100 != 11) {
            return $one;
        } elseif ($count % 10 >= 2 && $count % 10 <= 4 && ($count % 100 < 10 || $count % 100 >= 20)) {
            return $two;
        } else {
            return $many;
        }
    }
    
    /**
     * Генерирует описание рецепта для соцсетей
     */
    public function getSocialDescription(Recipe $recipe)
    {
        $description = $recipe->description;
        
        if (empty($description) || strlen($description) < 50) {
            $description = "Приготовьте вкусный {$recipe->title}. ";
            
            if ($recipe->cooking_time) {
                $description .= "Время приготовления всего {$recipe->cooking_time} минут. ";
            }
            
            if ($recipe->calories) {
                $description .= "Калорийность: {$recipe->calories} ккал. ";
            }
            
            $description .= "Легкий рецепт с фото и пошаговыми инструкциями на " . config('app.name') . ".";
        }
        
        return Str::limit($description, 200, '');
    }
    
    /**
     * Генерирует канонический URL для страницы с учетом пагинации
     */
    public function getCanonicalUrl($baseUrl, $page = null)
    {
        if ($page && $page > 1) {
            return $baseUrl . '?page=' . $page;
        }
        
        return $baseUrl;
    }
    
    /**
     * Генерирует rel=prev/next для пагинации
     */
    public function getPaginationLinks($paginator, $baseUrl)
    {
        $links = [];
        
        if ($paginator->currentPage() > 1) {
            $prevPage = $paginator->currentPage() - 1;
            $links['prev'] = $prevPage > 1 ? $baseUrl . '?page=' . $prevPage : $baseUrl;
        }
        
        if ($paginator->hasMorePages()) {
            $links['next'] = $baseUrl . '?page=' . ($paginator->currentPage() + 1);
        }
        
        return $links;
    }

    /**
     * Генерирует метатеги для SEO
     *
     * @param string $title Заголовок страницы
     * @param string $description Описание страницы
     * @param string|null $keywords Ключевые слова
     * @param string|null|array $url URL страницы или массив данных
     * @param string|null $image URL изображения для Open Graph
     * @param string $type Тип страницы для Open Graph
     * @param array $additional Дополнительные метатеги
     * @return array
     */
    public function generateMetaTags(
        string $title,
        string $description,
        ?string $keywords = null,
        $url = null,
        ?string $image = null,
        string $type = 'website',
        array $additional = []
    ): array {
        // Базовый набор ключевых слов всегда с "Яедок, я едок"
        $baseKeywords = 'Яедок, я едок, рецепты, кулинария, готовим дома, вкусные блюда, простые рецепты';
        
        if ($keywords) {
            $keywords = $baseKeywords . ', ' . $keywords;
        } else {
            $keywords = $baseKeywords;
        }
        
        // Формируем URL если не указан или передан массив
        if (is_array($url)) {
            $url = url()->current();
        } elseif (!$url) {
            $url = url()->current();
        }
        
        // Базовые метатеги
        $metaTags = [
            'title' => $title,
            'meta_description' => $description,
            'meta_keywords' => $keywords,
            'canonical' => $url,
            
            // Open Graph метатеги
            'og_title' => $title,
            'og_description' => $description,
            'og_url' => $url,
            'og_type' => $type,
            'og_site_name' => config('app.name'),
            
            // Twitter метатеги
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $title,
            'twitter_description' => $description,
        ];
        
        // Добавляем изображение если указано
        if ($image) {
            $metaTags['og_image'] = $image;
            $metaTags['twitter_image'] = $image;
        }
        
        // Добавляем дополнительные метатеги
        return array_merge($metaTags, $additional);
    }
    
    /**
     * Генерирует метатеги для главной страницы
     *
     * @return array
     */
    public function getHomePageMeta()
    {
        return Cache::remember('home_page_meta', 60*24, function() {
            // Формируем базовые SEO-данные
            $title = 'Яедок - лучшие кулинарные рецепты с пошаговыми инструкциями';
            $description = 'Кулинарные рецепты с фото и пошаговыми инструкциями. Простые и вкусные рецепты для всей семьи на каждый день и для праздничного стола.';
            
            // Ключевые слова с обязательным включением основных ключевых фраз сайта
            $keywords = 'Яедок, я едок, рецепты, кулинария, готовка, блюда, еда, домашние рецепты, простые рецепты, вкусные рецепты, рецепты с фото, пошаговые рецепты';
            
            // Включаем сезонные ключевые слова
            $season = $this->getCurrentSeason();
            $keywords .= ', ' . $season . 'ние рецепты, сезонные блюда, ' . $this->getSeasonalKeywords($season);
            
            return [
                'title' => $title,
                'meta_description' => $description,
                'meta_keywords' => $keywords,
                'canonical' => url('/'),
                'og_type' => 'website',
                'og_title' => 'Яедок - лучшие кулинарные рецепты на каждый день',
                'og_description' => 'Яедок - кулинарный портал с лучшими рецептами. Простые и вкусные блюда с пошаговыми инструкциями, фото и отзывами.',
                'og_image' => asset('images/og-home.jpg'),
                'og_site_name' => config('app.name'),
                'og_url' => url('/'),
                'twitter_card' => $this->twitterCard,
                'twitter_title' => $title,
                'twitter_description' => $description,
                'twitter_image' => asset('images/og-home.jpg'),
                'robots' => 'index, follow',
                'author' => config('app.name'),
                'alternate_languages' => [
                    'ru' => url('/')
                ]
            ];
        });
    }
    
    /**
     * Получает сезонные ключевые слова на основе текущего сезона
     *
     * @param string $season Текущий сезон
     * @return string
     */
    private function getSeasonalKeywords($season)
    {
        $seasonalKeywords = [
            'весенний' => 'легкие блюда, свежие овощи, зеленые салаты, весенние супы, тарты',
            'летний' => 'окрошка, холодные супы, шашлык, гриль, барбекю, ягодные десерты, мороженое',
            'осенний' => 'тыква, грибы, яблочная выпечка, запеканки, рагу, горячие супы, блюда из кабачков',
            'зимний' => 'горячие блюда, рождественские рецепты, новогодний стол, глинтвейн, запеченное мясо, домашняя выпечка'
        ];
        
        return $seasonalKeywords[$season] ?? 'сезонные рецепты, домашняя еда';
    }
    
    /**
     * Генерирует метатеги для страницы списка рецептов
     *
     * @return array
     */
    public function getRecipesPageMeta(): array
    {
        return $this->generateMetaTags(
            'Все рецепты - Яедок',
            'Полная коллекция рецептов на Яедок. Найдите идеальный рецепт для любого случая - завтраки, обеды, ужины, десерты с пошаговыми инструкциями.',
            'коллекция рецептов, завтраки, обеды, ужины, десерты, салаты, супы, закуски, выпечка'
        );
    }
 
    /**
     * Получает метатеги для страницы списка категорий
     *
     * @return array
     */
    public function getCategoriesPageMeta(): array
    {
        $title = 'Категории рецептов - ' . config('app.name');
        $description = 'Полный каталог кулинарных категорий. Найдите рецепты по любой категории блюд.';

        return [
            'title' => $title,
            'meta_description' => $description,
            'meta_keywords' => 'категории рецептов, кулинарные категории, рецепты по категориям',
            'canonical' => route('categories.index'),
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => asset('images/categories-cover.jpg'),
            'og_type' => 'website',
            'og_site_name' => config('app.name'),
            'twitter_card' => $this->twitterCard,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => asset('images/categories-cover.jpg'),
            'robots' => $this->getRobots(),
        ];
    }
 
    /**
     * Генерирует метатеги для страницы категории
     *
     * @param object $category Объект категории
     * @return array
     */
    public function getCategoryPageMeta($category): array
    {
        $title = $category->name . ' - рецепты блюд | Яедок';
        $description = 'Лучшие рецепты ' . mb_strtolower($category->name) . ' на кулинарном портале Яедок. Простые пошаговые инструкции, фото и советы по приготовлению.';
        $keywords = mb_strtolower($category->name) . ', рецепты ' . mb_strtolower($category->name) . ', как приготовить ' . mb_strtolower($category->name);
        
        // Получаем URL изображения, если оно есть
        $image = null;
        if (isset($category->image) && $category->image) {
            $image = asset('storage/' . $category->image);
        }
        
        return $this->generateMetaTags(
            $title,
            $description,
            $keywords,
            route('categories.show', $category->slug),
            $image
        );
    }
    
    /**
     * Получает популярные ключевые слова для кулинарных рецептов
     * 
     * @param int $count Количество слов
     * @return array
     */
    public function getPopularKeywords(int $count = 5): array
    {
        $allKeywords = [
            // Общие кулинарные ключевые слова
            'Яедок', 'я едок', 'рецепты блюд', 'пошаговые рецепты', 'рецепты с фото',
            'домашние рецепты', 'простые рецепты', 'вкусные рецепты', 'рецепты на каждый день',
            'быстрые рецепты', 'кулинария', 'приготовление пищи',
            
            // По типам блюд
            'рецепты салатов', 'рецепты супов', 'рецепты первых блюд', 'рецепты вторых блюд',
            'рецепты закусок', 'рецепты выпечки', 'рецепты десертов', 'рецепты соусов',
            'рецепты напитков',
            
            // По кухням мира
            'русская кухня', 'итальянская кухня', 'французская кухня', 'грузинская кухня',
            'азиатская кухня', 'средиземноморская кухня',
            
            // По случаю
            'рецепты на праздник', 'рецепты на новый год', 'рецепты на день рождения',
            'рецепты для пикника', 'постные рецепты',
            
            // По ингредиентам
            'рецепты из курицы', 'рецепты из мяса', 'рецепты из рыбы', 'вегетарианские рецепты',
            'рецепты из овощей', 'рецепты из фруктов', 'рецепты из грибов', 'рецепты из творога',
            
            // По технике приготовления
            'рецепты в духовке', 'рецепты в мультиварке', 'рецепты на сковороде',
            'рецепты в микроволновке', 'рецепты на гриле', 'рецепты в пароварке',
            
            // По времени
            'рецепты за 15 минут', 'рецепты за 30 минут', 'быстрый завтрак',
            'быстрый ужин', 'быстрый обед',
            
            // Дополнительные ключевые слова
            'полезные рецепты', 'диетические рецепты', 'низкокалорийные рецепты',
            'высокобелковые рецепты', 'здоровое питание', 'рецепты для детей'
        ];
        
        // Перемешиваем массив и берем первые $count элементов
        shuffle($allKeywords);
        
        // Выбираем определенные ключевые слова
        $selectedKeywords = array_slice($allKeywords, 0, $count);
        
        // Всегда включаем "Яедок, я едок" в начало ключевых слов
        if (!in_array('Яедок', $selectedKeywords) && !in_array('я едок', $selectedKeywords)) {
            array_unshift($selectedKeywords, 'Яедок', 'я едок');
            // Сокращаем до указанного количества
            $selectedKeywords = array_slice($selectedKeywords, 0, $count);
        }
        
        return $selectedKeywords;
    }
    
    /**
     * Генерирует structured data (Schema.org) разметку для рецепта
     *
     * @param object $recipe Объект рецепта
     * @return string JSON-LD разметка
     */
    public function generateRecipeSchema($recipe): string
    {
        // Базовая структура Schema.org для рецепта
        $schema = [
            '@context' => 'https://schema.org/',
            '@type' => 'Recipe',
            'name' => $recipe->title,
            'description' => strip_tags($recipe->description),
            'keywords' => 'Яедок, я едок, ' . strtolower($recipe->title) . ', рецепт с фото',
            'author' => [
                '@type' => 'Person',
                'name' => isset($recipe->user) ? $recipe->user->name : config('app.name')
            ],
            'datePublished' => $recipe->created_at->toIso8601String(),
            'dateModified' => $recipe->updated_at->toIso8601String(),
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png')
                ]
            ]
        ];
        
        // Добавляем изображение
        if (isset($recipe->image) && $recipe->image) {
            $schema['image'] = [
                '@type' => 'ImageObject',
                'url' => asset('storage/' . $recipe->image),
                'width' => 800,
                'height' => 600
            ];
        }
        
        // Добавляем время приготовления, если есть
        if (isset($recipe->cooking_time) && $recipe->cooking_time) {
            $cookTime = intval($recipe->cooking_time);
            $schema['cookTime'] = "PT{$cookTime}M";
            $schema['totalTime'] = "PT{$cookTime}M";
        }
        
        // Добавляем категорию, если есть - ИСПРАВЛЯЕМ ЭТУ ЧАСТЬ
        if (isset($recipe->categories) && $recipe->categories->isNotEmpty()) {
            $schema['recipeCategory'] = $recipe->categories->first()->name;
        }
        
        // Добавляем ингредиенты, если есть
        if (isset($recipe->ingredients) && $recipe->ingredients) {
            // Проверяем тип данных перед использованием explode
            if (is_string($recipe->ingredients)) {
                $ingredients = explode("\n", $recipe->ingredients);
            } else {
                // Если это уже массив, используем его как есть
                $ingredients = $recipe->ingredients;
            }
            
            // Безопасно обрабатываем каждый элемент массива
            $schema['recipeIngredient'] = array_map(function($item) {
                if (is_string($item)) {
                    return trim($item);
                } elseif (is_object($item) && method_exists($item, '__toString')) {
                    return trim((string)$item);
                } elseif (is_scalar($item)) {
                    return trim((string)$item);
                }
                return '';
            }, $ingredients);
            
            // Удаляем пустые строки
            $schema['recipeIngredient'] = array_filter($schema['recipeIngredient'], function($item) {
                return !empty($item);
            });
            
            // Если массив стал пустым, удаляем его из схемы
            if (empty($schema['recipeIngredient'])) {
                unset($schema['recipeIngredient']);
            }
        }
        
        // Добавляем инструкции, если есть
        if (isset($recipe->instructions) && $recipe->instructions) {
            // Проверяем тип данных перед использованием explode
            if (is_string($recipe->instructions)) {
                $instructions = explode("\n", $recipe->instructions);
            } else {
                // Если это уже массив, используем его как есть
                $instructions = $recipe->instructions;
            }
            $schema['recipeInstructions'] = [];
            
            foreach ($instructions as $index => $step) {
                $step = trim($step);
                if (empty($step)) continue;
                
                $schema['recipeInstructions'][] = [
                    '@type' => 'HowToStep',
                    'position' => $index + 1,
                    'text' => $step
                ];
            }
        }
        
        // Добавляем рейтинг, если есть
        if (isset($recipe->rating) && $recipe->rating > 0 && isset($recipe->ratings_count) && $recipe->ratings_count > 0) {
            $schema['aggregateRating'] = [
                '@type' => 'AggregateRating',
                'ratingValue' => round($recipe->rating, 1),
                'ratingCount' => $recipe->ratings_count,
                'bestRating' => '5',
                'worstRating' => '1'
            ];
        }
        
        // Формируем JSON-LD
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
    
    /**
     * Генерирует schema.org разметку для категории
     *
     * @param object $category Объект категории
     * @param array $recipes Массив рецептов в категории
     * @return string JSON-LD разметка
     */
    public function generateCategorySchema($category, $recipes): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $category->name . ' - рецепты блюд | Яедок',
            'description' => 'Лучшие рецепты ' . mb_strtolower($category->name) . ' на кулинарном портале Яедок.',
            'url' => route('categories.show', $category->slug),
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png')
                ]
            ]
        ];
        
        // Добавляем список рецептов, если есть
        if (!empty($recipes)) {
            $schema['mainEntity'] = [
                '@type' => 'ItemList',
                'itemListElement' => []
            ];
            
            foreach ($recipes as $index => $recipe) {
                $schema['mainEntity']['itemListElement'][] = [
                    '@type' => 'ListItem',
                    'position' => $index + 1,
                    'url' => route('recipes.show', $recipe->slug)
                ];
            }
        }
        
        // Формируем JSON-LD
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }
    
    /**
     * Генерирует schema.org разметку для главной страницы
     *
     * @return string JSON-LD разметка
     */
    public function generateHomeSchema(): string
    {
        $schema = [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => config('app.name'),
            'url' => url('/'),
            'potentialAction' => [
                '@type' => 'SearchAction',
                'target' => url('/search?q={search_term_string}'),
                'query-input' => 'required name=search_term_string'
            ],
            'publisher' => [
                '@type' => 'Organization',
                'name' => config('app.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => asset('images/logo.png')
                ]
            ]
        ];
        
        // Формируем JSON-LD
        return '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . '</script>';
    }

    /**
     * Устанавливает произвольный мета-тег
     *
     * @param string $name Имя мета-тега
     * @param string $content Содержимое мета-тега
     * @return $this
     */
    public function setMeta($name, $content)
    {
        $this->meta[$name] = $content;
        return $this;
    }

    /**
     * Устанавливает заголовок для Open Graph
     * 
     * @param string $title
     * @return $this
     */
    public function setOgTitle($title)
    {
        $this->ogTitle = $title;
        return $this;
    }
    
    /**
     * Устанавливает описание для Open Graph
     * 
     * @param string $description
     * @return $this
     */
    public function setOgDescription($description)
    {
        $this->ogDescription = $description;
        return $this;
    }
    
    /**
     * Получает все настроенные мета-данные
     * 
     * @return array
     */
    public function getMeta()
    {
        return [
            'title' => $this->title,
            'meta_description' => $this->description,
            'meta_keywords' => $this->keywords,
            'canonical' => $this->canonical,
            'og_type' => $this->ogType,
            'og_image' => $this->ogImage,
            'og_title' => $this->ogTitle,
            'og_description' => $this->ogDescription,
            'schema' => $this->schema
        ];
    }
    
    /**
     * Получает мета-данные для главной страницы
     * 
     * @return array
     */
   
    /**
     * Получает мета-данные для страницы списка рецептов
     * 
     * @param Request $request
     * @return array
     */
    public function getRecipesListPageMeta($request)
    {
        $title = 'Все рецепты - каталог кулинарных идей';
        $description = 'Каталог кулинарных рецептов с фото и пошаговыми инструкциями. Найдите рецепт по своему вкусу.';
        $keywords = 'рецепты, кулинария, приготовление блюд, домашняя кухня, пошаговые рецепты';
        $canonical = route('recipes.index');
        
        // Измененяем мета-данные в зависимости от фильтров
        if ($request->has('category')) {
            $category = \App\Models\Category::where('slug', $request->category)->first();
            if ($category) {
                $title = "Рецепты категории {$category->name}";
                $description = "Лучшие рецепты в категории {$category->name}. Пошаговые инструкции с фото для приготовления вкусных блюд.";
                $keywords = "рецепты {$category->name}, как приготовить {$category->name}, блюда {$category->name}";
                $canonical = route('recipes.index', ['category' => $category->slug]);
            }
        }
        
        if ($request->has('search') || $request->has('q')) {
            $searchTerm = trim($request->input('search', $request->input('q', '')));
            if (!empty($searchTerm)) {
                $title = "Поиск рецептов: {$searchTerm}";
                $description = "Результаты поиска рецептов по запросу '{$searchTerm}'. Находите лучшие рецепты для приготовления вкусных блюд.";
                $keywords = "рецепты {$searchTerm}, как приготовить {$searchTerm}, кулинария";
                $canonical = url()->current();
            }
        }
        
        return [
            'title' => $title,
            'meta_description' => $description,
            'meta_keywords' => $keywords,
            'canonical' => $canonical,
            'og_type' => 'website',
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => asset('images/og-recipes.jpg')
        ];
    }
    
    /**
     * Получает мета-данные для страницы рецепта
     * 
     * @param Recipe $recipe
     * @return array
     */
    public function getRecipePageMeta($recipe)
    {
        $title = $recipe->title . ' - пошаговый рецепт с фото';
        $description = Str::limit('Рецепт ' . $recipe->title . '. ' . $recipe->description, 160);
        
        // Формируем ключевые слова на основе названия и категорий рецепта
        $keywords = 'рецепт, ' . $recipe->title . ', как приготовить, готовим дома, кулинария, вкусные рецепты';
        
        if ($recipe->categories->isNotEmpty()) {
            $categoryKeywords = $recipe->categories->pluck('name')->implode(', ');
            $keywords .= ', ' . $categoryKeywords;
        }
        
        return [
            'title' => $title,
            'meta_description' => $description,
            'meta_keywords' => $keywords,
            'canonical' => route('recipes.show', $recipe->slug),
            'og_type' => 'article',
            'og_title' => $recipe->title,
            'og_description' => $description,
            'og_image' => $recipe->image_url ? asset($recipe->image_url) : asset('images/og-recipe-default.jpg')
        ];
    }
   
    /**
     * Получает метатеги для страницы конкретного рецепта
     *
     * @param Recipe $recipe
     * @return array
     */
    public function getRecipeMeta(Recipe $recipe)
    {
        $title = $recipe->meta_title ?? ($recipe->title . ' - пошаговый рецепт на Яедок');
        $description = $recipe->meta_description ?? Str::limit(strip_tags($recipe->description), 160);
        
        // Собираем ключевые слова из названия рецепта и категорий
        $keywords = implode(', ', array_merge(
            [$recipe->title],
            $recipe->categories->pluck('name')->toArray(),
            ['рецепт', 'приготовление', 'кулинария']
        ));

        // Определяем canonical URL
        $canonical = route('recipes.show', $recipe->slug);

        // Если есть изображение рецепта, используем его для OG
        $ogImage = $recipe->image_url ? asset($recipe->image_url) : asset('images/recipe-placeholder.jpg');
        
        // Устанавливаем тип Open Graph как статью рецепта
        $ogType = 'article';
        
        // Устанавливаем Schema.org для рецепта
        $schema = $this->generateRecipeSchema($recipe);

        return [
            'title' => $title,
            'meta_description' => $description,
            'meta_keywords' => $keywords,
            'canonical' => $canonical,
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $ogImage,
            'og_type' => $ogType,
            'og_site_name' => config('app.name'),
            'og_published_time' => $recipe->created_at->toIso8601String(),
            'og_modified_time' => $recipe->updated_at->toIso8601String(),
            'twitter_card' => $this->twitterCard,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => $ogImage,
            'robots' => $this->getRobots(),
            'schema' => $schema
        ];
    }

    /**
     * Получает метатеги для страницы категории
     *
     * @param Category $category
     * @return array
     */
    public function getCategoryMeta(Category $category)
    {
        $title = $category->meta_title ?? ($category->name . ' - рецепты на Яедок');
        $description = $category->meta_description ?? 
                   ('Рецепты в категории ' . $category->name . '. Подробные пошаговые инструкции с фото и видео. Готовьте вместе с нами!');
        $keywords = $category->meta_keywords ?? ($category->name . ', рецепты, кулинария, готовка, еда');
        
        $ogImage = $category->image_path ? asset($category->image_path) : asset('images/categories-cover.jpg');

        return [
            'title' => $title,
            'meta_description' => $description,
            'meta_keywords' => $keywords,
            'canonical' => route('categories.show', $category->slug),
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $ogImage,
            'og_type' => 'website',
            'og_site_name' => config('app.name'),
            'twitter_card' => $this->twitterCard,
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => $ogImage,
            'robots' => $this->getRobots(),
        ];
    }

    /**
     * Получает метатеги для страницы списка категорий
     *
     * @return array
     */


    /**
     * Получает метатеги для страницы поиска
     *
     * @param string $query Поисковый запрос
     * @param int $resultsCount Количество найденных результатов
     * @return array
     */
    public function getSearchPageMeta($query = '', $resultsCount = 0)
    {
        $title = !empty($query) ? 
                'Поиск: ' . $query . ' - ' . config('app.name') : 
                'Поиск рецептов - ' . config('app.name');
                
        $description = !empty($query) ? 
                     'Результаты поиска рецептов по запросу: ' . $query . '. Найдено ' . $resultsCount . ' ' . trans_choice('рецепт|рецепта|рецептов', $resultsCount) . '.' : 
                     'Поиск кулинарных рецептов по названиям, ингредиентам и категориям.';
        
        // Для страниц поиска всегда запрещаем индексацию
        $this->setRobots(false, true);

        return [
            'title' => $title,
            'meta_description' => $description,
            'meta_keywords' => !empty($query) ? $query . ', поиск рецептов, кулинария' : 'поиск рецептов, найти рецепт, кулинария',
            'canonical' => empty($query) ? route('search') : url('/search') . '?' . http_build_query(['query' => $query]),
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => asset('images/search-cover.jpg'),
            'og_type' => 'website',
            'og_site_name' => config('app.name'),
            'twitter_card' => 'summary',
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => asset('images/search-cover.jpg'),
            'robots' => $this->getRobots(),
        ];
    }

    /**
     * Получает метатеги для страницы списка рецептов
     *
     * @param array $params Параметры запроса
     * @return array
     */
    public function getRecipesListMeta($params = [])
    {
        $title = 'Все рецепты - ' . config('app.name');
        $description = 'Коллекция кулинарных рецептов с пошаговыми инструкциями, фото и списком ингредиентов.';
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        
        // Если есть поисковый запрос, корректируем метатеги
        if (isset($params['search']) && !empty($params['search'])) {
            $title = 'Поиск: ' . $params['search'] . ' - ' . config('app.name');
            $description = 'Результаты поиска рецептов по запросу: ' . $params['search'] . '.';
            
            // Для страниц поиска запрещаем индексацию
            $this->setRobots(false, true);
        }
        
        // Если есть категория, корректируем метатеги
        if (isset($params['category_id']) && !empty($params['category_id'])) {
            $category = \App\Models\Category::find($params['category_id']);
            if ($category) {
                $title = 'Рецепты в категории ' . $category->name . ' - ' . config('app.name');
                $description = 'Рецепты в категории ' . $category->name . '. Подробные пошаговые инструкции с фото и видео.';
            }
        }
        
        // Для всех страниц пагинации, кроме первой, запрещаем индексацию
        if ($page > 1) {
            $this->setRobots(false, true);
        }

        $baseUrl = route('recipes.index');
        $queryParams = array_filter($params, function($key) {
            return !in_array($key, ['page']);
        }, ARRAY_FILTER_USE_KEY);
        
        $canonical = $baseUrl;
        if (!empty($queryParams)) {
            $canonical .= '?' . http_build_query($queryParams);
        }

        $ogImage = asset('images/recipes-cover.jpg');

        return [
            'title' => $title,
            'meta_description' => $description,
            'meta_keywords' => 'рецепты, кулинария, еда, готовка, блюда, домашняя кухня',
            'canonical' => $canonical,
            'og_title' => $title,
            'og_description' => $description,
            'og_image' => $ogImage,
            'og_type' => 'website',
            'og_site_name' => config('app.name'),
            'twitter_card' => 'summary_large_image',
            'twitter_title' => $title,
            'twitter_description' => $description,
            'twitter_image' => $ogImage,
            'robots' => $this->getRobots(),
        ];
    }

    /**
     * Выполняет поиск рецептов по поисковому запросу
     *
     * @param string $query Поисковый запрос
     * @param array $filters Дополнительные фильтры (категория, время приготовления и пр.)
     * @param string $sort Метод сортировки результатов
     * @return \Illuminate\Pagination\LengthAwarePaginator
     */
    public function search($query, array $filters = [], $sort = 'relevance')
    {
        $recipesQuery = \App\Models\Recipe::where('is_published', true)
            ->where(function($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('description', 'like', "%{$query}%")
                  ->orWhere('ingredients', 'like', "%{$query}%")
                  ->orWhere('instructions', 'like', "%{$query}%");
            });

        // Применяем фильтры
        if (!empty($filters)) {
            // Фильтр по категории
            if (!empty($filters['category'])) {
                $recipesQuery->whereHas('categories', function($q) use ($filters) {
                    $q->where('categories.id', $filters['category'])
                      ->orWhere('categories.slug', $filters['category']);
                });
            }
            
            // Фильтр по времени приготовления
            if (!empty($filters['cooking_time'])) {
                $cookingTime = (int) $filters['cooking_time'];
                // Если cooking_time равно 30, ищем рецепты которые готовятся до 30 минут
                if ($cookingTime <= 30) {
                    $recipesQuery->where('cooking_time', '<=', $cookingTime);
                } else {
                    // Иначе ищем рецепты с временем приготовления от указанного значения
                    $recipesQuery->where('cooking_time', '>=', $cookingTime);
                }
            }
            
            // Фильтр по сложности
            if (isset($filters['difficulty']) && is_numeric($filters['difficulty'])) {
                $recipesQuery->where('difficulty', (int) $filters['difficulty']);
            }
            
            // Фильтр по калорийности
            if (!empty($filters['calories'])) {
                if (isset($filters['calories']['min'])) {
                    $recipesQuery->where('calories', '>=', (int) $filters['calories']['min']);
                }
                if (isset($filters['calories']['max'])) {
                    $recipesQuery->where('calories', '<=', (int) $filters['calories']['max']);
                }
            }
        }

        // Применяем сортировку
        switch ($sort) {
            case 'date_asc':
                $recipesQuery->orderBy('created_at', 'asc');
                break;
            case 'date_desc':
                $recipesQuery->orderBy('created_at', 'desc');
                break;
            case 'popularity':
                $recipesQuery->orderBy('views', 'desc');
                break;
            case 'rating':
                $recipesQuery->orderBy('rating', 'desc');
                break;
            case 'cooking_time_asc':
                $recipesQuery->orderBy('cooking_time', 'asc');
                break;
            case 'cooking_time_desc':
                $recipesQuery->orderBy('cooking_time', 'desc');
                break;
            case 'relevance':
            default:
                // По умолчанию сортируем по релевантности - сначала точные совпадения в заголовке
                $recipesQuery->orderByRaw("CASE 
                    WHEN title LIKE '{$query}' THEN 1
                    WHEN title LIKE '{$query}%' THEN 2
                    WHEN title LIKE '%{$query}%' THEN 3
                    ELSE 4
                END")
                ->orderBy('views', 'desc');
                break;
        }

        // Получаем результаты с пагинацией
        return $recipesQuery->paginate(12)->withQueryString();
    }
}
