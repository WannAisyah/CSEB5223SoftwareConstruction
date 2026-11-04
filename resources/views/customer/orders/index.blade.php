@extends('layouts.app', ['title' => 'My orders'])
@section('content')
    <div class="topbar"><div><h1>My orders</h1><p class="mt-2 text-sm text-zinc-600">Track your order and payment status.</p></div></div>
    <div class="table-wrap"><table>
        <thead><tr><th>Order</th><th>Date</th><th>Total</th><th>Order status</th><th>Payment</th><th></th></tr></thead>
        <tbody>
            @forelse($orders as $order)
                <tr><td>#{{ $order->id }}</td><td>{{ $order->order_date->format('d M Y') }}</td><td>${{ number_format((float) $order->total_amount, 2) }}</td><td>{{ Str::headline($order->status) }}</td><td>{{ Str::headline($order->payment?->payment_status ?? 'not recorded') }}</td><td><a class="btn secondary" href="{{ route('customer.orders.show', $order) }}">View</a></td></tr>
            @empty
                <tr><td colspan="6" class="py-12 text-center text-zinc-600">You have not placed an order yet.</td></tr>
            @endforelse
        </tbody>
    </table></div>
    <div class="mt-6">{{ $orders->links() }}</div>
@endsection
