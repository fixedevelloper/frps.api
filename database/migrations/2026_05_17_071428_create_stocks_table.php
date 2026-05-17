<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            $table->string('num_lot')->nullable(); // Numéro du lot fabriquant
            $table->date('date_peremption')->nullable(); // Critique pour la gestion médicale/périssable
            $table->date('date_reception'); // Utilisé principalement pour le FIFO / LIFO standard

            // Gestion des quantités
            $table->integer('quantite_initiale'); // Quantité entrée au départ (historique)
            $table->integer('quantite_actuelle');  // Quantité restante (décrémentée lors des sorties)

            // Prix d'achat du lot (important pour valoriser le stock en FIFO/LIFO comptable)
            $table->decimal('prix_achat', 15, 2)->default(0);

            $table->string('emplacement')->nullable(); // Optionnel : étagère, dépôt A, etc.
            $table->timestamps();

            // Index pour optimiser les requêtes FIFO/LIFO et alertes péremptions
            $table->index(['product_id', 'quantite_actuelle']);
            $table->index('date_peremption');
            $table->index('date_reception');
        });
        Schema::create('stock_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');

            // Nullable car si on supprime un lot obsolète, on veut garder l'historique du mouvement
            $table->foreignId('stock_id')->nullable()->constrained('stocks')->onDelete('set null');

            // Type de mouvement
            $table->enum('type', ['Entrée', 'Sortie', 'Ajustement Inventaire', 'Perte / Périmé']);

            $table->integer('quantite'); // Toujours positif (le 'type' détermine le sens +/-)

            // Polymorphisme optionnel (pour lier le mouvement à une commande, un bon de livraison, etc.)
            $table->nullableMorphs('movable');

            $table->foreignId('user_id')->nullable()->constrained(); // Qui a fait l'action
            $table->string('motif')->nullable(); // Ex: "Commande #FAC-004", "Erreur inventaire", "Lot cassé"
            $table->timestamps();

            // Index pour les rapports d'activité
            $table->index(['product_id', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stocks');
        Schema::dropIfExists('stock_movements');
    }
};
