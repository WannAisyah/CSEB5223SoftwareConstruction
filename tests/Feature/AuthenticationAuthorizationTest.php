<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('module-auth')]
class AuthenticationAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_and_administrator_use_one_sign_in_page(): void
    {
        $this->get(route('customer.login'))
            ->assertOk()
            ->assertSee('Sign in as customer')
            ->assertSee('Administrator');

        $this->get(route('customer.login', ['type' => 'admin']))
            ->assertOk()
            ->assertSee('Sign in as administrator');

        $this->get(route('login'))
            ->assertRedirect(route('customer.login', ['type' => 'admin']));
    }

    public function test_admin_management_routes_require_an_administrator(): void
    {
        $this->get(route('customers.index'))->assertRedirect(route('login'));

        $customer = Customer::query()->create([
            'name' => 'Shopper',
            'email' => 'shopper@example.test',
            'password' => 'password123',
        ]);
        $this->actingAs($customer, 'customer')
            ->get(route('customers.index'))
            ->assertRedirect(route('login'));

        $this->actingAs(User::factory()->create(), 'web')
            ->get(route('customers.index'))
            ->assertOk();
    }

    public function test_customer_registration_creates_a_private_cart_and_logs_the_customer_in(): void
    {
        $this->post(route('customer.register.store'), [
            'name' => 'Aminah',
            'email' => 'aminah@example.test',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'delivery_address' => '15 Jalan Merdeka',
        ])->assertRedirect(route('customer.cart.show'));

        $customer = Customer::query()->where('email', 'aminah@example.test')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $customer->password));
        $this->assertNotNull($customer->cart);
        $this->assertSame('15 Jalan Merdeka', $customer->delivery_address);
        $this->assertAuthenticatedAs($customer, 'customer');
    }

    public function test_leaving_password_blank_when_editing_a_customer_keeps_the_existing_password(): void
    {
        $admin = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Original Name',
            'email' => 'customer@example.test',
            'password' => 'password123',
        ]);
        $password = $customer->password;

        $this->actingAs($admin, 'web')->put(route('customers.update', $customer), [
            'name' => 'Updated Name',
            'password' => '',
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertSame('Updated Name', $customer->fresh()->name);
        $this->assertSame($password, $customer->fresh()->password);
        $this->assertTrue(Hash::check('password123', $customer->fresh()->password));
    }

    public function test_administrator_can_save_a_customer_default_address(): void
    {
        $admin = User::factory()->create();
        $customer = Customer::query()->create([
            'name' => 'Aminah',
            'email' => 'aminah@example.test',
            'password' => 'password123',
        ]);

        $this->actingAs($admin, 'web')->put(route('customers.update', $customer), [
            'delivery_address' => '27 Jalan Ampang',
        ])->assertRedirect(route('customers.show', $customer));

        $this->assertSame('27 Jalan Ampang', $customer->fresh()->delivery_address);
    }

    public function test_administrator_can_log_in_with_the_web_guard(): void
    {
        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'password' => 'password123',
        ]);

        $this->post(route('admin.login.store'), [
            'email' => 'admin@example.test',
            'password' => 'password123',
        ])->assertRedirect(route('products.index'));

        $this->assertAuthenticatedAs($admin, 'web');
        $this->assertGuest('customer');
    }

    public function test_customer_login_verifies_turnstile_when_configured(): void
    {
        config()->set('services.turnstile.site_key', 'site-key');
        config()->set('services.turnstile.secret_key', 'secret-key');
        Http::preventStrayRequests();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => true]),
        ]);
        $customer = Customer::query()->create([
            'name' => 'Aminah',
            'email' => 'aminah@example.test',
            'password' => 'password123',
        ]);
        $customer->cart()->create(['total' => 0]);

        $this->withSession(['url.intended' => route('customers.index')])->post(route('customer.login.store'), [
            'email' => 'aminah@example.test',
            'password' => 'password123',
            'cf-turnstile-response' => 'verified-token',
        ])->assertRedirect(route('customer.cart.show'));

        $this->assertAuthenticatedAs($customer, 'customer');
        Http::assertSent(fn (Request $request): bool => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
            && $request['response'] === 'verified-token');
    }

    public function test_customer_login_rejects_failed_turnstile_verification(): void
    {
        config()->set('services.turnstile.site_key', 'site-key');
        config()->set('services.turnstile.secret_key', 'secret-key');
        Http::preventStrayRequests();
        Http::fake([
            'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response(['success' => false]),
        ]);
        Customer::query()->create([
            'name' => 'Aminah',
            'email' => 'aminah@example.test',
            'password' => 'password123',
        ]);

        $this->post(route('customer.login.store'), [
            'email' => 'aminah@example.test',
            'password' => 'password123',
            'cf-turnstile-response' => 'failed-token',
        ])->assertSessionHasErrors('cf-turnstile-response');

        $this->assertGuest('customer');
        Http::assertSentCount(1);
    }
}
