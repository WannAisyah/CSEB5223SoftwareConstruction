<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerCartController extends Controller
{
    public function show(): View
    {
        $cart = $this->cart()->load('cartItems.product');

        $customer = $this->customer();

        return view('customer.cart', compact('cart', 'customer'));
    }

    public function store(Request $request): RedirectResponse
    {
        $cart = $this->cart();
        $validated = $request->validate([
            'product_id' => [
                'required',
                Rule::exists('products', 'id')->whereNull('deleted_at'),
            ],
            'quantity' => ['required', 'integer', 'min:1'],
        ]);

        $productName = DB::transaction(function () use ($cart, $validated): string {
            $lockedCart = Cart::query()->lockForUpdate()->findOrFail($cart->id);
            $product = Product::query()->lockForUpdate()->find($validated['product_id']);

            if ($product === null) {
                throw ValidationException::withMessages(['product_id' => 'This product is no longer available.']);
            }

            $item = $lockedCart->cartItems()->where('product_id', $product->id)->first();
            $quantity = ($item?->quantity ?? 0) + $validated['quantity'];
            $this->ensureStock($product, $quantity);

            if ($item) {
                $item->update(['quantity' => $quantity, 'price' => $product->price]);
            } else {
                $lockedCart->cartItems()->create([
                    'product_id' => $product->id,
                    'quantity' => $quantity,
                    'price' => $product->price,
                ]);
            }

            $this->syncTotal($lockedCart);

            return $product->name;
        }, attempts: 3);

        return back()->with('success', "Added {$validated['quantity']} × {$productName} to your cart.");
    }

    public function update(Request $request, int $cartItem): RedirectResponse
    {
        $item = $this->cart()->cartItems()->findOrFail($cartItem);
        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1'],
        ]);
        $product = Product::query()->find($item->product_id);

        if ($product === null) {
            throw ValidationException::withMessages([
                'quantity' => 'This product is no longer available. Remove it from your cart.',
            ]);
        }

        $this->ensureStock($product, $validated['quantity']);

        DB::transaction(function () use ($item, $product, $validated): void {
            $item->update([
                'quantity' => $validated['quantity'],
                'price' => $product->price,
            ]);
            $this->syncTotal($item->cart);
        });

        return back()->with('success', "Updated {$product->name} to {$validated['quantity']} in your cart.");
    }

    public function destroy(int $cartItem): RedirectResponse
    {
        $item = $this->cart()->cartItems()->findOrFail($cartItem);

        DB::transaction(function () use ($item): void {
            $cart = $item->cart;
            $item->delete();
            $this->syncTotal($cart);
        });

        return back()->with('success', 'Removed '.($item->product?->name ?? "product #{$item->product_id}")." ({$item->quantity}) from your cart.");
    }

    public function clear(): RedirectResponse
    {
        $removedCount = DB::transaction(function (): int {
            $cart = Cart::query()->whereBelongsTo($this->customer())->lockForUpdate()->firstOrFail();
            $removedCount = $cart->cartItems()->count();
            $cart->cartItems()->delete();
            $cart->update(['total' => 0]);

            return $removedCount;
        });

        return redirect()->route('customer.cart.show')
            ->with('success', "Cleared {$removedCount} ".($removedCount === 1 ? 'product line' : 'product lines').' from your cart.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'delivery_address' => ['required', 'string', 'max:255'],
            'payment_method' => ['required', Rule::in(['cash', 'card', 'bank_transfer'])],
        ]);
        $customer = $this->customer();

        $order = DB::transaction(function () use ($customer, $validated): Order {
            $cart = Cart::query()->whereBelongsTo($customer)->lockForUpdate()->firstOrFail();
            $items = $cart->cartItems()->orderBy('product_id')->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $lines = [];
            $total = 0.0;

            foreach ($items as $item) {
                $product = Product::query()->lockForUpdate()->find($item->product_id);

                if ($product === null) {
                    throw ValidationException::withMessages([
                        'cart' => "Product #{$item->product_id} is no longer available.",
                    ]);
                }

                $this->ensureStock($product, $item->quantity, 'cart');
                $lines[] = [
                    'product_id' => $product->id,
                    'quantity' => $item->quantity,
                    'price' => $product->price,
                ];
                $total += (float) $product->price * $item->quantity;
            }

            $order = $customer->orders()->create([
                'delivery_address' => $validated['delivery_address'],
                'order_date' => now(),
                'total_amount' => round($total, 2),
                'status' => 'pending',
            ]);
            $order->orderItems()->createMany($lines);
            $order->payment()->create([
                'amount' => $order->total_amount,
                'payment_method' => $validated['payment_method'],
                'payment_status' => 'pending',
            ]);
            $cart->cartItems()->delete();
            $cart->update(['total' => 0]);

            return $order;
        }, attempts: 3);

        return redirect()->route('customer.orders.show', $order)
            ->with('success', "Placed order #{$order->id} for \$".number_format((float) $order->total_amount, 2).'.');
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }

    private function cart(): Cart
    {
        return $this->customer()->cart()->firstOrFail();
    }

    private function syncTotal(Cart $cart): void
    {
        $cart->update([
            'total' => $cart->cartItems()->sum(DB::raw('quantity * price')),
        ]);
    }

    private function ensureStock(Product $product, int $quantity, string $field = 'quantity'): void
    {
        if ($product->stock < $quantity) {
            $message = $product->stock === 0
                ? "{$product->name} is out of stock."
                : "Only {$product->stock} units of {$product->name} are available.";

            throw ValidationException::withMessages([$field => $message]);
        }
    }
}
