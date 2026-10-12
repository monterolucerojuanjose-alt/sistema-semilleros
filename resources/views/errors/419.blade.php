@extends('errors.layout')
@section('codigo', '419')
@section('titulo', 'La página caducó')
@section('icono')
    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 11-20 0 10 10 0 0120 0z" />
@endsection
@section('mensaje')
    <p>Dejaste esta página abierta demasiado tiempo y, por seguridad, la sesión se reinició.</p>
    <p>No perdiste nada: vuelve a la pantalla de acceso e inténtalo otra vez.</p>
@endsection
@section('acciones')
    <a class="boton primario" href="{{ route('login') }}">Ir al inicio de sesión</a>
@endsection
