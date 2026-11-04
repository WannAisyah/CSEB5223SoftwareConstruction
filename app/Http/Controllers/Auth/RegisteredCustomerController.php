<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Rules\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisteredCustomerController extends Controller
{
    public function create(): View|RedirectResponse
    {
        if (Auth::guard('customer')->check()) {
            return redirect()->route('customer.cart.show');
        }

        return view('auth.register');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:customers,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'delivery_address' => ['nullable', 'string', 'max:255'],
            'cf-turnstile-response' => $this->turnstileRules(),
        ]);

        $customer = DB::transaction(function () use ($validated): Customer {
            $customer = Customer::query()->create([
                'name' => $validated['name'],
                'email' => $validated['email'],
                'password' => $validated['password'],
                'delivery_address' => $validated['delivery_address'] ?? null,
            ]);
            $customer->cart()->create(['total' => 0]);

            return $customer;
        });

        Auth::guard('web')->logout();
        Auth::guard('customer')->login($customer);
        $request->session()->regenerate();

        return redirect()->route('customer.cart.show');
    }

    /** @return array<int, mixed> */
    private function turnstileRules(): array
    {
        $required = ! app()->environment(['local', 'testing'])
            || filled(config('services.turnstile.site_key'));

        return [Rule::requiredIf($required), 'nullable', new Turnstile];
    }
}
