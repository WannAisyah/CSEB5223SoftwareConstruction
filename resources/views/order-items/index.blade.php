@extends('layouts.app', ['title' => 'Order items'])
@section('content')

        <div class="topbar">
            <h1>Order Items</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('order-items.create') }}" class="btn primary">Add order item</a></div>
        </div>
        <form action="{{ route('order-items.index') }}" method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-80"><label for="search" class="sr-only">Search order items</label><input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search product or customer"></div>
            <button class="btn secondary" type="submit">Search</button>
            @if(request()->filled('search'))<a href="{{ route('order-items.index') }}" class="text-sm text-zinc-500">Clear</a>@endif
        </form>

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
                @forelse ($orderItems as $item)
                    <tr>
                        <td>#{{ $item->id }}</td>
                        <td>#{{ $item->order_id }}</td>
                        <td>{{ $item->product->name ?? 'Unknown product' }} @if(!$item->product || $item->product->trashed())<span class="text-xs font-medium text-danger">(deleted)</span>@endif</td>
                        <td>{{ $item->quantity }}</td>
                        <td>${{ number_format((float) $item->price, 2) }}</td>
                        <td><a href="{{ route('order-items.show', $item) }}" class="btn secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-zinc-500">{{ request()->filled('search') ? 'No order items match your search.' : 'No order items yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-6">{{ $orderItems->links() }}</div>
    
@endsection
