<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\PhoneFactory;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $country_id
 * @property string $phone_number
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $full_phone_number
 * @property-read Country $country
 */
class Phone extends Model
{
    /** @use HasFactory<PhoneFactory> */
    use HasFactory;

    protected $fillable = ['country_id', 'phone_number'];

    protected $appends = ['full_phone_number'];

    public function country(): BelongsTo
    {
        return $this->belongsTo(Country::class);
    }

    public function fullPhoneNumber(): Attribute
    {
        return Attribute::get(
            fn (
                mixed $value,
                array $attributes,
            ): string => "{$this->country->call_code} {$attributes['phone_number']}",
        );
    }
}
