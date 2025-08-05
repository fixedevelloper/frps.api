<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;


class ProformaGenerated extends Notification implements ShouldQueue
{
    use Queueable;

    protected $commande;

    public function __construct($commande)
    {
        $this->commande = $commande;
    }

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $url = asset($this->commande->proforma_pdf); // lien public

        return (new MailMessage)
            ->subject('Votre proforma est disponible')
            ->greeting('Bonjour ' . $notifiable->name)
            ->line('Votre proforma pour la commande #' . $this->commande->id . ' est maintenant disponible.')
            ->action('Voir le proforma', $url)
            ->line('Merci de votre confiance.');
    }
}
