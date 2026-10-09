<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $payments = Payment::with('order')->get();

        if (request()->expectsJson()) {
            return response()->json($payments);
        }

        return view('Payments.index', compact('payments'));
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
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:255',
            'payment_status' => 'sometimes|string|max:255',
            'payment_date' => 'nullable|date',
        ]);

        $payment = Payment::create($validated);

        if (request()->expectsJson()) {
            return response()->json($payment->load('order'), 201);
        }

        return redirect()->route('payments.show', $payment)->with('success', 'Payment recorded successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Payment $payment)
    {
        if (request()->expectsJson()) {
            return response()->json($payment->load('order'));
        }

        return view('Payments.show', compact('payment'));
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
            'amount' => 'sometimes|required|numeric|min:0',
            'payment_method' => 'sometimes|required|string|max:255',
            'payment_status' => 'sometimes|required|string|max:255',
            'payment_date' => 'nullable|date',
        ]);

        $payment->update($validated);

        if (request()->expectsJson()) {
            return response()->json($payment->load('order'));
        }

        return redirect()->route('payments.show', $payment)->with('success', 'Payment updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payment $payment)
    {
        $payment->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Payment deleted successfully.',
            ]);
        }

        return redirect()->route('payments.index')->with('success', 'Payment deleted successfully.');
    }
}
