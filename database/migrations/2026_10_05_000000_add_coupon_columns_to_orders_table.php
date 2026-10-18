<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Which coupon was applied to the order, and the code as it read at
            // the time — the snapshot survives the coupon being renamed or
            // deleted afterwards. The discount it produced is the order's own
            // discount_amount_in_fils.
            $table
                ->foreignId('coupon_id')
                ->nullable()
                ->after('billing_address_id')
                ->constrained('coupons')
                ->nullOnDelete();

            $table->string('coupon_code')->nullable()->after('coupon_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropConstrainedForeignId('coupon_id');
            $table->dropColumn('coupon_code');
        });
    }
};
