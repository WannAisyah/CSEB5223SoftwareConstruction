@extends('layouts.app', ['title' => 'Order #'.$order->id])
@section('content')
    <div class="topbar"><div><a href="{{ route('customer.orders.index') }}" class="mb-3 inline-block text-sm text-zinc-600">← Back to my orders</a><h1>Order #{{ $order->id }}</h1></div><span class="badge">{{ Str::headline($order->status) }}</span></div>
    <div class="grid gap-6 md:grid-cols-3">
        <div class="panel"><h2>Delivery</h2><p class="mt-3 text-sm leading-6 text-zinc-600">{{ $order->delivery_address }}</p></div>
        <div class="panel"><h2>Payment</h2><p class="mt-3 text-sm text-zinc-600">{{ Str::headline($order->payment?->payment_method ?? 'not recorded') }} · {{ Str::headline($order->payment?->payment_status ?? 'not recorded') }}</p></div>
        <div class="panel"><h2>Total</h2><p class="mt-3 text-2xl font-semibold">${{ number_format((float) $order->total_amount, 2) }}</p></div>
    </div>
    <div class="table-wrap mt-6"><table><thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th></tr></thead><tbody>@foreach($order->orderItems as $item)<tr><td>{{ $item->product->name ?? 'Deleted product' }}</td><td>${{ number_format((float) $item->price, 2) }}</td><td>{{ $item->quantity }}</td><td>${{ number_format((float) $item->price * $item->quantity, 2) }}</td></tr>@endforeach</tbody></table></div>
@endsection
