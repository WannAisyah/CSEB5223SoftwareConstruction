<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CartItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $request->validate(['search' => ['nullable', 'string', 'max:255']]);
        $search = $request->string('search')->trim()->toString();
        $query = CartItem::with(['cart.customer', 'product'])
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->whereHas('product', fn ($productQuery) => $productQuery->where('name', 'like', "%{$search}%"))
                        ->orWhereHas('cart.customer', fn ($customerQuery) => $customerQuery->where('name', 'like', "%{$search}%"));
                });
            });

        if (request()->expectsJson()) {
            return response()->json($query->get());
        }

        $cartItems = $query->paginate(15)->withQueryString();

        return view('cart-items.index', compact('cartItems'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $carts = Cart::with('customer')->orderBy('id')->get();
        $products = Product::orderBy('name')->get();

        return view('cart-items.create', compact('carts', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
                Rule::unique('cart_items', 'product_id')->where(
                    fn ($query) => $query->where('cart_id', $request->integer('cart_id'))
                ),
            ],
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);
        $this->ensureProductIsAvailable($product, $validated['quantity']);

        $validated['price'] = $product->price;

        $cartItem = DB::transaction(function () use ($validated) {
            $cartItem = CartItem::create($validated);
            $this->syncCartTotal($cartItem->cart);

            return $cartItem;
        });

        if (request()->expectsJson()) {
            return response()->json($cartItem->load(['cart', 'product']), 201);
        }

        return redirect()->route('cart-items.show', $cartItem)
            ->with('success', "Added {$cartItem->quantity} × {$product->name} to cart #{$cartItem->cart_id}.");
    }

    /**
     * Display the specified resource.
     */
    public function show(CartItem $cartItem)
    {
        if (request()->expectsJson()) {
            return response()->json(
                $cartItem->load(['cart', 'product'])
            );
        }

        return view('cart-items.show', compact('cartItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CartItem $cartItem)
    {
        $carts = Cart::with('customer')->orderBy('id')->get();
        $products = Product::orderBy('name')->get();

        return view('cart-items.edit', compact('cartItem', 'carts', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $cartId = $request->has('cart_id') ? $request->integer('cart_id') : $cartItem->cart_id;

        $validated = $request->validate([
            'cart_id' => 'sometimes|required|exists:carts,id',
            'product_id' => [
                'sometimes',
                'required',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
                Rule::unique('cart_items', 'product_id')
                    ->where(fn ($query) => $query->where('cart_id', $cartId))
                    ->ignore($cartItem),
            ],
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        $product = Product::find($validated['product_id'] ?? $cartItem->product_id);

        if ($product === null) {
            throw ValidationException::withMessages([
                'product_id' => "Product #{$cartItem->product_id} is no longer available. Remove it from the cart.",
            ]);
        }

        $this->ensureProductIsAvailable($product, $validated['quantity'] ?? $cartItem->quantity);
        $validated['price'] = $product->price;

        DB::transaction(function () use ($cartItem, $validated) {
            $previousCart = $cartItem->cart;
            $cartItem->update($validated);
            $this->syncCartTotal($previousCart);
            $this->syncCartTotal($cartItem->fresh()->cart);
        });

        if (request()->expectsJson()) {
            return response()->json($cartItem->load(['cart', 'product']));
        }

        return redirect()->route('cart-items.show', $cartItem)
            ->with('success', "Updated {$product->name} to {$cartItem->quantity} in cart #{$cartItem->cart_id}.");
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(CartItem $cartItem)
    {
        DB::transaction(function () use ($cartItem) {
            $cart = $cartItem->cart;
            $cartItem->delete();
            $this->syncCartTotal($cart);
        });

        if (request()->expectsJson()) {
            return response()->json([
                'message' => 'Removed '.($cartItem->product?->name ?? "product #{$cartItem->product_id}")." ({$cartItem->quantity}) from cart #{$cartItem->cart_id}.",
            ]);
        }

        return redirect()->route('cart-items.index')->with('success', 'Removed '.($cartItem->product?->name ?? "product #{$cartItem->product_id}")." ({$cartItem->quantity}) from cart #{$cartItem->cart_id}.");
    }

    private function syncCartTotal(Cart $cart): void
    {
        $cart->update([
            'total' => $cart->cartItems()->sum(DB::raw('quantity * price')),
        ]);
    }

    private function ensureProductIsAvailable(Product $product, int $quantity): void
    {
        if ($product->stock < $quantity) {
            $message = $product->stock === 0
                ? "{$product->name} is out of stock."
                : "Only {$product->stock} units of {$product->name} are available.";

            throw ValidationException::withMessages(['quantity' => $message]);
        }
    }
}
