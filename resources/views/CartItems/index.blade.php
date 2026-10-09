@extends('layouts.app', ['title' => 'Cart items'])
@section('content')

        <div class="topbar">
            <h1>Cart Items</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('cart-items.create') }}" class="btn primary">Add cart item</a></div>
        </div>

        

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
                @foreach ($cartItems as $item)
                    <tr>
                        <td>#{{ $item->id }}</td>
                        <td>#{{ $item->cart_id }}</td>
                        <td>{{ $item->product->name ?? 'Unknown' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format((float) $item->price, 2) }}</td>
                        <td><a href="{{ route('cart-items.show', $item) }}" class="btn secondary">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    
@endsection
