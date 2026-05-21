<?php

namespace App\Notifications;

use Illuminate\Broadcasting\Channel;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Messages\BroadcastMessage; // <--- Importez ceci
use Illuminate\Notifications\Notification;
use App\Models\Commande;

class NewOrderNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public $commande;

    public function __construct(Commande $commande)
    {
        $this->commande = $commande;
    }

    /**
     * Get the notification's delivery channels.
     */
    public function via(object $notifiable): array
    {
        // Ajoutez 'broadcast' ici pour activer Pusher
        return ['mail', 'database', 'broadcast'];
    }
    public function broadcastOn(): Channel
    {
        // Pour notifier tout le monde
        return new Channel('global-notifications');

        // OU si vous voulez cibler un canal privé spécifique
        // return new PrivateChannel('App.Models.User.' . $this->userId);
    }
    /**
     * Get the mail representation of the notification.
     */
    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("Nouvelle commande n°{$this->commande->id}")
            ->markdown('mail.new-order-notification', [
                'admin_name' => 'Administrateur',
                'commande' => $this->commande,
            ]);
    }

    /**
     * Get the broadcastable representation of the notification.
     */
    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'commande_id' => $this->commande->id,
            'total' => $this->commande->total,
            'message' => "Nouvelle commande n°{$this->commande->id} reçue !",
        ]);
    }

    /**
     * Get the array representation of the notification.
     */
    public function toArray(object $notifiable): array
    {
        return [
            'commande_id' => $this->commande->id,
            'total' => $this->commande->total,
        ];
    }
}

