<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class CartItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $cartItems = CartItem::with(['cart', 'product'])->get();

        if (request()->expectsJson()) {
            return response()->json($cartItems);
        }

        return view('CartItems.index', compact('cartItems'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $carts = Cart::with('customer')->orderBy('id')->get();
        $products = Product::orderBy('name')->get();

        return view('CartItems.create', compact('carts', 'products'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'cart_id' => 'required|exists:carts,id',
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
        ]);

        $product = Product::findOrFail($validated['product_id']);

        $validated['price'] = $product->price;

        $cartItem = DB::transaction(function () use ($validated) {
            $cartItem = CartItem::create($validated);
            $this->syncCartTotal($cartItem->cart);

            return $cartItem;
        });

        if (request()->expectsJson()) {
            return response()->json($cartItem->load(['cart', 'product']), 201);
        }

        return redirect()->route('cart-items.show', $cartItem)->with('success', 'Cart item added successfully.');
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

        return view('CartItems.show', compact('cartItem'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(CartItem $cartItem)
    {
        $carts = Cart::with('customer')->orderBy('id')->get();
        $products = Product::orderBy('name')->get();

        return view('CartItems.edit', compact('cartItem', 'carts', 'products'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, CartItem $cartItem)
    {
        $validated = $request->validate([
            'cart_id' => 'sometimes|required|exists:carts,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'quantity' => 'sometimes|required|integer|min:1',
        ]);

        if (isset($validated['product_id'])) {
            $product = Product::findOrFail($validated['product_id']);
            $validated['price'] = $product->price;
        }

        DB::transaction(function () use ($cartItem, $validated) {
            $previousCart = $cartItem->cart;
            $cartItem->update($validated);
            $this->syncCartTotal($previousCart);
            $this->syncCartTotal($cartItem->fresh()->cart);
        });

        if (request()->expectsJson()) {
            return response()->json($cartItem->load(['cart', 'product']));
        }

        return redirect()->route('cart-items.show', $cartItem)->with('success', 'Cart item updated successfully.');
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
                'message' => 'Cart item deleted successfully.',
            ]);
        }

        return redirect()->route('cart-items.index')->with('success', 'Cart item deleted successfully.');
    }

    private function syncCartTotal(Cart $cart): void
    {
        $cart->update([
            'total' => $cart->cartItems()->sum(DB::raw('quantity * price')),
        ]);
    }
}
