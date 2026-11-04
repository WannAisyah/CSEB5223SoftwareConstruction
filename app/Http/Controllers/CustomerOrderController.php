<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class CustomerOrderController extends Controller
{
    public function index(): View
    {
        $orders = $this->customer()->orders()
            ->with('payment')
            ->latest('order_date')
            ->paginate(15);

        return view('customer.orders.index', compact('orders'));
    }

    public function show(int $order): View
    {
        $order = $this->customer()->orders()
            ->with(['orderItems.product', 'payment'])
            ->findOrFail($order);

        return view('customer.orders.show', compact('order'));
    }

    private function customer(): Customer
    {
        $customer = Auth::guard('customer')->user();
        abort_unless($customer instanceof Customer, 403);

        return $customer;
    }
}
