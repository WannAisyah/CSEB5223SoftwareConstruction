<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);

        $query = Product::query()
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $search = $request->string('search')->trim()->toString();

                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest();

        if (request()->expectsJson()) {
            return response()->json($query->get());
        }

        $products = $query->paginate(15)->withQueryString();

        return view('products.index', compact('products'));
    }

    public function create()
    {
        return view('products.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'required|numeric|min:0',
            'stock' => 'required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $product = new Product;
        $this->saveWithImage($product, $request, $validated);

        if (request()->expectsJson()) {
            return response()->json($product, 201);
        }

        return redirect()->route('products.show', $product)->with('success', "Created product {$product->name} (#{$product->id}).");
    }

    public function show(Product $product)
    {
        if (request()->expectsJson()) {
            return response()->json($product);
        }

        return view('products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('products.edit', compact('product'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'price' => 'sometimes|required|numeric|min:0',
            'stock' => 'sometimes|required|integer|min:0',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $this->saveWithImage($product, $request, $validated);

        if (request()->expectsJson()) {
            return response()->json($product);
        }

        return redirect()->route('products.show', $product)->with('success', "Updated product {$product->name} (#{$product->id}).");
    }

    public function destroy(Product $product)
    {
        $product->delete();

        if (request()->expectsJson()) {
            return response()->json([
                'message' => "Removed product {$product->name} (#{$product->id}) from the catalog.",
            ]);
        }

        return redirect()->route('products.index')->with('success', "Removed product {$product->name} (#{$product->id}) from the catalog.");
    }

    /** @param array<string, mixed> $validated */
    private function saveWithImage(Product $product, Request $request, array $validated): void
    {
        $oldImage = $product->image;
        unset($validated['image']);
        $path = null;

        if ($request->hasFile('image')) {
            $image = $request->file('image');

            if (! $image->isValid() || $image->getPathname() === '' || ! is_file($image->getPathname())) {
                throw ValidationException::withMessages([
                    'image' => 'The image upload was not received correctly. Please try again.',
                ]);
            }

            $path = 'products/'.Str::random(40).'.'.$image->extension();
            $stream = fopen($image->getPathname(), 'rb');

            if ($stream === false) {
                throw ValidationException::withMessages(['image' => 'The image could not be read. Please try again.']);
            }

            try {
                $stored = Storage::disk('public')->put($path, $stream);
            } finally {
                fclose($stream);
            }

            if (! $stored) {
                throw ValidationException::withMessages(['image' => 'The image could not be saved. Please try again.']);
            }

            $validated['image'] = $path;
        }

        try {
            $product->fill($validated)->save();
        } catch (Throwable $exception) {
            if ($path !== null) {
                Storage::disk('public')->delete($path);
            }

            throw $exception;
        }

        if ($path !== null) {
            $this->deleteUnusedImage($oldImage);
        }
    }

    private function deleteUnusedImage(?string $path): void
    {
        if ($path !== null && preg_match('/\Aproducts\/[a-zA-Z0-9]+\.(jpg|jpeg|png|webp)\z/', $path)
            && ! Product::withTrashed()->where('image', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }
}
