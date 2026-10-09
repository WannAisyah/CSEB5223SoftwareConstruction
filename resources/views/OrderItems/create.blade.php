@extends('layouts.form', ['title' => 'Add Order Item', 'backUrl' => route('order-items.index')])
@section('form') @include('OrderItems._form', ['action' => route('order-items.store'), 'method' => 'POST', 'submitLabel' => 'Add item', 'backUrl' => route('order-items.index')]) @endsection
