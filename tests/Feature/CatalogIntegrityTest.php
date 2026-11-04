<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Customer;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Payment;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

class CatalogIntegrityTest extends TestCase
{
    use RefreshDatabase;

    #[Group('module-products')]
    public function test_deleting_a_product_preserves_cart_history_and_marks_it_unavailable(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = $this->customer();
        $product = $this->product(stock: 5);
        $cart = Cart::query()->create(['customer_id' => $customer->id, 'total' => 10]);
        $item = CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 5,
        ]);

        $this->delete(route('products.destroy', $product))->assertRedirect(route('products.index'));

        $this->assertSoftDeleted($product);
        $this->assertDatabaseHas('cart_items', ['id' => $item->id, 'product_id' => $product->id]);
        $this->assertTrue($item->fresh()->product->trashed());
        $this->get(route('carts.show', $cart))->assertOk()->assertSee('Unavailable — remove this item');
    }

    #[Group('module-cart-items')]
    public function test_cart_rejects_more_units_than_are_in_stock(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = $this->customer();
        $product = $this->product(stock: 2);
        $cart = Cart::query()->create(['customer_id' => $customer->id, 'total' => 0]);

        $this->from(route('cart-items.create'))->post(route('cart-items.store'), [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 3,
        ])->assertRedirect(route('cart-items.create'))->assertSessionHasErrors('quantity');

        $this->assertDatabaseCount('cart_items', 0);
        $this->assertSame('0.00', $cart->fresh()->total);
    }

    #[Group('module-cart-items')]
    public function test_the_same_product_cannot_be_added_to_a_cart_twice(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = $this->customer();
        $product = $this->product(stock: 5);
        $cart = Cart::query()->create(['customer_id' => $customer->id, 'total' => 5]);
        CartItem::query()->create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 5,
        ]);

        $this->post(route('cart-items.store'), [
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'quantity' => 1,
        ])->assertSessionHasErrors('product_id');

        $this->assertDatabaseCount('cart_items', 1);
    }

    #[Group('module-payments')]
    public function test_payment_becoming_paid_decreases_stock_once_regardless_of_order_processing(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = $this->customer();
        $product = $this->product(stock: 5);
        $order = $this->order($customer, 'pending');
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 5,
        ]);
        $payment = $order->payment()->create(['amount' => 10, 'payment_method' => 'cash', 'payment_status' => 'pending']);

        $this->put(route('orders.update', $order), ['status' => 'processing'])->assertRedirect();
        $this->assertSame(5, $product->fresh()->stock);
        $this->put(route('orders.update', $order), ['status' => 'processing'])->assertRedirect();
        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertRedirect();
        $this->assertSame(3, $product->fresh()->stock);
        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'completed'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'completed'])->assertRedirect();

        $this->assertSame('completed', $order->fresh()->status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    #[Group('module-orders')]
    public function test_an_order_cannot_skip_processing_and_paid_payment_requires_stock(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = $this->customer();
        $product = $this->product(stock: 1);
        $order = $this->order($customer, 'pending');
        OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 2,
            'price' => 5,
        ]);
        $payment = $order->payment()->create(['amount' => 10, 'payment_method' => 'cash', 'payment_status' => 'pending']);

        $this->put(route('orders.update', $order), ['status' => 'completed'])->assertSessionHasErrors('status');
        $this->put(route('orders.update', $order), ['status' => 'processing'])->assertRedirect();
        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertSessionHasErrors('payment_status');

        $this->assertSame('processing', $order->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->payment_status);
        $this->assertSame(1, $product->fresh()->stock);
    }

    #[Group('module-orders')]
    public function test_an_empty_pending_order_cannot_be_processed(): void
    {
        $this->actingAs(User::factory()->create());
        $order = $this->order($this->customer(), 'pending', '0.00');

        $this->put(route('orders.update', $order), ['status' => 'processing'])
            ->assertSessionHasErrors('status');

        $this->assertSame('pending', $order->fresh()->status);
    }

    #[Group('module-orders')]
    public function test_cancelling_or_deleting_a_processing_order_restores_its_reserved_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 5);
        $order = $this->order($this->customer(), 'pending');
        $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);

        $payment = $order->payment()->create(['amount' => 10, 'payment_method' => 'cash', 'payment_status' => 'pending']);
        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'processing'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'cancelled'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'cancelled'])->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame('cancelled', $order->fresh()->status);

        $other = $this->order($this->customer(), 'pending');
        $other->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);
        $otherPayment = $other->payment()->create(['amount' => 10, 'payment_method' => 'cash', 'payment_status' => 'pending']);
        $this->put(route('payments.update', $otherPayment), ['payment_status' => 'paid'])->assertRedirect();
        $this->put(route('orders.update', $other), ['status' => 'processing'])->assertRedirect();
        $this->delete(route('orders.destroy', $other))->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertModelMissing($other);
    }

    #[Group('module-order-items')]
    public function test_processing_order_lines_cannot_be_changed_after_stock_is_reserved(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 5);
        $order = $this->order($this->customer(), 'pending');
        $item = $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);
        $this->put(route('orders.update', $order), ['status' => 'processing'])->assertRedirect();

        $this->post(route('order-items.store'), ['order_id' => $order->id, 'product_id' => $this->product(stock: 5)->id, 'quantity' => 1])
            ->assertSessionHasErrors('order_id');
        $this->put(route('order-items.update', $item), ['quantity' => 1])->assertSessionHasErrors('order_id');
        $this->delete(route('order-items.destroy', $item))->assertSessionHasErrors('order_id');

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertSame(2, $item->fresh()->quantity);
    }

    #[Group('module-orders')]
    public function test_unpaid_processing_orders_never_change_stock_on_completion_or_cancellation(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 5);
        $order = $this->order($this->customer(), 'processing');
        $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);
        $cancelled = $this->order($this->customer(), 'processing');
        $cancelled->orderItems()->create(['product_id' => $product->id, 'quantity' => 1, 'price' => 5]);

        $this->put(route('orders.update', $cancelled), ['status' => 'cancelled'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'completed'])->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
    }

    #[Group('module-orders')]
    public function test_saving_an_existing_processing_order_does_not_reserve_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 5);
        $order = $this->order($this->customer(), 'processing');
        $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);

        $this->put(route('orders.update', $order), ['status' => 'processing'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'processing'])->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertNull($order->fresh()->stock_deducted_at);
    }

    #[Group('module-payments')]
    public function test_paying_rolls_back_all_stock_changes_if_any_item_is_unavailable(): void
    {
        $this->actingAs(User::factory()->create());
        $available = $this->product(stock: 5);
        $insufficient = $this->product(stock: 1);
        $order = $this->order($this->customer(), 'pending');
        $order->orderItems()->create(['product_id' => $available->id, 'quantity' => 2, 'price' => 5]);
        $order->orderItems()->create(['product_id' => $insufficient->id, 'quantity' => 2, 'price' => 5]);
        $payment = $order->payment()->create(['amount' => 20, 'payment_method' => 'cash', 'payment_status' => 'pending']);

        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertSessionHasErrors('payment_status');

        $this->assertSame('pending', $order->fresh()->status);
        $this->assertSame('pending', $payment->fresh()->payment_status);
        $this->assertNull($order->fresh()->stock_deducted_at);
        $this->assertSame(5, $available->fresh()->stock);
        $this->assertSame(1, $insufficient->fresh()->stock);
    }

    #[Group('module-payments')]
    public function test_paid_pending_order_lines_cannot_change_and_reversing_payment_restores_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 5);
        $order = $this->order($this->customer(), 'pending');
        $item = $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);
        $payment = $order->payment()->create(['amount' => 10, 'payment_method' => 'cash', 'payment_status' => 'pending']);

        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertRedirect();
        $this->put(route('order-items.update', $item), ['quantity' => 1])->assertSessionHasErrors('order_id');
        $this->delete(route('order-items.destroy', $item))->assertSessionHasErrors('order_id');
        $this->put(route('payments.update', $payment), ['payment_status' => 'failed'])->assertRedirect();
        $this->put(route('payments.update', $payment), ['payment_status' => 'failed'])->assertRedirect();
        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertRedirect();
        $this->assertSame(3, $product->fresh()->stock);
        $this->put(route('payments.update', $payment), ['payment_status' => 'pending'])->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertNull($order->fresh()->stock_deducted_at);
        $this->assertSame(2, $item->fresh()->quantity);
    }

    #[Group('module-payments')]
    public function test_completed_paid_order_cannot_release_stock_by_reversing_or_deleting_payment(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 5);
        $order = $this->order($this->customer(), 'pending');
        $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);
        $payment = $order->payment()->create(['amount' => 10, 'payment_method' => 'cash', 'payment_status' => 'pending']);

        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'processing'])->assertRedirect();
        $this->put(route('orders.update', $order), ['status' => 'completed'])->assertRedirect();
        $this->put(route('payments.update', $payment), ['payment_status' => 'failed'])->assertSessionHasErrors('payment_status');
        $this->delete(route('payments.destroy', $payment))->assertSessionHasErrors('payment_status');

        $this->assertSame('paid', $payment->fresh()->payment_status);
        $this->assertSame(3, $product->fresh()->stock);
    }

    #[Group('module-payments')]
    public function test_paid_payment_cannot_move_orders_and_deleting_it_restores_stock(): void
    {
        $this->actingAs(User::factory()->create());
        $product = $this->product(stock: 5);
        $order = $this->order($this->customer(), 'pending');
        $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);
        $otherOrder = $this->order($this->customer(), 'pending');
        $payment = $order->payment()->create(['amount' => 10, 'payment_method' => 'cash', 'payment_status' => 'pending']);

        $this->put(route('payments.update', $payment), ['payment_status' => 'paid'])->assertRedirect();
        $this->put(route('payments.update', $payment), ['order_id' => $otherOrder->id])->assertSessionHasErrors('order_id');
        $this->delete(route('payments.destroy', $payment))->assertRedirect();

        $this->assertSame(5, $product->fresh()->stock);
        $this->assertNull($order->fresh()->stock_deducted_at);
        $this->assertModelMissing($payment);
    }

    #[Group('module-payments')]
    public function test_paid_payment_cannot_be_recorded_for_a_cancelled_order(): void
    {
        $this->actingAs(User::factory()->create());
        $order = $this->order($this->customer(), 'cancelled');

        $this->post(route('payments.store'), [
            'order_id' => $order->id, 'payment_method' => 'cash', 'payment_status' => 'paid',
        ])->assertSessionHasErrors('payment_status');

        $this->assertDatabaseCount('payments', 0);
    }

    #[Group('module-payments')]
    public function test_payment_amount_always_matches_the_order_total(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = $this->customer();
        $order = $this->order($customer, 'pending', '37.50');
        $product = $this->product(stock: 5);
        $order->orderItems()->create(['product_id' => $product->id, 'quantity' => 2, 'price' => 5]);

        $this->post(route('payments.store'), [
            'order_id' => $order->id,
            'amount' => '0.01',
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ])->assertRedirect();

        $this->assertDatabaseHas('payments', [
            'order_id' => $order->id,
            'amount' => '37.50',
            'payment_method' => 'card',
            'payment_status' => 'paid',
        ]);
        $this->assertSame(3, $product->fresh()->stock);
    }

    #[Group('module-order-items')]
    public function test_payment_amount_stays_in_sync_when_order_items_change(): void
    {
        $this->actingAs(User::factory()->create());

        $customer = $this->customer();
        $product = $this->product(stock: 5);
        $order = $this->order($customer, 'pending', '5.00');
        $item = OrderItem::query()->create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'quantity' => 1,
            'price' => 5,
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'amount' => 5,
            'payment_method' => 'cash',
            'payment_status' => 'pending',
        ]);

        $this->put(route('order-items.update', $item), ['quantity' => 3])->assertRedirect();

        $this->assertSame('15.00', $order->fresh()->total_amount);
        $this->assertSame('15.00', $payment->fresh()->amount);
    }

    private function customer(): Customer
    {
        return Customer::query()->create([
            'name' => 'Aminah',
            'email' => fake()->unique()->safeEmail(),
            'password' => 'hashed-password',
        ]);
    }

    private function product(int $stock): Product
    {
        return Product::query()->create([
            'name' => 'Local Rice',
            'price' => 5,
            'stock' => $stock,
        ]);
    }

    private function order(Customer $customer, string $status, string $total = '10.00'): Order
    {
        return Order::query()->create([
            'customer_id' => $customer->id,
            'delivery_address' => '12 Market Street',
            'order_date' => now(),
            'total_amount' => $total,
            'status' => $status,
        ]);
    }
}
