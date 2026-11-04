@extends('layouts.app', ['title' => 'My cart'])
@section('content')
    <div class="topbar"><div><h1>My cart</h1><p class="mt-2 text-sm text-zinc-600">Review availability before checkout.</p></div><a class="btn secondary" href="{{ route('products.index') }}">Continue shopping</a></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Product</th><th>Price</th><th>Quantity</th><th>Total</th><th>Availability</th><th></th></tr></thead>
            <tbody>
                @forelse($cart->cartItems as $item)
                    <tr>
                        <td class="font-medium">{{ $item->product->name ?? 'Unknown product' }}</td>
                        <td>${{ number_format((float) $item->price, 2) }}</td>
                        <td><form action="{{ route('customer.cart-items.update', $item) }}" method="POST" class="flex items-center gap-2">@csrf @method('PATCH')<label for="quantity-{{ $item->id }}" class="sr-only">Quantity</label><input id="quantity-{{ $item->id }}" type="number" name="quantity" min="1" value="{{ $item->quantity }}" class="max-w-20"><button class="btn secondary" type="submit">Update</button></form></td>
                        <td>${{ number_format((float) $item->price * $item->quantity, 2) }}</td>
                        <td>
                            @if(!$item->product || $item->product->trashed())<span class="font-medium text-danger">Unavailable</span>
                            @elseif($item->product->stock === 0)<span class="font-medium text-danger">Out of stock</span>
                            @elseif($item->quantity > $item->product->stock)<span class="font-medium text-danger">Only {{ $item->product->stock }} available</span>
                            @else<span class="text-success">Available</span>@endif
                        </td>
                        <td><form action="{{ route('customer.cart-items.destroy', $item) }}" method="POST">@csrf @method('DELETE')<button type="submit" class="text-sm font-medium text-danger">Remove</button></form></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-12 text-center text-zinc-600">Your cart is empty.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    @if($cart->cartItems->isNotEmpty())
        <form action="{{ route('customer.cart.clear') }}" method="POST" class="mt-5" onsubmit="return confirm('Remove every item from your cart?')">@csrf @method('DELETE')<button type="submit" class="text-sm font-semibold text-zinc-700 underline underline-offset-4 hover:text-zinc-950">Clear cart</button></form>
        <div class="mt-8 grid gap-8 lg:grid-cols-[minmax(0,1fr)_360px]">
            <div></div>
            <div class="rounded-md border border-zinc-200 bg-white p-6">
                <div class="flex items-center justify-between"><span class="font-medium">Order total</span><strong class="text-xl">${{ number_format((float) $cart->total, 2) }}</strong></div>
                <form action="{{ route('customer.checkout') }}" method="POST" class="mt-6">@csrf
                    <div><label for="delivery_address">Delivery address</label><textarea id="delivery_address" name="delivery_address" required>{{ old('delivery_address', $customer->delivery_address) }}</textarea><p class="mt-2 text-xs text-zinc-600">Prefilled from your saved address. This order keeps the address you enter here.</p>@error('delivery_address')<div class="error">{{ $message }}</div>@enderror</div>
                    <div class="mt-5"><label for="payment_method">Payment method</label><select id="payment_method" name="payment_method" required><option value="">Choose a method</option>@foreach(['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank transfer'] as $value => $label)<option value="{{ $value }}" @selected(old('payment_method') === $value)>{{ $label }}</option>@endforeach</select>@error('payment_method')<div class="error">{{ $message }}</div>@enderror</div>
                    <p class="mt-4 text-xs leading-5 text-zinc-600">Payment covers the full order total. No real payment gateway is used.</p>
                    <button class="btn mt-6 w-full" type="submit">Place order</button>
                </form>
            </div>
        </div>
    @endif
@endsection
