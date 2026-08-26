@extends('layouts.admin')

@section('title', __('Demo data'))

@section('content')
    <livewire:admin.tickets.demo-data-generator />
@endsection
