<?php

use App\Helpers\Helper;
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
        Schema::create('settings', function (Blueprint $table) {
            $table->id();
            $table->string('name',244)->nullable(false);
            $table->string('phone',244)->nullable(false);
            $table->string('email',244)->nullable(false);
            $table->string('address',244)->nullable(false);
            $table->string('logo',244)->nullable(false);
            $table->integer('stock_alert')->default(5);
            $table->string('notification_address',244)->nullable(false);
            $table->string('notification_phone',244)->nullable(false);
            $table->integer('dateline_litige')->default(48)->comment('en heure');
            $table->integer('percent_payable')->default(48)->comment('payables immédiatement');
            $table->timestamps();
        });
        Schema::create('departements', function (Blueprint $table) {
            $table->id();
            $table->string('name',244)->nullable(false);
            $table->timestamps();
        });
        Schema::create('cities', function (Blueprint $table) {
            $table->id();
            $table->string('name',244)->nullable(false);
            $table->foreignId('departement_id')->nullable()->constrained('departements')->onDelete('set null');
            $table->timestamps();
        });
        Schema::create('images', function (Blueprint $table) {
            $table->id();
            $table->string('src');
            $table->string('name')->nullable();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
            $table->string('intitule');
            $table->foreignId('parent_id')->nullable()->constrained('categories')->onDelete('set null');
            $table->foreignId('image_id')->nullable()->constrained("images",'id')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('intitule');
            $table->string('reference')->unique();
            $table->decimal('price_buy')->default(0.0);
            $table->decimal('price')->default(0.0);
            $table->enum('type_stock',['Lot','FIFO','LIFO']);
            $table->string('presentation')->nullable();
            $table->string('lot')->nullable();
            $table->date('date_fabrication');
            $table->date('date_peremption');
            $table->string('financement');
            $table->string('utilisateur_cible');
            $table->float('quantite');
            $table->string('unite');
            $table->string('poids')->nullable();
            $table->boolean('publish')->default(false);
            $table->text('description')->nullable();
            $table->foreignId('category_id')->nullable()->constrained("categories",'id')->nullOnDelete();
            $table->foreignId('image_id')->nullable()->constrained("images",'id')->nullOnDelete();
            $table->foreignId('created_by')->nullable()->constrained("users",'id')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('enter_stocks', function (Blueprint $table) {
            $table->id();
            $table->integer('quantity')->default(0);
            $table->integer('previous_quantity')->default(0);
            $table->enum('status',[Helper::STATUSSUCCESS,Helper::STATUSCONFIRM,Helper::STATUSPENDING])->default(Helper::STATUSPENDING);
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('created_by')->nullable()->constrained("users",'id')->nullOnDelete();
            $table->timestamps();
        });
        Schema::create('commandes', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->nullable();
            $table->decimal('total', 10, 2);
            $table->decimal('rest_to_pay', 10, 2);
            $table->foreignId('customer_id')->constrained('users')->onDelete('cascade');
            $table->date('date_validation')->nullable();
            $table->date('timer_auto')->nullable();
            $table->text('motif_rejet')->nullable();
            $table->boolean('is_modifiable')->default(true);
            $table->string('proforma_pdf')->nullable();
            $table->string('facture_pdf')->nullable();
            $table->string('bordereau_pdf')->nullable();
            $table->integer('validatedBy')->nullable()->comment('0:System');
           $table->tinyInteger('status')->default(Helper::STATUSPENDING);
            $table->tinyInteger('validatedStatus')->default(Helper::STATUSPENDING);
            $table->timestamps();
        });
        //pending, confirmed, preparing, shipped, delivered, investigation, returned, cancelled
        Schema::create('product_commande', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained('commandes')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->unsignedInteger('quantite');
            $table->decimal('amount', 10, 2);
            $table->timestamps();
        });
        Schema::create('proformas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->onDelete('cascade');
            $table->date('date_generation');
            $table->enum('status', ['manuel', 'automatique'])->default('automatique');
            $table->boolean('is_printable')->default(true);
            $table->timestamps();
        });
        Schema::create('paiements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->onDelete('cascade');
            $table->decimal('montant', 12, 2);
            $table->enum('methode', [Helper::METHODCHECK, Helper::METHODOM,Helper::METHODMTN]);
            $table->enum('etat', [Helper::PAIEMENTETATCOMPLET, Helper::PAIEMENTETATPARTIEL])->default(Helper::PAIEMENTETATPARTIEL);
            $table->date('date_paiement');
            $table->timestamps();
        });
        Schema::create('livraisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained()->onDelete('cascade');
            $table->foreignId('transporteur_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('tracking_number')->nullable();
            $table->date('date_expedition')->nullable();
            $table->date('date_livraison')->nullable();
            $table->enum('status', [Helper::STATUSSUCCESS, Helper::STATUSDELIVERYD, Helper::STATUSAUTOCONFIRM, Helper::STATUSPENDING])->default(Helper::STATUSPENDING);
            $table->text('proces_verbal_reception')->nullable();
            $table->timestamps();
        });
        Schema::create('litiges', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained();
            $table->enum('type',['retard','colis_endommage','non_conformite','produit_defectueux','erreur_livraison','quantite_incorrecte']); // retard, colis endommagé, etc.
            $table->text('description')->nullable()->comment('Description du problème');
            $table->enum('status',['en_investigation','accepte','refuse','archive'])->default('en_investigation');
            $table->date('submitted_at')->nullable(); //Date de soumission
            $table->date('resolution_deadline');//Délai max de traitement
            $table->json('photos')->nullable();
            $table->timestamps();
        });

        Schema::create('return_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('commande_id')->constrained();
            $table->foreignId('product_order_id')->constrained('products');
            $table->string('reason'); // erreur, quantité, etc.
            $table->json('photos')->nullable();
            $table->text('return_label')->nullable();
            $table->string('status')->default('en attente');
            $table->timestamp('date_demande')->nullable();
            $table->timestamp('date_traitement')->nullable();
            $table->timestamps();
        });
        Schema::create('advantages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('users');
            $table->enum('type',['paiement_differé','remise','bon_reduction']); // réduction, paiement partiel, etc.
            $table->decimal('percentage_paid')->default(0.0); //Pourcentage payé immédiatement
            $table->date('due_date')->nullable(); //Date limite pour le solde
            $table->timestamps();
        });
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('discount_rate',10,2)->default(0.0);
            $table->decimal('pending_balance',10,2)->default(0.0);
            $table->foreignId('image_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('city_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('departement_id')->nullable()->constrained()->nullOnDelete();
        });
        Schema::create('tickets', function (Blueprint $table) {
            $table->id();
            $table->string('title',244)->nullable(false);
            $table->string('subject',244)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('customer_id')->nullable()->constrained('users')->nullOnDelete();
            $table->json('attachments')->nullable();
            $table->timestamps();
        });
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->string('from',244)->nullable(false);
            $table->string('type',244)->nullable();
            $table->text('description')->nullable();
            $table->foreignId('order_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
