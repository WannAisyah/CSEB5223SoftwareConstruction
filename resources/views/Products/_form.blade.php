<form action="{{ $action }}" method="POST" enctype="multipart/form-data">
    @csrf
    @if ($method !== 'POST') @method($method) @endif
    <div class="form-grid">
        <div><label for="name">Name</label><input id="name" name="name" value="{{ old('name', $product->name ?? '') }}" required>@error('name')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="price">Price</label><input id="price" type="number" step="0.01" min="0" name="price" value="{{ old('price', $product->price ?? '') }}" required>@error('price')<div class="error">{{ $message }}</div>@enderror</div>
        <div><label for="stock">Stock</label><input id="stock" type="number" min="0" name="stock" value="{{ old('stock', $product->stock ?? 0) }}" required>@error('stock')<div class="error">{{ $message }}</div>@enderror</div>
        <div>
            <label for="image">Product image</label>
            <input id="image" type="file" name="image" accept="image/jpeg,image/png,image/webp" aria-describedby="image-hint">
            <p id="image-hint" class="mt-2 text-xs text-zinc-500">JPG, PNG or WebP. Maximum 2 MB.</p>
            @error('image')<div class="error">{{ $message }}</div>@enderror
            @if(isset($product) && $product->image)
                <div class="mt-3 flex items-center gap-3">
                    <div class="relative flex h-14 w-14 shrink-0 items-center justify-center overflow-hidden rounded-md border border-zinc-200 bg-zinc-100 text-xs text-zinc-400">
                        <span>{{ Str::upper(Str::substr($product->name, 0, 2)) }}</span>
                        <img src="{{ $product->imageUrl() }}" alt="Current image for {{ $product->name }}" data-product-image class="absolute inset-0 h-full w-full bg-white object-contain opacity-0">
                    </div>
                    <span class="text-xs text-zinc-500">Current image</span>
                </div>
            @endif
        </div>
    </div>
    <p><label for="description">Description</label><textarea id="description" name="description">{{ old('description', $product->description ?? '') }}</textarea>@error('description')<span class="error">{{ $message }}</span>@enderror</p>
    <div class="actions"><button class="btn" type="submit">{{ $submitLabel }}</button><a class="btn secondary" href="{{ $backUrl }}">Cancel</a></div>
</form>
