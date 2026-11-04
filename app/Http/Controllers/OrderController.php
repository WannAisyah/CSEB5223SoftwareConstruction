<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class OrderController extends Controller
{
    private const STATUS_TRANSITIONS = [
        'pending' => ['pending', 'processing', 'cancelled'],
        'processing' => ['processing', 'completed', 'cancelled'],
        'completed' => ['completed'],
        'cancelled' => ['cancelled'],
    ];

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(array_keys(self::STATUS_TRANSITIONS))],
        ]);
        $search = $request->string('search')->trim()->toString();
        $query = Order::with(['customer', 'orderItems.product', 'payment'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('customer', fn ($customerQuery) => $customerQuery
                        ->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%"));

                    if (ctype_digit($search)) {
                        $query->orWhereKey((int) $search);
                    }
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->string('status')))
            ->latest('order_date');

        if (request()->expectsJson()) {
            return response()->json($query->get());
        }

        $orders = $query->paginate(15)->withQueryString();

        return view('orders.index', compact('orders'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::orderBy('name')->get();

        $statuses = ['pending'];

        return view('orders.create', compact('customers', 'statuses'));
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
        ]);

        $validated['total_amount'] = 0;
        $validated['status'] = 'pending';

        $order = Order::create($validated);

        if (request()->expectsJson()) {
            return response()->json($order, 201);
        }

        return redirect()->route('orders.show', $order)
            ->with('success', "Created pending order #{$order->id} for {$order->customer->name}.");
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

        return view('orders.show', compact('order'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Order $order)
    {
        $customers = Customer::orderBy('name')->get();
        $statuses = self::STATUS_TRANSITIONS[$order->status] ?? [$order->status];

        return view('orders.edit', compact('order', 'customers', 'statuses'));
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
            'status' => ['sometimes', 'required', Rule::in(array_keys(self::STATUS_TRANSITIONS))],
        ]);

        DB::transaction(function () use ($order, $validated) {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);
            $newStatus = $validated['status'] ?? $lockedOrder->status;
            $allowedStatuses = self::STATUS_TRANSITIONS[$lockedOrder->status] ?? [];

            if (! in_array($newStatus, $allowedStatuses, true)) {
                throw ValidationException::withMessages([
                    'status' => "An order cannot move from {$lockedOrder->status} to {$newStatus}.",
                ]);
            }

            if ($lockedOrder->status === 'completed' && count($validated) > (isset($validated['status']) ? 1 : 0)) {
                throw ValidationException::withMessages(['status' => 'Completed orders are final and cannot be changed.']);
            }

            if ($newStatus === 'processing' && $lockedOrder->status === 'pending' && ! $lockedOrder->orderItems()->exists()) {
                throw ValidationException::withMessages(['status' => "Add at least one item before processing order #{$lockedOrder->id}."]);
            }

            if ($newStatus === 'cancelled' && $lockedOrder->status !== 'cancelled') {
                $lockedOrder->restoreStock();
            }

            $lockedOrder->fill($validated)->save();
            $order->setRawAttributes($lockedOrder->getAttributes(), true);
        }, attempts: 3);

        if (request()->expectsJson()) {
            return response()->json($order);
        }

        return redirect()->route('orders.show', $order)
            ->with('success', "Updated order #{$order->id}; status: {$order->status}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Order $order)
    {
        DB::transaction(function () use ($order): void {
            $lockedOrder = Order::query()->lockForUpdate()->findOrFail($order->id);

            if ($lockedOrder->status === 'completed') {
                throw ValidationException::withMessages(['order' => "Order #{$order->id} is completed and cannot be deleted."]);
            }

            $lockedOrder->restoreStock();

            $lockedOrder->delete();
        }, attempts: 3);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Deleted order #{$order->id} for {$order->customer->name}.",
            ]);
        }

        return redirect()->route('orders.index')
            ->with('success', "Deleted order #{$order->id} for {$order->customer->name}.");
    }
}
