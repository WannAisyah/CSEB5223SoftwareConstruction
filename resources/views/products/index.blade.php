@extends('layouts.app', ['title' => 'Products'])
@section('content')
    @php($isAdmin = Auth::guard('web')->check())

    @if($isAdmin)
        <div class="topbar">
            <div><h1>Products</h1><p class="mt-2 text-sm text-zinc-600">{{ $products->total() }} {{ Str::plural('product', $products->total()) }}{{ request('search') ? ' matching your search' : ' in the catalog' }}</p></div>
            <a class="btn" href="{{ route('products.create') }}"><span aria-hidden="true" class="text-lg leading-none">+</span> Create product</a>
        </div>
    @else
        <section class="grid gap-8 py-8 sm:py-12 lg:grid-cols-[minmax(0,1fr)_340px] lg:items-end lg:py-16">
            <div><h1 class="max-w-5xl text-[clamp(3.5rem,9vw,8.5rem)] font-black uppercase leading-[0.82] tracking-[-0.065em]">Find your everyday.</h1></div>
            <p class="max-w-sm text-base leading-7 text-zinc-700 lg:pb-2">Useful products, clear prices, local service. Browse what is available and add it straight to your cart.</p>
        </section>
    @endif

    <form action="{{ route('products.index') }}" method="GET" class="mb-8 flex flex-wrap items-end gap-3">
        <div class="w-full sm:w-96"><label for="search" class="sr-only">Search products</label><input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Search products"></div>
        <button class="btn secondary" type="submit">Search</button>
        @if(request('search'))<a href="{{ route('products.index') }}" class="pb-3 text-sm font-semibold underline underline-offset-4">Clear</a>@endif
    </form>

    @if($isAdmin)
        <div class="table-wrap">
            <table>
                <thead><tr><th>Product</th><th>Price</th><th>Stock</th><th class="text-right">Actions</th></tr></thead>
                <tbody>
                    @forelse($products as $product)
                        @php($imageUrl = $product->imageUrl())
                        <tr>
                            <td><div class="flex items-center gap-4">
                                <div class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden bg-zinc-100 text-sm font-semibold text-zinc-600">
                                    <span>{{ Str::upper(Str::substr($product->name, 0, 2)) }}</span>
                                    @if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $product->name }}" data-product-image class="absolute inset-0 h-full w-full bg-white object-cover opacity-0" loading="lazy">@endif
                                </div>
                                <div class="min-w-0"><a href="{{ route('products.show', $product) }}" class="font-semibold hover:underline">{{ $product->name }}</a><p class="mt-1 max-w-md text-xs text-zinc-600">{{ Str::limit($product->description ?? '', 90) }}</p></div>
                            </div></td>
                            <td class="whitespace-nowrap font-medium tabular-nums">${{ number_format((float) $product->price, 2) }}</td>
                            <td><span @class(['inline-flex whitespace-nowrap rounded-full bg-zinc-100 px-3 py-1 text-xs font-semibold', 'text-zinc-800' => $product->stock > 0, 'text-danger' => $product->stock <= 0])>{{ $product->stock > 0 ? $product->stock.' available' : 'Out of stock' }}</span></td>
                            <td><div class="flex justify-end gap-2"><a class="btn secondary" href="{{ route('products.edit', $product) }}">Edit</a><form action="{{ route('products.destroy', $product) }}" method="POST" onsubmit="return confirm('Delete product #{{ $product->id }}?')">@csrf @method('DELETE')<button class="btn danger" type="submit">Delete</button></form></div></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="py-16 text-center text-zinc-600">{{ request('search') ? 'No products match your search.' : 'No products yet.' }}</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @else
        <div class="mb-6 flex items-baseline justify-between"><h2 class="text-2xl">Shop products</h2><span class="text-sm text-zinc-600">{{ $products->total() }} available</span></div>
        <div class="grid gap-x-5 gap-y-12 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($products as $product)
                @php($imageUrl = $product->imageUrl())
                <article class="group">
                    <a href="{{ route('products.show', $product) }}" class="relative flex aspect-square items-center justify-center overflow-hidden bg-zinc-100">
                        <span class="text-5xl font-black tracking-[-0.06em] text-zinc-300">{{ Str::upper(Str::substr($product->name, 0, 2)) }}</span>
                        @if($imageUrl)<img src="{{ $imageUrl }}" alt="{{ $product->name }}" data-product-image class="absolute inset-0 h-full w-full bg-zinc-100 object-cover opacity-0 transition-transform duration-500 group-hover:scale-[1.025]" loading="lazy">@endif
                    </a>
                    <div class="mt-4 flex items-start justify-between gap-4"><div><h3 class="font-bold"><a href="{{ route('products.show', $product) }}" class="hover:underline">{{ $product->name }}</a></h3><p class="mt-1 text-sm text-zinc-600">{{ $product->stock > 0 ? $product->stock.' in stock' : 'Out of stock' }}</p></div><p class="shrink-0 font-semibold tabular-nums">${{ number_format((float) $product->price, 2) }}</p></div>
                    <div class="mt-4">
                        @if(Auth::guard('customer')->check() && $product->stock > 0)
                            <form action="{{ route('customer.cart-items.store') }}" method="POST" class="flex items-center gap-2">@csrf<input type="hidden" name="product_id" value="{{ $product->id }}"><label for="quantity-{{ $product->id }}" class="sr-only">Quantity</label><input id="quantity-{{ $product->id }}" type="number" name="quantity" min="1" max="{{ $product->stock }}" value="1" class="max-w-20"><button class="btn flex-1" type="submit">Add to cart</button></form>
                        @else
                            <a class="btn w-full" href="{{ route('products.show', $product) }}">View product</a>
                        @endif
                    </div>
                </article>
            @empty
                <p class="col-span-full py-20 text-center text-zinc-600">{{ request('search') ? 'No products match your search.' : 'No products yet.' }}</p>
            @endforelse
        </div>
    @endif

    <div class="mt-12">{{ $products->links() }}</div>
@endsection
