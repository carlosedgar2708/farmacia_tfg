# Flujo de una Compra — Farmacia Katy

**Trazabilidad con la tesis:** corresponde a `CU09` "Registrar compras" (`RF08` Registro de Compras), memoria de tesis §3.5.4. La excepción "Falta de permisos" de esa especificación sí es reproducible acá: `POST /compras` exige `permiso:compras.crear` (a diferencia de Ventas, ver nota en `docs/flujo_venta.md`). Ver `docs/requisitos.md` para el catálogo completo de RF/CU.

## Descripción General

El módulo de compras permite registrar la adquisición de mercancía a un proveedor. Cada compra puede contener múltiples productos, y cada producto se ingresa especificando su número de lote farmacéutico, fecha de vencimiento, costo unitario y cantidad. El sistema crea o actualiza los lotes correspondientes, incrementa el stock y deja registro en el historial de movimientos.

---

## Actores y Requisitos de Acceso

| Actor | Requisito |
|---|---|
| Usuario autenticado | Middleware `auth` |
| Ver listado de compras | Permiso `compras.ver` |
| Registrar una compra | Permiso `compras.crear` |
| Administrador | Bypass automático (rol `admin`) |

---

## Paso 1 — Acceso al Formulario

**Ruta:** `GET /compras/create`
**Controlador:** `CompraController::create()`
**Vista:** `resources/views/compras/create.blade.php`

El controlador carga:
- Lista de todos los proveedores (`id`, `nombre`), ordenados por nombre.
- Lista de todos los productos (`id`, `codigo`, `nombre`), ordenados por nombre.

Ambas listas se pasan a la vista en dos formatos:
1. Como colecciones Blade (`$proveedores`, `$productos`) para los selects HTML tradicionales.
2. Como arrays JSON serializados (`$productosForJs`, `$proveedoresForJs`) disponibles como variables JavaScript en la vista para los autocompletes dinámicos.

---

## Paso 2 — Construcción del Formulario (Frontend)

La vista presenta dos paneles:

**Panel izquierdo — Tabla de ítems:**
- Campo de búsqueda de producto (autocomplete sobre el array JS `PRODUCTOS`).
- Al seleccionar un producto, se habilitan los campos: número de lote, fecha de vencimiento, costo unitario (o costo total con cálculo automático), cantidad.
- Botón "Agregar ítem" que inserta una fila en la tabla resumen.
- Cada fila muestra: producto, nro_lote, vencimiento, costo, cantidad, subtotal y botón de eliminar.

**Panel derecho — Datos generales:**
- Búsqueda de proveedor (autocomplete sobre el array JS `PROVEEDORES`).
- Campo de observaciones (opcional).
- Botón "Confirmar Compra".

**Creación rápida desde el formulario:**
- Si el producto no existe, hay un modal para crear uno nuevo vía AJAX (`POST /productos` con cabecera `Accept: application/json`). El controlador devuelve `{id, nombre}` en JSON y el frontend lo agrega automáticamente al array.
- Si el proveedor no existe, hay un modal equivalente (`POST /proveedors`).

**Construcción del payload al enviar:**
JavaScript genera campos hidden con la estructura `items[i][campo]` para cada fila de la tabla antes de hacer el submit tradicional del formulario.

---

## Paso 3 — Validación del Servidor

**Ruta:** `POST /compras`
**Controlador:** `CompraController::store(Request $request)`

```
proveedor_id       → requerido, entero, debe existir en tabla proveedors
observacion        → opcional, string, máximo 500 caracteres
items              → requerido, array, mínimo 1 elemento
items.*.producto_id    → requerido, entero, debe existir en tabla productos
items.*.nro_lote       → requerido, string, máximo 100 caracteres
items.*.fecha_vencimiento → opcional, debe ser una fecha válida
items.*.costo_unitario → requerido, numérico, mínimo 0
items.*.cantidad       → requerido, entero, mínimo 1
```

Si la validación falla, Laravel redirige de vuelta al formulario con los errores en `$errors` y los valores anteriores en `old()`.

---

## Paso 4 — Transacción de Base de Datos

Todo el proceso de guardado ocurre dentro de `DB::transaction()`. Si cualquier operación falla, se hace rollback completo y no queda ningún registro parcial.

### 4.1 — Creación del Registro de Compra

```sql
INSERT INTO compras (fecha, proveedor_id, user_id, created_at, updated_at)
VALUES (NOW(), ?, ?, NOW(), NOW())
```

Se obtiene el `id` de la compra recién creada para usarlo en los detalles.

### 4.2 — Procesamiento de cada Ítem

Para cada elemento del array `items`:

**a) Validación de fecha de vencimiento:**

Si el campo `fecha_vencimiento` viene informado y es una fecha en el pasado, se aborta con código HTTP 422:

```
abort(422, "El lote {nro_lote} está vencido. No puedes ingresarlo.")
```

Esto previene el ingreso de mercancía ya expirada al inventario.

**b) Búsqueda o creación del lote:**

Se busca en la tabla `lotes` un registro con `producto_id` y `nro_lote` coincidentes, usando `lockForUpdate()` para prevenir condiciones de carrera en entornos concurrentes:

```sql
SELECT * FROM lotes
WHERE producto_id = ? AND nro_lote = ?
FOR UPDATE
```

**Si el lote NO existe:** Se crea un nuevo lote con `stock = 0`:
```sql
INSERT INTO lotes (producto_id, nro_lote, fecha_vencimiento, costo_unitario, stock, ...)
VALUES (?, ?, ?, ?, 0, ...)
```

**Si el lote YA existe:** Se actualiza su costo unitario con el más reciente y, si se informó, su fecha de vencimiento. El stock no se toca aquí, se incrementa en el paso siguiente.

**c) Registro del detalle de compra:**

```sql
INSERT INTO detalles_compra (compra_id, lote_id, cantidad, costo_unitario, ...)
VALUES (?, ?, ?, ?, ...)
```

**d) Incremento de stock:**

```sql
UPDATE lotes SET stock = stock + ? WHERE id = ?
```

Esto es atómico gracias al `lockForUpdate` previo. El stock del lote aumenta por la cantidad comprada.

**e) Registro del movimiento de stock:**

```sql
INSERT INTO movimientos_stock (lote_id, fecha, tipo, motivo, cantidad, referencia, ...)
VALUES (?, NOW(), 'Entrada', 'Compra', ?, 'Compra #N', ...)
```

Este registro queda como auditoría permanente del movimiento.

---

## Paso 5 — Respuesta al Usuario

Si la transacción se completa sin errores:

```php
redirect()->route('compras.index')->with('success', 'Compra registrada correctamente.')
```

El usuario es redirigido al listado de compras y ve el mensaje de confirmación en el flash de la vista `app.blade.php`.

---

## Diagrama del Flujo

```
Usuario
  │
  ├── GET /compras/create
  │       └── Carga proveedores + productos → Vista con autocomplete JS
  │
  ├── (opcional) POST /productos [AJAX] → Crear producto al vuelo → JSON {id, nombre}
  ├── (opcional) POST /proveedors [AJAX] → Crear proveedor al vuelo → JSON {id, nombre}
  │
  └── POST /compras
          │
          ├── Validación de campos
          │       └── Fallo → redirect back con $errors
          │
          └── DB::transaction
                  │
                  ├── INSERT compras
                  │
                  └── Por cada ítem:
                          ├── ¿Lote vencido? → abort(422)
                          ├── SELECT lotes WHERE producto_id + nro_lote FOR UPDATE
                          ├── ¿No existe? → INSERT lotes (stock=0)
                          │   ¿Existe?   → UPDATE lotes (costo, vencimiento)
                          ├── INSERT detalles_compra
                          ├── UPDATE lotes SET stock = stock + cantidad
                          └── INSERT movimientos_stock (tipo=Entrada, motivo=Compra)
                  │
                  └── redirect compras.index + flash success
```

---

## Efecto Neto en la Base de Datos

Tras registrar una compra con N ítems:

| Tabla | Registros creados/modificados |
|---|---|
| `compras` | 1 nuevo registro |
| `detalles_compra` | N registros (uno por ítem) |
| `lotes` | N registros creados o actualizados |
| `movimientos_stock` | N registros de tipo `Entrada` |

El stock total de cada producto afectado aumenta por la suma de las cantidades compradas de todos sus lotes.

---

## Consideraciones de Integridad

- **Transacción atómica**: si falla cualquier operación (ej: constraint de BD, lote vencido detectado a mitad del proceso), todo el conjunto se revierte. No hay compras parcialmente registradas.
- **Concurrencia**: el uso de `lockForUpdate` garantiza que dos solicitudes simultáneas sobre el mismo lote no resulten en stock incorrecto.
- **Lotes repetidos**: si se compra el mismo número de lote para el mismo producto (re-abastecimiento), el sistema actualiza los datos del lote existente y suma al stock en lugar de crear un duplicado.
- **Sin anulación**: el sistema no tiene implementada la anulación de compras. El permiso `compras.anular` está definido en el seeder pero no existe ninguna ruta ni lógica asociada.
