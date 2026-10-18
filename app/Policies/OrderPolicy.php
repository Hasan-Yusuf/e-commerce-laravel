<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    public function before(User $user, string $ability): ?bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return null;
    }

    /**
     * The order's own account.
     */
    public function view(User $user, Order $order): bool
    {
        return $this->owns($user, $order);
    }

    /**
     * The confirmation page. It is reachable as a guest, straight off the
     * checkout, so the token the browser was handed stands in for the account
     * that is not there yet.
     */
    public function viewConfirmation(
        ?User $user,
        Order $order,
        #[\SensitiveParameter]
        ?string $token,
    ): bool {
        if ($user !== null && $this->view($user, $order)) {
            return true;
        }

        return $this->holdsAccessToken($order, $token);
    }

    /**
     * Whether the token the request carried is this order's own.
     */
    private function holdsAccessToken(Order $order, #[\SensitiveParameter] ?string $token): bool
    {
        return
            $token !== null
            && $token !== ''
            && $order->access_token !== null
            && hash_equals($order->access_token, $token);
    }

    private function owns(User $user, Order $order): bool
    {
        return (int) $order->user_id === (int) $user->getKey();
    }
}
