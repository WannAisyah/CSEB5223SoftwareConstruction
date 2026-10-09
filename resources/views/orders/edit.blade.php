@extends('layouts.form', ['title' => 'Edit Order', 'backUrl' => route('orders.show', $order)])
@section('form') @include('orders._form', ['action' => route('orders.update', $order), 'method' => 'PUT', 'submitLabel' => 'Save changes', 'backUrl' => route('orders.show', $order)]) @endsection
