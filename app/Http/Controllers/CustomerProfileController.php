<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerProfileController extends Controller
{
    public function edit(): View
    {
        return view('customer.profile', ['customer' => $this->customer()]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('customers', 'email')->ignore($this->customer())],
            'delivery_address' => ['nullable', 'string', 'max:255'],
        ]);

        $customer = $this->customer();
        $customer->update($validated);

        return redirect()->route('customer.profile.edit')
            ->with('success', "Saved profile for {$customer->name} ({$customer->email}).");
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
