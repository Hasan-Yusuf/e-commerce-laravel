<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\Cart\CartService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('carts:prune')]
#[Description('Delete carts whose expiry has passed')]
class PruneExpiredCarts extends Command
{
    public function handle(CartService $cartService): int
    {
        $cartService->pruneExpired();

        return self::SUCCESS;
    }
}
