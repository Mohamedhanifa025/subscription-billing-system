<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('usage_events', function (Blueprint $t) {
            $t->bigIncrements('id');
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $t->date('usage_date');
            $t->unsignedBigInteger('quantity');
            $t->string('idempotency_key', 128);
            $t->json('metadata')->nullable();
            $t->timestamps();
            $t->unique(['merchant_id', 'idempotency_key']);
            $t->index(['subscription_id', 'usage_date', 'id']);
            $t->index(['merchant_id', 'usage_date', 'customer_id']);
        });

        Schema::create('daily_usage_summaries', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $t->date('usage_date');
            $t->unsignedBigInteger('quantity')->default(0);
            $t->timestamps();
            $t->unique(['subscription_id', 'usage_date']);
            $t->index(['merchant_id', 'usage_date', 'quantity']);
        });

        Schema::create('invoices', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $t->foreignId('subscription_id')->constrained()->cascadeOnDelete();
            $t->dateTime('period_start');
            $t->dateTime('period_end');
            $t->unsignedBigInteger('subtotal_cents');
            $t->string('status')->default('draft');
            $t->timestamps();
            $t->unique(['subscription_id', 'period_start', 'period_end']);
        });

        Schema::create('invoice_items', function (Blueprint $t) {
            $t->id();
            $t->foreignId('invoice_id')->constrained()->cascadeOnDelete();
            $t->string('description');
            $t->unsignedBigInteger('quantity')->default(1);
            $t->unsignedBigInteger('unit_amount_cents');
            $t->unsignedBigInteger('amount_cents');
            $t->json('metadata')->nullable();
            $t->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('invoice_items');
        Schema::dropIfExists('invoices');
        Schema::dropIfExists('daily_usage_summaries');
        Schema::dropIfExists('usage_events');
    }
};
