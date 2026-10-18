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
        Schema::create('payments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')->constrained()->cascadeOnDelete();

            // Enum-backed strings, cast on the model: the method the order was
            // placed with (cash on delivery in v1) and where it stands
            // (pending until the courier collects the money).
            $table->string('method');
            $table->string('status');

            $table->unsignedBigInteger('amount_in_fils');
            $table->timestamp('paid_at')->nullable();

            // Provider payload for future gateways (transaction id, response…);
            // the cash-on-delivery driver leaves it empty.
            $table->json('meta')->nullable();

            $table->timestamps();

            $table->index(['order_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
