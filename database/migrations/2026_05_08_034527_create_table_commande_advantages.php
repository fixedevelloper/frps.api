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
        Schema::table('advantages', function (Blueprint $table) {


            $table->enum('type', [
                'paiement_differe',
                'remise',
                'bon_reduction'
            ])->change();

            // ex: 20% de remise
            $table->decimal('value', 12, 2)->default(0);

            // remise en %
            $table->boolean('is_percentage')->default(true);

            $table->boolean('active')->default(true);

            $table->string('label')->nullable();
            $table->text('description')->nullable();

        });
        Schema::create('commande_advantages', function (Blueprint $table) {

            $table->id();

            $table->foreignId('commande_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('advantage_id')
                ->constrained()
                ->cascadeOnDelete();

            // montant réellement appliqué
            $table->decimal('amount', 15, 2)->default(0);

            $table->timestamps();

        });
        Schema::table('commandes', function (Blueprint $table) {

            $table->decimal('subtotal', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('remaining_amount', 15, 2)->default(0);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('table_commande_advantages');
    }
};
