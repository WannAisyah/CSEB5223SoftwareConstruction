<?php

namespace Tests\Feature;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SearchAndPaymentFeatureTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_search_matches_part_of_a_name(): void
    {
        $this->customer('Aminah Ahmad', 'aminah@example.test');
        $this->customer('Nora Hassan', 'nora@example.test');

        $this->get(route('customers.index', ['search' => '  Ahmad  ']))
            ->assertOk()
            ->assertSee('Aminah Ahmad')
            ->assertDontSee('Nora Hassan');

        $this->get(route('customers.index', ['search' => 'Unmatched']))
            ->assertOk()
            ->assertSee('No customers match your search.');
    }

    public function test_cart_search_matches_the_exact_id_and_rejects_invalid_input(): void
    {
        $customer = $this->customer('Aminah', 'aminah@example.test');
        $otherCustomer = $this->customer('Nora', 'nora@example.test');
        Cart::query()->forceCreate(['id' => 1, 'customer_id' => $customer->id, 'total' => 0]);
        Cart::query()->forceCreate(['id' => 10, 'customer_id' => $otherCustomer->id, 'total' => 0]);

        $this->getJson(route('carts.index', ['search' => 1]))
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', 1);

        $this->getJson(route('carts.index', ['search' => 'invalid']))
            ->assertUnprocessable()
            ->assertJsonValidationErrors('search');
    }

    public function test_payments_display_readable_labels_and_preserve_method_values(): void
    {
        $customer = $this->customer('Aminah', 'aminah@example.test');
        $order = Order::query()->create([
            'customer_id' => $customer->id,
            'delivery_address' => '12 Market Street',
            'order_date' => now(),
            'total_amount' => 10,
            'status' => 'pending',
        ]);
        $payment = Payment::query()->create([
            'order_id' => $order->id,
            'amount' => 10,
            'payment_method' => 'bank_transfer',
            'payment_status' => 'pending',
        ]);

        $this->get(route('payments.index'))
            ->assertOk()
            ->assertSee('Bank Transfer')
            ->assertSee('Pending')
            ->assertDontSee('bank_transfer');
        $this->get(route('payments.show', $payment))
            ->assertOk()
            ->assertSee('Bank Transfer');
        $this->get(route('payments.edit', $payment))
            ->assertOk()
            ->assertSee('value="bank_transfer" selected', false);

        $this->assertDatabaseHas('payments', ['id' => $payment->id, 'payment_method' => 'bank_transfer']);
    }

    private function customer(string $name, string $email): Customer
    {
        return Customer::query()->create([
            'name' => $name,
            'email' => $email,
            'password' => 'hashed-password',
        ]);
    }
}
