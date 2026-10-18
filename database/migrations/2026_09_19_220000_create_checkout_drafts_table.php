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
        Schema::create('checkout_drafts', function (Blueprint $table) {
            $table->id();

            // One draft per account: a shopper has one checkout in flight, and
            // it is theirs alone, so it goes when the account goes.
            $table->foreignId('user_id')->unique()->constrained()->cascadeOnDelete();

            // The state as the checkout holds it (the shape CheckoutState
            // writes), so a stored draft can be picked up verbatim.
            $table->json('state');

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('checkout_drafts');
    }
};
