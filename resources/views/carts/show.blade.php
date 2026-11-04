@extends('layouts.app', ['title' => 'Carts'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('carts.index') }}" class="btn secondary">← Back to carts</a><a href="{{ route('carts.edit', $cart) }}" class="btn">Edit cart</a></div>

        <div class="panel" >
            <h1>Cart #{{ $cart->id }}</h1>
            <p class="meta">Customer: {{ $cart->customer->name ?? 'Unknown customer' }}</p>
            <p class="meta">Total: ${{ number_format((float) $cart->total, 2) }}</p>
            <p class="meta">Item count: {{ $cart->cartItems->count() }}</p>
        </div>

        <div class="table-wrap mt-6"><table>
            <thead><tr><th>Product</th><th>Quantity</th><th>Price</th><th>Availability</th></tr></thead>
            <tbody>
                @forelse($cart->cartItems as $item)
                    <tr>
                        <td>{{ $item->product->name ?? 'Unknown product' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format((float) $item->price, 2) }}</td>
                        <td>
                            @if(!$item->product || $item->product->trashed())<span class="font-medium text-danger">Unavailable — remove this item</span>
                            @elseif($item->product->stock === 0)<span class="font-medium text-danger">Out of stock</span>
                            @elseif($item->quantity > $item->product->stock)<span class="font-medium text-danger">Only {{ $item->product->stock }} available</span>
                            @else<span class="text-success">Available</span>@endif
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-10 text-center text-zinc-500">This cart is empty.</td></tr>
                @endforelse
            </tbody>
        </table></div>

        <div class="panel" >
            <form action="{{ route('carts.destroy', $cart) }}" method="POST"
                onsubmit="return confirm('Delete cart #{{ $cart->id }}?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete cart</button>
            </form>
        </div>
    
@endsection
