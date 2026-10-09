<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? 'OLMS' }} | Local Mart</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-zinc-50 font-sans text-zinc-900 antialiased">
    <div class="min-h-screen lg:grid lg:grid-cols-[232px_minmax(0,1fr)]">
        <aside class="border-b border-zinc-200 bg-white lg:sticky lg:top-0 lg:h-screen lg:border-r lg:border-b-0">
            <a href="{{ route('products.index') }}" class="flex items-center gap-3 px-6 py-7">
                <span class="flex h-10 w-10 items-center justify-center rounded-md bg-emerald-800 text-sm font-bold text-white">LM</span>
                <span><span class="block text-lg font-semibold">Local Mart</span><span class="block text-xs text-zinc-500">OLMS</span></span>
            </a>
            <nav aria-label="Main navigation" class="flex gap-1 overflow-x-auto px-3 pb-4 lg:flex-col">
                @foreach (['products' => 'Products', 'customers' => 'Customers', 'carts' => 'Carts', 'cart-items' => 'Cart items', 'orders' => 'Orders', 'order-items' => 'Order items', 'payments' => 'Payments'] as $resource => $label)
                    <a href="{{ route($resource.'.index') }}" @if(request()->routeIs($resource.'.*')) aria-current="page" @endif
                        @class(['shrink-0 rounded-md px-4 py-3 text-sm font-medium transition-colors', 'bg-emerald-50 text-emerald-900' => request()->routeIs($resource.'.*'), 'text-zinc-600 hover:bg-zinc-100 hover:text-zinc-900' => !request()->routeIs($resource.'.*')])>{{ $label }}</a>
                @endforeach
            </nav>
            <div class="absolute bottom-6 hidden px-7 text-xs text-zinc-400 lg:block">Online Local Mart System</div>
        </aside>
        <div class="min-w-0">
            <header class="flex h-16 items-center justify-between border-b border-zinc-200 bg-white px-6 lg:px-10">
                <span class="text-sm text-zinc-500">Mart management <span class="mx-2 text-zinc-300">/</span> <span class="font-medium text-zinc-800">{{ $title ?? 'OLMS' }}</span></span>
                <span class="hidden text-xs text-zinc-500 sm:block">{{ now()->format('d M Y') }}</span>
            </header>
            <main class="mx-auto max-w-7xl px-5 py-8 sm:px-8 lg:px-10 lg:py-10">
                @if(session('success'))<div class="mb-6 rounded-md border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-900" role="status">{{ session('success') }}</div>@endif
                @if($errors->any())<div class="mb-6 rounded-md border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800" role="alert"><ul class="list-inside list-disc">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
                @yield('content')
            </main>
        </div>
    </div>
</body>
</html>
