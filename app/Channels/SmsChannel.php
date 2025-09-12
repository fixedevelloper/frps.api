<?php


namespace App\Channels;


use Illuminate\Notifications\Notification;
use App\Services\SmsService;

class SmsChannel
{
    protected $sms;

    public function __construct(SmsService $sms)
    {
        $this->sms = $sms;
    }

    /**
     * Envoi de la notification via SMS
     * @param $notifiable
     * @param Notification $notification
     */
    public function send($notifiable, Notification $notification)
    {
        if (! method_exists($notification, 'toSms')) {
            return;
        }

        $message = $notification->toSms($notifiable);

        $phone = $notifiable->phone ?? null;

        if ($phone && $message) {
            $this->sms->sendSms($phone, $message);
        }
    }
}

