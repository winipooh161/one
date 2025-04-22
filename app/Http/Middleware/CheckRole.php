<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Обработка запроса.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $role
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        // Проверяем, аутентифицирован ли пользователь
        if (!$request->user()) {
            return redirect()->route('login');
        }
        
        // Если пользователь не имеет указанной роли, перенаправляем его назад
        if ($request->user()->role !== $role) {
            return redirect()->back()->with('error', 'У вас нет прав для доступа к этой странице.');
        }
        
        return $next($request);
    }
}
