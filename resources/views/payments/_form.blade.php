<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="form-grid">
        <div><label for="order_id">Order</label><select id="order_id" name="order_id" required><option value="">Select an order</option>@foreach ($orders as $option)<option value="{{ $option->id }}" @selected((string) old('order_id', $payment->order_id ?? '') === (string) $option->id)>Order #{{ $option->id }} - {{ $option->customer->name }} (${{ number_format((float) $option->total_amount, 2) }})</option>@endforeach</select><p class="mt-2 text-xs text-zinc-500">The payment amount is the order total.</p>@error('order_id')<div class="error">{{ $message }}</div>@enderror</div>
        <div>
            <label for="payment_method">Method</label>
            @php
                $selectedMethod = old('payment_method', $payment->payment_method ?? '');
                $methods = ['cash' => 'Cash', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'];
                if ($selectedMethod !== '' && ! array_key_exists($selectedMethod, $methods)) {
                    $methods[$selectedMethod] = Str::headline($selectedMethod);
                }
            @endphp
            <select id="payment_method" name="payment_method" required>
                <option value="">Select a payment method</option>
                @foreach($methods as $value => $label)<option value="{{ $value }}" @selected($selectedMethod === $value)>{{ $label }}</option>@endforeach
            </select>
            @error('payment_method')<div class="error">{{ $message }}</div>@enderror
        </div>
        <div><label for="payment_status">Status</label><select id="payment_status" name="payment_status">@foreach (['pending', 'paid', 'failed'] as $status)<option value="{{ $status }}" @selected(old('payment_status', $payment->payment_status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>@error('payment_status')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="payment_date">Payment date</label><input id="payment_date" type="datetime-local" name="payment_date" value="{{ old('payment_date', isset($payment) && $payment->payment_date ? $payment->payment_date->format('Y-m-d\\TH:i') : '') }}">@error('payment_date')<div class="error">{{ $message }}</div>@enderror</div>
    </div>
    <div class="actions" style="margin-top:16px"><button class="btn" type="submit">{{ $submitLabel }}</button><a class="btn secondary" href="{{ $backUrl }}">Cancel</a></div>
</form>
