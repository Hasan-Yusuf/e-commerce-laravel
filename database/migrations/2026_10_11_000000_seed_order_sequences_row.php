<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guarantee the single `order_sequences` row exists, so allocation never has
     * to create it under a lock it cannot take: the row is what serialises
     * concurrent placements. The row is normally seeded when the table is
     * created; this covers an install where it was removed.
     */
    public function up(): void
    {
        if (! Schema::hasTable('order_sequences')) {
            return;
        }

        if (DB::table('order_sequences')->exists()) {
            return;
        }

        DB::table('order_sequences')->insert([
            'last_number' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Nothing to undo: the row is a fixture the sequence needs to exist, not
     * data this migration owns.
     */
    public function down(): void {}
};
