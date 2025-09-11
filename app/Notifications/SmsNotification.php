<?php


namespace App\Notifications;

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
        // on utilise seulement le canal "sms" personnalisé
        return ['sms'];
    }

    public function toSms($notifiable)
    {
        $sms = new SmsService();
        return $sms->sendSms($notifiable->phone, $this->message);
    }
    /**
     * Nombre maximum de tentatives avant échec définitif
     */
    public function tries()
    {
        return 3; // ✅ on retente 3 fois
    }

    /**
     * Temps d’attente avant un nouveau retry (en secondes)
     */
    public function backoff()
    {
        return [10, 30, 60];
        // 1er retry après 10s, 2e après 30s, 3e après 60s
    }

    /**
     * Timeout maximum d’exécution du job (en secondes)
     */
    public $timeout = 30;
}
