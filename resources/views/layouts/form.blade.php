@extends('layouts.app')
@section('content')
    <div class="topbar">
        <div><a href="{{ $backUrl }}" class="mb-3 inline-block text-sm text-zinc-500 hover:text-emerald-800">Back to records</a><h1>{{ $title }}</h1></div>
    </div>
    <div class="max-w-3xl border-t border-zinc-200 pt-7">
        @yield('form')
    </div>
@endsection
