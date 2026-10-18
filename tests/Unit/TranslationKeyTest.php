<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Enums\OrderStatus;
use App\Filament\Pages\Dashboard;
use App\Filament\Resources\Orders\OrderResource;
use App\Filament\Widgets\LowStockProducts;
use App\Support\TranslationKey;
use Tests\TestCase;

/**
 * The one place translation groups are assembled. The two separators matter:
 * `/` steps into a directory, `.` into an array inside one file, and getting
 * either wrong silently returns the key verbatim.
 */
class TranslationKeyTest extends TestCase
{
    public function test_a_resource_reads_its_own_filament_file(): void
    {
        static::assertSame(
            'filament/order',
            TranslationKey::for(OrderResource::class)->group(),
        );

        static::assertSame('Order', OrderResource::trans('labels.model_name'));
    }

    public function test_an_enum_reads_the_enums_file_by_its_own_name(): void
    {
        static::assertSame(
            'enums.order_status',
            TranslationKey::for(OrderStatus::class)->group(),
        );

        static::assertSame('Pending', OrderStatus::PENDING->getLabel());
    }

    public function test_a_non_resource_class_defaults_to_its_own_name_under_filament(): void
    {
        static::assertSame(
            'filament/dashboard',
            TranslationKey::for(Dashboard::class)->group(),
        );

        static::assertSame('Dashboard', Dashboard::trans('title'));
    }

    public function test_a_class_can_compose_a_custom_group(): void
    {
        static::assertSame(
            'filament/dashboard',
            TranslationKey::for(LowStockProducts::class)
                ->forFilament()
                ->setKey('dashboard')
                ->group(),
        );

        static::assertSame(
            'Low-stock products',
            LowStockProducts::trans('tables.low_stock_products'),
        );
    }

    public function test_segments_are_appended_in_order(): void
    {
        static::assertSame(
            'first/second',
            TranslationKey::for(Dashboard::class)
                ->setKey('first')
                ->setKey('second')
                ->group(),
        );
    }

    public function test_it_casts_to_a_string_without_asking_for_the_group(): void
    {
        static::assertSame(
            'filament/order',
            (string) TranslationKey::for(OrderResource::class),
        );

        static::assertSame(
            'filament/dashboard',
            (string) TranslationKey::for(Dashboard::class)->forFilament()->setKey('dashboard'),
        );
    }

    public function test_a_missing_key_comes_back_verbatim(): void
    {
        static::assertSame(
            'filament/order.labels.nope',
            OrderResource::trans('labels.nope'),
        );
    }
}
