@extends('layouts.app', ['title' => 'Cart items'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('cart-items.index') }}" class="btn secondary">← Back to cart items</a><a href="{{ route('cart-items.edit', $cartItem) }}" class="btn">Edit cart item</a></div>

        <div class="panel" >
            <h1>Cart Item #{{ $cartItem->id }}</h1>
            <p class="meta">Cart: #{{ $cartItem->cart_id }}</p>
            <p class="meta">Product: {{ $cartItem->product->name ?? 'Unknown' }}</p>
            <p class="meta">Quantity: {{ $cartItem->quantity }}</p>
            <p class="meta">Price: ${{ number_format((float) $cartItem->price, 2) }}</p>
        </div>

        

        <div class="panel" >
            <form action="{{ route('cart-items.destroy', $cartItem) }}" method="POST"
                onsubmit="return confirm('Delete this item?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete item</button>
            </form>
        </div>
    
@endsection
