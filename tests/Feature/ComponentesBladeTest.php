<?php

// ===================== x-badge =====================

test('x-badge usa variant ok por defecto', function () {
    $html = (string) $this->blade('<x-badge>Disponible</x-badge>');

    expect($html)->toContain('class="badge ok"');
    expect($html)->toContain('Disponible');
});

test('x-badge variant warn/danger/critical generan las clases correctas', function () {
    expect((string) $this->blade('<x-badge variant="warn">x</x-badge>'))->toContain('class="badge warn"');
    expect((string) $this->blade('<x-badge variant="danger">x</x-badge>'))->toContain('class="badge danger"');
    expect((string) $this->blade('<x-badge variant="critical">x</x-badge>'))->toContain('class="badge critical"');
});

test('x-badge con variant desconocida cae a ok', function () {
    $html = (string) $this->blade('<x-badge variant="lo-que-sea">x</x-badge>');

    expect($html)->toContain('class="badge ok"');
});

// ===================== x-button =====================

test('x-button variant secondary por defecto renderiza boton btn-outline', function () {
    $html = (string) $this->blade('<x-button>Cancelar</x-button>');

    expect($html)->toContain('<button');
    expect($html)->toContain('class="btn-outline"');
    expect($html)->toContain('type="button"');
    expect($html)->toContain('Cancelar');
});

test('x-button variant primary/danger/ghost generan las clases correctas', function () {
    expect((string) $this->blade('<x-button variant="primary">x</x-button>'))->toContain('class="btn"');
    expect((string) $this->blade('<x-button variant="danger">x</x-button>'))->toContain('class="btn danger"');
    expect((string) $this->blade('<x-button variant="ghost">x</x-button>'))->toContain('class="btn-ghost"');
});

test('x-button con href renderiza un enlace', function () {
    $html = (string) $this->blade('<x-button href="/ventas/create" variant="primary">Nueva venta</x-button>');

    expect($html)->toContain('<a href="/ventas/create"');
    expect($html)->not->toContain('<button');
});

test('x-button deshabilitado con href igual renderiza boton disabled, no enlace', function () {
    $html = (string) $this->blade('<x-button href="/devoluciones/create" :disabled="true">Registrar devolución</x-button>');

    expect($html)->not->toContain('<a href');
    expect($html)->toContain('<button');
    expect($html)->toContain('disabled');
    expect($html)->toContain('aria-disabled="true"');
});

test('x-button con icon renderiza el icono antes del texto', function () {
    $html = (string) $this->blade('<x-button icon="ri-add-line">Nuevo</x-button>');

    expect($html)->toContain('<i class="ri-add-line"></i>');
});

// ===================== x-alert =====================

test('x-alert variant info por defecto no agrega clase modificadora', function () {
    $html = (string) $this->blade('<x-alert>Mensaje</x-alert>');

    expect($html)->toContain('class="alert"');
    expect($html)->toContain('Mensaje');
});

test('x-alert variant success/danger/warning generan las clases correctas', function () {
    expect((string) $this->blade('<x-alert variant="success">x</x-alert>'))->toContain('class="alert alert-success"');
    expect((string) $this->blade('<x-alert variant="danger">x</x-alert>'))->toContain('class="alert alert-danger"');
    expect((string) $this->blade('<x-alert variant="warning">x</x-alert>'))->toContain('class="alert alert-warning"');
});

test('x-alert con title renderiza el titulo en negrita antes del contenido', function () {
    $html = (string) $this->blade('<x-alert title="Revisa los campos:"><ul><li>Error</li></ul></x-alert>');

    expect($html)->toContain('<strong>Revisa los campos:</strong>');
});

test('x-alert sin title no renderiza strong', function () {
    $html = (string) $this->blade('<x-alert>Mensaje simple</x-alert>');

    expect($html)->not->toContain('<strong>');
});

// ===================== x-empty-state =====================

test('x-empty-state por defecto reproduce exactamente el empty-box actual', function () {
    $html = (string) $this->blade('<x-empty-state message="No hay compras que coincidan con estos filtros." />');
    $normalizado = trim(preg_replace('/\s+/', ' ', $html));

    expect($normalizado)->toBe('<div class="empty-box"> No hay compras que coincidan con estos filtros. </div>');
});

test('x-empty-state compact usa la clase empty-state--compact con icono', function () {
    $html = (string) $this->blade('<x-empty-state compact icon="ri-checkbox-circle-line" message="No hay productos vencidos." />');

    expect($html)->toContain('class="empty-state--compact"');
    expect($html)->toContain('<i class="ri-checkbox-circle-line"></i>');
    expect($html)->toContain('No hay productos vencidos.');
});

test('x-empty-state sin slot de acciones no agrega markup extra', function () {
    $html = (string) $this->blade('<x-empty-state message="Nada aquí." />');

    expect($html)->not->toContain('mt-12');
});

test('x-empty-state con slot de acciones lo renderiza despues del mensaje', function () {
    $html = (string) $this->blade('<x-empty-state message="No hay productos."><a href="/productos">Crear producto</a></x-empty-state>');

    expect($html)->toContain('class="mt-12"');
    expect($html)->toContain('Crear producto');
});

// ===================== x-card =====================

test('x-card sin title no renderiza h3', function () {
    $html = (string) $this->blade('<x-card>Contenido</x-card>');

    expect($html)->toContain('class="card"');
    expect($html)->not->toContain('<h3');
    expect($html)->toContain('Contenido');
});

test('x-card con title renderiza h3.card-title', function () {
    $html = (string) $this->blade('<x-card title="Datos de la venta">Contenido</x-card>');

    expect($html)->toContain('<h3 class="card-title">');
    expect($html)->toContain('Datos de la venta');
});

test('x-card con title e icon renderiza el icono dentro del card-title', function () {
    $html = (string) $this->blade('<x-card title="Ventas de hoy" icon="ri-shopping-bag-3-line">Contenido</x-card>');

    expect($html)->toContain('<i class="ri-shopping-bag-3-line"></i>');
});
