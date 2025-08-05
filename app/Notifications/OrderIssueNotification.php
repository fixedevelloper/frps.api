<?php

namespace App\Notifications;

use App\Models\Litige;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class OrderIssueNotification extends Notification
{
    use Queueable;

    public $issue;

    public function __construct(Litige $issue)
    {
        $this->issue = $issue;
    }


    /**
     * Get the notification's delivery channels.
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Get the mail representation of the notification.
     */
    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('⚠️ Nouveau problème signalé sur une commande')
            ->line('Commande : #' . $this->issue->commande_id)
            ->line('Type : ' . ucfirst($this->issue->type))
            ->line('Description : ' . $this->issue->description)
            ->action('Voir dans l’admin', url('/admin/order-issues/' . $this->issue->id));
    }

    // Optionnel : pour stocker la notification en base
    public function toArray($notifiable)
    {
        return [
            'order_id' => $this->issue->commande_id,
            'type' => $this->issue->type,
            'status' => $this->issue->status,
        ];
    }
}
