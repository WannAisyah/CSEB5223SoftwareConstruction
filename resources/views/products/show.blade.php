@extends('layouts.app', ['title' => $product->name])
@section('content')
    @php
        $imageUrl = $product->imageUrl();
        $isAdmin = Auth::guard('web')->check();
    @endphp

    @if($isAdmin)
        <div class="topbar"><div><a class="mb-3 inline-block text-sm font-semibold underline underline-offset-4" href="{{ route('products.index') }}">Back to products</a><h1>{{ $product->name }}</h1></div><a class="btn" href="{{ route('products.edit', $product) }}">Edit product</a></div>
        <div class="grid gap-8 border-t border-zinc-200 pt-8 md:grid-cols-[280px_minmax(0,1fr)]">
            <div class="relative flex aspect-square items-center justify-center overflow-hidden bg-zinc-100 text-sm text-zinc-600"><span>No image available</span>@if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $product->name }}" data-product-image class="absolute inset-0 h-full w-full bg-zinc-100 object-contain opacity-0">@endif</div>
            <div><dl class="grid grid-cols-2 gap-6 border-b border-zinc-200 pb-6"><div><dt class="text-sm text-zinc-600">Price</dt><dd class="mt-2 text-2xl font-semibold tabular-nums">${{ number_format((float) $product->price, 2) }}</dd></div><div><dt class="text-sm text-zinc-600">Stock</dt><dd class="mt-2 text-2xl font-semibold tabular-nums">{{ $product->stock }}<span class="ml-2 text-sm font-normal text-zinc-600">units</span></dd></div></dl><h2 class="mt-6 text-sm">Description</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-zinc-600">{{ $product->description ?: 'No description provided.' }}</p><form action="{{ route('products.destroy', $product) }}" method="POST" class="mt-8" onsubmit="return confirm('Delete product #{{ $product->id }}?')">@csrf @method('DELETE')<button class="btn danger" type="submit">Delete product</button></form></div>
        </div>
    @else
        <a class="mb-8 inline-block text-sm font-semibold underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-950" href="{{ route('products.index') }}">← Back to products</a>
        <div class="grid gap-10 lg:grid-cols-[minmax(0,1.15fr)_minmax(360px,0.85fr)] lg:gap-16">
            <div class="relative flex aspect-square items-center justify-center overflow-hidden bg-zinc-100"><span class="text-6xl font-black tracking-[-0.06em] text-zinc-300">{{ Str::upper(Str::substr($product->name, 0, 2)) }}</span>@if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $product->name }}" data-product-image class="absolute inset-0 h-full w-full bg-zinc-100 object-contain opacity-0">@endif</div>
            <div class="lg:pt-8">
                <h1 class="text-4xl font-black uppercase leading-[0.9] tracking-[-0.05em] sm:text-6xl">{{ $product->name }}</h1>
                <p class="mt-6 text-2xl font-semibold tabular-nums">${{ number_format((float) $product->price, 2) }}</p>
                <p class="mt-2 text-sm font-medium text-zinc-600">{{ $product->stock > 0 ? $product->stock.' available' : 'Out of stock' }}</p>
                <p class="mt-8 max-w-xl whitespace-pre-line text-base leading-7 text-zinc-700">{{ $product->description ?: 'No description provided.' }}</p>
                @if(Auth::guard('customer')->check() && $product->stock > 0)
                    <form action="{{ route('customer.cart-items.store') }}" method="POST" class="mt-10 flex max-w-md items-end gap-3">@csrf<div class="w-24"><label for="quantity">Quantity</label><input id="quantity" type="number" name="quantity" min="1" max="{{ $product->stock }}" value="1"></div><input type="hidden" name="product_id" value="{{ $product->id }}"><button class="btn flex-1" type="submit">Add to cart</button></form>
                @elseif(!Auth::guard('customer')->check())
                    <a href="{{ route('customer.login') }}" class="btn mt-10 w-full max-w-md">Log in to shop</a>
                @endif
            </div>
        </div>
    @endif
@endsection
