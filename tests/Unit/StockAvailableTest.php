<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Models\Product;
use App\Rules\StockAvailable;
use Illuminate\Support\Facades\Validator;
use Tests\TestCase;

class StockAvailableTest extends TestCase
{
    public function test_the_message_names_the_available_quantity(): void
    {
        $product = (new Product)->forceFill([
            'stock' => 3,
            'reserved_stock' => 0,
        ]);

        static::assertSame(
            'Insufficient quantity. The available stock for this product is 3.',
            $this->message($product),
        );
    }

    public function test_the_message_is_translated_and_uses_the_same_placeholder(): void
    {
        // The rule resolves lang/validation.php, so the Arabic message has to
        // carry the same :max placeholder as the English one, or the count is
        // printed literally.
        app()->setLocale('ar');

        $product = (new Product)->forceFill([
            'stock' => 3,
            'reserved_stock' => 0,
        ]);

        static::assertSame(
            'المخزون غير كافي. الكمية المتوفرة: 3',
            $this->message($product),
        );
    }

    private function message(Product $product): string
    {
        return Validator::make(
            ['quantity' => 5],
            ['quantity' => [new StockAvailable($product)]],
        )
            ->errors()
            ->first('quantity');
    }
}
