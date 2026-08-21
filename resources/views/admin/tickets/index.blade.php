@extends('layouts.admin')

@section('title', __('Tickets'))

@section('content')
    <livewire:admin.tickets.ticket-list />
@endsection
