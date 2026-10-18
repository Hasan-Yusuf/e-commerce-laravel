<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsMoney;
use App\Enums\DeliveryMethod;
use App\Enums\OrderStatus;
use App\Observers\OrderObserver;
use App\Support\Money;
use Database\Factories\OrderFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int|null $user_id
 * @property string $number
 * @property OrderStatus $status
 * @property Money $subtotal_in_fils
 * @property Money $tax_amount_in_fils
 * @property Money $shipping_amount_in_fils
 * @property Money $discount_amount_in_fils
 * @property Money $total_amount_in_fils
 * @property int|null $shipping_address_id
 * @property int|null $billing_address_id
 * @property int|null $coupon_id
 * @property string|null $coupon_code
 * @property DeliveryMethod $delivery_method
 * @property string|null $notes
 * @property Carbon|null $paid_at
 * @property Carbon|null $delivered_at
 * @property string|null $checkout_token
 * @property string|null $access_token
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 * @property-read User|null $user
 * @property-read Coupon|null $coupon
 * @property-read Address|null $shippingAddress
 * @property-read Address|null $billingAddress
 * @property-read Collection<int, OrderItem> $orderItems
 * @property-read Collection<int, Payment> $payments
 * @property-read Payment|null $latestPayment
 */
#[ObservedBy(OrderObserver::class)]
class Order extends Model
{
    /** @use HasFactory<OrderFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'user_id',

        'status',

        'delivery_method',

        'number',

        'checkout_token',
        'access_token',

        'subtotal_in_fils',
        'tax_amount_in_fils',
        'shipping_amount_in_fils',
        'discount_amount_in_fils',
        'total_amount_in_fils',

        'shipping_address_id',
        'billing_address_id',

        'coupon_id',
        'coupon_code',

        'notes',
        'paid_at',
        'delivered_at',
    ];

    #[\Override]
    public function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'delivery_method' => DeliveryMethod::class,
            'paid_at' => 'datetime',
            'delivered_at' => 'datetime',

            'subtotal_in_fils' => AsMoney::class,
            'tax_amount_in_fils' => AsMoney::class,
            'shipping_amount_in_fils' => AsMoney::class,
            'discount_amount_in_fils' => AsMoney::class,
            'total_amount_in_fils' => AsMoney::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isShippingBillingSame(): bool
    {
        return $this->shipping_address_id === $this->billing_address_id;
    }

    public function shippingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'shipping_address_id');
    }

    public function billingAddress(): BelongsTo
    {
        return $this->belongsTo(Address::class, 'billing_address_id');
    }

    public function coupon(): BelongsTo
    {
        return $this->belongsTo(Coupon::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    /**
     * The newest settlement row, so a list reads an order's payment status
     * without a query per row.
     */
    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }
}
