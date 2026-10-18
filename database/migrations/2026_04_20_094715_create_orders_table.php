<?php

use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('number', 50)->unique();

            $table->string('status')->default(OrderStatus::PENDING);

            $table->unsignedInteger('subtotal_in_fils');
            $table->unsignedInteger('tax_amount_in_fils')->default(0);
            $table->unsignedInteger('shipping_amount_in_fils')->default(0);
            $table->unsignedInteger('discount_amount_in_fils')->default(0);
            $table->unsignedInteger('total_amount_in_fils')->default(0);

            $table
                ->foreignId('shipping_address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();
            $table
                ->foreignId('billing_address_id')
                ->nullable()
                ->constrained('addresses')
                ->nullOnDelete();

            $table->string('delivery_method')
                ->default(DeliveryMethod::STANDARD);

            $table->text('notes')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('delivered_at')->nullable();

            // The checkout attempt that placed the order: the value is unique, so
            // a resubmitted checkout finds its order instead of placing another.
            $table->uuid('checkout_token')->nullable()->unique();

            // The bearer token the guest-visible confirmation URL carries. Unique
            // as well, because it is what stands in for an account on that page.
            $table->uuid('access_token')->nullable()->unique();

            $table->timestamps();

            $table->softDeletes();

            $table->index(['user_id', 'status', 'number']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
