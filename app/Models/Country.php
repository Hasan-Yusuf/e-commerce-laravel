<?php

declare(strict_types=1);

namespace App\Models;

use App\Models\Traits\HasLocalizedAttributes;
use Database\Factories\CountryFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $name_en
 * @property string $name_ar
 * @property string $slug
 * @property string $calling_code
 * @property int $phone_length
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $name
 * @property-read Collection<int, Phone> $phones
 * @property-read Collection<int, Address> $addresses
 * @property-read Collection<int, User> $users
 * @property-read Collection<int, Order> $orders
 */
class Country extends Model
{
    /** @use HasFactory<CountryFactory> */
    use HasFactory, HasLocalizedAttributes;

    protected $fillable = [
        'name_en',
        'name_ar',

        'slug',

        'calling_code',

        'phone_length',
    ];

    public function phones(): HasMany
    {
        return $this->hasMany(Phone::class);
    }

    public function addresses(): HasMany
    {
        return $this->hasMany(Address::class);
    }

    public function users(): HasManyThrough
    {
        return $this->hasManyThrough(User::class, Address::class);
    }

    public function orders(): HasManyThrough
    {
        return $this->hasManyThrough(
            Order::class,
            Address::class,
            'country_id',
            'shipping_address_id',
        );
    }

    public function name(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->localizedValue('name'));
    }
}
