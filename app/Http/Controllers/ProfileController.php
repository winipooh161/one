<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Models\User;
use App\Models\Recipe;

class ProfileController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Показать профиль пользователя
     */
    public function show(User $user = null)
    {
        // Если пользователь не указан, показываем профиль текущего пользователя
        if (!$user) {
            $user = Auth::user();
        }

        // Получаем рецепты пользователя
        $recipes = Recipe::where('user_id', $user->id)
                        ->where('is_published', true)
                        ->latest()
                        ->paginate(10);

        return view('profile.show', compact('user', 'recipes'));
    }

    /**
     * Показать форму редактирования профиля
     */
    public function edit()
    {
        $user = Auth::user();
        return view('profile.edit', compact('user'));
    }

    /**
     * Обновить профиль пользователя
     */
    public function update(Request $request)
    {
        $user = Auth::user();

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'bio' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:2048'],
        ]);

        $user->name = $request->name;
        $user->email = $request->email;
        $user->bio = $request->bio;

        // Обработка аватара
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $user->avatar = $path;
        }

        $user->save();

        return redirect()->route('profile.show')->with('success', 'Профиль успешно обновлен!');
    }

    /**
     * Показать страницу изменения пароля
     */
    public function showChangePasswordForm()
    {
        return view('profile.change-password');
    }

    /**
     * Изменить пароль пользователя
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => ['required', function ($attribute, $value, $fail) {
                if (!Hash::check($value, Auth::user()->password)) {
                    $fail('Текущий пароль указан неверно.');
                }
            }],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        Auth::user()->update([
            'password' => Hash::make($request->password),
        ]);

        return redirect()->route('profile.show')->with('success', 'Пароль успешно изменен!');
    }

    public function show($id)
    {
        // TODO: загрузите профиль пользователя по ID
    }
    
    public function update(Request $request, $id)
    {
        // TODO: обработка обновления данных профиля
    }
}
