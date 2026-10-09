@extends('layouts.form', ['title' => 'Create Customer', 'backUrl' => route('customers.index')])
@section('form') @include('customers._form', ['action' => route('customers.store'), 'method' => 'POST', 'submitLabel' => 'Create customer', 'backUrl' => route('customers.index')]) @endsection
