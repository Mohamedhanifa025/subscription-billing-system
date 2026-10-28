<?php
namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class MerchantDashboardController extends Controller
{
    public function show(int $merchant)
    {
        $now = now(); $monthStart = $now->copy()->startOfMonth()->toDateString();
        $top = DB::table('daily_usage_summaries')->join('customers','customers.id','=','daily_usage_summaries.customer_id')
            ->where('daily_usage_summaries.merchant_id',$merchant)->where('usage_date','>=',$monthStart)
            ->select('customers.id','customers.name',DB::raw('SUM(quantity) as usage'))
            ->groupBy('customers.id','customers.name')->orderByDesc('usage')->limit(5)->get();

        $projection = DB::table('subscription_segments')->join('subscriptions','subscriptions.id','=','subscription_segments.subscription_id')
            ->leftJoin('daily_usage_summaries','daily_usage_summaries.subscription_id','=','subscriptions.id')
            ->where('subscriptions.merchant_id',$merchant)->where('subscriptions.status','active')
            ->whereNull('subscription_segments.ends_at')
            ->selectRaw('SUM(GREATEST(COALESCE(daily_usage_summaries.quantity,0)-subscription_segments.included_units,0)*subscription_segments.overage_rate_cents) as cents')
            ->value('cents') ?? 0;

        $thisMonth = DB::table('daily_usage_summaries')->where('merchant_id',$merchant)->where('usage_date','>=',$now->copy()->startOfMonth()->toDateString())->where('usage_date','<=',$now->toDateString())->select('customer_id',DB::raw('SUM(quantity) as total'))->groupBy('customer_id');
        $lastMonth = DB::table('daily_usage_summaries')->where('merchant_id',$merchant)->whereBetween('usage_date',[$now->copy()->subMonthNoOverflow()->startOfMonth()->toDateString(),$now->copy()->subMonthNoOverflow()->endOfMonth()->toDateString()])->select('customer_id',DB::raw('SUM(quantity) as total'))->groupBy('customer_id');
        $risk = DB::query()->fromSub($lastMonth,'lm')->joinSub($thisMonth,'tm','tm.customer_id','=','lm.customer_id')
            ->join('customers','customers.id','=','lm.customer_id')->whereRaw('lm.total > 0 AND tm.total < lm.total * 0.5')
            ->select('customers.id','customers.name','lm.total as previous_usage','tm.total as current_usage')->get();

        $trend = DB::table('daily_usage_summaries')->where('merchant_id',$merchant)->where('usage_date','>=',$now->copy()->subDays(29)->toDateString())
            ->select('usage_date',DB::raw('SUM(quantity) as quantity'))->groupBy('usage_date')->orderBy('usage_date')->get();

        return response()->json(['top_customers'=>$top,'projected_overage_revenue_cents'=>(int)$projection,
            'churn_risk'=>$risk,'daily_trend'=>$trend,'system_status'=>['plan_cache'=>'Redis TTL 10m','aggregation'=>'queued/chunked','usage_endpoint'=>'rate-limited']]);
    }
}
