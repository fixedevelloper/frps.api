<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class NewMessageNotification extends Notification
{
    use Queueable;

    public $data;

    public function __construct($data) {
        $this->data = $data;
    }

    // Définit les canaux : 'database' pour la table, 'broadcast' pour Pusher
    public function via($notifiable) {
        return ['database', 'broadcast'];
    }

    // Format pour la table 'notifications'
    public function toArray($notifiable) {
        return [
            'message' => $this->data['message'],
            'url' => $this->data['url']
        ];
    }

    // Format pour Pusher (temps réel)
    public function toBroadcast($notifiable) {
        return new BroadcastMessage([
            'message' => $this->data['message'],
            'url' => $this->data['url']
        ]);
    }
}
