<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\AddressType;
use Database\Factories\AddressFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property AddressType $address_type
 * @property string $street_address
 * @property string $state
 * @property string $city
 * @property int $country_id
 * @property string $postal_code
 * @property Carbon|null $defaulted_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $address
 * @property-read User|null $user
 * @property-read Country|null $country
 * @property-read Collection<int, Order> $orders
 */
class Address extends Model
{
    /** @use HasFactory<AddressFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'address_type',
        'street_address',
        'state',
        'city',
        'country_id',
        'postal_code',
        'defaulted_at',
    ];

    protected $appends = ['address'];

    #[\Override]
    public function casts()
    {
        return [
            'address_type' => AddressType::class,
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function address(): Attribute
    {
        return Attribute::get(
            static fn (mixed $value, array $attributes) => sprintf(
                '%s - %s',
                $attributes['city'] ?? 'N/A',
                $attributes['state'] ?? 'N/A',
            ),
        );
    }
}
