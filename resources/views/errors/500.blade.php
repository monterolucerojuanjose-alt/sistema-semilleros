@extends('errors.layout')
@section('codigo', '500')
@section('titulo', 'Ocurrió un problema en el servidor')
@section('icono')
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 11-18 0 9 9 0 0118 0zm-9 3.75h.008v.008H12v-.008z" />
@endsection
@section('mensaje')
    <p>No pudimos completar la operación. Vuelve a intentarlo en unos momentos.</p>
    <p>Si el problema continúa, informa al administrador del sistema.</p>
@endsection
@section('acciones')
    <a class="boton primario" href="{{ auth()->check() ? url('/dashboard') : route('login') }}">Volver al inicio</a>
@endsection
