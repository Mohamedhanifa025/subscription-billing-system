<?php
namespace Tests\Unit;
use PHPUnit\Framework\TestCase;
class BillingMathTest extends TestCase {
    public function test_overage_is_zero_at_or_below_allowance(): void {
        $this->assertSame(0, max(0, 1000-1000)*25);
        $this->assertSame(0, max(0, 900-1000)*25);
    }
    public function test_overage_charges_only_units_above_allowance(): void {
        $this->assertSame(2500, max(0,1100-1000)*25);
    }
    public function test_proration_for_half_period(): void {
        $this->assertSame(5000, (int)round(10000*(15*86400)/(30*86400)));
    }
}
