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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            // Nullable to match nullOnDelete: removing an order or a product
            // detaches the line rather than failing, keeping the snapshot.
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();

            $table->string('product_name');

            $table->string('sku');
            $table->unsignedInteger('quantity');

            $table->unsignedInteger('unit_price_in_fils');
            $table->unsignedInteger('total_price_in_fils');

            $table->softDeletes();

            $table->timestamps();

            $table->index(['order_id', 'product_id', 'sku']);
            $table->index('product_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
