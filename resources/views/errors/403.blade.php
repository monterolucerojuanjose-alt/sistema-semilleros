@extends('errors.layout')
@section('codigo', '403')
@section('titulo', 'No tienes permiso para entrar aquí')
@section('icono')
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
@endsection
@section('mensaje')
    <p>{{ $exception?->getMessage() ?: 'Esta sección está reservada para otro rol dentro del sistema.' }}</p>
    <p>Si crees que deberías tener acceso, comunícalo al administrador.</p>
@endsection
@section('acciones')
    <a class="boton primario" href="{{ auth()->check() ? url('/dashboard') : route('login') }}">
        {{ auth()->check() ? 'Volver al inicio' : 'Ir al inicio de sesión' }}
    </a>
@endsection
