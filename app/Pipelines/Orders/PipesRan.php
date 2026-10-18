<?php

declare(strict_types=1);

namespace App\Pipelines\Orders;

use App\DTOs\OrderProcessingContext;
use LogicException;

/**
 * Guards against a pipe that forgot to call `$next()`.
 *
 * Every pipe notes itself in the context as it runs, so a chain that stopped
 * short is visible: the pipes that were expected but never ran are missing
 * from the list. A chain is only allowed to stop before its last pipe when a
 * pipe said so on purpose — VerifyStock does, when the stock moved under the
 * shopper — and it may only stop at a pipe boundary, as a prefix of the chain
 * it was given. Anything else is a pipe that returned without handing the
 * context on, which the processor refuses to accept rather than silently
 * reporting a transition that never happened.
 */
final class PipesRan
{
    /**
     * @param  array<int, class-string>  $pipes
     *
     * @throws LogicException when a pipe the chain expected never ran
     */
    public static function ensure(
        OrderProcessingContext $context,
        array $pipes,
    ): void {
        if ($context->ranPipes === $pipes) {
            return;
        }

        if (
            $context->result?->stockChanged === true
            && $context->ranPipes === \array_slice($pipes, 0, \count($context->ranPipes))
        ) {
            return;
        }

        throw new LogicException(sprintf(
            'The order pipeline stopped before it was done: expected [%s] but ran [%s].',
            implode(', ', $pipes),
            $context->ranPipes === []
                ? 'no pipe'
                : implode(', ', $context->ranPipes),
        ));
    }
}
