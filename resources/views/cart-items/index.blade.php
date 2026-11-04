@extends('layouts.app', ['title' => 'Cart items'])
@section('content')

        <div class="topbar">
            <h1>Cart Items</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('cart-items.create') }}" class="btn primary">Add cart item</a></div>
        </div>
        <form action="{{ route('cart-items.index') }}" method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-80"><label for="search" class="sr-only">Search cart items</label><input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search product or customer"></div>
            <button class="btn secondary" type="submit">Search</button>
            @if(request()->filled('search'))<a href="{{ route('cart-items.index') }}" class="text-sm text-zinc-500">Clear</a>@endif
        </form>

        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Cart</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($cartItems as $item)
                    <tr>
                        <td>#{{ $item->id }}</td>
                        <td>#{{ $item->cart_id }}</td>
                        <td>
                            <div>{{ $item->product->name ?? 'Unknown product' }}</div>
                            @if(!$item->product || $item->product->trashed())<span class="text-xs font-medium text-danger">Unavailable</span>
                            @elseif($item->product->stock === 0)<span class="text-xs font-medium text-danger">Out of stock</span>
                            @elseif($item->quantity > $item->product->stock)<span class="text-xs font-medium text-danger">Only {{ $item->product->stock }} available</span>@endif
                        </td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format((float) $item->price, 2) }}</td>
                        <td><a href="{{ route('cart-items.show', $item) }}" class="btn secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-zinc-500">{{ request()->filled('search') ? 'No cart items match your search.' : 'No cart items yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-6">{{ $cartItems->links() }}</div>

@endsection
