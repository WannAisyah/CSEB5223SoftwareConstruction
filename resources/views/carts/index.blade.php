@extends('layouts.app', ['title' => 'Carts'])
@section('content')

        <div class="topbar">
            <h1>Carts</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('carts.create') }}" class="btn primary">Create cart</a></div>
        </div>

        

        <form action="{{ route('carts.index') }}" method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-80"><label for="search" class="sr-only">Search cart ID</label><input id="search" type="number" min="1" step="1" name="search" value="{{ request('search') }}" placeholder="Search cart ID"></div>
            <button class="btn secondary" type="submit">Search</button>
            @if(request()->filled('search'))<a href="{{ route('carts.index') }}" class="text-sm text-zinc-500">Clear</a>@endif
        </form>
        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Total</th>
                    <th>Items</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($carts as $cart)
                    <tr>
                        <td>#{{ $cart->id }}</td>
                        <td>{{ $cart->customer->name ?? 'Unknown' }}</td>
                        <td>${{ number_format((float) $cart->total, 2) }}</td>
                        <td>{{ $cart->cartItems->count() }}</td>
                        <td>
                            <a href="{{ route('carts.show', $cart) }}" class="btn secondary">View</a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-10 text-center text-zinc-500">{{ request()->filled('search') ? 'No cart matches this ID.' : 'No carts yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table></div>
    
@endsection
