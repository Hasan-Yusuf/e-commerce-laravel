<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('order_sequences', function (Blueprint $table) {
            $table->id();

            // The last number handed out. Allocation locks this row, adds one
            // and reads it back, so concurrent placements cannot collide.
            $table->unsignedBigInteger('last_number')->default(0);

            $table->timestamps();
        });

        // Continue from the highest number already issued, so an existing
        // install does not re-issue a number it has printed on an order.
        $latest = DB::table('orders')->orderByDesc('id')->value('number');

        DB::table('order_sequences')->insert([
            'last_number' => is_string($latest)
                ? (int) substr($latest, strlen('ORD-'))
                : 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_sequences');
    }
};
