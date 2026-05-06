<?php


namespace App\Http\Controllers;


use App\Helpers\Helper;
use App\Models\Commande;
use App\Models\Paiement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Models\Payment;
use App\Models\Reservation;

class TranzakWebhookController extends Controller
{
    public function handle(Request $request)
    {
        try {

            Log::info("TRANZAK WEBHOOK", $request->all());

            $payload = $request->all();

            if (!isset($payload['resource'])) {
                return response()->json(['success' => false]);
            }

            $resource = $payload['resource'];

            $reference = $resource['mchTransactionRef'] ?? null;
            $status = $resource['transactionStatus'] ?? null;

            if (!$reference) {
                return response()->json(['success' => false]);
            }

            $payment = Paiement::where('reference', $reference)->first();

            if (!$payment) {
                Log::warning("Payment not found", ['reference' => $reference]);
                return response()->json(['success' => false]);
            }

            DB::beginTransaction();

            if ($status === "SUCCESSFUL") {

                $payment->update([
                    'status' => 'paid',
                    'provider_response' => json_encode($payload)
                ]);

                $reservation = Commande::find($payment->commade_id);

                if ($reservation) {

                    $reservation->update([
                       // 'payment_status' => 'paid',
                        'status' => Helper::STATUSSUCCESS,
                       // 'confirmed_at' => now()
                    ]);
                }

            } else {

                $payment->update([
                    'status' => 'failed',
                    'provider_response' => json_encode($payload)
                ]);

                $reservation = Commande::find($payment->reservation_id);

                if ($reservation) {
                    $reservation->update([
                        'status' => Helper::STATUSFAILD
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'success' => true
            ]);

        } catch (\Exception $e) {

            DB::rollBack();

            Log::error("TRANZAK WEBHOOK ERROR", [
                'message' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false
            ], 500);
        }
    }
}
