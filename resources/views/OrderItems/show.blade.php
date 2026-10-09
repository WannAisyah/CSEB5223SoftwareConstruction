@extends('layouts.app', ['title' => 'Order items'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('order-items.index') }}" class="btn secondary">← Back to order items</a><a href="{{ route('order-items.edit', $orderItem) }}" class="btn">Edit order item</a></div>

        <div class="panel" >
            <h1>Order Item #{{ $orderItem->id }}</h1>
            <p class="meta">Order: #{{ $orderItem->order_id }}</p>
            <p class="meta">Product: {{ $orderItem->product->name ?? 'Unknown' }}</p>
            <p class="meta">Quantity: {{ $orderItem->quantity }}</p>
            <p class="meta">Price: ${{ number_format((float) $orderItem->price, 2) }}</p>
        </div>

        

        <div class="panel" >
            <form action="{{ route('order-items.destroy', $orderItem) }}" method="POST"
                onsubmit="return confirm('Delete this item?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete item</button>
            </form>
        </div>
    
@endsection
