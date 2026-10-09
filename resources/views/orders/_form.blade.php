<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="form-grid">
        <div><label for="customer_id">Customer</label><select id="customer_id" name="customer_id" required><option value="">Select a customer</option>@foreach ($customers as $option)<option value="{{ $option->id }}" @selected((string) old('customer_id', $order->customer_id ?? '') === (string) $option->id)>{{ $option->name }} ({{ $option->email }})</option>@endforeach</select>@error('customer_id')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="order_date">Order date</label><input id="order_date" type="datetime-local" name="order_date" value="{{ old('order_date', isset($order) ? $order->order_date->format('Y-m-d\\TH:i') : now()->format('Y-m-d\\TH:i')) }}" required>@error('order_date')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="status">Status</label><select id="status" name="status">@foreach (['pending', 'processing', 'completed'] as $status)<option value="{{ $status }}" @selected(old('status', $order->status ?? 'pending') === $status)>{{ ucfirst($status) }}</option>@endforeach</select>@error('status')<div class="error">{{ $message }}</div>@enderror</div>
    </div>
    <p><label for="delivery_address">Delivery address</label><textarea id="delivery_address" name="delivery_address" required>{{ old('delivery_address', $order->delivery_address ?? '') }}</textarea>@error('delivery_address')<span class="error">{{ $message }}</span>@enderror</p>
    <div class="actions"><button class="btn" type="submit">{{ $submitLabel }}</button><a class="btn secondary" href="{{ $backUrl }}">Cancel</a></div>
</form>
