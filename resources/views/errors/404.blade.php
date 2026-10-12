@extends('errors.layout')
@section('codigo', '404')
@section('titulo', 'No encontramos esta página')
@section('icono')
    <path stroke-linecap="round" stroke-linejoin="round" d="M21 21l-4.35-4.35M11 19a8 8 0 110-16 8 8 0 010 16z" />
@endsection
@section('mensaje')
    <p>La dirección que abriste no existe o el contenido fue eliminado.</p>
@endsection
@section('acciones')
    <a class="boton primario" href="{{ auth()->check() ? url('/dashboard') : route('login') }}">
        {{ auth()->check() ? 'Volver al inicio' : 'Ir al inicio de sesión' }}
    </a>
@endsection
