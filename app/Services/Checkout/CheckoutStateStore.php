<?php

declare(strict_types=1);

namespace App\Services\Checkout;

use App\DTOs\CheckoutState;
use App\Models\CheckoutDraft;
use App\Services\Cart\CartService;
use Illuminate\Support\Facades\Auth;

/**
 * Keeps the checkout state: the session is the working copy, and a signed-in
 * shopper's is mirrored onto their account so it is still there days later,
 * when the session that started it has expired.
 *
 * The state itself is a CheckoutState; this class is only responsible for where
 * it is kept.
 */
class CheckoutStateStore
{
    /**
     * How long a stored draft stays worth restoring. It is only useful while
     * the cart it was written for can still be resolved, so it follows the
     * cart's own lifetime for a signed-in shopper.
     */
    public const int STORED_LIFETIME_DAYS = CartService::USER_LIFETIME_DAYS;

    private const string SESSION_KEY = 'checkout';

    /**
     * The state as the shopper left it: the session's copy, or the draft on
     * their account when the session that started it has expired. A shopper
     * with neither gets a blank flow.
     */
    public function get(): CheckoutState
    {
        return CheckoutState::fromArray(
            session()->get(self::SESSION_KEY) ?? $this->draft(),
        );
    }

    /**
     * Store the state, in the session and — for a signed-in shopper — on their
     * account.
     */
    public function put(CheckoutState $state): void
    {
        $payload = $state->toArray();

        session()->put(self::SESSION_KEY, $payload);

        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        CheckoutDraft::query()->updateOrCreate(
            ['user_id' => $userId],
            ['state' => $payload],
        );
    }

    /**
     * Drop the state: the session's copy and, for a signed-in shopper, the
     * draft on their account.
     */
    public function forget(): void
    {
        session()->forget(self::SESSION_KEY);

        $userId = Auth::id();

        if ($userId === null) {
            return;
        }

        CheckoutDraft::query()->where('user_id', $userId)->delete();
    }

    /**
     * The draft a signed-in shopper left behind, if it is still recent enough
     * to belong to the cart it was written for.
     *
     * @return array<string, mixed>
     */
    private function draft(): array
    {
        $userId = Auth::id();

        if ($userId === null) {
            return [];
        }

        $draft = CheckoutDraft::query()
            ->where('user_id', $userId)
            ->where(
                'updated_at',
                '>=',
                now()->subDays(self::STORED_LIFETIME_DAYS),
            )
            ->first();

        $state = $draft?->state;

        return is_array($state) ? $state : [];
    }
}
