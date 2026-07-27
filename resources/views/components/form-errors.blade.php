@if ($errors->any())
    <x-alert variant="danger">
        <strong>No se pudo guardar la información.</strong><br>
        Corrige los campos marcados e inténtalo nuevamente.
    </x-alert>
@endif
