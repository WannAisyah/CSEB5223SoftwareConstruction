@extends('layouts.app', ['title' => 'Order items'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('order-items.index') }}" class="btn secondary">← Back to order items</a>@if($orderItem->order->status === 'pending' && $orderItem->order->stock_deducted_at === null && $orderItem->order->payment?->payment_status !== 'paid')<a href="{{ route('order-items.edit', $orderItem) }}" class="btn">Edit order item</a>@endif</div>

        @if($orderItem->order->status === 'pending' && $orderItem->order->stock_deducted_at === null && $orderItem->order->payment?->payment_status !== 'paid')<div class="panel" >
            <h1>Order Item #{{ $orderItem->id }}</h1>
            <p class="meta">Order: #{{ $orderItem->order_id }}</p>
            <p class="meta">Product: {{ $orderItem->product->name ?? 'Unknown' }}</p>
            @if(!$orderItem->product || $orderItem->product->trashed())<p class="mt-3 text-sm font-medium text-danger">This product has been deleted from the catalog.</p>@endif
            <p class="meta">Quantity: {{ $orderItem->quantity }}</p>
            <p class="meta">Price: ${{ number_format((float) $orderItem->price, 2) }}</p>
        </div>@endif

        

        <div class="panel" >
            <form action="{{ route('order-items.destroy', $orderItem) }}" method="POST"
                onsubmit="return confirm('Delete order item #{{ $orderItem->id }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete item</button>
            </form>
        </div>
    
@endsection
