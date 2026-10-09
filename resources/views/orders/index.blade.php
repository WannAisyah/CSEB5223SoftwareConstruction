@extends('layouts.app', ['title' => 'Orders'])
@section('content')

        <div class="topbar">
            <h1>Orders</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('orders.create') }}" class="btn primary">Create order</a></div>
        </div>

        

        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Date</th>
                    <th>Delivery Address</th>
                    <th>Total</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($orders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->customer->name ?? 'Unknown' }}</td>
                        <td>{{ $order->order_date }}</td>
                        <td>{{ $order->delivery_address ?? 'Not recorded' }}</td>
                        <td>${{ number_format((float) $order->total_amount, 2) }}</td>
                        <td>{{ $order->status ?? 'Pending' }}</td>
                        <td><a href="{{ route('orders.show', $order) }}" class="btn secondary">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    
@endsection
