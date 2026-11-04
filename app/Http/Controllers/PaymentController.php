<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(['pending', 'paid', 'failed'])],
        ]);
        $search = $request->string('search')->trim()->toString();
        $query = Payment::with('order.customer')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('payment_method', 'like', "%{$search}%")
                        ->orWhereHas('order.customer', fn ($customerQuery) => $customerQuery
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%"));

                    if (ctype_digit($search)) {
                        $query->orWhere('order_id', (int) $search);
                    }
                });
            })
            ->when($request->filled('status'), fn ($query) => $query->where('payment_status', $request->string('status')))
            ->latest();

        if (request()->expectsJson()) {
            return response()->json($query->get());
        }

        $payments = $query->paginate(15)->withQueryString();

        return view('payments.index', compact('payments'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $orders = Order::doesntHave('payment')->with('customer')->orderByDesc('order_date')->get();

        return view('payments.create', compact('orders'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id|unique:payments,order_id',
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer'])],
            'payment_status' => ['sometimes', Rule::in(['pending', 'paid', 'failed'])],
            'payment_date' => 'nullable|date',
        ]);
        $payment = DB::transaction(function () use ($validated): Payment {
            $order = Order::query()->lockForUpdate()->findOrFail($validated['order_id']);

            if (($validated['payment_status'] ?? 'pending') === 'paid') {
                $this->ensureOrderCanBePaid($order);
                $order->deductStock();
            }

            return Payment::create([...$validated, 'amount' => $order->total_amount]);
        }, attempts: 3);

        if (request()->expectsJson()) {
            return response()->json($payment->load('order'), 201);
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', 'Recorded $'.number_format((float) $payment->amount, 2)." payment for order #{$payment->order_id}; status: {$payment->payment_status}.");
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        if (request()->expectsJson()) {
            return response()->json($payment->load('order'));
        }

        return view('payments.show', compact('payment'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payment $payment)
    {
        $orders = Order::where(function ($query) use ($payment) {
            $query->doesntHave('payment')->orWhereKey($payment->order_id);
        })->with('customer')->orderByDesc('order_date')->get();

        return view('payments.edit', compact('payment', 'orders'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'order_id' => 'sometimes|required|exists:orders,id|unique:payments,order_id,'.$payment->id,
            'payment_method' => ['sometimes', 'required', Rule::in(['cash', 'card', 'bank_transfer'])],
            'payment_status' => ['sometimes', 'required', Rule::in(['pending', 'paid', 'failed'])],
            'payment_date' => 'nullable|date',
        ]);
        DB::transaction(function () use ($payment, $validated): void {
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);
            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $newStatus = $validated['payment_status'] ?? $lockedPayment->payment_status;
            $newOrderId = (int) ($validated['order_id'] ?? $lockedPayment->order_id);

            if ($newOrderId !== $lockedPayment->order_id && ($newStatus === 'paid' || $order->stock_deducted_at !== null)) {
                throw ValidationException::withMessages(['order_id' => 'A paid payment cannot be moved to another order.']);
            }

            if ($newOrderId !== $lockedPayment->order_id) {
                $order = Order::query()->lockForUpdate()->findOrFail($newOrderId);
            }

            if ($newStatus === 'paid') {
                $this->ensureOrderCanBePaid($order);
                $order->deductStock();
            } elseif ($order->stock_deducted_at !== null) {
                if ($order->status === 'completed') {
                    throw ValidationException::withMessages(['payment_status' => 'A completed order cannot have its paid payment reversed.']);
                }

                $order->restoreStock();
            }

            $lockedPayment->update([...$validated, 'amount' => $order->total_amount]);
            $payment->setRawAttributes($lockedPayment->getAttributes(), true);
        }, attempts: 3);

        if (request()->expectsJson()) {
            return response()->json($payment->load('order'));
        }

        return redirect()->route('payments.show', $payment)
            ->with('success', "Updated payment for order #{$payment->order_id}; status: {$payment->payment_status}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        DB::transaction(function () use ($payment): void {
            $order = Order::query()->lockForUpdate()->findOrFail($payment->order_id);

            if ($order->status === 'completed' && $order->stock_deducted_at !== null) {
                throw ValidationException::withMessages(['payment_status' => 'A completed order cannot have its paid payment deleted.']);
            }

            $lockedPayment = Payment::query()->lockForUpdate()->findOrFail($payment->id);
            $order->restoreStock();
            $lockedPayment->delete();
        }, attempts: 3);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Deleted payment #{$payment->id} for order #{$payment->order_id}.",
            ]);
        }

        return redirect()->route('payments.index')
            ->with('success', "Deleted payment #{$payment->id} for order #{$payment->order_id}.");
    }

    private function ensureOrderCanBePaid(Order $order): void
    {
        if ($order->status === 'cancelled') {
            throw ValidationException::withMessages(['payment_status' => "Cancelled order #{$order->id} cannot be marked paid."]);
        }
    }
}
