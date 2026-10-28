<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UsageRecorder;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UsageController extends Controller
{
    public function store(Request $request, UsageRecorder $recorder)
    {
        $data = $request->validate([
            'merchant_id'=>'required|integer|exists:merchants,id',
            'customer_id'=>'required|integer|exists:customers,id',
            'subscription_id'=>'required|integer|exists:subscriptions,id',
            'usage_date'=>'required|date',
            'quantity'=>'required|integer|min:1',
            'idempotency_key'=>'required|string|max:128',
            'metadata'=>'nullable|array'
        ]);
        // Tenant relationship validation prevents cross-merchant writes.
        $subscription = \App\Models\Subscription::findOrFail($data['subscription_id']);
        if ($subscription->merchant_id != $data['merchant_id'] || $subscription->customer_id != $data['customer_id']) {
            throw ValidationException::withMessages(['subscription_id'=>'Subscription/customer does not belong to merchant.']);
        }
        $result = $recorder->record($data);
        return response()->json(['data'=>$result['event'],'created'=>$result['created']], $result['created'] ? 201 : 200);
    }
}
