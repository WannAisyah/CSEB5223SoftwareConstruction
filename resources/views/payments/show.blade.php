@extends('layouts.app', ['title' => 'Payments'])
@section('content')

        
        <div class="actions mb-7"><a href="{{ route('payments.index') }}" class="btn secondary">← Back to payments</a><a href="{{ route('payments.edit', $payment) }}" class="btn">Edit payment</a></div>

        <div class="panel" >
            <h1>Payment #{{ $payment->id }}</h1>
            <p class="meta">Order: #{{ $payment->order_id }}</p>
            <p class="meta">Amount: ${{ number_format((float) $payment->amount, 2) }}</p>
            <p class="meta">Method: {{ Str::headline($payment->payment_method) }}</p>
            <p class="meta">Status: {{ Str::headline($payment->payment_status ?? 'pending') }}</p>
        </div>

        

        <div class="panel" >
            <form action="{{ route('payments.destroy', $payment) }}" method="POST"
                onsubmit="return confirm('Delete this payment?')">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn danger">Delete payment</button>
            </form>
        </div>
    
@endsection
