<?php

namespace App\Console\Commands;

use App\Events\NewOrderEvent;
use App\Helpers\Helper;
use App\Models\Commande;
use App\Models\User;
use App\Notifications\NewMessageNotification;
use App\Notifications\NewOrderNotification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Notification;

class SendNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:send-notification';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $commande = Commande::find(1);

        if (!$commande) {
            $this->error('Commande non trouvée !');
            return;
        }

        // 1. Récupérer les utilisateurs (avec ->get())
        $users = User::whereNot('type', User::CUSTOMER_TYPE)->get();

        // 2. Utiliser la façade Notification pour une exécution plus propre
        // Cela gère l'enregistrement en BDD et le broadcast pour chaque utilisateur
        \Illuminate\Support\Facades\Notification::send($users, new NewOrderEvent($commande));

        $this->info('Notifications envoyées à ' . $users->count() . ' utilisateurs.');
    }
}
