<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\DTOs\OrderProcessingContext;
use App\Enums\OrderStatus;
use App\Models\Order;
use Illuminate\Support\Facades\Pipeline;

/**
 * The shape every order pipeline has: the pipes it runs, and how they are run.
 *
 * Each operation an order goes through is a subclass — placing, confirming,
 * fulfilling, cancelling, refunding, reinstating, reserving — contributing only
 * its own list of pipes and its own `__invoke()`. The chain runs inside one
 * transaction, so the status (or the order row itself) and the stock it implies
 * commit or roll back together; and it may not stop before its last pipe unless
 * a pipe said so on purpose, which is what PipesRan enforces.
 *
 * Subclasses are stateless and resolved per call; App\Services\Order\OrderService is
 * the way in to them.
 */
abstract class OrderPipeline
{
    /**
     * The pipes this pipeline runs, oldest first. Each subclass names its own.
     *
     * @var array<int, class-string>
     */
    protected const array PIPES = [];

    /**
     * Run the chain this pipeline declares inside one transaction.
     */
    protected function run(
        OrderProcessingContext $context,
    ): OrderProcessingContext {
        $context = Pipeline::send($context)
            ->through(static::PIPES)
            ->withinTransaction()
            ->thenReturn();

        /** @var OrderProcessingContext $context */
        PipesRan::ensure($context, static::PIPES);

        return $context;
    }

    /**
     * Move an order to a status, unless it is not in a state to go there.
     *
     * An illegal move is a no-op rather than an error: the panel never offers
     * one, and the second half of a double click arrives after the first has
     * already moved the order.
     *
     * @param  array<string, mixed>  $data
     */
    protected function changeStatus(
        Order $order,
        OrderStatus $target,
        array $data = [],
    ): Order {
        if (! $order->status->canTransitionTo($target)) {
            return $order;
        }

        return $this->run(
            new OrderProcessingContext(
                order: $order->loadMissing('orderItems'),
                targetStatus: $target,
                data: $data,
            ),
        )->orderOrFail();
    }
}
