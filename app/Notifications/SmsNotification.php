<?php


namespace App\Notifications;

use App\Channels\SmsChannel;
use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;


class SmsNotification extends Notification implements ShouldQueue
{
    use Queueable;

    protected $message;

    public function __construct(string $message)
    {
        $this->message = $message;
    }

    public function via($notifiable)
    {
        // On utilise le canal custom SmsChannel
        return [SmsChannel::class];
    }

    public function toSms($notifiable)
    {
        return $this->message;
    }

    public function tries()
    {
        return 3;
    }

    public function backoff()
    {
        return [10, 30, 60];
    }

    public $timeout = 30;
}

