<?php

namespace App\Services;

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Subscription;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class BillingService
{
    /**
     * Segment billing uses actual active seconds / full billing-period seconds for base proration.
     * Usage is allocated to the segment whose [starts_at, ends_at) contains the event date.
     * Amounts are integer cents; overage units are max(usage - allowance, 0).
     */
    public function generate(Subscription $subscription, Carbon $start, Carbon $end): Invoice
    {
        return DB::transaction(function () use ($subscription, $start, $end) {
            $invoice = Invoice::firstOrCreate([
                'subscription_id' => $subscription->id,
                'period_start' => $start,
                'period_end' => $end,
            ], [
                'merchant_id' => $subscription->merchant_id,
                'customer_id' => $subscription->customer_id,
                'subtotal_cents' => 0,
                'status' => 'draft',
            ]);

            if (!$invoice->wasRecentlyCreated) {
                return $invoice->load('items');
            }

            $subtotal = 0;
            foreach (
                $subscription->segments()
                    ->where('starts_at', '<', $end)
                    ->where(fn ($query) => $query->whereNull('ends_at')->orWhere('ends_at', '>', $start))
                    ->get() as $segment
            ) {
                $segStart = Carbon::parse($segment->starts_at)->max($start);
                $segEnd = ($segment->ends_at ? Carbon::parse($segment->ends_at) : $end)
                    ->min($end);

                if ($segEnd <= $segStart) {
                    continue;
                }

                $cycleSeconds = $segment->billing_cycle === 'yearly'
                    ? 365 * 86400
                    : 30 * 86400;
                $activeRatio = min(1, $segStart->diffInSeconds($segEnd) / $cycleSeconds);
                $base = (int) round($segment->base_price_cents * $activeRatio);
                $usage = DB::table('daily_usage_summaries')
                    ->where('subscription_id', $subscription->id)
                    ->whereBetween('usage_date', [
                        $segStart->toDateString(),
                        $segEnd->copy()->subSecond()->toDateString(),
                    ])
                    ->sum('quantity');
                $overUnits = max(0, (int)$usage - (int)$segment->included_units);
                $overage = $overUnits * (int)$segment->overage_rate_cents;

                if ($base) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Prorated plan base',
                        'quantity' => 1,
                        'unit_amount_cents' => $base,
                        'amount_cents' => $base,
                        'metadata' => ['segment_id' => $segment->id],
                    ]);
                }

                if ($overage) {
                    InvoiceItem::create([
                        'invoice_id' => $invoice->id,
                        'description' => 'Usage overage',
                        'quantity' => $overUnits,
                        'unit_amount_cents' => $segment->overage_rate_cents,
                        'amount_cents' => $overage,
                        'metadata' => [
                            'usage' => $usage,
                            'included_units' => $segment->included_units,
                            'segment_id' => $segment->id,
                        ],
                    ]);
                }

                $subtotal += $base + $overage;
            }

            $invoice->update(['subtotal_cents' => $subtotal]);

            return $invoice->load('items');
        });
    }
}
