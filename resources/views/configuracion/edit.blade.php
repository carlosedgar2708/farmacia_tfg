@extends('app')

@section('title', 'Configuración del Sistema')

@section('content')
<section class="grid" style="grid-template-columns:1fr">
  <div class="hero">
    <div class="panel" style="background:#1157c2;color:#fff">
      <h1><span class="h-top" style="color:#fff;font-size:38px">CONFIGURACIÓN DEL SISTEMA</span></h1>

      @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
      @endif

      <form method="POST" action="{{ route('configuracion.update') }}">
        @csrf
        @method('PUT')

        <div style="margin-top:20px">
          <label for="dias_alerta_vencimiento">Días de antelación para alertar lotes próximos a vencer</label><br>
          <input type="number" id="dias_alerta_vencimiento" name="dias_alerta_vencimiento"
                 value="{{ old('dias_alerta_vencimiento', $diasAlertaVencimiento) }}"
                 min="1" max="365" required>
        </div>

        <button type="submit" class="btn add mt-12">Guardar</button>
      </form>
    </div>
  </div>
</section>
@endsection
