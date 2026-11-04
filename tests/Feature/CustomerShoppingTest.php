<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class CustomerShoppingTest extends TestCase
{
    use RefreshDatabase;

    #[Group('module-customers')]
    public function test_saved_address_prefills_checkout_but_order_retains_its_own_address(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        $customer->update(['delivery_address' => '10 Jalan Lama, Kuala Lumpur']);
        $product = $this->product();
        $cart->cartItems()->create(['product_id' => $product->id, 'price' => 5, 'quantity' => 1]);

        $this->actingAs($customer, 'customer')->get(route('customer.cart.show'))
            ->assertOk()->assertSee('10 Jalan Lama, Kuala Lumpur');
        $this->post(route('customer.checkout'), [
            'delivery_address' => '11 Jalan Baru, Kuala Lumpur',
            'payment_method' => 'cash',
        ])->assertRedirect();

        $order = $customer->orders()->firstOrFail();
        $this->assertSame('11 Jalan Baru, Kuala Lumpur', $order->delivery_address);
        $this->assertSame('10 Jalan Lama, Kuala Lumpur', $customer->fresh()->delivery_address);
    }

    #[Group('module-customers')]
    public function test_customer_can_change_their_default_address_without_changing_old_orders(): void
    {
        [$customer] = $this->customerWithCart('customer@example.test');
        [$otherCustomer] = $this->customerWithCart('other@example.test');
        $oldOrder = $customer->orders()->create([
            'delivery_address' => '12 Old Street', 'order_date' => now(),
            'total_amount' => 0, 'status' => 'pending',
        ]);

        $this->actingAs($customer, 'customer')->patch(route('customer.profile.update'), [
            'name' => 'Updated Customer',
            'email' => 'updated@example.test',
            'delivery_address' => '24 New Street',
            'customer_id' => $otherCustomer->id,
        ])->assertRedirect(route('customer.profile.edit'));

        $this->assertSame('24 New Street', $customer->fresh()->delivery_address);
        $this->assertSame('Updated Customer', $customer->fresh()->name);
        $this->assertSame('updated@example.test', $customer->fresh()->email);
        $this->assertNull($otherCustomer->fresh()->delivery_address);
        $this->assertSame('12 Old Street', $oldOrder->fresh()->delivery_address);
    }

    #[Group('module-customers')]
    public function test_default_address_has_a_length_limit_and_checkout_still_requires_an_address(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        $product = $this->product();
        $cart->cartItems()->create(['product_id' => $product->id, 'price' => 5, 'quantity' => 1]);

        $this->actingAs($customer, 'customer')->patch(route('customer.profile.update'), [
            'name' => $customer->name,
            'email' => $customer->email,
            'delivery_address' => str_repeat('x', 256),
        ])->assertSessionHasErrors('delivery_address');
        $this->post(route('customer.checkout'), [
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('delivery_address');

        $this->assertNull($customer->fresh()->delivery_address);
        $this->assertDatabaseCount('orders', 0);
    }

    #[Group('module-carts')]
    public function test_customer_can_clear_only_their_own_cart(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        [$otherCustomer, $otherCart] = $this->customerWithCart('other@example.test');
        $product = $this->product();
        $cart->cartItems()->create(['product_id' => $product->id, 'price' => 5, 'quantity' => 1]);
        $otherCart->cartItems()->create(['product_id' => $product->id, 'price' => 5, 'quantity' => 1]);
        $cart->update(['total' => 5]);
        $otherCart->update(['total' => 5]);

        $this->actingAs($customer, 'customer')->delete(route('customer.cart.clear'))
            ->assertRedirect(route('customer.cart.show'))
            ->assertSessionHas('success', 'Cleared 1 product line from your cart.');

        $this->assertSame(0, $cart->cartItems()->count());
        $this->assertSame('0.00', $cart->fresh()->total);
        $this->assertSame(1, $otherCart->cartItems()->count());
        $this->assertSame('5.00', $otherCart->fresh()->total);
    }

    #[Group('module-cart-items')]
    public function test_customer_adds_a_product_to_their_own_cart_at_the_catalog_price(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        $product = $this->product(price: '7.50');

        $this->actingAs($customer, 'customer')->post(route('customer.cart-items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => '0.01',
            'cart_id' => 999,
        ])->assertRedirect()
            ->assertSessionHas('success', 'Added 2 × Local Rice to your cart.');

        $item = $cart->cartItems()->firstOrFail();
        $this->assertSame($product->id, $item->product_id);
        $this->assertSame('7.50', $item->price);
        $this->assertSame(2, $item->quantity);
        $this->assertSame('15.00', $cart->fresh()->total);
    }

    #[Group('module-cart-items')]
    public function test_adding_a_product_already_in_the_cart_increases_quantity_without_creating_another_line(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        $product = $this->product(price: '7.50', stock: 6);
        $item = $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => '5.00']);
        $cart->update(['total' => '10.00']);

        $this->actingAs($customer, 'customer')->from(route('products.show', $product))
            ->post(route('customer.cart-items.store'), ['product_id' => $product->id, 'quantity' => 3])
            ->assertRedirect(route('products.show', $product))
            ->assertSessionHas('success', 'Added 3 × Local Rice to your cart.');

        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame($item->id, $cart->cartItems()->firstOrFail()->id);
        $this->assertSame(5, $item->fresh()->quantity);
        $this->assertSame('7.50', $item->fresh()->price);
        $this->assertSame('37.50', $cart->fresh()->total);
    }

    #[Group('module-cart-items')]
    public function test_repeat_add_rejects_a_combined_quantity_above_stock_without_changing_the_cart(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        $product = $this->product(price: '5.00', stock: 4);
        $item = $cart->cartItems()->create(['product_id' => $product->id, 'quantity' => 3, 'price' => '5.00']);
        $cart->update(['total' => '15.00']);

        $this->actingAs($customer, 'customer')->post(route('customer.cart-items.store'), [
            'product_id' => $product->id,
            'quantity' => 2,
        ])->assertSessionHasErrors('quantity');

        $this->assertSame(3, $item->fresh()->quantity);
        $this->assertSame('15.00', $cart->fresh()->total);
        $this->assertDatabaseCount('cart_items', 1);
    }

    #[Group('module-cart-items')]
    public function test_customer_can_manage_only_items_in_their_own_cart(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        [$otherCustomer, $otherCart] = $this->customerWithCart('other@example.test');
        $product = $this->product();
        $otherItem = CartItem::query()->create([
            'cart_id' => $otherCart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => $product->price,
        ]);

        $this->actingAs($customer, 'customer')
            ->patch(route('customer.cart-items.update', $otherItem), ['quantity' => 2])
            ->assertNotFound();

        $this->assertSame(1, $otherItem->fresh()->quantity);
        $this->assertSame($otherCustomer->id, $otherItem->fresh()->cart->customer_id);
        $this->assertSame('0.00', $cart->fresh()->total);
    }

    #[Group('module-carts')]
    public function test_customer_checkout_creates_a_full_pending_payment_and_clears_the_cart(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        $product = $this->product(price: '7.50', stock: 5);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => '1.00',
        ]);
        $cart->update(['total' => 2]);

        $response = $this->actingAs($customer, 'customer')->post(route('customer.checkout'), [
            'delivery_address' => '12 Market Street',
            'payment_method' => 'card',
        ]);

        $order = Order::query()->whereBelongsTo($customer)->firstOrFail();
        $response->assertRedirect(route('customer.orders.show', $order))
            ->assertSessionHas('success', "Placed order #{$order->id} for \$15.00.");
        $this->assertSame('15.00', $order->total_amount);
        $this->assertSame('pending', $order->status);
        $this->assertSame('15.00', $order->payment->amount);
        $this->assertSame('pending', $order->payment->payment_status);
        $this->assertSame('card', $order->payment->payment_method);
        $this->assertSame(2, $order->orderItems->firstOrFail()->quantity);
        $this->assertSame(5, $product->fresh()->stock);
        $this->assertDatabaseCount('cart_items', 0);
        $this->assertSame('0.00', $cart->fresh()->total);
    }

    #[Group('module-orders')]
    public function test_customer_cannot_view_another_customers_order(): void
    {
        [$customer] = $this->customerWithCart('customer@example.test');
        [$otherCustomer] = $this->customerWithCart('other@example.test');
        $order = $otherCustomer->orders()->create([
            'delivery_address' => '99 Other Street',
            'order_date' => now(),
            'total_amount' => 10,
            'status' => 'pending',
        ]);

        $this->actingAs($customer, 'customer')
            ->get(route('customer.orders.show', $order))
            ->assertNotFound();
    }

    #[Group('module-carts')]
    public function test_checkout_rejects_insufficient_stock_without_creating_an_order(): void
    {
        [$customer, $cart] = $this->customerWithCart('customer@example.test');
        $product = $this->product(stock: 1);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => $product->price,
        ]);

        $this->actingAs($customer, 'customer')->post(route('customer.checkout'), [
            'delivery_address' => '12 Market Street',
            'payment_method' => 'cash',
        ])->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('payments', 0);
        $this->assertDatabaseCount('cart_items', 1);
        $this->assertSame(1, $product->fresh()->stock);
    }

    /** @return array{Customer, Cart} */
    private function customerWithCart(string $email): array
    {
        $customer = Customer::query()->create([
            'name' => 'Customer',
            'email' => $email,
            'password' => 'password123',
        ]);
        $cart = $customer->cart()->create(['total' => 0]);

        return [$customer, $cart];
    }

    private function product(string $price = '5.00', int $stock = 5): Product
    {
        return Product::query()->create([
            'name' => 'Local Rice',
            'price' => $price,
            'stock' => $stock,
        ]);
    }
}
