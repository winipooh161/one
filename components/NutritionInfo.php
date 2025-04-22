<?php

namespace app\components;

use yii\base\Widget;
use yii\helpers\Html;

class NutritionInfo extends Widget
{
    public $calories = 0;
    public $protein = 0;
    public $fat = 0;
    public $carbohydrates = 0;
    
    public function init()
    {
        parent::init();
    }

    public function run()
    {
        return $this->render('nutrition-info', [
            'calories' => $this->calories,
            'protein' => $this->protein,
            'fat' => $this->fat,
            'carbohydrates' => $this->carbohydrates,
        ]);
    }
}
