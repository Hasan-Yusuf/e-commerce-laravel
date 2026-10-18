<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Services\Order\OrderNumberService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderNumberTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_service_allocates_sequential_padded_numbers(): void
    {
        $service = app(OrderNumberService::class);

        static::assertSame('ORD-00000001', $service->next());
        static::assertSame('ORD-00000002', $service->next());
        static::assertSame('ORD-00000003', $service->next());
    }

    public function test_peek_shows_the_number_the_next_allocation_hands_out(): void
    {
        $service = app(OrderNumberService::class);

        // What a form shows before saving is what the order is then given...
        static::assertSame('ORD-00000001', $service->peek());
        static::assertSame('ORD-00000001', $service->next());

        // ...and showing it does not take it.
        static::assertSame('ORD-00000002', $service->peek());
        static::assertSame('ORD-00000002', $service->next());
    }

    /**
     * Two placements in the same transaction boundary must not be handed the
     * same number: the sequence row is locked before it is read back, so the
     * second allocation waits for the first.
     */
    public function test_two_orders_placed_in_the_same_transaction_get_distinct_numbers(): void
    {
        [$first, $second] = DB::transaction(
            static fn (): array => [
                Order::factory()->create(),
                Order::factory()->create(),
            ],
        );

        static::assertNotSame($first->number, $second->number);
        static::assertMatchesRegularExpression('/^ORD-\d{8}$/', $first->number);
        static::assertMatchesRegularExpression('/^ORD-\d{8}$/', $second->number);

        static::assertSame(
            (int) substr($first->number, strlen(OrderNumberService::PREFIX)) + 1,
            (int) substr($second->number, strlen(OrderNumberService::PREFIX)),
        );
    }

    public function test_an_order_placed_through_the_model_carries_a_sequence_number(): void
    {
        $order = Order::factory()->create();

        static::assertSame('ORD-00000001', $order->number);
    }

    /**
     * The row is seeded by a migration, but allocation must still answer when it
     * is missing: it checks for the absent row before reading a last number, and
     * takes the first number rather than tripping over the null.
     */
    public function test_it_allocates_the_first_number_when_the_sequence_row_is_missing(): void
    {
        DB::table('order_sequences')->delete();

        $service = app(OrderNumberService::class);

        static::assertSame('ORD-00000001', $service->next());
        static::assertSame('ORD-00000002', $service->next());
    }
}
