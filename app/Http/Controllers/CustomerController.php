<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        $request->validate(['search' => 'nullable|string|max:255']);
        $search = $request->string('search')->trim()->toString();
        $customers = Customer::with(['cart', 'orders'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
            ->get();

        if (request()->expectsJson()) {
            return response()->json($customers);
        }

        return view('Customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('customers.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:customers,email',
            'password' => 'required|string|min:8',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $customer = Customer::create($validated);

        if (request()->expectsJson()) {
            return response()->json($customer->makeHidden('password'), 201);
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Customer created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        if (request()->expectsJson()) {
            return response()->json(
                $customer->load(['cart', 'orders'])->makeHidden('password')
            );
        }

        return view('Customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'email' => [
                'sometimes',
                'required',
                'email',
                Rule::unique('customers', 'email')->ignore($customer->id),
            ],
            'password' => 'nullable|string|min:8',
        ]);

        if (isset($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        }

        $customer->update($validated);

        if (request()->expectsJson()) {
            return response()->json($customer->makeHidden('password'));
        }

        return redirect()->route('customers.show', $customer)->with('success', 'Customer updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists()) {
            return back()->withErrors([
                'customer' => 'This customer cannot be deleted because they have orders.',
            ]);
        }

        $customer->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Customer deleted successfully.',
            ]);
        }

        return redirect()->route('customers.index')->with('success', 'Customer deleted successfully.');
    }
}
