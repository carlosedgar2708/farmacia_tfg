# Flujo de una Venta — Farmacia Katy

## Descripción General

El módulo de ventas permite registrar la salida de productos del inventario hacia un cliente (identificado o anónimo). El sistema descuenta el stock automáticamente siguiendo una estrategia **FIFO por fecha de vencimiento** (se despachan primero los lotes que vencen antes). El precio de venta siempre se toma de la base de datos, nunca del formulario. Los descuentos solo están disponibles para usuarios administradores.

---

## Actores y Requisitos de Acceso

| Actor | Requisito |
|---|---|
| Usuario autenticado | Middleware `auth` |
| Registrar venta | Autenticado (sin permiso granular en la ruta principal) |
| Aplicar descuentos | Solo administradores (`esAdmin() === true`) |

---

## Paso 1 — Acceso al Formulario

**Ruta:** `GET /ventas/create`
**Controlador:** `VentaController::create()`
**Vista:** `resources/views/ventas/create.blade.php`

El controlador construye dos estructuras de datos:

**Lista de clientes:**
```php
Cliente::orderBy('nombre')->get(['id', 'nombre'])
```

**Lista de productos con sus lotes disponibles (serializada a JSON):**
```php
Producto::with([
    'lotes' => fn($q) => $q->where('stock', '>', 0)->vigentes()->orderBy('fecha_vencimiento')
])->orderBy('nombre')->get(['id', 'nombre', 'precio_venta'])
```

Para cada producto se incluye:
- `id`, `nombre`, `precio_venta`
- `lotes`: array de lotes con `stock > 0` **y vigentes** (scope `Lote::vigentes()` — excluye lotes con `fecha_vencimiento` pasada), cada uno con `id`, `label` (texto para mostrar), `stock`, `producto_id`

También se evalúa `$esAdmin = auth()->user()->esAdmin()` y se pasa a la vista para controlar la visibilidad del campo descuento.

---

## Paso 2 — Construcción del Formulario (Frontend)

La vista presenta dos paneles:

**Panel izquierdo — Líneas de venta:**
- Buscador de producto (autocomplete sobre el array JS `PRODUCTOS`).
- Al seleccionar un producto se muestra: stock total disponible (suma de todos sus lotes), precio unitario, campo de cantidad y campo de descuento.
- El campo descuento es visible y editable **solo si `$esAdmin` es `true`**. Para no-administradores se renderiza como `<input type="hidden" value="0">`.
- Validación client-side: la cantidad ingresada no puede superar el stock total del producto.
- Botón "Agregar" que inserta la línea en la tabla resumen con el subtotal calculado: `(cantidad × precio) − descuento`.
- El total acumulado de la venta se actualiza en tiempo real.

**Panel derecho — Datos de la transacción:**
- Buscador de cliente con autocomplete (cliente opcional — si no se selecciona, la venta se registra sin cliente identificado).
- Opción para crear un cliente nuevo vía modal AJAX.
- Checkbox "Emitir recibo", tipo de comprobante (Ticket/Factura), folio, monto recibido y cálculo de cambio.
- Botón "Confirmar Venta".

**Nota:** Los campos de recibo (tipo comprobante, folio, monto recibido) se envían al servidor pero `VentaController::store()` no los procesa actualmente. Son parte de una funcionalidad incompleta.

**Construcción del payload:**
Al hacer submit, JavaScript genera campos hidden con la estructura:
```
items[0][producto_id]
items[0][cantidad]
items[0][precio]
items[0][descuento]
items[1][producto_id]
...
```

No se envía `lote_id` — el servidor decide qué lotes usar.

---

## Paso 3 — Validación del Servidor

**Ruta:** `POST /ventas`
**Controlador:** `VentaController::store(Request $request)`

```
cliente_id          → opcional, entero, debe existir en tabla clientes si se indica
observacion         → opcional, string, máximo 500 caracteres
items               → requerido, array, mínimo 1 elemento
items.*.producto_id → requerido, entero, debe existir en tabla productos
items.*.cantidad    → requerido, entero, mínimo 1
items.*.precio      → opcional, numérico, mínimo 0 (el servidor lo ignora y usa el precio de BD)
items.*.descuento   → opcional, numérico, mínimo 0
```

Mensaje de error personalizado:
- `items.required` → `"Agrega al menos un renglón de venta."`

---

## Paso 4 — Transacción de Base de Datos

Todo el proceso ocurre dentro de `DB::transaction()`. Si cualquier operación falla, se hace rollback completo.

### 4.1 — Creación del Registro de Venta

```sql
INSERT INTO ventas (cliente_id, user_id, fecha_venta, observacion, estado, ...)
VALUES (?, ?, NOW(), ?, 'confirmada', ...)
```

El `estado` siempre se establece como `'confirmada'` al crear.

### 4.2 — Procesamiento de cada Ítem

Para cada elemento del array `items`:

**a) Obtención del precio real:**

```php
$producto = Producto::findOrFail($productoId);
$precioUnitario = (float) $producto->precio_venta;
```

El precio que envía el formulario es ignorado. El sistema siempre usa el `precio_venta` de la base de datos en el momento de la venta. Esto previene manipulaciones de precio desde el cliente.

**b) Aplicación del descuento:**

```php
$descuento = $esAdmin ? (float)($it['descuento'] ?? 0) : 0;
```

Solo los administradores pueden aplicar descuentos. Para el resto el descuento se fuerza a `0` independientemente de lo que envíe el formulario.

**c) Consulta FIFO de lotes disponibles (solo vigentes):**

```sql
SELECT * FROM lotes
WHERE producto_id = ?
  AND stock > 0
  AND (fecha_vencimiento IS NULL OR fecha_vencimiento >= CURDATE())  -- scope Lote::vigentes()
ORDER BY (fecha_vencimiento IS NULL) ASC, fecha_vencimiento ASC
FOR UPDATE
```

Esta consulta implementa la estrategia **FIFO por vencimiento**:
- El scope `Lote::vigentes()` excluye los lotes ya vencidos del conjunto de candidatos **antes** de aplicar el orden FIFO — un lote vencido nunca puede ser despachado, sin importar cuánto stock tenga.
- Entre los lotes vigentes, los que tienen fecha de vencimiento más próxima se procesan primero.
- Los lotes sin fecha de vencimiento (`NULL`) van al final.
- `lockForUpdate()` bloquea las filas para prevenir condiciones de carrera.

**Nota:** si el stock vigente no alcanza para cubrir la cantidad solicitada, la venta se rechaza con "Stock insuficiente" aunque existan lotes vencidos con stock disponible — ese stock ya no se contabiliza (ver `Paso 4.1.d`).

**d) Verificación de stock suficiente:**

```php
$stockTotal = $lotes->sum('stock');
if ($stockTotal < $cantidadSolicitada) {
    abort(422, "Stock insuficiente para el producto ID {$productoId}. Disponible total: {$stockTotal}");
}
```

**e) Descuento de stock lote por lote (FIFO):**

```php
$cantidadRestante = $cantidadSolicitada;

foreach ($lotes as $lote) {
    if ($cantidadRestante <= 0) break;

    $tomar = min($cantidadRestante, $lote->stock);

    // Registrar detalle
    DetalleVenta::create([...]);

    // Decrementar stock
    $lote->decrement('stock', $tomar);

    // Registrar movimiento
    MovimientoStock::create([...]);

    $cantidadRestante -= $tomar;
}
```

Por cada lote del que se extrae stock:

1. **INSERT en `detalles_venta`:**
   ```sql
   INSERT INTO detalles_venta (venta_id, producto_id, lote_id, cantidad, precio_unitario, ...)
   VALUES (?, ?, ?, ?, ?, ...)
   ```

2. **UPDATE en `lotes`:**
   ```sql
   UPDATE lotes SET stock = stock - ? WHERE id = ?
   ```

3. **INSERT en `movimientos_stock`:**
   ```sql
   INSERT INTO movimientos_stock (lote_id, fecha, tipo, motivo, cantidad, referencia, ...)
   VALUES (?, NOW(), 'Salida', 'Venta', ?, 'Venta #N', ...)
   ```

**f) Acumulación del total:**

```php
$importeItem = ($cantidadSolicitada * $precioUnitario) - $descuento;
$total += $importeItem;
```

### 4.3 — Creación del Recibo (Intento)

Al finalizar todos los ítems, el sistema intenta crear el recibo:

```php
if (method_exists($venta, 'recibo')) {
    $venta->recibo()->create([
        'venta_id' => $venta->id,
        'monto'    => $total,
    ]);
}
```

**Estado actual:** Este bloque siempre se ejecuta (el método `recibo()` existe en el modelo `Venta`), pero el campo `monto` no existe en la tabla `recibos`. La operación falla silenciosamente porque el intento de inserción lanza una excepción que no está siendo capturada en este contexto — o Laravel descarta el campo desconocido. El recibo **no se genera correctamente** en el estado actual del código.

---

## Paso 5 — Respuesta al Usuario

```php
return redirect()->route('ventas.index')->with('success', 'Venta registrada correctamente.');
```

---

## Ejemplo: Una Venta con Stock en Múltiples Lotes

**Escenario:** Se solicita vender 15 unidades del producto "Ibuprofeno 400mg".

**Estado de los lotes en BD** (fechas vigentes, es decir futuras respecto a hoy):

| lote_id | nro_lote | fecha_vencimiento | stock |
|---|---|---|---|
| 3 | LOT-2026-A | 2026-09-01 | 8 |
| 7 | LOT-2026-B | 2026-12-01 | 10 |
| 12 | LOT-2027-C | NULL | 20 |

**Resultado del procesamiento FIFO** (todos vigentes; un cuarto lote hipotético ya vencido con stock quedaría excluido por `Lote::vigentes()` y no participaría):

| Iteración | Lote procesado | Tomar | Stock restante en lote | cantidadRestante |
|---|---|---|---|---|
| 1 | LOT-2026-A (vence antes) | 8 | 0 | 7 |
| 2 | LOT-2026-B (vence después) | 7 | 3 | 0 |
| — | LOT-2027-C (sin fecha) | no se toca | 20 | — |

**Registros creados:**
- 2 filas en `detalles_venta` (una por cada lote consumido)
- 2 filas en `movimientos_stock` (una por cada lote)
- Decrementos: lote 3 pasa de 8 a 0; lote 7 pasa de 10 a 3

---

## Diagrama del Flujo

```
Usuario
  │
  ├── GET /ventas/create
  │       └── Carga clientes + productos+lotes JSON → Vista con autocomplete JS
  │
  ├── (opcional) POST /clientes [AJAX] → Crear cliente al vuelo → JSON {id, nombre}
  │
  └── POST /ventas
          │
          ├── Validación de campos
          │       └── Fallo → redirect back con $errors
          │
          └── DB::transaction
                  │
                  ├── INSERT ventas (estado='confirmada')
                  │
                  └── Por cada ítem:
                          ├── Producto::findOrFail → precio_venta real
                          ├── $esAdmin ? descuento : 0
                          ├── SELECT lotes WHERE producto_id + stock>0
                          │   ORDER BY vencimiento ASC (FIFO) FOR UPDATE
                          ├── ¿Stock total < cantidad? → abort(422)
                          │
                          └── Por cada lote (FIFO hasta agotar cantidad):
                                  ├── INSERT detalles_venta (lote_id, cantidad, precio)
                                  ├── UPDATE lotes SET stock = stock - tomar
                                  └── INSERT movimientos_stock (tipo=Salida, motivo=Venta)
                  │
                  ├── (intento) INSERT recibos → falla silenciosamente
                  │
                  └── redirect ventas.index + flash success
```

---

## Efecto Neto en la Base de Datos

Tras registrar una venta con N productos que consumen M lotes en total:

| Tabla | Registros creados/modificados |
|---|---|
| `ventas` | 1 nuevo registro |
| `detalles_venta` | M registros (uno por cada lote consumido, puede ser más que N) |
| `lotes` | M actualizaciones de stock (decrementos) |
| `movimientos_stock` | M registros de tipo `Salida` |

---

## Consideraciones de Integridad

- **Precio inmutable**: el precio se lee de la BD en el momento de la venta y se guarda en `detalles_venta.precio_unitario`. Si el precio del producto cambia después, el precio histórico de la venta queda preservado.
- **Descuento controlado**: la lógica de descuento se aplica en el servidor, no en el cliente. Aunque un atacante manipule el campo `descuento` en el formulario, el servidor lo fuerza a `0` para no-administradores.
- **FIFO garantizado**: el ordenamiento por `fecha_vencimiento ASC` asegura que siempre se despachan primero los lotes más próximos a vencer, reduciendo las pérdidas por vencimiento.
- **Sin anulación**: no existe mecanismo para anular una venta ya registrada. El permiso `ventas.anular` está definido en el seeder pero no tiene ruta ni lógica asociada.
- **Lotes vencidos excluidos (corregido 2026-07-07):** tanto la consulta FIFO de `store()` como el listado de stock disponible de `create()` usan el scope `Lote::vigentes()` (`app/Models/Lote.php`), que excluye cualquier lote con `fecha_vencimiento < hoy`. Un lote vencido nunca puede ser vendido, independientemente de su stock. Ver `docs/arquitectura.md` § Control de Vencimientos.
