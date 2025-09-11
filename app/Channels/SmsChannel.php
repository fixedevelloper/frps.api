<?php


namespace App\Channels;


use Illuminate\Notifications\Notification;
use App\Services\SmsService;

class SmsChannel
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);
        return $this->smsService->sendSms($notifiable->phone, $message);
    }
}

