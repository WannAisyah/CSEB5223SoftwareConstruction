@extends('layouts.app', ['title' => 'Product details'])
@section('content')
    <div class="topbar">
        <div><a class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-800" href="{{ route('products.index') }}">Back to products</a><h1>{{ $product->name }}</h1></div>
        <a class="btn" href="{{ route('products.edit', $product) }}">Edit product</a>
    </div>
    <div class="grid gap-8 border-t border-zinc-200 pt-8 md:grid-cols-[280px_minmax(0,1fr)]">
        <div class="relative flex aspect-square items-center justify-center overflow-hidden rounded-md border border-zinc-200 bg-white text-sm text-zinc-400">
            <span>No image available</span>
            @if($product->image)<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" data-product-image class="absolute inset-0 h-full w-full bg-white object-contain opacity-0">@endif
        </div>
        <div>
            <dl class="grid grid-cols-2 gap-6 border-b border-zinc-200 pb-6">
                <div><dt class="text-sm text-zinc-500">Price</dt><dd class="mt-2 text-2xl font-semibold tabular-nums">${{ number_format((float) $product->price, 2) }}</dd></div>
                <div><dt class="text-sm text-zinc-500">Stock</dt><dd class="mt-2 text-2xl font-semibold tabular-nums">{{ $product->stock }}<span class="ml-2 text-sm font-normal text-zinc-500">units</span></dd></div>
            </dl>
            <h2 class="mt-6 text-sm">Description</h2><p class="mt-3 whitespace-pre-line text-sm leading-7 text-zinc-600">{{ $product->description ?: 'No description provided.' }}</p>
            <form action="{{ route('products.destroy', $product) }}" method="POST" class="mt-8" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button class="btn danger" type="submit">Delete product</button></form>
        </div>
    </div>
@endsection
