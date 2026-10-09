@extends('layouts.form', ['title' => 'Edit Customer', 'backUrl' => route('customers.show', $customer)])
@section('form') @include('customers._form', ['action' => route('customers.update', $customer), 'method' => 'PUT', 'submitLabel' => 'Save changes', 'backUrl' => route('customers.show', $customer)]) @endsection
