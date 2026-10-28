<?php
namespace Tests\Feature;
use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use App\Models\{Merchant,Customer,Plan,Subscription,SubscriptionSegment,UsageEvent};
class UsageIdempotencyTest extends TestCase {
    use RefreshDatabase;
    public function test_same_idempotency_key_is_not_counted_twice(): void {
        $m=Merchant::create(['name'=>'Acme']);$c=Customer::create(['merchant_id'=>$m->id,'name'=>'Demo','email'=>'demo@example.test']);
        $p=Plan::create(['merchant_id'=>$m->id,'name'=>'Basic','base_price_cents'=>1000,'billing_cycle'=>'monthly','included_units'=>100,'overage_rate_cents'=>10]);
        $s=Subscription::create(['merchant_id'=>$m->id,'customer_id'=>$c->id,'started_at'=>now(),'current_period_start'=>now(),'current_period_end'=>now()->addMonth(),'status'=>'active']);
        SubscriptionSegment::create(['subscription_id'=>$s->id,'plan_id'=>$p->id,'starts_at'=>now(),'base_price_cents'=>1000,'included_units'=>100,'overage_rate_cents'=>10,'billing_cycle'=>'monthly']);
        $payload=['merchant_id'=>$m->id,'customer_id'=>$c->id,'subscription_id'=>$s->id,'usage_date'=>now()->toDateString(),'quantity'=>5,'idempotency_key'=>'unique-test-key'];
        $this->postJson('/api/usage',$payload)->assertSuccessful();
        $this->postJson('/api/usage',$payload)->assertSuccessful();
        $this->assertSame(1,UsageEvent::count());
    }
}
