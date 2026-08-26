@extends('layouts.admin')

@section('title', __('Edit draft'))

@section('content')
    <livewire:admin.tickets.create-ticket :draft="$ticket" />
@endsection
