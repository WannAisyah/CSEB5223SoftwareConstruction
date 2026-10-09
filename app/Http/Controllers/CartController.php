<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View|JsonResponse
    {
        $request->validate(['search' => 'nullable|integer|min:1']);
        $carts = Cart::with(['customer', 'cartItems.product'])
            ->when($request->filled('search'), fn ($query) => $query->whereKey($request->integer('search')))
            ->get();

        if (request()->expectsJson()) {
            return response()->json($carts);
        }

        return view('Carts.index', compact('carts'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::doesntHave('cart')->orderBy('name')->get();

        return view('carts.create', compact('customers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id|unique:carts,customer_id',
            'total' => 'nullable|numeric|min:0',
        ]);

        $validated['total'] = $validated['total'] ?? 0;

        $cart = Cart::create($validated);

        if (request()->expectsJson()) {
            return response()->json($cart, 201);
        }

        return redirect()->route('carts.show', $cart)->with('success', 'Cart created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Cart $cart)
    {
        if (request()->expectsJson()) {
            return response()->json(
                $cart->load(['customer', 'cartItems.product'])
            );
        }

        return view('Carts.show', compact('cart'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Cart $cart)
    {
        $customers = Customer::where(function ($query) use ($cart) {
            $query->doesntHave('cart')->orWhereKey($cart->customer_id);
        })->orderBy('name')->get();

        return view('carts.edit', compact('cart', 'customers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Cart $cart)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id|unique:carts,customer_id,'.$cart->id,
            'total' => 'sometimes|required|numeric|min:0',
        ]);

        $cart->update($validated);

        if (request()->expectsJson()) {
            return response()->json($cart);
        }

        return redirect()->route('carts.show', $cart)->with('success', 'Cart updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Cart $cart)
    {
        $cart->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Cart deleted successfully.',
            ]);
        }

        return redirect()->route('carts.index')->with('success', 'Cart deleted successfully.');
    }
}
