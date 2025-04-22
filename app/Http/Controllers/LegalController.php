<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\SeoService;

class LegalController extends Controller
{
    protected $seoService;

    public function __construct(SeoService $seoService)
    {
        $this->seoService = $seoService;
    }

    public function disclaimer()
    {
        $this->seoService->setTitle('Отказ от ответственности')
            ->setDescription('Отказ от ответственности сайта с рецептами. Информация об использовании материалов сайта.')
            ->setKeywords('отказ от ответственности, правовая информация, условия использования')
            ->setCanonical(route('legal.disclaimer'));
            
        return view('legal.disclaimer');
    }

    public function terms()
    {
        $this->seoService->setTitle('Пользовательское соглашение')
            ->setDescription('Пользовательское соглашение сайта рецептов. Правила и условия использования сервиса.')
            ->setKeywords('пользовательское соглашение, условия использования, правила сайта')
            ->setCanonical(route('legal.terms'));
            
        return view('legal.terms');
    }

    public function dmca()
    {
        $this->seoService->setTitle('DMCA - Защита авторских прав')
            ->setDescription('Политика в отношении авторских прав и процедура подачи жалоб на нарушение авторских прав (DMCA).')
            ->setKeywords('DMCA, защита авторских прав, жалоба на нарушение, авторское право')
            ->setCanonical(route('legal.dmca'));
            
        return view('legal.dmca');
    }

    public function privacy()
    {
        $this->seoService->setTitle('Политика конфиденциальности')
            ->setDescription('Политика конфиденциальности сайта рецептов. Информация о сборе и обработке персональных данных.')
            ->setKeywords('политика конфиденциальности, персональные данные, защита информации')
            ->setCanonical(route('legal.privacy'));
            
        return view('legal.privacy');
    }

    public function dmcaSubmit(Request $request)
    {
        // Валидация данных формы DMCA
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email',
            'content_url' => 'required|url',
            'original_url' => 'required|url',
            'description' => 'required|string|min:10',
            'agreement' => 'required|accepted',
        ]);
        
        // Отправка уведомления администрации о DMCA жалобе
        // ...
        
        return redirect()->route('legal.dmca')
            ->with('success', 'Ваша жалоба на нарушение авторских прав успешно отправлена.');
    }
}
