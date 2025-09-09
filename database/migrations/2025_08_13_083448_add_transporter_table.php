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
        // Table transporteurs
        Schema::create('transporteurs', function (Blueprint $table) {
            $table->id();
            $table->string('nom');
            $table->enum('type', ['interne', 'externe']);
            $table->timestamps();
        });

        // Table transporteur_externes
        Schema::create('transporteur_externes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporteur_id')->constrained('transporteurs')->onDelete('cascade');
            $table->string('contrat');
            $table->decimal('cout', 10, 2);
            $table->integer('delai'); // en heures ou jours
            $table->timestamps();
        });

        // Table véhicules
        Schema::create('vehicules', function (Blueprint $table) {
            $table->id();
            $table->string('immatriculation')->unique();
            $table->string('modele');
            $table->decimal('capacite', 10, 2); // en kg ou tonnes
            $table->timestamps();
        });


        // Table transporteur_internes
        Schema::create('transporteur_internes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transporteur_id')->constrained('transporteurs')->onDelete('cascade');
            $table->foreignId('vehicule_id')->constrained('vehicules')->onDelete('cascade');
            $table->foreignId('chauffeur_id')->constrained('users')->onDelete('cascade');
            $table->timestamps();
        });

        // Modifier transporteur_id dans commandes
        Schema::table('commandes', function (Blueprint $table) {
            if (!Schema::hasColumn('commandes', 'transporteur_id')) {
                $table->foreignId('transporteur_id')->nullable()->constrained('transporteurs')->onDelete('set null');
            }
        });
    }


    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Modifier commandes : supprimer FK et remettre colonne non nullable si besoin
        Schema::table('commandes', function (Blueprint $table) {
            $table->dropForeign(['transporteur_id']);
            //$table->unsignedBigInteger('transporteur_id')->nullable(false)->change();
        });

        // Supprimer tables transporteur_internes
        Schema::dropIfExists('transporteur_internes');

        // Supprimer tables chauffeurs
        Schema::dropIfExists('chauffeurs');

        // Supprimer tables vehicules
        Schema::dropIfExists('vehicules');

        // Supprimer tables transporteur_externes
        Schema::dropIfExists('transporteur_externes');

        // Supprimer table transporteurs
        Schema::dropIfExists('transporteurs');
    }

};
