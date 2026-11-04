<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Validation\ValidationException;

class Order extends Model
{
    protected $fillable = [
        'customer_id',
        'delivery_address',
        'order_date',
        'total_amount',
        'status',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function payment(): HasOne
    {
        return $this->hasOne(Payment::class);
    }

    public function deductStock(): void
    {
        if ($this->stock_deducted_at !== null) {
            return;
        }

        $items = $this->orderItems()->orderBy('product_id')->get();

        if ($items->isEmpty()) {
            throw ValidationException::withMessages(['payment_status' => "Add at least one item before paying for order #{$this->id}."]);
        }

        foreach ($items as $item) {
            $product = Product::query()->lockForUpdate()->find($item->product_id);

            if ($product === null || $product->stock < $item->quantity) {
                throw ValidationException::withMessages([
                    'payment_status' => $product === null
                        ? "Product #{$item->product_id} is no longer available."
                        : "{$product->name} only has {$product->stock} units available.",
                ]);
            }

            $product->decrement('stock', $item->quantity);
        }

        $this->stock_deducted_at = now();
        $this->save();
    }

    public function restoreStock(): void
    {
        if ($this->stock_deducted_at === null) {
            return;
        }

        foreach ($this->orderItems()->orderBy('product_id')->get() as $item) {
            Product::withTrashed()->whereKey($item->product_id)->increment('stock', $item->quantity);
        }

        $this->stock_deducted_at = null;
        $this->save();
    }

    protected function casts(): array
    {
        return [
            'order_date' => 'datetime',
            'stock_deducted_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }
}
