<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class PlanController extends Controller
{
    public function index(int $merchant)
    {
        return Plan::where('merchant_id', $merchant)->get();
    }

    public function store(Request $r, int $merchant)
    {
        $d = $r->validate([
            'name' => 'required|string',
            'base_price_cents' => 'required|integer|min:0',
            'billing_cycle' => 'required|in:monthly,yearly',
            'included_units' => 'required|integer|min:0',
            'overage_rate_cents' => 'required|integer|min:0',
        ]);

        $p = Plan::create($d + ['merchant_id' => $merchant]);
        Cache::tags(['plans'])->flush();

        return response()->json($p, 201);
    }

    public function show(Plan $plan)
    {
        return $plan;
    }

    public function update(Request $r, Plan $plan)
    {
        $plan->update($r->validate([
            'name' => 'sometimes|string',
            'base_price_cents' => 'sometimes|integer|min:0',
            'billing_cycle' => 'sometimes|in:monthly,yearly',
            'included_units' => 'sometimes|integer|min:0',
            'overage_rate_cents' => 'sometimes|integer|min:0',
        ]));

        $plan->increment('version');
        Cache::tags(['plans'])->flush();

        return $plan;
    }

    public function destroy(Plan $plan)
    {
        $plan->delete();
        Cache::tags(['plans'])->flush();

        return response()->noContent();
    }
}
