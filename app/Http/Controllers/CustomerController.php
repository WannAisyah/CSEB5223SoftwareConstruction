<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $query = Customer::with(['cart', 'orders'])
            ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"));

        if (request()->expectsJson()) {
            return response()->json($query->get()->makeHidden('password'));
        }

        $customers = $query->paginate(15)->withQueryString();

        return view('customers.index', compact('customers'));
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
            'delivery_address' => 'nullable|string|max:255',
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $customer = DB::transaction(function () use ($validated): Customer {
            $customer = Customer::query()->create($validated);
            $customer->cart()->create(['total' => 0]);

            return $customer;
        });

        if (request()->expectsJson()) {
            return response()->json($customer->makeHidden('password'), 201);
        }

        return redirect()->route('customers.show', $customer)->with('success', "Created customer {$customer->name} (#{$customer->id}).");
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

        return view('customers.show', compact('customer'));
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
            'delivery_address' => 'sometimes|nullable|string|max:255',
        ]);

        if (($validated['password'] ?? null) !== null) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $customer->update($validated);

        if (request()->expectsJson()) {
            return response()->json($customer->makeHidden('password'));
        }

        return redirect()->route('customers.show', $customer)->with('success', "Updated customer {$customer->name} (#{$customer->id}).");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        if ($customer->orders()->exists()) {
            return back()->withErrors([
                'customer' => "{$customer->name} cannot be deleted because they have orders.",
            ]);
        }

        $customer->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Deleted customer {$customer->name} (#{$customer->id}).",
            ]);
        }

        return redirect()->route('customers.index')->with('success', "Deleted customer {$customer->name} (#{$customer->id}).");
    }
}
