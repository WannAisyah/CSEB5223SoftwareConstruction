@extends('layouts.app', ['title' => 'Orders'])
@section('content')

        <div class="topbar">
            <h1>Orders</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('orders.create') }}" class="btn primary">Create order</a></div>
        </div>
        <form action="{{ route('orders.index') }}" method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-80"><label for="search" class="sr-only">Search orders</label><input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Order ID, customer, or email"></div>
            <div><label for="status" class="sr-only">Order status</label><select id="status" name="status"><option value="">All statuses</option>@foreach(['pending', 'processing', 'completed', 'cancelled'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <button class="btn secondary" type="submit">Filter</button>
            @if(request()->filled('search') || request()->filled('status'))<a href="{{ route('orders.index') }}" class="text-sm text-zinc-500">Clear</a>@endif
        </form>

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
                @forelse ($orders as $order)
                    <tr>
                        <td>#{{ $order->id }}</td>
                        <td>{{ $order->customer->name ?? 'Unknown' }}</td>
                        <td>{{ $order->order_date }}</td>
                        <td>{{ $order->delivery_address ?? 'Not recorded' }}</td>
                        <td>${{ number_format((float) $order->total_amount, 2) }}</td>
                        <td>{{ $order->status ?? 'Pending' }}</td>
                        <td><a href="{{ route('orders.show', $order) }}" class="btn secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-10 text-center text-zinc-500">No orders match these filters.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-6">{{ $orders->links() }}</div>
    
@endsection
