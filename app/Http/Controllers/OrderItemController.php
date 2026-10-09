<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $orderItems = OrderItem::with(['order', 'product'])->get();

        if (request()->expectsJson()) {
            return response()->json($orderItems);
        }

        return view('OrderItems.index', compact('orderItems'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $orders = Order::with('customer')->orderByDesc('order_date')->get();
        $products = Product::orderBy('name')->get();

        return view('OrderItems.create', compact('orders', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $validated['price'] = $product->price;

        $orderItem = DB::transaction(function () use ($validated) {
            $orderItem = OrderItem::create($validated);
            $this->syncOrderTotal($orderItem->order);

            return $orderItem;
        });

        if (request()->expectsJson()) {
            return response()->json($orderItem->load(['order', 'product']), 201);
        }

        return redirect()->route('order-items.show', $orderItem)->with('success', 'Order item added successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(OrderItem $orderItem)
    {
        if (request()->expectsJson()) {
            return response()->json(
                $orderItem->load(['order', 'product'])
            );
        }

        return view('OrderItems.show', compact('orderItem'));
    }

    public function edit(OrderItem $orderItem)
    {
        $orders = Order::with('customer')->orderByDesc('order_date')->get();
        $products = Product::orderBy('name')->get();

        return view('OrderItems.edit', compact('orderItem', 'orders', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderItem $orderItem)
    {
        $validated = $request->validate([
            'order_id' => 'sometimes|required|exists:orders,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        if (isset($validated['product_id'])) {
            $product = Product::findOrFail($validated['product_id']);
            $validated['price'] = $product->price;
        }

        DB::transaction(function () use ($orderItem, $validated) {
            $previousOrder = $orderItem->order;
            $orderItem->update($validated);
            $this->syncOrderTotal($previousOrder);
            $this->syncOrderTotal($orderItem->fresh()->order);
        });

        if (request()->expectsJson()) {
            return response()->json($orderItem->load(['order', 'product']));
        }

        return redirect()->route('order-items.show', $orderItem)->with('success', 'Order item updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderItem $orderItem)
    {
        DB::transaction(function () use ($orderItem) {
            $order = $orderItem->order;
            $orderItem->delete();
            $this->syncOrderTotal($order);
        });

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Order item deleted successfully.',
            ]);
        }

        return redirect()->route('order-items.index')->with('success', 'Order item deleted successfully.');
    }

    private function syncOrderTotal(Order $order): void
    {
        $order->update([
            'total_amount' => $order->orderItems()->sum(DB::raw('quantity * price')),
        ]);
    }
}
