@extends('layouts.app', ['title' => 'Orders'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('orders.index') }}" class="btn secondary">← Back to orders</a><a href="{{ route('orders.edit', $order) }}" class="btn">Edit order</a></div>

        <div class="panel" >
            <h1>Order #{{ $order->id }}</h1>
            <p class="meta">Customer: {{ $order->customer->name ?? 'Unknown' }}</p>
            <p class="meta">Date: {{ $order->order_date }}</p>
            <p class="meta">Delivery address: {{ $order->delivery_address ?? 'Not recorded' }}</p>
            <p class="meta">Total: ${{ number_format((float) $order->total_amount, 2) }}</p>
            <p class="meta">Status: {{ $order->status ?? 'Pending' }}</p>
        </div>

        

        <div class="panel" >
            <form action="{{ route('orders.destroy', $order) }}" method="POST"
                onsubmit="return confirm('Delete this order?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete order</button>
            </form>
        </div>
    
@endsection
