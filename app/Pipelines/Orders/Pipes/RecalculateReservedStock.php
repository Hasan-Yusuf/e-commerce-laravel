<?php

declare(strict_types=1);

namespace App\Pipelines\Orders\Pipes;

use App\DTOs\OrderProcessingContext;
use App\Enums\OrderStatus;
use App\Models\OrderItem;
use App\Models\Product;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Derive the hold from the lines that are still open, rather than moving it by
 * a delta: recomputing can never drift, so running the same transition twice —
 * or reading it after any other write — lands on the same figure instead of
 * applying a second increment.
 *
 * Only the products on this order are recomputed, and the on-hand `stock` is
 * not touched here — that moves in AdjustOnHandStock. The products this run
 * touches are left in the context, for the last pipe of the chain to report
 * on once every stock write is done.
 */
class RecalculateReservedStock
{
    /**
     * The statuses whose lines still hold stock: an order that is still being
     * prepared, rather than one that has left the shop or been called off.
     */
    private const array OPEN_STATUSES = [
        OrderStatus::PENDING,
        OrderStatus::PROCESSING,
    ];

    public function handle(
        OrderProcessingContext $context,
        Closure $next,
    ): OrderProcessingContext {
        /** @var Collection<int, int> $productIds */
        $productIds = $context
            ->orderOrFail()
            ->orderItems()
            ->whereNotNull('product_id')
            ->pluck('product_id')
            ->unique()
            ->values();

        if ($productIds->isEmpty()) {
            return $next($context->recordPipe(self::class));
        }

        $reserved = $this->reservedQuantityFor($productIds);

        foreach ($productIds as $productId) {
            Product::query()
                ->whereKey($productId)
                ->update([
                    'reserved_stock' => (int) ($reserved[$productId] ?? 0),
                ]);
        }

        return $next(
            $context->withAffectedProductIds($productIds)->recordPipe(self::class),
        );
    }

    /**
     * Each product's hold, in one query: the quantities of its open lines,
     * summed. A product with no open lines is absent from the result, which
     * reads as the zero its reservation is set to.
     *
     * @param  Collection<int, int>  $productIds
     * @return Collection<int, int>
     */
    private function reservedQuantityFor(Collection $productIds): Collection
    {
        return OrderItem::query()
            ->whereIn('product_id', $productIds)
            ->whereHas(
                'order',
                static fn (Builder $orders): Builder => $orders->whereIn(
                    'status',
                    self::OPEN_STATUSES,
                ),
            )
            ->groupBy('product_id')
            ->select('product_id')
            ->selectExpression('SUM(quantity)', 'reserved')
            ->pluck('reserved', 'product_id');
    }
}
