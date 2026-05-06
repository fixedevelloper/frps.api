<?php

namespace App\Console\Commands;


use App\Helpers\api\Helpers;
use App\Helpers\Helper;
use App\Models\Paiement;
use App\Services\TransactService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class CheckPendingPayments extends Command
{
    protected $signature = 'payments:check-pending';

    protected $description = 'Vérifie les paiements Tranzak en attente';

    public function handle(TransactService $tranzakService)
    {
        $payments = Paiement::where('status', 'pending')
            ->whereNotNull('reference')
            ->get();

        $this->info("Paiements à vérifier : " . $payments->count());

        foreach ($payments as $payment) {

            try {
                $status = $tranzakService->collectionStatus([
                    'requestId' => $payment->reference
                ]);

                $transactionStatus = $status['status'] ?? null;

                if ($transactionStatus === 'SUCCESSFUL') {

                    DB::transaction(function () use ($payment, $status) {

                        $payment->update([
                            'status' => 'paid',
                            'provider_response' => json_encode($status)
                        ]);

                        $payment->commande->update([
                            'status' => Helper::STATUSSUCCESS,
                            'validatedStatus' => Helper::STATUSSUCCESS
                        ]);

                    });

                    $this->info("Paiement confirmé : {$payment->id}");
                }

                if ($transactionStatus === 'FAILED' || $transactionStatus == 'CANCELLED') {

                    $payment->update([
                        'status' => 'failed',
                        'provider_response' => json_encode($status)
                    ]);
                    $payment->commande->update([
                        'status' => Helper::STATUSFAILD,
                        'validatedStatus' => Helper::STATUSFAILD
                    ]);
                    $this->warn("Paiement échoué : {$payment->id}");
                }

            } catch (\Exception $e) {
                logger($e->getMessage());
                $this->error("Erreur paiement {$payment->id} : " . $e->getMessage());
            }
        }

        return Command::SUCCESS;
    }
}
