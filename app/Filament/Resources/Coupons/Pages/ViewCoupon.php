<?php

declare(strict_types=1);

namespace App\Filament\Resources\Coupons\Pages;

use App\Filament\Resources\Coupons\CouponResource;
use App\Filament\Resources\Pages\Concerns\HasInfoRelations;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

class ViewCoupon extends ViewRecord
{
    use HasInfoRelations;

    protected static string $resource = CouponResource::class;

    /**
     * The orders the coupon was used on, and each order's shopper.
     *
     * @return array<int, string>
     */
    protected function infoRelations(): array
    {
        return ['orders.user'];
    }

    #[\Override]
    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
