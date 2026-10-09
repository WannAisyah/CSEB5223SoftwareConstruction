<form action="{{ $action }}" method="POST">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="form-grid">
        <div><label for="name">Name</label><input id="name" name="name" value="{{ old('name', $customer->name ?? '') }}" required>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="email">Email</label><input id="email" type="email" name="email" value="{{ old('email', $customer->email ?? '') }}" required>@error('email')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="password">{{ isset($customer) ? 'New password (leave blank to keep current)' : 'Password' }}</label><input id="password" type="password" name="password" {{ isset($customer) ? '' : 'required' }}>@error('password')<div class="error">{{ $message }}</div>@enderror</div>
    </div>
    <div class="actions" style="margin-top:16px"><button class="btn" type="submit">{{ $submitLabel }}</button><a class="btn secondary" href="{{ $backUrl }}">Cancel</a></div>
</form>
