@extends('layouts.app', ['title' => 'My account'])
@section('content')
    <div class="mx-auto max-w-xl py-8">
        <h1>My account</h1>
        <p class="mt-2 text-zinc-600">Update your details and save an address for future checkouts. Existing orders keep their original address.</p>
        <form action="{{ route('customer.profile.update') }}" method="POST" class="mt-8">
            @csrf
            @method('PATCH')
            <label for="name">Name</label>
            <input id="name" name="name" autocomplete="name" value="{{ old('name', $customer->name) }}" required>
            @error('name')<div class="error">{{ $message }}</div>@enderror
            <label for="email" class="mt-5">Email</label>
            <input id="email" type="email" name="email" autocomplete="email" value="{{ old('email', $customer->email) }}" required>
            @error('email')<div class="error">{{ $message }}</div>@enderror
            <label for="delivery_address" class="mt-5">Default delivery address</label>
            <textarea id="delivery_address" name="delivery_address" autocomplete="street-address">{{ old('delivery_address', $customer->delivery_address) }}</textarea>
            @error('delivery_address')<div class="error">{{ $message }}</div>@enderror
            <button class="btn mt-6" type="submit">Save details</button>
        </form>
    </div>
@endsection
