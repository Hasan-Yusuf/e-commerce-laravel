<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\SpecFilterType;
use App\Enums\SpecValueType;
use App\Models\Traits\HasLocalizedAttributes;
use App\Observers\SpecFilterValuesObserver;
use Database\Factories\TypeSpecFactory;
use Illuminate\Database\Eloquent\Attributes\ObservedBy;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $type_id
 * @property string $name_en
 * @property string $name_ar
 * @property SpecValueType $value_type
 * @property array<string, mixed>|null $options
 * @property string|null $unit
 * @property bool $is_filterable
 * @property SpecFilterType|null $filter_type
 * @property int $sort_order
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $name
 * @property-read Type|null $type
 */
#[ObservedBy(SpecFilterValuesObserver::class)]
class TypeSpec extends Model
{
    /** @use HasFactory<TypeSpecFactory> */
    use HasFactory, HasLocalizedAttributes;

    protected $fillable = [
        'type_id',

        'name_en',
        'name_ar',

        'value_type',
        'options',
        'unit',

        'is_filterable',
        'filter_type',

        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    public function casts(): array
    {
        return [
            'value_type' => SpecValueType::class,
            'filter_type' => SpecFilterType::class,

            'options' => 'array',

            'is_filterable' => 'bool',
            'sort_order' => 'int',
        ];
    }

    public function type(): BelongsTo
    {
        return $this->belongsTo(Type::class);
    }

    /**
     * The spec name in the current locale.
     */
    public function name(): Attribute
    {
        return Attribute::get(fn (): string => (string) $this->localizedValue('name'));
    }

    /**
     * The spec name in the given locale, which is also the key its product
     * values are stored under.
     */
    public function nameFor(string $locale): string
    {
        return (string) $this->getAttribute('name_'.self::localeColumn($locale));
    }

    /**
     * The column suffix one locale reads and writes under, from the locales the
     * application declares (`config/locales.php`): the catalogue's localized
     * columns — `name_en`, `specs_en`, `label_en` — all carry the locale's own
     * code, so a third locale needs no code of its own. A locale the application
     * does not declare reads as the fallback's.
     */
    public static function localeColumn(string $locale): string
    {
        if (! array_key_exists($locale, config('locales'))) {
            $locale = app()->getFallbackLocale();
        }

        return $locale;
    }

    /**
     * The key one locale's option labels live under.
     */
    public function optionKey(string $locale): string
    {
        return 'label_'.self::localeColumn($locale);
    }

    /**
     * Whether this spec narrows products with a numeric min/max filter.
     */
    public function usesRangeFilter(): bool
    {
        return $this->is_filterable && $this->filter_type->is(SpecFilterType::RANGE);
    }

    /**
     * Format one of this spec's stored product values for display.
     */
    public function formatValue(mixed $value): string
    {
        return $this->value_type->getFormattedValue($value, $this->unit);
    }

    /**
     * The label for one of this spec's canonical stored values — the value
     * itself, except for a yes/no, which reads as the locale's yes or no.
     */
    public function valueLabel(string $value): string
    {
        if ($this->value_type->isNot(SpecValueType::BOOLEAN)) {
            return $value;
        }

        return filter_var($value, FILTER_VALIDATE_BOOL)
            ? __('catalog.product.spec_yes')
            : __('catalog.product.spec_no');
    }

    /**
     * The option labels for the given locale, in definition order.
     *
     * @return array<int, string>
     */
    public function optionLabels(?string $locale = null): array
    {
        $key = $this->optionKey($locale ?? app()->getLocale());

        return collect($this->options ?? [])
            ->pluck($key)
            ->filter(static fn (mixed $label): bool => filled($label))
            ->map(static fn (mixed $label): string => (string) $label)
            ->values()
            ->all();
    }

    /**
     * The option labels as `label => label`, for a select whose stored value is
     * the label itself.
     *
     * @return array<string, string>
     */
    public function optionChoices(?string $locale = null): array
    {
        return collect($this->optionLabels($locale))
            ->mapWithKeys(static fn (string $label): array => [$label => $label])
            ->all();
    }

    /**
     * The numeric values a number spec presets — the storage sizes a storage spec
     * offers — read back out of the same option list an options spec answers
     * with. A spec that fixes its values this way is captured by picking one of
     * them and filtered as a list, rather than typed and ranged.
     *
     * @return array<int, int|float>
     */
    public function optionValues(): array
    {
        if ($this->value_type->isNot(SpecValueType::NUMBER)) {
            return [];
        }

        return collect($this->optionLabels())
            ->filter(static fn (string $label): bool => is_numeric($label))
            ->map(static fn (string $label): int|float => +$label)
            ->values()
            ->all();
    }

    /**
     * Whether a numeric spec names the values it takes rather than leaving them
     * open.
     */
    public function presetsValues(): bool
    {
        return $this->optionValues() !== [];
    }
}
