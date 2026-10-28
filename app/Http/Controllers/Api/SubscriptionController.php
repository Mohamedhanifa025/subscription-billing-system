<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\{Plan, Subscription, SubscriptionSegment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SubscriptionController extends Controller
{
    public function index(int $merchant)
    {
        return Subscription::where('merchant_id', $merchant)
            ->with('segments')
            ->paginate();
    }

    public function store(Request $r, int $merchant)
    {
        $d = $r->validate([
            'customer_id' => 'required|exists:customers,id',
            'plan_id' => 'required|exists:plans,id',
            'started_at' => 'nullable|date',
        ]);

        $plan = Plan::where('merchant_id', $merchant)->findOrFail($d['plan_id']);
        $start = isset($d['started_at']) ? now()->parse($d['started_at']) : now();
        $end = $plan->billing_cycle === 'yearly'
            ? $start->copy()->addYear()
            : $start->copy()->addMonth();

        $s = DB::transaction(function () use ($d, $merchant, $plan, $start, $end) {
            $s = Subscription::create([
                'merchant_id' => $merchant,
                'customer_id' => $d['customer_id'],
                'started_at' => $start,
                'current_period_start' => $start,
                'current_period_end' => $end,
                'status' => 'active',
            ]);

            $this->segment($s, $plan, $start, null);

            return $s;
        });

        return response()->json($s->load('segments'), 201);
    }

    public function show(Subscription $subscription)
    {
        return $subscription->load('segments');
    }

    public function update(Request $r, Subscription $subscription)
    {
        $subscription->update($r->validate([
            'status' => 'sometimes|in:active,canceled,past_due',
        ]));

        return $subscription;
    }

    public function destroy(Subscription $subscription)
    {
        $subscription->update(['status' => 'canceled']);

        return response()->noContent();
    }

    public function changePlan(Request $r, Subscription $subscription)
    {
        $d = $r->validate([
            'plan_id' => 'required|exists:plans,id',
            'effective_at' => 'nullable|date',
        ]);

        $plan = Plan::where('merchant_id', $subscription->merchant_id)->findOrFail($d['plan_id']);
        $at = isset($d['effective_at']) ? now()->parse($d['effective_at']) : now();

        DB::transaction(function () use ($subscription, $plan, $at) {
            $current = $subscription->segments()
                ->whereNull('ends_at')
                ->lockForUpdate()
                ->firstOrFail();

            if ($at <= $current->starts_at) {
                abort(422, 'Effective date must follow current segment start.');
            }

            $current->update(['ends_at' => $at]);
            $this->segment($subscription, $plan, $at, null);
        });

        return $subscription->load('segments');
    }

    private function segment($s, $p, $start, $end)
    {
        return SubscriptionSegment::create([
            'subscription_id' => $s->id,
            'plan_id' => $p->id,
            'starts_at' => $start,
            'ends_at' => $end,
            'base_price_cents' => $p->base_price_cents,
            'included_units' => $p->included_units,
            'overage_rate_cents' => $p->overage_rate_cents,
            'billing_cycle' => $p->billing_cycle,
        ]);
    }
}
