<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class ModuleOperationsTest extends TestCase
{
    use RefreshDatabase;

    #[Group('module-customers')]
    public function test_administrator_creates_a_customer_and_cannot_delete_one_with_orders(): void
    {
        $this->actingAs(User::factory()->create());

        $this->post(route('customers.store'), [
            'name' => 'Aminah', 'email' => 'aminah@example.test', 'password' => 'password123',
        ])->assertRedirect();

        $customer = Customer::query()->where('email', 'aminah@example.test')->firstOrFail();
        $this->assertNotNull($customer->cart);
        $customer->orders()->create([
            'order_date' => now(), 'delivery_address' => '12 Market Street',
            'total_amount' => 0, 'status' => 'pending',
        ]);

        $this->delete(route('customers.destroy', $customer))->assertSessionHasErrors('customer');
        $this->assertModelExists($customer);
    }

    #[Group('module-carts')]
    public function test_admin_cart_creation_requires_a_customer_without_an_existing_cart(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::query()->create([
            'name' => 'Nora', 'email' => 'nora@example.test', 'password' => 'password123',
        ]);

        $this->post(route('carts.store'), ['customer_id' => $customer->id, 'total' => 99])->assertRedirect();
        $this->post(route('carts.store'), ['customer_id' => $customer->id])->assertSessionHasErrors('customer_id');

        $this->assertSame('0.00', $customer->cart->total);
        $this->assertSame(1, Cart::query()->count());
    }

    #[Group('module-cart-items')]
    public function test_removing_a_cart_item_recalculates_the_cart_total(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::query()->create([
            'name' => 'Nora', 'email' => 'nora@example.test', 'password' => 'password123',
        ]);
        $cart = $customer->cart()->create(['total' => 15]);
        $product = Product::query()->create(['name' => 'Rice', 'price' => 5, 'stock' => 10]);
        $item = $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => 5]);

        $this->delete(route('cart-items.destroy', $item))->assertRedirect();

        $this->assertModelMissing($item);
        $this->assertSame('0.00', $cart->fresh()->total);
    }

    #[Group('module-order-items')]
    public function test_removing_a_pending_order_item_recalculates_the_order_and_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $customer = Customer::query()->create([
            'name' => 'Nora', 'email' => 'nora@example.test', 'password' => 'password123',
        ]);
        $order = Order::query()->create([
            'customer_id' => $customer->id, 'order_date' => now(),
            'delivery_address' => '12 Market Street', 'total_amount' => 15, 'status' => 'pending',
        ]);
        $product = Product::query()->create(['name' => 'Rice', 'price' => 5, 'stock' => 10]);
        $item = $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => 5]);
        $payment = $order->payment()->create(['payment_method' => 'cash', 'payment_status' => 'pending', 'amount' => 15]);

        $this->delete(route('order-items.destroy', $item))->assertRedirect();

        $this->assertModelMissing($item);
        $this->assertSame('0.00', $order->fresh()->total_amount);
        $this->assertSame('0.00', $payment->fresh()->amount);
    }
}
