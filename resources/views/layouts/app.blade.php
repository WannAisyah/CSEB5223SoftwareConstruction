<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'OLMS' }} | Local Mart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-white font-sans text-zinc-950 antialiased">
    @php
        $admin = Auth::guard('web')->user();
        $customer = Auth::guard('customer')->user();
    @endphp

    @if($admin)
        <div class="min-h-screen lg:grid lg:grid-cols-[232px_minmax(0,1fr)]">
            <aside class="border-b border-zinc-200 bg-zinc-100 lg:sticky lg:top-0 lg:h-screen lg:border-r lg:border-b-0">
                <a href="{{ route('products.index') }}" class="block px-6 py-7 text-xl font-black tracking-[-0.04em]">LOCAL MART<span class="mt-1 block text-xs font-medium tracking-normal text-zinc-600">Administration</span></a>
                <nav aria-label="Admin navigation" class="flex gap-1 overflow-x-auto px-3 pb-4 lg:flex-col">
                    @foreach (['products' => 'Products', 'customers' => 'Customers', 'carts' => 'Carts', 'cart-items' => 'Cart items', 'orders' => 'Orders', 'order-items' => 'Order items', 'payments' => 'Payments'] as $resource => $label)
                        <a href="{{ route($resource.'.index') }}" @if(request()->routeIs($resource.'.*')) aria-current="page" @endif
                            @class(['shrink-0 rounded-full px-4 py-2.5 text-sm font-semibold transition-colors', 'bg-zinc-950 text-white' => request()->routeIs($resource.'.*'), 'text-zinc-600 hover:bg-zinc-200 hover:text-zinc-950' => !request()->routeIs($resource.'.*')])>{{ $label }}</a>
                    @endforeach
                </nav>
                <div class="absolute bottom-6 hidden px-7 text-xs text-zinc-600 lg:block">Online Local Mart System</div>
            </aside>
            <div class="min-w-0 bg-white">
                <header class="flex min-h-16 items-center justify-between border-b border-zinc-200 px-5 sm:px-8 lg:px-10">
                    <span class="text-sm text-zinc-600">{{ $title ?? 'Administration' }}</span>
                    <div class="flex items-center gap-4 text-sm"><span class="hidden font-medium sm:block">{{ $admin->name }}</span><form action="{{ route('admin.logout') }}" method="POST">@csrf<button type="submit" class="font-semibold underline decoration-zinc-300 underline-offset-4 hover:decoration-zinc-950">Log out</button></form></div>
                </header>
                <main class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                    @if(session('success'))<div class="mb-6 rounded-md bg-zinc-100 px-4 py-3 text-sm text-success" role="status">{{ session('success') }}</div>@endif
                    @if($errors->any())<div class="mb-6 rounded-md bg-zinc-100 px-4 py-3 text-sm text-danger" role="alert"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                    @yield('content')
                </main>
            </div>
        </div>
    @else
        <div class="min-h-screen">
            <header class="sticky top-0 z-30 border-b border-zinc-200 bg-white/95 px-5 sm:px-8">
                <div class="mx-auto flex min-h-18 max-w-[1440px] flex-wrap items-center justify-between gap-4 py-3">
                    <a href="{{ route('products.index') }}" class="text-xl font-black tracking-[-0.05em]">LOCAL MART</a>
                    <nav aria-label="Store navigation" class="order-3 flex w-full items-center justify-center gap-7 text-sm font-semibold sm:order-2 sm:w-auto">
                        <a href="{{ route('products.index') }}" @class(['underline decoration-2 underline-offset-8' => request()->routeIs('products.*'), 'hover:text-zinc-600' => !request()->routeIs('products.*')])>Products</a>
                        @if($customer)
                            <a href="{{ route('customer.orders.index') }}" @class(['underline decoration-2 underline-offset-8' => request()->routeIs('customer.orders.*'), 'hover:text-zinc-600' => !request()->routeIs('customer.orders.*')])>My orders</a>
                        @endif
                    </nav>
                    <div class="order-2 flex items-center gap-4 text-sm sm:order-3">
                        @if($customer)
                            <a href="{{ route('customer.cart.show') }}" aria-label="My cart" title="My cart" @class(['flex h-11 w-11 items-center justify-center rounded-full transition-colors', 'bg-zinc-950 text-white' => request()->routeIs('customer.cart*'), 'bg-zinc-100 text-zinc-950 hover:bg-zinc-200' => !request()->routeIs('customer.cart*')])>
                                <svg aria-hidden="true" viewBox="0 0 24 24" class="h-5 w-5 fill-none stroke-current" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"><path d="M3 4h2l1.8 10.2a2 2 0 0 0 2 1.7h7.9a2 2 0 0 0 2-1.6L20 8H6.1"/><circle cx="9" cy="20" r="1"/><circle cx="17" cy="20" r="1"/></svg>
                            </a>
                            <a href="{{ route('customer.profile.edit') }}" class="font-medium underline-offset-4 hover:underline">My account</a>
                            <form action="{{ route('customer.logout') }}" method="POST">@csrf<button type="submit" class="font-semibold hover:text-zinc-600">Log out</button></form>
                        @else
                            <a href="{{ route('customer.login') }}" class="font-semibold hover:text-zinc-600">Log in</a>
                            <a href="{{ route('customer.register') }}" class="rounded-full bg-zinc-950 px-5 py-2.5 font-semibold text-white hover:bg-zinc-700">Register</a>
                        @endif
                    </div>
                </div>
            </header>
            <main class="mx-auto max-w-[1440px] px-5 py-8 sm:px-8 lg:py-12">
                @if(session('success'))<div class="mb-6 rounded-md bg-zinc-100 px-4 py-3 text-sm text-success" role="status">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="mb-6 rounded-md bg-zinc-100 px-4 py-3 text-sm text-danger" role="alert"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </main>
            <footer class="mt-20 bg-zinc-950 px-5 py-12 text-white sm:px-8"><div class="mx-auto flex max-w-[1440px] flex-wrap items-end justify-between gap-6"><div class="text-4xl font-black tracking-[-0.06em] sm:text-6xl">LOCAL MART</div><p class="max-w-xs text-sm leading-6 text-zinc-400">Everyday products, local service, straightforward shopping.</p></div></footer>
        </div>
    @endif
    @stack('scripts')
</body>
</html>
