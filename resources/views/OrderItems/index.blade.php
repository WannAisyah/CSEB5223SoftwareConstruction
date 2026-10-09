@extends('layouts.app', ['title' => 'Order items'])
@section('content')

        <div class="topbar">
            <h1>Order Items</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('order-items.create') }}" class="btn primary">Add order item</a></div>
        </div>

        

        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order</th>
                    <th>Product</th>
                    <th>Qty</th>
                    <th>Price</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orderItems as $item)
                    <tr>
                        <td>#{{ $item->id }}</td>
                        <td>#{{ $item->order_id }}</td>
                        <td>{{ $item->product->name ?? 'Unknown' }}</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format((float) $item->price, 2) }}</td>
                        <td><a href="{{ route('order-items.show', $item) }}" class="btn secondary">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    
@endsection
