<?php

declare(strict_types=1);

namespace Tests\Support;

use App\Models\Traits\HasActivationWindow;
use Illuminate\Database\Eloquent\Model;

/**
 * A model that exists only to exercise HasActivationWindow. The real consumers
 * (Coupon, Discount) both name an activation marker column, so the trait's
 * "no marker column" branch needs a model of its own to reach.
 */
class ActivationWindowFixture extends Model
{
    use HasActivationWindow;

    protected $table = 'coupons';

    /**
     * The marker column the trait should ask for, or null for none.
     */
    public ?string $activationColumnName = null;

    protected function activationColumn(): ?string
    {
        return $this->activationColumnName;
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
