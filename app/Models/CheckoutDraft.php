<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\CheckoutDraftFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * The checkout a signed-in shopper left behind: the state the session holds,
 * kept on their account so it is still there when the session that started it
 * has long expired.
 *
 * @property int $id
 * @property int|null $user_id
 * @property array<string, mixed> $state
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User|null $user
 */
class CheckoutDraft extends Model
{
    /** @use HasFactory<CheckoutDraftFactory> */
    use HasFactory;

    protected $fillable = [
        'user_id',
        'state',
    ];

    /**
     * @return array<string, string>
     */
    #[\Override]
    protected function casts(): array
    {
        return [
            'state' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
