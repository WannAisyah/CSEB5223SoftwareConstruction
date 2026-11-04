<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $request->string('search')->trim()->toString();
        $query = OrderItem::with(['order.customer', 'product'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('order.customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            });

        if (request()->expectsJson()) {
            return response()->json($query->get());
        }

        $orderItems = $query->paginate(15)->withQueryString();

        return view('order-items.index', compact('orderItems'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $orders = Order::with('customer')->where('status', 'pending')->whereNull('stock_deducted_at')
            ->whereDoesntHave('payment', fn ($query) => $query->where('payment_status', 'paid'))
            ->orderByDesc('order_date')->get();
        $products = Product::orderBy('name')->get();

        return view('order-items.create', compact('orders', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => [
                'required',
                Rule::exists('orders', 'id')->where('status', 'pending'),
            ],
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
                Rule::unique('order_items', 'product_id')->where(
                    fn ($query) => $query->where('order_id', $request->integer('order_id'))
                ),
            ],
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $this->ensureProductIsAvailable($product, $validated['quantity']);

        $validated['price'] = $product->price;

        $orderItem = DB::transaction(function () use ($validated) {
            $this->ensureOrderCanBeChanged(Order::query()->lockForUpdate()->findOrFail($validated['order_id']));
            $orderItem = OrderItem::create($validated);
            $this->syncOrderTotal($orderItem->order);

            return $orderItem;
        });

        if (request()->expectsJson()) {
            return response()->json($orderItem->load(['order', 'product']), 201);
        }

        return redirect()->route('order-items.show', $orderItem)
            ->with('success', "Added {$orderItem->quantity} × {$product->name} to order #{$orderItem->order_id}.");
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

        return view('order-items.show', compact('orderItem'));
    }

    public function edit(OrderItem $orderItem)
    {
        $this->ensureOrderCanBeChanged($orderItem->order);
        $orders = Order::with('customer')->where('status', 'pending')->whereNull('stock_deducted_at')
            ->whereDoesntHave('payment', fn ($query) => $query->where('payment_status', 'paid'))
            ->orderByDesc('order_date')->get();
        $products = Product::orderBy('name')->get();

        return view('order-items.edit', compact('orderItem', 'orders', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, OrderItem $orderItem)
    {
        $this->ensureOrderCanBeChanged($orderItem->order);
        $orderId = $request->has('order_id') ? $request->integer('order_id') : $orderItem->order_id;

        $validated = $request->validate([
            'order_id' => [
                'sometimes',
                'required',
                Rule::exists('orders', 'id')->where('status', 'pending'),
            ],
            'product_id' => [
                'sometimes',
                'required',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
                Rule::unique('order_items', 'product_id')
                    ->where(fn ($query) => $query->where('order_id', $orderId))
                    ->ignore($orderItem),
            ],
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        $product = Product::find($validated['product_id'] ?? $orderItem->product_id);

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => "Product #{$orderItem->product_id} is no longer available.",
            ]);
        }

        $this->ensureProductIsAvailable($product, $validated['quantity'] ?? $orderItem->quantity);
        $validated['price'] = $product->price;

        DB::transaction(function () use ($orderItem, $validated) {
            $this->ensureOrderCanBeChanged(Order::query()->lockForUpdate()->findOrFail($orderItem->order_id));
            if (isset($validated['order_id']) && $validated['order_id'] !== $orderItem->order_id) {
                $this->ensureOrderCanBeChanged(Order::query()->lockForUpdate()->findOrFail($validated['order_id']));
            }
            $previousOrder = $orderItem->order;
            $orderItem->update($validated);
            $this->syncOrderTotal($previousOrder);
            $this->syncOrderTotal($orderItem->fresh()->order);
        });

        if (request()->expectsJson()) {
            return response()->json($orderItem->load(['order', 'product']));
        }

        return redirect()->route('order-items.show', $orderItem)
            ->with('success', "Updated {$product->name} to {$orderItem->quantity} in order #{$orderItem->order_id}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(OrderItem $orderItem)
    {
        $this->ensureOrderCanBeChanged($orderItem->order);

        DB::transaction(function () use ($orderItem) {
            $this->ensureOrderCanBeChanged(Order::query()->lockForUpdate()->findOrFail($orderItem->order_id));
            $order = $orderItem->order;
            $orderItem->delete();
            $this->syncOrderTotal($order);
        });

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Removed '.($orderItem->product?->name ?? "product #{$orderItem->product_id}")." ({$orderItem->quantity}) from order #{$orderItem->order_id}.",
            ]);
        }

        return redirect()->route('order-items.index')->with('success', 'Removed '.($orderItem->product?->name ?? "product #{$orderItem->product_id}")." ({$orderItem->quantity}) from order #{$orderItem->order_id}.");
    }

    private function syncOrderTotal(Order $order): void
    {
        $total = $order->orderItems()->sum(DB::raw('quantity * price'));

        $order->update(['total_amount' => $total]);
        $order->payment()->update(['amount' => $total]);
    }

    private function ensureOrderCanBeChanged(Order $order): void
    {
        if ($order->status !== 'pending' || $order->stock_deducted_at !== null || $order->payment?->payment_status === 'paid') {
            throw ValidationException::withMessages([
                'order_id' => 'Paid or non-pending orders cannot be changed.',
            ]);
        }
    }

    private function ensureProductIsAvailable(Product $product, int $quantity): void
    {
        if ($product->stock < $quantity) {
            $message = $product->stock === 0
                ? "{$product->name} is out of stock."
                : "Only {$product->stock} units of {$product->name} are available.";

            throw ValidationException::withMessages(['quantity' => $message]);
        }
    }
}
