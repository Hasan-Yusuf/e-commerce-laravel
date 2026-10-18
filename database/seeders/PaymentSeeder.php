<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Database\Seeder;

class PaymentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $orders = Order::all();

        if ($orders->isEmpty()) {
            Payment::factory()
                ->count(10)
                ->create();

            return;
        }

        foreach ($orders as $order) {
            Payment::factory()
                ->paidFor($order)
                ->create();
        }
    }
}
