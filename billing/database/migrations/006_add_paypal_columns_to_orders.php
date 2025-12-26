<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->string('payment_method')->default('stripe')->after('stripe_payment_id');
            $table->string('paypal_order_id')->nullable()->after('payment_method');
            $table->string('paypal_payer_id')->nullable()->after('paypal_order_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'paypal_order_id', 'paypal_payer_id']);
        });
    }
};
