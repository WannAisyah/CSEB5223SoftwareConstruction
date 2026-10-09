<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orders = Order::with(['customer', 'orderItems.product', 'payment'])->get();

        if (request()->expectsJson()) {
            return response()->json($orders);
        }

        return view('Orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        return view('orders.create', compact('customers'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'delivery_address' => 'required|string|max:255',
            'order_date' => 'required|date',
            'total_amount' => 'nullable|numeric|min:0',
            'status' => 'sometimes|string|max:255',
        ]);

        $validated['total_amount'] = $validated['total_amount'] ?? 0;

        $order = Order::create($validated);

        if (request()->expectsJson()) {
            return response()->json($order, 201);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Order $order)
    {
        if (request()->expectsJson()) {
            return response()->json(
                $order->load(['customer', 'orderItems.product', 'payment'])
            );
        }

        return view('Orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $customers = Customer::orderBy('name')->get();

        return view('orders.edit', compact('order', 'customers'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'customer_id' => 'sometimes|required|exists:customers,id',
            'delivery_address' => 'sometimes|required|string|max:255',
            'order_date' => 'sometimes|required|date',
            'total_amount' => 'sometimes|required|numeric|min:0',
            'status' => 'sometimes|required|string|max:255',
        ]);

        $order->update($validated);

        if (request()->expectsJson()) {
            return response()->json($order);
        }

        return redirect()->route('orders.show', $order)->with('success', 'Order updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        $order->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Order deleted successfully.',
            ]);
        }

        return redirect()->route('orders.index')->with('success', 'Order deleted successfully.');
    }
}
