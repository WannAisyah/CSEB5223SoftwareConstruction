<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::transaction(function (): void {
            DB::table('orders')->where('status', 'completed')->whereNull('stock_deducted_at')
                ->update(['stock_deducted_at' => now()]);

            $orderIds = DB::table('orders')->whereIn('status', ['pending', 'processing'])
                ->where(function ($query): void {
                    $query->whereNotNull('stock_deducted_at')
                        ->orWhereExists(fn ($payments) => $payments->selectRaw('1')->from('payments')
                            ->whereColumn('payments.order_id', 'orders.id')->where('payment_status', 'paid'));
                })->orderBy('id')->pluck('id');

            foreach ($orderIds as $orderId) {
                $order = DB::table('orders')->where('id', $orderId)->lockForUpdate()->first();
                $paid = DB::table('payments')->where('order_id', $orderId)->where('payment_status', 'paid')->exists();

                if ($paid === ($order->stock_deducted_at !== null)) {
                    continue;
                }

                $items = DB::table('order_items')->where('order_id', $orderId)->orderBy('product_id')->get();

                if ($paid && $items->isEmpty()) {
                    throw new RuntimeException("Paid order #{$orderId} has no items; inventory was not changed.");
                }

                foreach ($items as $item) {
                    $product = DB::table('products')->where('id', $item->product_id)->lockForUpdate()->first();

                    if (! $product || ($paid && ($product->deleted_at !== null || $product->stock < $item->quantity))) {
                        throw new RuntimeException("Cannot reconcile stock for order #{$orderId}, product #{$item->product_id}.");
                    }

                    DB::table('products')->where('id', $item->product_id)
                        ->update(['stock' => $product->stock + ($paid ? -$item->quantity : $item->quantity)]);
                }

                DB::table('orders')->where('id', $orderId)
                    ->update(['stock_deducted_at' => $paid ? now() : null]);
            }
        }, attempts: 3);
    }

    public function down(): void
    {
        // Inventory may have changed since reconciliation, so it cannot be safely reversed.
    }
};
