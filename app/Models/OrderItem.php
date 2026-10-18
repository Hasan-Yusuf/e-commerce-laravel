<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsMoney;
use App\Support\Money;
use Database\Factories\OrderItemFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property int|null $product_id
 * @property string|null $product_name
 * @property string|null $sku
 * @property int $quantity
 * @property Money $unit_price_in_fils
 * @property Money $total_price_in_fils
 * @property Carbon|null $deleted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order|null $order
 * @property-read Product|null $product
 */
class OrderItem extends Model
{
    /** @use HasFactory<OrderItemFactory> */
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'order_id',
        'product_id',
        'product_name',

        'sku',
        'quantity',

        'unit_price_in_fils',
        'total_price_in_fils',
    ];

    #[\Override]
    public function casts(): array
    {
        return [
            'unit_price_in_fils' => AsMoney::class,
            'total_price_in_fils' => AsMoney::class,
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    #[\Override]
    protected static function booted(): void
    {
        static::creating(
            static function (OrderItem $item) {
                if (! $item->product_name && $item->product_id) {
                    $item->product_name = $item->product?->name;
                }

                $item->total_price_in_fils = $item->unit_price_in_fils->multiply(
                    (int) $item->quantity,
                );
            },
        );
    }
}
