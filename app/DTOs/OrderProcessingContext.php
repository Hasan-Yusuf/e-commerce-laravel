<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\OrderStatus;
use App\Models\Address;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Collection;
use LogicException;

final readonly class OrderProcessingContext
{
    /**
     * @param  array<string, mixed>  $data  the action's own input, such as the fulfil modal's answers
     * @param  array<int, class-string>  $ranPipes  the pipes that have run, in order (see PipesRan)
     */
    public function __construct(
        public ?Order $order = null,
        public ?CheckoutState $checkout = null,
        public ?CartSummary $cart = null,
        public ?User $user = null,
        public ?string $note = null,
        public ?Address $address = null,
        public ?OrderStatus $targetStatus = null,
        public array $data = [],
        public ?OrderPlacementResult $result = null,
        public OrderStockState $stock = new OrderStockState,
        public array $ranPipes = [],
    ) {}

    public function withOrder(Order $order): self
    {
        return $this->copy(['order' => $order]);
    }

    /**
     * The shipping address the merchant already holds for this order, resolved
     * before the order is written — null when the order is collected in the shop
     * and ships nowhere.
     */
    public function withAddress(Address $address): self
    {
        return $this->copy(['address' => $address]);
    }

    /**
     * @param  Collection<int, Product>  $products
     */
    public function withLockedProducts(Collection $products): self
    {
        return $this->copy(['stock' => $this->stock->withLockedProducts($products)]);
    }

    /**
     * @param  Collection<int, int>  $productIds
     */
    public function withAffectedProductIds(Collection $productIds): self
    {
        return $this->copy(['stock' => $this->stock->withAffectedProductIds($productIds)]);
    }

    public function withResult(OrderPlacementResult $result): self
    {
        return $this->copy(['result' => $result]);
    }

    public function recordPipe(string $pipe): self
    {
        $ranPipes = [...$this->ranPipes, $pipe];

        return $this->copy(['ranPipes' => $ranPipes]);
    }

    public function orderOrFail(): Order
    {
        return
            $this->order ?? throw new LogicException(
                'The pipeline reached a pipe that needs an order before one exists.',
            );
    }

    public function cartOrFail(): CartSummary
    {
        return
            $this->cart ?? throw new LogicException(
                'The pipeline reached a pipe that needs a cart this run does not carry.',
            );
    }

    public function checkoutOrFail(): CheckoutState
    {
        return
            $this->checkout ?? throw new LogicException(
                'The pipeline reached a pipe that needs a checkout this run does not carry.',
            );
    }

    public function userOrFail(): User
    {
        return
            $this->user ?? throw new LogicException(
                'The pipeline reached a pipe that needs a shopper this run does not carry.',
            );
    }

    public function targetStatusOrFail(): OrderStatus
    {
        return
            $this->targetStatus ?? throw new LogicException(
                'The pipeline reached a pipe that needs a target status this run does not carry.',
            );
    }

    /**
     * A copy of this context with the given properties replaced — the one place
     * that has to know every property a copy carries.
     *
     * @param  array<string, mixed>  $changes
     */
    private function copy(array $changes): self
    {
        return new self(
            ...array_merge(
                [
                    'order' => $this->order,
                    'checkout' => $this->checkout,
                    'cart' => $this->cart,
                    'user' => $this->user,
                    'note' => $this->note,
                    'address' => $this->address,
                    'targetStatus' => $this->targetStatus,
                    'data' => $this->data,
                    'result' => $this->result,
                    'stock' => $this->stock,
                    'ranPipes' => $this->ranPipes,
                ],
                $changes,
            ),
        );
    }
}
