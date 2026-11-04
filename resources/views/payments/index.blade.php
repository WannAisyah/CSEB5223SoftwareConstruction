@extends('layouts.app', ['title' => 'Payments'])
@section('content')

        <div class="topbar">
            <h1>Payments</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('payments.create') }}" class="btn primary">Record payment</a></div>
        </div>
        <form action="{{ route('payments.index') }}" method="GET" class="mb-6 flex flex-wrap items-end gap-3">
            <div class="w-full sm:w-80"><label for="search" class="sr-only">Search payments</label><input id="search" type="search" name="search" value="{{ request('search') }}" placeholder="Order ID, customer, or method"></div>
            <div><label for="status" class="sr-only">Payment status</label><select id="status" name="status"><option value="">All statuses</option>@foreach(['pending', 'paid', 'failed'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ ucfirst($status) }}</option>@endforeach</select></div>
            <button class="btn secondary" type="submit">Filter</button>
            @if(request()->filled('search') || request()->filled('status'))<a href="{{ route('payments.index') }}" class="text-sm text-zinc-500">Clear</a>@endif
        </form>

        <div class="table-wrap"><table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Order</th>
                    <th>Amount</th>
                    <th>Method</th>
                    <th>Status</th>
                    <th>Action</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($payments as $payment)
                    <tr>
                        <td>#{{ $payment->id }}</td>
                        <td>#{{ $payment->order_id }}</td>
                        <td>${{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ Str::headline($payment->payment_method) }}</td>
                        <td>{{ Str::headline($payment->payment_status ?? 'pending') }}</td>
                        <td><a href="{{ route('payments.show', $payment) }}" class="btn secondary">View</a></td>
                    </tr>
                @empty
                    <tr><td colspan="6" class="py-10 text-center text-zinc-500">No payments match these filters.</td></tr>
                @endforelse
            </tbody>
        </table></div>
        <div class="mt-6">{{ $payments->links() }}</div>
    
@endsection
