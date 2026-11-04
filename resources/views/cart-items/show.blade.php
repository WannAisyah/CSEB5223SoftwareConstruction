@extends('layouts.app', ['title' => 'Cart items'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('cart-items.index') }}" class="btn secondary">← Back to cart items</a><a href="{{ route('cart-items.edit', $cartItem) }}" class="btn">Edit cart item</a></div>

        <div class="panel" >
            <h1>Cart Item #{{ $cartItem->id }}</h1>
            <p class="meta">Cart: #{{ $cartItem->cart_id }}</p>
            <p class="meta">Product: {{ $cartItem->product->name ?? 'Unknown' }}</p>
            @if(!$cartItem->product || $cartItem->product->trashed())<p class="mt-3 text-sm font-medium text-danger">This product is no longer available. Remove it from the cart.</p>
            @elseif($cartItem->product->stock === 0)<p class="mt-3 text-sm font-medium text-danger">This product is out of stock.</p>
            @elseif($cartItem->quantity > $cartItem->product->stock)<p class="mt-3 text-sm font-medium text-danger">Only {{ $cartItem->product->stock }} units are available.</p>@endif
            <p class="meta">Quantity: {{ $cartItem->quantity }}</p>
            <p class="meta">Price: ${{ number_format((float) $cartItem->price, 2) }}</p>
        </div>

        

        <div class="panel" >
            <form action="{{ route('cart-items.destroy', $cartItem) }}" method="POST"
                onsubmit="return confirm('Delete cart item #{{ $cartItem->id }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete item</button>
            </form>
        </div>
    
@endsection
