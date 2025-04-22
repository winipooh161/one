<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Notification extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'read_at',
        'data'
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'read_at' => 'datetime',
        'data' => 'array'
    ];

    /**
     * Get the user that owns the notification.
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Determine if the notification has been read.
     *
     * @return bool
     */
    public function isRead()
    {
        return $this->read_at !== null;
    }

    /**
     * Mark the notification as read.
     *
     * @return $this
     */
    public function markAsRead()
    {
        if (is_null($this->read_at)) {
            $this->forceFill(['read_at' => now()])->save();
        }

        return $this;
    }

    /**
     * Создать уведомление об отказе в модерации рецепта
     */
    public static function createModerationRejected($user, $recipe, $message)
    {
        return self::create([
            'user_id' => $user->id,
            'title' => 'Модерация рецепта отклонена',
            'content' => "Ваш рецепт \"{$recipe->title}\" не прошел модерацию. Причина: {$message}",
            'type' => 'moderation_rejected',
            'data' => [
                'recipe_id' => $recipe->id,
                'recipe_slug' => $recipe->slug,
                'rejection_reason' => $message
            ]
        ]);
    }
}
