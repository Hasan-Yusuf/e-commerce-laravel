<?php

declare(strict_types=1);

namespace App\Services\Order;

use App\DTOs\AppliedCoupon;
use App\DTOs\CartSummary;
use App\DTOs\CheckoutState;
use App\DTOs\OrderAmounts;
use App\DTOs\OrderPlacementResult;
use App\Models\Order;
use App\Models\User;
use App\Pipelines\Orders\OrderCanceller;
use App\Pipelines\Orders\OrderConfirmer;
use App\Pipelines\Orders\OrderFulfiller;
use App\Pipelines\Orders\OrderPlacer;
use App\Pipelines\Orders\OrderRefunder;
use App\Pipelines\Orders\OrderReinstater;
use App\Pipelines\Orders\OrderReserver;
use Illuminate\Support\Collection;

class OrderService
{
    public const array PER_PAGE_OPTIONS = [5, 10, 25, 50];

    public const int DEFAULT_PER_PAGE = 10;

    public const int RECENT_LIMIT = 5;

    /**
     * The operations an order goes through arrive as the pipelines that own
     * them, so each is a plain call rather than a lookup. The order pipelines
     * are stateless, and none of them depends on this service, so wiring them
     * here introduces no cycle.
     */
    public function __construct(
        private OrderPlacer $orderPlacer,
        private OrderConfirmer $orderConfirmer,
        private OrderFulfiller $orderFulfiller,
        private OrderCanceller $orderCanceller,
        private OrderRefunder $orderRefunder,
        private OrderReinstater $orderReinstater,
        private OrderReserver $orderReserver,
    ) {}

    public function confirmationUrl(Order $order): string
    {
        return route('checkout.confirmation', [
            'order' => $order->number,
            'token' => $order->access_token,
        ]);
    }

    public function perPage(?string $requested): int
    {
        $perPage = (int) $requested;

        return \in_array($perPage, self::PER_PAGE_OPTIONS, true)
            ? $perPage
            : self::DEFAULT_PER_PAGE;
    }

    /**
     * The account's newest orders, for the overview that sits in front of the
     * history. Each one arrives with its payment, so a row reads what it shows
     * without a query of its own.
     *
     * @return Collection<int, Order>
     */
    public function getRecentFor(User $user): Collection
    {
        return $user
            ->orders()
            ->with('payments')
            ->latest('id')
            ->limit(self::RECENT_LIMIT)
            ->get();
    }

    public function getOrderFrom(#[\SensitiveParameter] string $token, User $user): ?Order
    {
        if ($token === '') {
            return null;
        }

        return Order::query()
            ->where('user_id', $user->getKey())
            ->where('checkout_token', $token)
            ->first();
    }

    public function placeOrder(
        CheckoutState $state,
        CartSummary $cart,
        User $user,
        ?string $note = null,
    ): OrderPlacementResult {
        return ($this->orderPlacer)($state, $cart, $user, $note);
    }

    public function confirmOrder(Order $order): Order
    {
        return ($this->orderConfirmer)($order);
    }

    /**
     * The goods have left the shop. The modal's own answers — whether the
     * order shipped or was delivered, and when — arrive in `$data`.
     *
     * @param  array<string, mixed>  $data
     */
    public function fulfilOrder(Order $order, array $data = []): Order
    {
        return ($this->orderFulfiller)($order, $data);
    }

    public function cancelOrder(Order $order): Order
    {
        return ($this->orderCanceller)($order);
    }

    public function refundOrder(Order $order): Order
    {
        return ($this->orderRefunder)($order);
    }

    public function reinstateOrder(Order $order): Order
    {
        return ($this->orderReinstater)($order);
    }

    public function reserveOrder(Order $order): Order
    {
        return ($this->orderReserver)($order);
    }

    public function amounts(
        CartSummary $cart,
        ?AppliedCoupon $coupon = null,
        int|float|string|null $taxPercentage = null,
        int|float|string|null $shippingInFils = null,
    ): OrderAmounts {
        return OrderCalculator::forCart(
            $cart,
            $coupon,
            $taxPercentage,
            $shippingInFils,
        );
    }
}
