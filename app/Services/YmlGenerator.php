<?php

namespace App\Services;

use App\Models\Recipe;
use App\Models\Category;

class YmlGenerator
{
    /**
     * Генерирует YML-фид для Яндекса
     *
     * @return string XML-содержимое
     */
    public function generate()
    {
        $recipes = Recipe::where('is_published', true)
            ->with(['categories', 'user'])
            ->latest()
            ->get();
            
        $categories = Category::withCount('recipes')
            ->having('recipes_count', '>', 0)
            ->get();
            
        // Создаем XML с помощью XMLWriter вместо Blade
        $xml = new \XMLWriter();
        $xml->openMemory();
        $xml->setIndent(true);
        $xml->setIndentString('    ');
        $xml->startDocument('1.0', 'UTF-8');

        // Добавляем DTD
        $xml->writeDtd('yml_catalog', null, 'shops.dtd');
        
        // Начинаем yml_catalog
        $xml->startElement('yml_catalog');
        $xml->writeAttribute('date', now()->format('Y-m-d H:i'));
        
        // Магазин
        $xml->startElement('shop');
        
        // Информация о магазине
        $xml->writeElement('name', config('app.name'));
        $xml->writeElement('company', config('app.name'));
        $xml->writeElement('url', config('app.url'));
        
        // Валюты
        $xml->startElement('currencies');
        $xml->startElement('currency');
        $xml->writeAttribute('id', 'RUB');
        $xml->writeAttribute('rate', '1');
        $xml->endElement(); // currency
        $xml->endElement(); // currencies
        
        // Категории
        $xml->startElement('categories');
        foreach ($categories as $category) {
            $xml->startElement('category');
            $xml->writeAttribute('id', $category->id);
            $xml->text($category->name);
            $xml->endElement(); // category
        }
        $xml->endElement(); // categories
        
        // Предложения (рецепты)
        $xml->startElement('offers');
        foreach ($recipes as $recipe) {
            $xml->startElement('offer');
            $xml->writeAttribute('id', $recipe->id);
            $xml->writeAttribute('available', 'true');
            $xml->writeAttribute('type', 'vendor.model');
            
            $xml->writeElement('name', $recipe->title);
            $xml->writeElement('url', route('recipes.show', $recipe->slug));
            $xml->writeElement('price', '0');
            $xml->writeElement('currencyId', 'RUB');
            
            if ($recipe->categories->isNotEmpty()) {
                $xml->writeElement('categoryId', $recipe->categories->first()->id);
            }
            
            if ($recipe->image_url) {
                $xml->writeElement('picture', asset($recipe->image_url));
            }
            
            $xml->startElement('description');
            $xml->writeCdata(strip_tags($recipe->description));
            $xml->endElement(); // description
            
            $xml->writeElement('vendor', config('app.name'));
            $xml->writeElement('model', 'recipe-' . $recipe->id);
            
            // Параметры рецепта
            if ($recipe->cooking_time) {
                $xml->startElement('param');
                $xml->writeAttribute('name', 'cooking_time');
                $xml->text($recipe->cooking_time . ' мин');
                $xml->endElement(); // param
            }
            
            if ($recipe->servings) {
                $xml->startElement('param');
                $xml->writeAttribute('name', 'servings');
                $xml->text($recipe->servings);
                $xml->endElement(); // param
            }
            
            if ($recipe->calories) {
                $xml->startElement('param');
                $xml->writeAttribute('name', 'calories');
                $xml->text($recipe->calories . ' ккал');
                $xml->endElement(); // param
            }
            
            if ($recipe->proteins) {
                $xml->startElement('param');
                $xml->writeAttribute('name', 'proteins');
                $xml->text($recipe->proteins . ' г');
                $xml->endElement(); // param
            }
            
            if ($recipe->fats) {
                $xml->startElement('param');
                $xml->writeAttribute('name', 'fats');
                $xml->text($recipe->fats . ' г');
                $xml->endElement(); // param
            }
            
            if ($recipe->carbs) {
                $xml->startElement('param');
                $xml->writeAttribute('name', 'carbs');
                $xml->text($recipe->carbs . ' г');
                $xml->endElement(); // param
            }
            
            $xml->endElement(); // offer
        }
        $xml->endElement(); // offers
        
        $xml->endElement(); // shop
        $xml->endElement(); // yml_catalog
        
        return $xml->outputMemory();
    }
}
