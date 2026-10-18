<?php

use App\Enums\DiscountType;
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
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();

            // Stored lowercase so a lookup is an indexed equality rather than a
            // LOWER() scan; read back uppercased (see Coupon::code).
            $table->string('code', 50);
            $table->string('description')->nullable();
            $table->string('discount_type')->default(DiscountType::FIXED_AMOUNT);

            // A share is not money: a percentage coupon keeps the percentage it
            // was entered as, a fixed one the amount off in fils.
            $table->decimal('discount_percentage', 5, 2)->nullable();
            $table->integer('discount_value_in_fils')->nullable();

            // Money is canonical in fils (1000 fils = 1 BHD), the same unit as
            // the cart subtotal it is compared against.
            $table->integer('maximum_discount_in_fils')->nullable();
            $table->integer('min_order_amount')->default(0);

            // A null limit means unlimited uses.
            $table->integer('usage_limit')->nullable();
            $table->integer('used_count')->default(0);
            $table->timestamp('activated_at')->nullable();
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['code']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('coupons');
    }
};
