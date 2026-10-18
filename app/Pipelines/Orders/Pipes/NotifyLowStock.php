<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\OrderProcessingContext;
use App\Enums\UserRole;
use App\Models\Product;
use App\Models\User;
use App\Notifications\LowStockWarningNotification;
use Closure;
use Illuminate\Support\Facades\Notification;

/**
 * The one place a low stock alert is raised.
 *
 * It is the last pipe of every chain that can drain a product, so it reads the
 * stock the earlier pipes have finished writing rather than the figure they
 * started from: a product is reported when the shelf it is left with — stock
 * minus the hold — is at or below its threshold.
 */
class NotifyLowStock
{
    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        $products = Product::query()
            ->whereKey($context->stock->affectedProductIds)
            ->get()
            ->filter(static fn (Product $product): bool => $product->isLowOnStock())
            ->values();

        if ($products->isNotEmpty()) {
            Notification::send(
                User::query()->where('role', UserRole::ADMIN)->get(),
                new LowStockWarningNotification($products->all()),
            );
        }

        return $next($context->recordPipe(self::class));
    }
}
