<?php

declare(strict_types=1);

namespace App\DTOs;

/**
 * The coupon a shopper has applied to the checkout: its identity, the code as
 * it was typed (and as it is snapshotted onto the order), and the order-level
 * discount it produced in fils.
 */
final readonly class AppliedCoupon
{
    public function __construct(
        public int $id,
        public string $code,
        public int $discountInFils,
    ) {}

    /**
     * The shape the checkout state serializes: the session's `checkout` key and
     * the draft's `state` column.
     *
     * @return array{id: int, code: string, discount_in_fils: int}
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'code' => $this->code,
            'discount_in_fils' => $this->discountInFils,
        ];
    }

    public static function fromArray(mixed $data): ?self
    {
        if (! is_array($data)) {
            return null;
        }

        $id = $data['id'] ?? null;
        $code = $data['code'] ?? null;

        if ($id === null || $code === null) {
            return null;
        }

        return new self(
            id: (int) $id,
            code: (string) $code,
            discountInFils: (int) ($data['discount_in_fils'] ?? 0),
        );
    }
}
