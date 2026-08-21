@extends('layouts.admin')

@section('title', $ticket->ticket_number)

@section('content')
    <livewire:admin.tickets.show-ticket :ticket="$ticket" />
@endsection
