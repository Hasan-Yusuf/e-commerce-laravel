<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\User;
use App\Services\Order\OrderService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

/**
 * The orders a shopping session has placed: the confirmation a checkout lands
 * on, the list of the account's orders, and one of them in detail.
 *
 * The confirmation is not the account's — a guest walks the checkout and gets
 * the order's access token back with the URL — so the OrderPolicy owns both
 * cases: it is asked with the token here, and on its own for the account-scoped
 * pages.
 */
final class OrderController extends Controller
{
    public function __construct(
        private OrderService $orders,
    ) {}

    public function confirmation(Request $request, Order $order): View
    {
        $token = $request->query('token');

        Gate::authorize('viewConfirmation', [
            $order,
            is_string($token) ? $token : null,
        ]);

        return view('checkout.confirmation', [
            'order' => $this->order($order),
        ]);
    }

    /**
     * The orders the signed-in shopper has placed, newest first. How many of
     * them a page holds is the shopper's own choice on the page itself.
     */
    public function index(Request $request): View
    {
        /** @var User $user */
        $user = $request->user();

        $requested = $request->query('per_page');
        $perPage = $this->orders->perPage(
            is_string($requested) ? $requested : null,
        );

        return view('orders.index', [
            'orders' => $user
                ->orders()
                ->with('payments')
                ->latest('id')
                ->paginate($perPage)
                // The page size travels with the page links, so moving through
                // the history keeps the shopper's choice.
                ->withQueryString(),
            'perPage' => $perPage,
        ]);
    }

    public function show(Order $order): View
    {
        Gate::authorize('view', $order);

        return view('orders.show', ['order' => $this->order($order)]);
    }

    /**
     * An order with everything its summary prints, in one read.
     */
    private function order(Order $order): Order
    {
        return $order->loadMissing([
            'orderItems',
            'payments',
            'shippingAddress.country',
        ]);
    }
}
