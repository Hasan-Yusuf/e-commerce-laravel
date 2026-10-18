<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockWarningNotification;
use Illuminate\Foundation\Testing\LazilyRefreshDatabase;
use Tests\TestCase;

/**
 * The low stock warning as the panel receives it: it is a running-out-of-stock
 * alarm, so it stays on screen until the admin answers it instead of timing out
 * like a "saved" toast.
 */
class LowStockWarningNotificationTest extends TestCase
{
    use LazilyRefreshDatabase;

    public function test_the_panel_notification_waits_to_be_acknowledged(): void
    {
        $product = Product::factory()->create(['name' => 'Titan Workstation']);

        $payload = (new LowStockWarningNotification($product))->toDatabase(User::factory()->create());

        static::assertSame('filament', $payload['format']);
        static::assertSame('warning', $payload['status']);

        // Persistent: the notification itself is what closes it.
        static::assertSame('persistent', $payload['duration']);

        static::assertStringContainsString('Titan Workstation', $payload['body']);

        // Acknowledging it is what marks it read.
        static::assertSame('acknowledge', $payload['actions'][0]['name']);
        static::assertSame('Acknowledge', $payload['actions'][0]['label']);
        static::assertTrue($payload['actions'][0]['shouldMarkAsRead']);
    }

    public function test_the_panel_notification_names_every_product_it_covers(): void
    {
        $products = collect([
            Product::factory()->create(['name' => 'First']),
            Product::factory()->create(['name' => 'Second']),
        ]);

        $payload = (new LowStockWarningNotification($products->all()))->toDatabase(User::factory()->create());

        static::assertStringContainsString('First', $payload['body']);
        static::assertStringContainsString('Second', $payload['body']);
    }
}
