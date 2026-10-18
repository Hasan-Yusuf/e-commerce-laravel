<?php

declare(strict_types=1);

namespace App\DTOs;

use App\Enums\CheckoutStep;
use App\Enums\DeliveryMethod;

/**
 * The steps a checkout asks for, worked out from how the order reaches the
 * shopper: collecting it from the shop lifts the address step, since there is
 * nowhere to deliver it to.
 *
 * Everything about the flow's ordering — the steps it requires, the steps it
 * shows, the number each prints and the step that follows or precedes another —
 * is read from here, so the state itself only has to carry the answers and how
 * far the shopper has got.
 */
final readonly class CheckoutStepPlan
{
    /**
     * The steps the checkout requires, in order.
     *
     * @var array<int, CheckoutStep>
     */
    public array $required;

    /**
     * The steps the shopper is shown: the required ones, then the review.
     *
     * @var array<int, CheckoutStep>
     */
    public array $visible;

    public function __construct(
        public DeliveryMethod $deliveryMethod,
    ) {
        $this->required = array_values(
            array_filter(
                CheckoutStep::formSteps(),
                static fn (CheckoutStep $step): bool => $step !== CheckoutStep::SHIPPING_ADDRESS
                || ! $deliveryMethod->is(DeliveryMethod::PICKUP),
            ),
        );

        $this->visible = [...$this->required, CheckoutStep::REVIEW];
    }

    /**
     * The number a step prints, 1-based, or null when the flow does not ask for
     * it. The count is of the steps shown, so a lifted step leaves no gap: the
     * ones after it count down.
     */
    public function number(CheckoutStep $step): ?int
    {
        $order = array_search($step, $this->visible, true);

        return $order === false ? null : $order + 1;
    }

    /**
     * Where a step falls among the steps the flow requires, 1-based, or null
     * when it is not asked for.
     */
    public function order(CheckoutStep $step): ?int
    {
        $order = array_search($step, $this->required, true);

        return $order === false ? null : $order + 1;
    }

    /**
     * The step that follows $step: the next one the flow requires, or the
     * review at the end.
     */
    public function after(CheckoutStep $step): CheckoutStep
    {
        $order = $this->order($step);

        return $order === null
            ? CheckoutStep::REVIEW
            : $this->required[$order] ?? CheckoutStep::REVIEW;
    }

    /**
     * The step before $step — what its Back button returns to — or null at the
     * start of the flow.
     */
    public function before(CheckoutStep $step): ?CheckoutStep
    {
        $order = $this->order($step);

        return $order === null ? null : $this->required[$order - 2] ?? null;
    }

    /**
     * Whether a step has been answered, given how many of the flow's steps have
     * been: the count is 1-based, so a step answered when the count reached its
     * position is answered.
     */
    public function hasAnswered(CheckoutStep $step, int $progress): bool
    {
        $order = $this->order($step);

        return $order !== null && $order <= $progress;
    }

    /**
     * Whether every step the flow asks for has been answered.
     */
    public function isComplete(int $progress): bool
    {
        return $progress >= \count($this->required);
    }

    /**
     * The step answered next once everything before it is answered, or the
     * review once the flow is done.
     */
    public function reachableStep(int $progress): CheckoutStep
    {
        return $this->required[$progress] ?? CheckoutStep::REVIEW;
    }

    /**
     * How many of the given run of answered steps this flow still counts, read
     * against its own steps: the count that survives a step being lifted from
     * the flow, or put back into it.
     *
     * @param  array<int, CheckoutStep>  $answered
     */
    public function countAnswered(array $answered): int
    {
        $progress = 0;

        foreach ($this->required as $step) {
            if (! \in_array($step, $answered, true)) {
                break;
            }

            $progress++;
        }

        return $progress;
    }
}
