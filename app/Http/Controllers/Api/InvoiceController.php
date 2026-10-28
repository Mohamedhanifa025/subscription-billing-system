<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Invoice;
use App\Models\Subscription;
use App\Services\BillingService;
use Illuminate\Http\Request;

class InvoiceController extends Controller
{
    public function generate(
        Request $request,
        Subscription $subscription,
        BillingService $billing
    ) {
        $data = $request->validate([
            'period_start' => 'required|date',
            'period_end' => 'required|date|after:period_start',
        ]);

        return $billing->generate(
            $subscription,
            now()->parse($data['period_start']),
            now()->parse($data['period_end'])
        );
    }

    public function index(Subscription $subscription)
    {
        return Invoice::where('subscription_id', $subscription->id)
            ->with('items')
            ->paginate();
    }
}
