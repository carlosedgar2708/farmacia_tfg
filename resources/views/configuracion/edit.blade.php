@extends('app')

@section('title', 'Configuración del Sistema')

@section('content')
<x-card title="Configuración del sistema" icon="ri-settings-3-line">
  @if (session('success'))
    <x-alert variant="success">{{ session('success') }}</x-alert>
  @endif

  <form method="POST" action="{{ route('configuracion.update') }}">
    @csrf
    @method('PUT')

    <div style="margin-top:20px">
      <label for="dias_alerta_vencimiento">Días de antelación para alertar lotes próximos a vencer</label><br>
      <input type="number" id="dias_alerta_vencimiento" name="dias_alerta_vencimiento"
             value="{{ old('dias_alerta_vencimiento', $diasAlertaVencimiento) }}"
             min="1" max="365" required>
      @error('dias_alerta_vencimiento') <small class="field-error">{{ $message }}</small> @enderror
    </div>

    <x-button type="submit" variant="primary" style="margin-top:12px">Guardar</x-button>
  </form>
</x-card>
@endsection
