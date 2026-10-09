@extends('layouts.app', ['title' => 'Customers'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('customers.index') }}" class="btn secondary">← Back to customers</a><a href="{{ route('customers.edit', $customer) }}" class="btn">Edit customer</a></div>

        <div class="panel">
            <h1>{{ $customer->name }}</h1>
            <p class="meta">Email: {{ $customer->email }}</p>
            <p class="meta">Orders count: {{ $customer->orders->count() }}</p>
            <p class="meta">
                @if ($customer->cart)
                    Active cart: #{{ $customer->cart->id }}
                @else
                    No cart created
                @endif
            </p>
        </div>

        

        <div class="panel">
            <form action="{{ route('customers.destroy', $customer) }}" method="POST"
                onsubmit="return confirm('Delete this customer?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete customer</button>
            </form>
        </div>
    
@endsection
