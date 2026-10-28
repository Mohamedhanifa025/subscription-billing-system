<?php
namespace App\Jobs;

use App\Models\UsageEvent;
use App\Models\DailyUsageSummary;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\DB;

class AggregateDailyUsage implements ShouldQueue
{
    use Queueable;
    public int $tries = 5;
    public function __construct(public int $subscriptionId, public string $date) {}
    public function handle(): void
    {
        // Recompute (rather than increment) so retries and duplicate queue delivery remain safe.
        $sum = UsageEvent::where('subscription_id',$this->subscriptionId)->whereDate('usage_date',$this->date)->sum('quantity');
        $first = UsageEvent::where('subscription_id',$this->subscriptionId)->whereDate('usage_date',$this->date)->first();
        if (!$first) return;
        DailyUsageSummary::updateOrCreate(
            ['subscription_id'=>$this->subscriptionId,'usage_date'=>$this->date],
            ['merchant_id'=>$first->merchant_id,'customer_id'=>$first->customer_id,'quantity'=>$sum]
        );
    }
}
