@extends('layouts.app', ['title' => 'Products'])
@section('content')
    <div class="topbar">
        <div><h1>Products</h1><p class="mt-2 text-sm text-zinc-500">{{ $products->count() }} {{ Str::plural('product', $products->count()) }}{{ request('search') ? ' matching your search' : ' in the catalog' }}</p></div>
        <a class="btn" href="{{ route('products.create') }}"><span aria-hidden="true" class="text-lg leading-none">+</span> Create product</a>
    </div>
    <form action="{{ route('products.index') }}" method="GET" class="mb-6 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-80"><label for="search" class="sr-only">Search products</label><input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search products by name or description"></div>
        <button class="btn secondary" type="submit">Search</button>
        @if(request('search'))<a href="{{ route('products.index') }}" class="text-sm text-zinc-500 hover:text-zinc-900">Clear</a>@endif
    </form>
    <div class="table-wrap">
        <table>
            <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th class="text-right">Actions</th></tr></thead>
            <tbody>
                @forelse($products as $product)
                    <tr>
                        <td><div class="flex items-center gap-4">
                            <div class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-md border border-zinc-200 bg-zinc-100 text-sm font-semibold text-zinc-400">
                                <span>{{ Str::upper(Str::substr($product->name, 0, 2)) }}</span>
                                @if($product->image)<img src="{{ $product->imageUrl() }}" alt="{{ $product->name }}" data-product-image class="absolute inset-0 h-full w-full bg-white object-cover opacity-0" loading="lazy">@endif
                            </div>
                            <div class="min-w-0"><a href="{{ route('products.show', $product) }}" class="font-semibold text-zinc-900 hover:text-emerald-800">{{ $product->name }}</a><p class="mt-1 max-w-md text-xs text-zinc-500">{{ Str::limit($product->description ?? '', 90) }}</p></div>
                        </div></td>
                        <td class="whitespace-nowrap font-medium tabular-nums">${{ number_format((float) $product->price, 2) }}</td>
                        <td><span @class(['inline-flex whitespace-nowrap rounded px-2 py-1 text-xs font-medium', 'bg-emerald-50 text-emerald-800' => $product->stock > 0, 'bg-red-50 text-red-700' => $product->stock <= 0])>{{ $product->stock > 0 ? $product->stock.' available' : 'Out of stock' }}</span></td>
                        <td><div class="flex justify-end gap-2"><a class="btn secondary" href="{{ route('products.edit', $product) }}">Edit</a><form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete this product?')">@csrf @method('DELETE')<button class="btn danger" type="submit">Delete</button></form></div></td>
                    </tr>
                @empty
                    <tr><td colspan="4" class="py-16 text-center text-zinc-500">{{ request('search') ? 'No products match your search.' : 'No products yet.' }}</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
