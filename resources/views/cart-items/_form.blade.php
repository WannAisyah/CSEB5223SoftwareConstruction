<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="form-grid">
        <div><label for="cart_id">Cart</label><select id="cart_id" name="cart_id" required><option value="">Select a cart</option>@foreach ($carts as $option)<option value="{{ $option->id }}" @selected((string) old('cart_id', $cartItem->cart_id ?? '') === (string) $option->id)>Cart #{{ $option->id }} - {{ $option->customer->name }}</option>@endforeach</select>@error('cart_id')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="product_id">Product</label><select id="product_id" name="product_id" required><option value="">Select a product</option>@foreach ($products as $option)<option value="{{ $option->id }}" @selected((string) old('product_id', $cartItem->product_id ?? '') === (string) $option->id)>{{ $option->name }} (${{ number_format((float) $option->price, 2) }})</option>@endforeach</select>@error('product_id')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="quantity">Quantity</label><input id="quantity" type="number" min="1" name="quantity" value="{{ old('quantity', $cartItem->quantity ?? 1) }}" required>@error('quantity')<div class="error">{{ $message }}</div>@enderror</div>
    </div>
    <div class="actions" style="margin-top:16px"><button class="btn" type="submit">{{ $submitLabel }}</button><a class="btn secondary" href="{{ $backUrl }}">Cancel</a></div>
</form>
