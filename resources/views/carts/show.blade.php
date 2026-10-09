@extends('layouts.app', ['title' => 'Carts'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('carts.index') }}" class="btn secondary">← Back to carts</a><a href="{{ route('carts.edit', $cart) }}" class="btn">Edit cart</a></div>

        <div class="panel" >
            <h1>Cart #{{ $cart->id }}</h1>
            <p class="meta">Customer: {{ $cart->customer->name ?? 'Unknown customer' }}</p>
            <p class="meta">Total: ${{ number_format((float) $cart->total, 2) }}</p>
            <p class="meta">Item count: {{ $cart->cartItems->count() }}</p>
        </div>

        

        <div class="panel" >
            <form action="{{ route('carts.destroy', $cart) }}" method="POST"
                onsubmit="return confirm('Delete this cart?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete cart</button>
            </form>
        </div>
    
@endsection
