@extends('layouts.app', ['title' => 'Sign in'])
@section('content')
    <div class="mx-auto max-w-md rounded-md border border-zinc-200 bg-white p-6 sm:p-8">
        <h1>Sign in</h1>
        <p class="mt-2 text-sm text-zinc-600">Choose the account type you want to access.</p>

        <div class="mt-6 grid grid-cols-2 rounded-full bg-zinc-100 p-1" role="tablist" aria-label="Account type">
            <a href="{{ route('customer.login') }}" role="tab" @if($accountType === 'customer') aria-selected="true" @else aria-selected="false" @endif
                @class(['rounded-full px-3 py-2 text-center text-sm font-semibold', 'bg-zinc-950 text-white' => $accountType === 'customer', 'text-zinc-600 hover:text-zinc-950' => $accountType !== 'customer'])>Customer</a>
            <a href="{{ route('customer.login', ['type' => 'admin']) }}" role="tab" @if($accountType === 'admin') aria-selected="true" @else aria-selected="false" @endif
                @class(['rounded-full px-3 py-2 text-center text-sm font-semibold', 'bg-zinc-950 text-white' => $accountType === 'admin', 'text-zinc-600 hover:text-zinc-950' => $accountType !== 'admin'])>Administrator</a>
        </div>

        @if($accountType === 'admin')
            <form action="{{ route('admin.login.store') }}" method="POST" class="mt-6">
                @csrf
                <div><label for="admin-email">Email</label><input id="admin-email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>@error('email')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="mt-5"><label for="admin-password">Password</label><input id="admin-password" type="password" name="password" autocomplete="current-password" required>@error('password')<div class="error">{{ $message }}</div>@enderror</div>
                <button class="btn mt-6 w-full" type="submit">Sign in as administrator</button>
            </form>
        @else
            <form action="{{ route('customer.login.store') }}" method="POST" class="mt-6">
                @csrf
                <div><label for="customer-email">Email</label><input id="customer-email" type="email" name="email" value="{{ old('email') }}" autocomplete="username" required autofocus>@error('email')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="mt-5"><label for="customer-password">Password</label><input id="customer-password" type="password" name="password" autocomplete="current-password" required>@error('password')<div class="error">{{ $message }}</div>@enderror</div>
                <div class="mt-5">@include('auth._turnstile')</div>
                <button class="btn mt-6 w-full" type="submit">Sign in as customer</button>
            </form>
            <p class="mt-6 text-center text-sm text-zinc-600">New customer? <a href="{{ route('customer.register') }}" class="font-semibold text-zinc-950 underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-950">Create an account</a></p>
        @endif
    </div>
@endsection
