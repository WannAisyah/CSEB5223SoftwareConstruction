<?php

namespace Database\Seeders;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => Hash::make('password123'),
        ]);

        $products = [
            ['name' => 'Classic White T-Shirt', 'description' => 'Comfortable everyday cotton tee.', 'price' => 24.99, 'stock' => 50, 'image' => 'shirts/white-tee.jpg'],
            ['name' => 'Premium Hoodie', 'description' => 'Warm fleece hoodie for everyday wear.', 'price' => 59.99, 'stock' => 35, 'image' => 'hoodies/premium-hoodie.jpg'],
            ['name' => 'Running Sneakers', 'description' => 'Lightweight sneakers with cushioned support.', 'price' => 89.99, 'stock' => 22, 'image' => 'shoes/running-sneakers.jpg'],
            ['name' => 'Leather Backpack', 'description' => 'Durable backpack with room for daily essentials.', 'price' => 74.99, 'stock' => 18, 'image' => 'bags/leather-backpack.jpg'],
            ['name' => 'Wireless Headphones', 'description' => 'Noise-reducing Bluetooth headphones.', 'price' => 129.99, 'stock' => 12, 'image' => 'audio/wireless-headphones.jpg'],
            ['name' => 'Water Bottle', 'description' => 'Insulated stainless steel bottle.', 'price' => 19.99, 'stock' => 60, 'image' => 'accessories/water-bottle.jpg'],
        ];

        $createdProducts = [];

        foreach ($products as $productData) {
            $createdProducts[] = Product::create($productData);
        }

        $customers = [
            ['name' => 'Alice Johnson', 'email' => 'alice@example.com', 'password' => 'password123'],
            ['name' => 'Brian Smith', 'email' => 'brian@example.com', 'password' => 'password123'],
            ['name' => 'Carla Gomez', 'email' => 'carla@example.com', 'password' => 'password123'],
            ['name' => 'Daniel Lee', 'email' => 'daniel@example.com', 'password' => 'password123'],
            ['name' => 'Eva Martinez', 'email' => 'eva@example.com', 'password' => 'password123'],
        ];

        $customerModels = [];

        foreach ($customers as $customerData) {
            $customerModels[] = Customer::create([
                'name' => $customerData['name'],
                'email' => $customerData['email'],
                'password' => Hash::make($customerData['password']),
            ]);
        }

        foreach ($customerModels as $customer) {
            $cart = Cart::create([
                'customer_id' => $customer->id,
                'total' => 0,
            ]);

            $cartTotal = 0;
            $selectedProducts = collect($createdProducts)->shuffle()->take(rand(2, 4));

            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 3);
                $price = (float) $product->price;

                $cart->cartItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);

                $cartTotal += $price * $quantity;
            }

            $cart->update(['total' => $cartTotal]);
        }

        foreach ($customerModels as $customer) {
            if (rand(0, 1) === 0) {
                continue;
            }

            $selectedProducts = collect($createdProducts)->shuffle()->take(rand(1, 3));
            $totalAmount = 0;

            $order = Order::create([
                'customer_id' => $customer->id,
                'order_date' => now()->subDays(rand(1, 45)),
                'total_amount' => 0,
                'status' => ['pending', 'processing', 'completed'][rand(0, 2)],
            ]);

            foreach ($selectedProducts as $product) {
                $quantity = rand(1, 2);
                $price = (float) $product->price;
                $lineTotal = $price * $quantity;

                OrderItem::create([
                    'order_id' => $order->id,
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $price,
                ]);

                $totalAmount += $lineTotal;
            }

            $order->update(['total_amount' => $totalAmount]);

            Payment::create([
                'order_id' => $order->id,
                'amount' => $totalAmount,
                'payment_method' => ['cash', 'card', 'bank_transfer'][rand(0, 2)],
                'payment_status' => ['pending', 'paid', 'failed'][rand(0, 2)],
                'payment_date' => now()->subDays(rand(0, 20)),
            ]);
        }
    }
}
