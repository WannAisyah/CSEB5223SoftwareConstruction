<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="form-grid"><div><label for="customer_id">Customer</label><select id="customer_id" name="customer_id" required><option value="">Select a customer</option>@foreach ($customers as $option)<option value="{{ $option->id }}" @selected((string) old('customer_id', $cart->customer_id ?? '') === (string) $option->id)>{{ $option->name }} ({{ $option->email }})</option>@endforeach</select>@error('customer_id')<div class="error">{{ $message }}</div>@enderror</div></div>
    <div class="actions" style="margin-top:16px"><button class="btn" type="submit">{{ $submitLabel }}</button><a class="btn secondary" href="{{ $backUrl }}">Cancel</a></div>
</form>
