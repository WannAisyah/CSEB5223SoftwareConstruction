@extends('layouts.app', ['title' => 'Payments'])
@section('content')

        <div class="topbar">
            <h1>Payments</h1>
            <div class="actions"><a href="{{ route('products.index') }}" class="btn secondary">Products</a><a href="{{ route('payments.create') }}" class="btn primary">Record payment</a></div>
        </div>

        

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
                @foreach ($payments as $payment)
                    <tr>
                        <td>#{{ $payment->id }}</td>
                        <td>#{{ $payment->order_id }}</td>
                        <td>${{ number_format((float) $payment->amount, 2) }}</td>
                        <td>{{ Str::headline($payment->payment_method) }}</td>
                        <td>{{ Str::headline($payment->payment_status ?? 'pending') }}</td>
                        <td><a href="{{ route('payments.show', $payment) }}" class="btn secondary">View</a></td>
                    </tr>
                @endforeach
            </tbody>
        </table></div>
    
@endsection
