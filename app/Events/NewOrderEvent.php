<?php

namespace App\Events;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Broadcasting\Channel;

class NewOrderEvent extends Notification implements ShouldBroadcast
{
    use Queueable;

    public $commande;

    public function __construct($commande)
    {
        $this->commande = $commande;
    }

    /**
     * Définit les canaux de livraison.
     * 'database' pour la table notifications, 'broadcast' pour le temps réel.
     */
    public function via($notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * Données stockées dans la colonne 'data' de la table 'notifications'.
     */
    public function toArray($notifiable): array
    {
        return [
            'order_id'      => $this->commande->id,
            'customer_name' => $this->commande->user->name ?? 'Client inconnu',
            'total'         => $this->commande->total,
            'message'       => "Nouvelle commande #{$this->commande->id} de " . number_format($this->commande->total, 0, ',', ' ') . " FCFA",
        ];
    }

    /**
     * Données envoyées via WebSockets (Laravel Echo).
     */
    public function toBroadcast($notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'notification' => [
                'title'   => 'Nouvelle Commande',
                'content' => "Une nouvelle commande a été placée.",
                'data'    => $this->toArray($notifiable), // Réutilise les données de toArray
                'time'    => now()->toDateTimeString(),
            ]
        ]);
    }

    /**
     * Canal de diffusion.
     */
    public function broadcastOn(): Channel
    {
        return new Channel('orders-channel');
    }

    /**
     * Nom de l'événement côté client (frontend).
     */
    public function broadcastAs(): string
    {
        return 'new.order';
    }
}
