<?php

namespace App\Http\Controllers\API;
use App\Helpers\api\Helpers;
use App\Http\Controllers\Controller;
use App\Models\Commande;
use App\Models\User;
use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
     public function salesChart()
    {
        $months = [];
        $revenu = [];
        $depenses = [];

        $currentYear = Carbon::now()->year;

        for ($i = 1; $i <= 12; $i++) {

            $months[] = Carbon::create()->month($i)->format('M');

            $monthRevenue = Commande::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $i)
                ->sum('total');

            /*$monthExpense = Expense::whereYear('created_at', $currentYear)
                ->whereMonth('created_at', $i)
                ->sum('amount');*/
            $monthExpense=0.0;
            $revenu[] = $monthRevenue;
            $depenses[] = $monthExpense;
        }

        $totalRevenu = array_sum($revenu);
        $totalDepense = array_sum($depenses);
        $balance = $totalRevenu - $totalDepense;

        return response()->json([
            "labels" => $months,

            "series" => [
                [
                    "name" => "Dépenses",
                    "data" => $depenses
                ],
                [
                    "name" => "Revenu",
                    "data" => $revenu
                ]
            ],

            "revenu" => $totalRevenu,
            "depense" => $totalDepense,
            "balance" => $balance
        ]);
    }


    public function lastOrders(Request $request)
    {
        $currentYear = Carbon::now()->year;

        $orders = Commande::with('customer')
            ->latest()
            ->take(5)
            ->get()
            ->map(function ($commande) use ($currentYear) {

                return [
                    'id' =>$commande->id,
                    'purchaseId' => '#TZ' . $commande->id,
                    'user' => $commande->customer?->image
                    ? asset('storage/'.$commande->customer->image)
                    : 'assets/images/users/avatar-1.jpg',

                'agentName' => $commande->customer?->name,

                'invoiceNumber' => 'IN-' . $commande->id,

                'purchaseDate' => Carbon::parse($commande->created_at)
                    ->format('d M, ') . $currentYear,

                'amount' => number_format($commande->total, 0, ',', ','),

                'paymentType' => $commande->payment_method ?? 'MTN',

                'paymentStatus' => $commande->stringStatus->value ?? 'Pending',
            ];
        });

        return Helpers::success($orders);
    }


    public function getStatistics()
    {
        $currentMonth = Carbon::now()->month;
        $currentYear = Carbon::now()->year;

        // Ventes du mois courant
        $ventes = Commande::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->sum('total');

        // Ventes du mois précédent
        $ventesPrev = Commande::whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth - 1)
            ->sum('total');

        // Calcul variation en %
        $ventesChange = $ventesPrev ? round((($ventes - $ventesPrev) / $ventesPrev) * 100, 2) : 0;

        // Clients ce mois-ci
        $clients = DB::table('users')
            ->where('user_type',User::CUSTOMER_TYPE)
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth)
            ->count();

        // Clients le mois précédent
        $clientsPrev = DB::table('users')
            ->where('user_type',User::CUSTOMER_TYPE)
            ->whereYear('created_at', $currentYear)
            ->whereMonth('created_at', $currentMonth - 1)
            ->count();

        $clientsChange = $clientsPrev ? round((($clients - $clientsPrev)/$clientsPrev) * 100, 2) : 0;

        // Revenue ce mois-ci (idem ventes mais on peut utiliser Commande::sum())
        $revenue = $ventes;
        $revenuePrev = $ventesPrev;
        $revenueChange = $ventesChange;

        return response()->json([
            'ventes' => $ventes,
            'ventesChange' => $ventesChange,
            'clients' => $clients,
            'clientsChange' => $clientsChange,
            'revenue' => $revenue,
            'revenueChange' => $revenueChange
        ]);
    }

    public function salesWeekChart()
    {
        $days = [];
        $revenu = [];
        $depenses = [];

        $startOfWeek = Carbon::now()->startOfWeek(); // Lundi
        $endOfWeek = Carbon::now()->endOfWeek();     // Dimanche

        for ($i = 0; $i < 7; $i++) {
            $day = $startOfWeek->copy()->addDays($i);
            $days[] = $day->format('D'); // Labels pour le chart : Mon, Tue, etc.

            // Somme des commandes pour ce jour
            $dayRevenue = Commande::whereDate('created_at', $day)->sum('total');
          //  $dayExpense = Expense::whereDate('created_at', $day)->sum('amount');
            $dayExpense=0.0;
            $revenu[] = $dayRevenue;
            $depenses[] = $dayExpense;
        }

        $totalRevenu = array_sum($revenu);
        $totalDepense = array_sum($depenses);
        $balance = $totalRevenu - $totalDepense;

        return response()->json([
            "labels" => $days,
            "series" => [
                [
                    "name" => "Dépenses",
                    "data" => $depenses
                ],
                [
                    "name" => "Revenu",
                    "data" => $revenu
                ]
            ],
            "revenu" => $totalRevenu,
            "depense" => $totalDepense,
            "balance" => $balance
        ]);
    }
}
