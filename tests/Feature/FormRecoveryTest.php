<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// items()/label() dependen de old(), que a su vez depende de una sesión
// vinculada a un ciclo HTTP real (Request::hasSession()) — no se puede
// seedear de forma confiable fuera de una request real. Esa parte queda
// cubierta por los tests de CompraController/VentaController/LoteController,
// que sí ejercitan el flujo completo. Acá solo lo que es válido aislado.

test('items() devuelve vacio si no hay old()', function () {
    expect(App\Support\FormRecovery::items('items', ['producto_id' => App\Models\Producto::class]))->toBe([]);
});

test('label() devuelve null si no hay old() para esa clave', function () {
    expect(App\Support\FormRecovery::label('proveedor_id', App\Models\Proveedor::class))->toBeNull();
});

test('fieldErrors() devuelve vacio sin errores en sesion', function () {
    expect(App\Support\FormRecovery::fieldErrors())->toBe([]);
});

test('fieldErrors() expone los mensajes con la misma forma que $errors->messages()', function () {
    $bag = new \Illuminate\Support\MessageBag(['items.0.cantidad' => 'Stock insuficiente.']);
    $viewErrorBag = (new \Illuminate\Support\ViewErrorBag())->put('default', $bag);
    session()->flash('errors', $viewErrorBag);

    expect(App\Support\FormRecovery::fieldErrors())->toBe(['items.0.cantidad' => ['Stock insuficiente.']]);
});
