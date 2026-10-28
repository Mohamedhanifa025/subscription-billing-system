<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('subscriptions', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->dateTime('started_at');
            $t->dateTime('current_period_start');
            $t->dateTime('current_period_end');
            $t->string('status')->default('active');
            $t->timestamps();
            $t->index(['merchant_id', 'status', 'current_period_end']);
        });

        Schema::create('subscription_segments', function (Blueprint $t) {
            $t->id();
            $t->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $t->foreignId('plan_id')->constrained()->restrictOnDelete();
            $t->dateTime('starts_at');
            $t->dateTime('ends_at')->nullable();
            $t->unsignedBigInteger('base_price_cents');
            $t->unsignedBigInteger('included_units');
            $t->unsignedBigInteger('overage_rate_cents');
            $t->string('billing_cycle');
            $t->timestamps();
            $t->index(['subscription_id', 'starts_at', 'ends_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('subscription_segments');
        Schema::dropIfExists('subscriptions');
    }
};
