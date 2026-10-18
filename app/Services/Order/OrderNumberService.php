<?php

declare(strict_types=1);

namespace App\Services\Order;

use Illuminate\Support\Facades\DB;

/**
 * Allocates the storefront's sequential order numbers.
 *
 * The number comes from a single `order_sequences` row that is locked for the
 * rest of the transaction and incremented before it is read back, so two
 * placements running at once cannot be handed the same number. The shape —
 * `ORD-` plus eight digits — is fixed here because the confirmation URL and
 * the exports print the number verbatim.
 */
class OrderNumberService
{
    public const string PREFIX = 'ORD-';

    public const int PADDING = 8;

    /**
     * The next order number, allocated atomically. The sequence row is seeded by
     * a migration, so the empty-table branch is only a safety net for an install
     * whose row was removed; it still checks for null first, before reading the
     * row's last number.
     */
    public function next(): string
    {
        return DB::transaction(static function (): string {
            $sequence = DB::table('order_sequences')
                ->orderBy('id')
                ->lockForUpdate()
                ->first();

            if ($sequence === null) {
                DB::table('order_sequences')->insert([
                    'last_number' => 1,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);

                return self::format(1);
            }

            $next = (int) $sequence->last_number + 1;

            DB::table('order_sequences')
                ->where('id', $sequence->id)
                ->update(['last_number' => $next, 'updated_at' => now()]);

            return self::format($next);
        });
    }

    /**
     * The number the next allocation will hand out, without taking it: a form
     * can show the administrator what the order is about to be called, while
     * the allocation itself still happens on save.
     */
    public function peek(): string
    {
        $lastNumber = (int) DB::table('order_sequences')
            ->orderBy('id')
            ->value('last_number');

        return self::format($lastNumber + 1);
    }

    /**
     * The number for a given sequence value, padded to the fixed shape.
     */
    public static function format(int $sequence): string
    {
        return self::PREFIX
        .str_pad(
            (string) $sequence,
            self::PADDING,
            '0',
            STR_PAD_LEFT,
        );
    }
}
