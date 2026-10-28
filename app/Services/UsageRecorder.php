<?php

namespace App\Services;

use App\Jobs\AggregateDailyUsage;
use App\Models\UsageEvent;
use Illuminate\Support\Facades\DB;

class UsageRecorder
{
    public function record(array $data): array
    {
        $event = DB::transaction(function () use ($data) {
            $row = UsageEvent::firstOrCreate(
                [
                    'merchant_id' => $data['merchant_id'],
                    'idempotency_key' => $data['idempotency_key'],
                ],
                [
                    'customer_id' => $data['customer_id'],
                    'subscription_id' => $data['subscription_id'],
                    'usage_date' => $data['usage_date'],
                    'quantity' => $data['quantity'],
                    'metadata' => $data['metadata'] ?? null,
                ]
            );

            return [$row, $row->wasRecentlyCreated];
        });

        if ($event[1]) {
            AggregateDailyUsage::dispatch(
                $event[0]->subscription_id,
                $event[0]->usage_date->toDateString()
            );
        }

        return ['event' => $event[0], 'created' => $event[1]];
    }
}
