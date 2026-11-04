<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Rules\Turnstile;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerSessionController extends Controller
{
    public function create(Request $request): View|RedirectResponse
    {
        $accountType = $request->string('type')->toString() === 'admin' ? 'admin' : 'customer';

        if ($accountType === 'admin' && Auth::guard('web')->check()) {
            return redirect()->route('products.index');
        }

        if ($accountType === 'customer' && Auth::guard('customer')->check()) {
            return redirect()->route('customer.cart.show');
        }

        return view('auth.login', compact('accountType'));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'cf-turnstile-response' => $this->turnstileRules(),
        ]);

        if (! Auth::guard('customer')->attempt([
            'email' => $validated['email'],
            'password' => $validated['password'],
        ])) {
            throw ValidationException::withMessages([
                'email' => 'The provided credentials do not match our records.',
            ]);
        }

        Auth::guard('web')->logout();
        $request->session()->regenerate();

        /** @var Customer $customer */
        $customer = Auth::guard('customer')->user();
        $customer->cart()->firstOrCreate([], ['total' => 0]);

        return redirect()->route('customer.cart.show');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('customer')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('products.index');
    }

    /** @return array<int, mixed> */
    private function turnstileRules(): array
    {
        $required = ! app()->environment(['local', 'testing'])
            || filled(config('services.turnstile.site_key'));

        return [Rule::requiredIf($required), 'nullable', new Turnstile];
    }
}
