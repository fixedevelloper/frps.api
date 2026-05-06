<?php


namespace App\Http\Controllers;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    public function success(Request $request)
    {
        // Si tu passes des infos en query params
        $reference = $request->query('reference');

        return view('payment.success', compact('reference'));
    }

    public function failed(Request $request)
    {
        $reference = $request->query('reference');

        return view('payment.failed', compact('reference'));
    }
}
