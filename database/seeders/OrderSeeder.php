<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\AddressType;
use App\Enums\OrderStatus;
use App\Enums\UserRole;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use App\Services\Order\OrderNumberService;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection as SupportCollection;

class OrderSeeder extends Seeder
{
    /**
     * How many orders to spread across the seeded customers.
     */
    private const ORDERS = 40;

    /**
     * VAT rate applied to every order.
     */
    private const VAT_RATE = 0.1;

    /**
     * Free delivery threshold and flat delivery fee, in fils.
     */
    private const FREE_DELIVERY_THRESHOLD = 50_000;

    private const DELIVERY_FEE = 2_000;

    /**
     * Realistic delivery notes left by customers.
     *
     * @var array<int, string>
     */
    private const NOTES = [
        'Please call before delivery.',
        'Leave the parcel with the building reception.',
        'Deliver after 6 PM if possible.',
        'Second floor, blue door.',
        'Please handle with care — fragile.',
    ];

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        if (Order::query()->exists()) {
            return;
        }

        // Seeded history is written without firing model events, so the
        // numbers, stamps and totals the seeder chose are exactly what lands in
        // the rows. None of it is a live transition, so the processor is
        // deliberately not involved.
        Model::withoutEvents($this->seedOrders(...));
    }

    private function seedOrders(): void
    {
        $customers = User::query()
            ->where('role', UserRole::CUSTOMER)
            ->has('addresses')
            ->with('addresses')
            ->get();

        $products = Product::query()->get();

        if ($customers->isEmpty() || $products->isEmpty()) {
            return;
        }

        for ($i = 0; $i < self::ORDERS; $i++) {
            $this->seedOrder($customers, $products);
        }
    }

    /**
     * @param  Collection<int, User>  $customers
     * @param  Collection<int, Product>  $products
     */
    private function seedOrder(
        Collection $customers,
        Collection $products,
    ): void {
        $user = $customers->random();

        $shipping = $user->addresses->firstWhere(
            'address_type',
            AddressType::SHIPPING,
        ) ?? $user->addresses->first();

        $billing =
            $user->addresses->firstWhere(
                'address_type',
                AddressType::BILLING,
            ) ?? $shipping;

        /** @var Carbon $createdAt */
        $createdAt = now()->subDays(fake()->numberBetween(1, 180))->subMinutes(fake()->numberBetween(0, 1439));

        $items = $this->buildItems($products);

        $subtotal = (int) $items->sum('total_price_in_fils');
        $tax = (int) round($subtotal * self::VAT_RATE);
        $delivery = $subtotal >= self::FREE_DELIVERY_THRESHOLD ? 0 : self::DELIVERY_FEE;
        $discount = fake()->boolean(25) ? (int) round($subtotal * 0.1) : 0;

        $status = fake()->randomElement([
            OrderStatus::DELIVERED,
            OrderStatus::DELIVERED,
            OrderStatus::DELIVERED,
            OrderStatus::SHIPPED,
            OrderStatus::SHIPPED,
            OrderStatus::PROCESSING,
            OrderStatus::PROCESSING,
            OrderStatus::PENDING,
            OrderStatus::CANCELLED,
            OrderStatus::REFUNDED,
        ]);

        $isPaid = in_array(
            $status,
            [
                OrderStatus::PROCESSING,
                OrderStatus::SHIPPED,
                OrderStatus::DELIVERED,
            ],
            true,
        );

        $order = new Order([
            'user_id' => $user->id,
            'number' => app(OrderNumberService::class)->next(),
            'status' => $status,
            'subtotal_in_fils' => $subtotal,
            'tax_amount_in_fils' => $tax,
            'shipping_amount_in_fils' => $delivery,
            'discount_amount_in_fils' => $discount,
            'total_amount_in_fils' => $subtotal + $tax + $delivery - $discount,
            'shipping_address_id' => $shipping?->id,
            'billing_address_id' => $billing?->id,
            'notes' => fake()->boolean(25)
                ? fake()->randomElement(self::NOTES)
                : null,
            'paid_at' => $isPaid
                ? $createdAt->copy()->addHours(fake()->numberBetween(1, 48))
                : null,
        ]);

        $order->created_at = $createdAt;
        $order->updated_at = $createdAt;
        $order->save();

        foreach ($items as $item) {
            $orderItem = new OrderItem([
                'order_id' => $order->id,
                'product_id' => $item['product']->id,
                'product_name' => $item['product']->name,
                'sku' => $item['product']->sku,
                'quantity' => $item['quantity'],
                'unit_price_in_fils' => $item['unit_price_in_fils'],
                'total_price_in_fils' => $item['total_price_in_fils'],
            ]);

            $orderItem->created_at = $createdAt;
            $orderItem->updated_at = $createdAt;
            $orderItem->save();
        }
    }

    /**
     * Pick a handful of distinct products and price their lines.
     *
     * @param  Collection<int, Product>  $products
     * @return SupportCollection<int, array{product: Product, quantity: int, unit_price_in_fils: int, total_price_in_fils: int}>
     */
    private function buildItems(Collection $products): SupportCollection
    {
        return $products
            ->random(fake()->numberBetween(1, 4))
            ->map(static function (Product $product): array {
                $quantity = fake()->numberBetween(1, 2);

                return [
                    'product' => $product,
                    'quantity' => $quantity,
                    'unit_price_in_fils' => $product->price_in_fils->toFils(),
                    'total_price_in_fils' => $product->price_in_fils->toFils() * $quantity,
                ];
            })
            ->values();
    }
}
