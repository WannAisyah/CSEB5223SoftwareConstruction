@extends('layouts.app', ['title' => 'Customers'])
@section('content')

        <div class="topbar">
            <h1>Customers</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('customers.create') }}" class="btn primary">Create customer</a></div>
        </div>

        

        <form action="{{ route('customers.index') }}" method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-80"><label for="search" class="sr-only">Search customer name</label><input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search customer name"></div>
            <button class="btn secondary" type="submit">Search</button>
            @if(request()->filled('search'))<a href="{{ route('customers.index') }}" class="text-sm text-zinc-500">Clear</a>@endif
        </form>
        <div class="card-grid">
            @forelse ($customers as $customer)
                <div class="customer-card">
                    <div class="customer-body">
                        <h2>{{ $customer->name }}</h2>
                        <div class="meta">{{ $customer->email }}</div>
                        <div class="meta">Orders: {{ $customer->orders->count() }}</div>
                        <div class="meta">
                            @if ($customer->cart)
                                <span class="badge">Cart #{{ $customer->cart->id }}</span>
                            @else
                                No cart yet
                            @endif
                        </div>
                        <div class="actions">
                            <a href="{{ route('customers.show', $customer) }}" class="btn secondary">View</a>
                            <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                                onsubmit="return confirm('Delete this customer?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn danger">Delete</button>
                            </form>
                        </div>
                    </div>
                </div>
            @empty
                <p class="py-8 text-sm text-zinc-500">{{ request()->filled('search') ? 'No customers match your search.' : 'No customers yet.' }}</p>
            @endforelse
        </div>
    
@endsection
