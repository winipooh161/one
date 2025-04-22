<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TelegramMessage extends Model
{
    use HasFactory;

    protected $fillable = [
        'message_id',
        'chat_id',
        'user_id',
        'text',
        'direction',
        'additional_data'
    ];

    protected $casts = [
        'additional_data' => 'array'
    ];

    /**
     * Получить чат, к которому относится сообщение
     */
    public function chat()
    {
        return $this->belongsTo(TelegramChat::class, 'chat_id', 'chat_id');
    }

    /**
     * Определить, является ли сообщение входящим
     */
    public function isIncoming()
    {
        return $this->direction === 'incoming';
    }

    /**
     * Определить, является ли сообщение исходящим
     */
    public function isOutgoing()
    {
        return $this->direction === 'outgoing';
    }
}
