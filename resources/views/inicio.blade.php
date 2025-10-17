@extends('app')

@section('title', 'Inicio')

@section('content')
@php
  $stats = $stats ?? ['usuarios' => 0, 'roles' => 0, 'proveedors' => 0];
@endphp
<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<link rel="stylesheet" href="{{ asset('css/style.css') }}">

<section class="panel">
  <h1 class="h-top" style="color:white;">Inicio</h1>
  <p style="color:white;">Bienvenido, aquí tienes accesos rápidos y un resumen del sistema.</p>

  {{-- Tarjetas de resumen --}}
  <div class="grid" style="margin-top:16px;">
    <div class="panel">
      <h3>Usuarios</h3>
      <p style="font-size:28px;font-weight:700;margin:6px 0;">{{ $stats['usuarios'] }}</p>
      @if (Route::has('users.index'))
        <a class="btn" href="{{ route('users.index') }}">Gestionar usuarios</a>
      @endif
    </div>

    <div class="panel">
      <h3>Roles</h3>
      <p style="font-size:28px;font-weight:700;margin:6px 0;">{{ $stats['roles'] }}</p>
      @if (Route::has('rols.index'))
        <a class="btn" href="{{ route('rols.index') }}">Gestionar roles</a>
      @endif
    </div>

    <div class="panel">
      <h3>Proveedors</h3>
      <p style="font-size:28px;font-weight:700;margin:6px 0;">{{ $stats['proveedors'] }}</p>
      @if (Route::has('proveedors.index'))
        <a class="btn" href="{{ route('proveedors.index') }}">Gestionar proveedors</a>
      @endif
    </div>
  </div>

  {{-- Acciones rápidas --}}
  <div class="toolbar" style="margin-top:18px;">
    @if (Route::has('proveedors.index'))
      <a class="btn" href="{{ route('proveedors.index') }}">Nuevo proveedor</a>
    @endif
    @if (Route::has('users.index'))
      <a class="btn-outline" href="{{ route('users.index') }}">Nuevo usuario</a>
    @endif
  </div>
</section>
@endsection
