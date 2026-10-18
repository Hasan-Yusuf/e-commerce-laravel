<?php

declare(strict_types=1);

namespace App\Models;

use App\Casts\AsMoney;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Support\Money;
use Database\Factories\PaymentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * A settlement record for an order: which method the order was placed with,
 * where that payment stands, and the amount to collect. An order can carry
 * more than one row (a failed attempt followed by a successful one), which is
 * why the amount lives here as well as on the order.
 *
 * @property int $id
 * @property int $order_id
 * @property PaymentMethod $method
 * @property PaymentStatus $status
 * @property Money $amount_in_fils
 * @property Carbon|null $paid_at
 * @property array<string, mixed>|null $meta
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read Order|null $order
 */
class Payment extends Model
{
    /** @use HasFactory<PaymentFactory> */
    use HasFactory;

    protected $fillable = [
        'order_id',
        'method',
        'status',
        'amount_in_fils',
        'paid_at',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'method' => PaymentMethod::class,
            'status' => PaymentStatus::class,
            'amount_in_fils' => AsMoney::class,
            'paid_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
