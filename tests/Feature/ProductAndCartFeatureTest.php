<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductAndCartFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_create_form_renders_and_store_persists_valid_input(): void
    {
        $this->get(route('products.create'))
            ->assertOk()
            ->assertSee('Create Product');

        $this->post(route('products.store'), [
            'name' => 'Fresh Apples',
            'description' => 'Crisp local apples',
            'price' => '4.50',
            'stock' => 10,
        ])->assertRedirect();

        $this->assertDatabaseHas('products', [
            'name' => 'Fresh Apples',
            'stock' => 10,
        ]);
    }

    public function test_product_search_only_returns_matching_products(): void
    {
        Product::query()->create([
            'name' => 'Local Honey',
            'price' => 12.50,
            'stock' => 3,
        ]);
        Product::query()->create([
            'name' => 'Garden Basil',
            'price' => 2.00,
            'stock' => 8,
        ]);

        $this->get(route('products.index', ['search' => 'Honey']))
            ->assertOk()
            ->assertSee('Local Honey')
            ->assertDontSee('Garden Basil');
    }

    public function test_cart_and_order_item_changes_recalculate_parent_totals(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Aminah',
            'email' => 'aminah@example.test',
            'password' => 'hashed-password',
        ]);
        $product = Product::query()->create([
            'name' => 'Rice',
            'price' => 8.25,
            'stock' => 25,
        ]);
        $cart = Cart::query()->create(['customer_id' => $customer->id, 'total' => 0]);
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'delivery_address' => '12 Market Street',
            'order_date' => now(),
            'total_amount' => 0,
            'status' => 'pending',
        ]);

        $this->post(route('cart-items.store'), [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertRedirect();
        $this->post(route('order-items.store'), [
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertRedirect();

        $this->assertSame('24.75', $cart->fresh()->total);
        $this->assertSame('16.50', $order->fresh()->total_amount);
        $this->assertInstanceOf(CartItem::class, $cart->fresh()->cartItems->first());
        $this->assertInstanceOf(OrderItem::class, $order->fresh()->orderItems->first());
    }

    public function test_order_create_rejects_a_missing_delivery_address(): void
    {
        $customer = Customer::query()->create([
            'name' => 'Nora',
            'email' => 'nora@example.test',
            'password' => 'hashed-password',
        ]);

        $this->from(route('orders.create'))
            ->post(route('orders.store'), [
                'customer_id' => $customer->id,
                'order_date' => now()->format('Y-m-d H:i:s'),
                'status' => 'pending',
            ])
            ->assertRedirect(route('orders.create'))
            ->assertSessionHasErrors('delivery_address');
    }
}
