<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;

class DmcaNotification extends Mailable
{
    use Queueable, SerializesModels;

    public $data;

    /**
     * Create a new message instance.
     *
     * @param array $data
     * @return void
     */
    public function __construct(array $data)
    {
        $this->data = $data;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        return $this->subject('Уведомление о нарушении авторских прав')
                    ->markdown('emails.dmca-notification')
                    ->with([
                        'name' => $this->data['name'],
                        'email' => $this->data['email'],
                        'content_url' => $this->data['content_url'],
                        'original_url' => $this->data['original_url'] ?? null,
                        'description' => $this->data['description'],
                    ]);
    }
}
