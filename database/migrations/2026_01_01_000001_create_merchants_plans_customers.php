<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('merchants', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('timezone')->default('UTC');
            $t->timestamps();
        });

        Schema::create('plans', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->unsignedBigInteger('base_price_cents');
            $t->enum('billing_cycle', ['monthly','yearly'])->default('monthly');
            $t->unsignedBigInteger('included_units')->default(0);
            $t->unsignedBigInteger('overage_rate_cents')->default(0);
            $t->unsignedInteger('version')->default(1);
            $t->timestamps();
            $t->index(['merchant_id','id']);
        });

        Schema::create('customers', function (Blueprint $t) {
            $t->id();
            $t->foreignId('merchant_id')->constrained()->cascadeOnDelete();
            $t->string('name');
            $t->string('email')->nullable();
            $t->timestamps();
            $t->unique(['merchant_id','email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
        Schema::dropIfExists('plans');
        Schema::dropIfExists('merchants');
    }
};
