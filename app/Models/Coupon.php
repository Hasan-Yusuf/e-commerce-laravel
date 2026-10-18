<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsMoney;
use App\Enums\DiscountType;
use App\Models\Traits\HasActivationWindow;
use App\Models\Traits\HasCouponAmounts;
use App\Support\Money;
use Database\Factories\CouponFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property string $code Stored lowercase; read back uppercased.
 * @property string|null $description
 * @property DiscountType $discount_type
 * @property Money $discount_value_in_fils The amount off, in fils, for a fixed coupon.
 * @property string|null $discount_percentage The share, as a percentage, for a percentage coupon.
 * @property float $discount_value The share of the subtotal for a percentage
 *                                 coupon, or the amount off it in BHD for a fixed one.
 * @property float|null $maximum_discount The most the coupon may take off, in
 *                                        BHD, or null when it is uncapped.
 * @property float $min_order_amount The subtotal, in BHD, the coupon needs
 *                                   before it applies.
 * @property int|null $usage_limit
 * @property int $used_count
 * @property Carbon|null $activated_at
 * @property Carbon|null $starts_at
 * @property Carbon|null $expires_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read bool $is_active
 * @property-read Collection<int, Order> $orders
 */
class Coupon extends Model
{
    /** @use HasFactory<CouponFactory> */
    use HasActivationWindow, HasCouponAmounts, HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'description',
        'discount_type',

        // Written through the HasCouponAmounts accessors, which store the
        // underlying integer-fils and percentage columns.
        'discount_value',
        'maximum_discount',
        'min_order_amount',

        'usage_limit',
        'used_count',
        'activated_at',
        'starts_at',
        'expires_at',
    ];

    protected $appends = ['is_active'];

    #[\Override]
    public function casts(): array
    {
        return [
            'discount_type' => DiscountType::class,

            'discount_value_in_fils' => AsMoney::class,
            'maximum_discount_in_fils' => AsMoney::class,

            'discount_percentage' => 'decimal:2',

            'activated_at' => 'datetime',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    /**
     * A code is stored lowercase so an applied one is a plain indexed equality
     * (`CouponService::resolve` rather than a LOWER() scan), and reads back
     * uppercase, the way a code is written on a page.
     */
    public function code(): Attribute
    {
        return Attribute::make(
            get: static fn (mixed $value): string => Str::upper((string) $value),
            set: static fn (mixed $value): string => Str::lower(trim((string) $value)),
        );
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    /**
     * A null usage limit means unlimited; otherwise the code is spent once
     * used_count has reached it.
     */
    public function isExhausted(): bool
    {
        return $this->usage_limit !== null && $this->used_count >= $this->usage_limit;
    }

    /**
     * The order-level discount this coupon gives on the already-discounted
     * subtotal, in fils. A percentage is a share of the subtotal and a fixed
     * amount is an amount off it; either is capped by maximum_discount (when
     * set) and never allowed to exceed the subtotal. A subtotal under the
     * coupon's minimum earns nothing.
     */
    public function discountFor(int $subtotalInFils): int
    {
        if ($subtotalInFils < Money::fromBHD($this->min_order_amount)->toFils()) {
            return 0;
        }

        $discountInFils = $this->discount_type->is(DiscountType::PERCENTAGE)
            ? Money::percentage($subtotalInFils, $this->discount_value)
            : Money::fromBHD($this->discount_value)->toFils();

        if ($this->maximum_discount !== null) {
            $discountInFils = min(
                $discountInFils,
                Money::fromBHD($this->maximum_discount)->toFils(),
            );
        }

        return max(0, min($discountInFils, $subtotalInFils));
    }

    protected function activationColumn(): ?string
    {
        return 'activated_at';
    }

    protected function activationStartColumn(): string
    {
        return 'starts_at';
    }

    protected function activationEndColumn(): string
    {
        return 'expires_at';
    }
}
