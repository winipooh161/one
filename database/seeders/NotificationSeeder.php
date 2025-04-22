<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Notification;
use App\Models\User;
use App\Models\Recipe;

class NotificationSeeder extends Seeder
{
    /**
     * Заполнение таблицы уведомлений.
     *
     * @return void
     */
    public function run()
    {
        // Получаем пользователей
        $user = User::where('role', 'user')->first();
        $admin = User::where('role', 'admin')->first();
        
        if ($user && $admin) {
            // Создаем примеры уведомлений
            $notifications = [
                [
                    'user_id' => $user->id,
                    'title' => 'Ваш рецепт одобрен',
                    'content' => 'Ваш рецепт "Греческий салат" успешно прошел модерацию и опубликован на сайте.',
                    'type' => 'moderation_approved',
                    'data' => json_encode(['recipe_id' => 3, 'recipe_slug' => 'grecheskij-salat']),
                    'is_read' => false,
                    'created_at' => now()->subDays(1),
                ],
                [
                    'user_id' => $admin->id,
                    'title' => 'Новый рецепт на модерацию',
                    'content' => 'Пользователь "Пользователь" добавил новый рецепт "Шоколадный торт" на модерацию.',
                    'type' => 'new_recipe',
                    'data' => json_encode(['recipe_id' => 2, 'recipe_slug' => 'shokoladnyj-tort']),
                    'is_read' => true,
                    'created_at' => now()->subDays(3),
                ],
            ];
            
            foreach ($notifications as $notificationData) {
                Notification::firstOrCreate(
                    [
                        'user_id' => $notificationData['user_id'],
                        'title' => $notificationData['title'],
                        'created_at' => $notificationData['created_at']
                    ],
                    [
                        'content' => $notificationData['content'],
                        'type' => $notificationData['type'],
                        'data' => $notificationData['data'],
                        'is_read' => $notificationData['is_read'],
                    ]
                );
            }
        }
    }
}
