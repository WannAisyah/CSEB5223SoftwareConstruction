<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Throwable;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::query()
            ->when($request->string('search')->trim()->isNotEmpty(), function ($query) use ($request) {
                $query->where(function ($query) use ($request) {
                    $search = $request->string('search')->trim()->toString();

                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        if (request()->expectsJson()) {
            return response()->json($products);
        }

        return view('Products.index', compact('products'));
    }

    public function create()
    {
        return view('Products.create');
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

        return redirect()->route('products.show', $product)->with('success', 'Product created successfully.');
    }

    public function show(Product $product)
    {
        if (request()->expectsJson()) {
            return response()->json($product);
        }

        return view('Products.show', compact('product'));
    }

    public function edit(Product $product)
    {
        return view('Products.edit', compact('product'));
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

        return redirect()->route('products.show', $product)->with('success', 'Product updated successfully.');
    }

    public function destroy(Product $product)
    {
        if ($product->orderItems()->exists()) {
            return back()->withErrors([
                'product' => 'This product cannot be deleted because it belongs to an order.',
            ]);
        }

        $image = $product->image;
        $product->delete();
        $this->deleteUnusedImage($image);

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Product deleted successfully.',
            ]);
        }

        return redirect()->route('products.index')->with('success', 'Product deleted successfully.');
    }

    /** @param array<string, mixed> $validated */
    private function saveWithImage(Product $product, Request $request, array $validated): void
    {
        $oldImage = $product->image;
        unset($validated['image']);
        $path = null;

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('products', 'public');

            if ($path === false) {
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
            && ! Product::where('image', $path)->exists()) {
            Storage::disk('public')->delete($path);
        }
    }
}
