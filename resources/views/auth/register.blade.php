@extends('layouts.app', ['title' => 'Register'])
@section('content')
    <div class="mx-auto max-w-lg rounded-md border border-zinc-200 bg-white p-6 sm:p-8">
        <h1>Create a customer account</h1>
        <p class="mt-2 text-sm text-zinc-600">Register to shop and track your orders.</p>
        <form action="{{ route('customer.register.store') }}" method="POST" class="mt-6">
            @csrf
            <div><label for="name">Name</label><input id="name" name="name" value="{{ old('name') }}" autocomplete="name" required autofocus>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="mt-5"><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email') }}" autocomplete="email" required>@error('email')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="mt-5"><label for="delivery_address">Default delivery address (optional)</label><textarea id="delivery_address" name="delivery_address" autocomplete="street-address">{{ old('delivery_address') }}</textarea>@error('delivery_address')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="mt-5"><label for="password">Password</label><input id="password" type="password" name="password" autocomplete="new-password" required>@error('password')<div class="error">{{ $message }}</div>@enderror</div>
            <div class="mt-5"><label for="password_confirmation">Confirm password</label><input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" required></div>
            <div class="mt-5">@include('auth._turnstile')</div>
            <button class="btn mt-6 w-full" type="submit">Create account</button>
        </form>
        <p class="mt-6 text-center text-sm text-zinc-600">Already registered? <a href="{{ route('customer.login') }}" class="font-semibold text-zinc-950 underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-950">Log in</a></p>
    </div>
@endsection
