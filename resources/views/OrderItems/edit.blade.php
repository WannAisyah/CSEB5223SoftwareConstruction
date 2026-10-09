@extends('layouts.form', ['title' => 'Edit Order Item', 'backUrl' => route('order-items.show', $orderItem)])
@section('form') @include('OrderItems._form', ['action' => route('order-items.update', $orderItem), 'method' => 'PUT', 'submitLabel' => 'Save changes', 'backUrl' => route('order-items.show', $orderItem)]) @endsection
