# Base de Datos — Farmacia Katy

## Resumen

- **Motor**: MySQL (XAMPP)
- **Nombre de la base de datos**: `farmacia_tfg`
- **ORM**: Eloquent (Laravel)
- **Soft Deletes**: habilitado en la mayoría de tablas (columna `deleted_at`)
- **Timestamps**: todas las tablas tienen `created_at` y `updated_at`

---

## Diagrama de Relaciones

```
users ──────────────────── rol_user ──────────── rols
  │                                                │
  │                                           permiso_rol ───── permisos
  │
  ├── ventas ──────────── detalles_venta ──────── lotes ──── productos
  │       │
  │       └── recibos
  │
  └── compras ──────────── detalles_compra ─────── lotes
           │
           └── (proveedor_id) ─── proveedors

clientes ──── ventas (cliente_id nullable)

devolucions ──── detalles_devolucion ──── lotes
     │
     └── (user_id) ─── users

lotes ──── movimientos_stock

configuraciones (tabla independiente, sin relaciones — clave/valor)
```

---

## Tablas

### `users`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `username` | varchar, unique | Generado como slug del email si no se indica |
| `name` | varchar | Nombre |
| `apellido` | varchar | |
| `email` | varchar, unique | |
| `telefono` | varchar, nullable | |
| `activo` | boolean, default true | |
| `email_verified_at` | timestamp, nullable | |
| `password` | varchar (hashed) | |
| `remember_token` | varchar, nullable | |
| `two_factor_secret` | text, nullable | Para 2FA (no implementado en UI) |
| `two_factor_recovery_codes` | text, nullable | Para 2FA |
| `two_factor_confirmed_at` | timestamp, nullable | Para 2FA |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Relaciones:**
- M:N con `rols` a través de `rol_user`
- 1:N con `ventas` (usuario que registra la venta)
- 1:N con `compras` (usuario que registra la compra)
- 1:N con `devolucions`

---

### `rols`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `nombre` | varchar, unique | Ej: `Administrador`, `Vendedor` |
| `slug` | varchar, unique | Ej: `admin`, `vendedor` |
| `descripcion` | text, nullable | |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Migración lo tiene; el modelo NO usa `SoftDeletes` |

**Relaciones:**
- M:N con `users` a través de `rol_user`
- M:N con `permisos` a través de `permiso_rol`

**Roles predefinidos por seeder:**

| slug | nombre | Permisos |
|---|---|---|
| `admin` | Administrador | Todos los permisos |
| `vendedor` | Vendedor | `ventas.ver`, `ventas.crear`, `productos.ver`, `devoluciones.registrar` |

---

### `permisos`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `nombre` | varchar, unique | Ej: `Ver ventas` |
| `slug` | varchar, unique | Ej: `ventas.ver` |
| `descripcion` | text, nullable | |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Migración lo tiene; el modelo NO usa `SoftDeletes` |

**Permisos predefinidos por seeder (29 en total):**

| Módulo | Slugs |
|---|---|
| Usuarios | `usuarios.ver`, `usuarios.crear`, `usuarios.editar`, `usuarios.eliminar` |
| Roles | `rols.ver`, `rols.crear`, `rols.editar`, `rols.eliminar` |
| Proveedores | `proveedors.ver`, `proveedors.crear`, `proveedors.editar`, `proveedors.eliminar` (el slug real usa `proveedors`, sin `e` — ver nota más abajo) |
| Clientes | `clientes.ver`, `clientes.crear`, `clientes.editar`, `clientes.eliminar` — agregados el 2026-07-28 (`BUG-13`): las rutas ya los exigían desde siempre, pero nunca se habían sembrado. |
| Productos/Stock | `productos.ver`, `productos.crear`, `productos.editar`, `productos.eliminar`, `productos.stock` |
| Compras | `compras.ver`, `compras.crear`, `compras.anular` |
| Ventas | `ventas.ver`, `ventas.crear`, `ventas.anular` |
| Devoluciones | `devoluciones.registrar` |
| Reportes | `reportes.ver` |

---

### `rol_user` (pivot)

| Columna | Tipo | Notas |
|---|---|---|
| `rol_id` | bigint, FK → rols | |
| `user_id` | bigint, FK → users | |
| `created_at`, `updated_at` | timestamps | |

Restricción UNIQUE sobre `(rol_id, user_id)`.

---

### `permiso_rol` (pivot)

| Columna | Tipo | Notas |
|---|---|---|
| `permiso_id` | bigint, FK → permisos | |
| `rol_id` | bigint, FK → rols | |
| `created_at`, `updated_at` | timestamps | |

Restricción UNIQUE sobre `(permiso_id, rol_id)`.

---

### `productos`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `codigo` | varchar(50), unique | Código interno del producto |
| `nombre` | varchar | |
| `es_inyectable` | boolean, default false | Indica si requiere manejo especial |
| `description` | text, nullable | |
| `precio_venta` | decimal(10,2), default 0 | Precio al público |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Importante:** El stock NO se almacena en esta tabla. Se obtiene sumando `lotes.stock`.

**Relaciones:**
- 1:N con `lotes`

---

### `lotes`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `producto_id` | bigint, FK → productos | |
| `nro_lote` | varchar(100) | Número de lote farmacéutico |
| `fecha_vencimiento` | date, nullable | Fecha de expiración |
| `costo_unitario` | decimal(12,2) | Costo de adquisición por unidad |
| `stock` | integer, default 0 | **Fuente de verdad del stock disponible** |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

Restricción UNIQUE sobre `(producto_id, nro_lote)`.

**Relaciones:**
- N:1 con `productos`
- 1:N con `detalles_venta`
- 1:N con `detalles_compra`
- 1:N con `movimientos_stock`
- 1:N con `detalles_devolucion`

---

### `proveedors`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `nombre` | varchar, unique | |
| `contacto` | varchar, nullable | Nombre de persona de contacto |
| `telefono` | varchar, nullable | |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Relaciones:**
- 1:N con `compras`

---

### `clientes`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `nombre` | varchar | |
| `documento` | varchar, nullable | DNI u otro documento |
| `telefono` | varchar, nullable | |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Relaciones:**
- 1:N con `ventas` (nullable: una venta puede no tener cliente asignado)

---

### `ventas`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `fecha_venta` | datetime | Fecha y hora de la venta |
| `user_id` | bigint, FK → users | Empleado que registró la venta |
| `cliente_id` | bigint, nullable, FK → clientes | Cliente (puede ser venta sin identificar) |
| `observacion` | text, nullable | |
| `estado` | varchar, default `confirmada` | Estado de la venta |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Relaciones:**
- N:1 con `clientes`
- N:1 con `users`
- 1:N con `detalles_venta`
- 1:1 con `recibos`

**Nota:** El total de la venta no se guarda en esta tabla. Se calcula sumando `detalles_venta.cantidad * detalles_venta.precio_unitario`.

---

### `detalles_venta`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `venta_id` | bigint, FK → ventas (cascade delete) | |
| `producto_id` | bigint, FK → productos | |
| `lote_id` | bigint, FK → lotes | Lote específico del que salió el stock |
| `cantidad` | integer | Unidades vendidas de este lote |
| `precio_unitario` | decimal(12,2) | Precio al momento de la venta |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Importante:** Una sola línea de venta (un producto) puede generar múltiples registros en esta tabla si el stock se repartió entre varios lotes (lógica FIFO).

---

### `compras`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `fecha` | datetime | Fecha y hora de la compra |
| `proveedor_id` | bigint, FK → proveedors | |
| `user_id` | bigint, FK → users | Empleado que registró la compra |
| `created_at`, `updated_at` | timestamps | |
| `deleted_at` | timestamp, nullable | Soft delete |

**Nota:** Los campos `observacion` y `estado` están en el `fillable` del modelo pero **no existen en la migración**. Intentar guardarlos causaría error.

---

### `detalles_compra`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `compra_id` | bigint, FK → compras (cascade delete) | |
| `lote_id` | bigint, FK → lotes | Lote creado o actualizado |
| `cantidad` | integer | Unidades adquiridas |
| `costo_unitario` | decimal(12,2) | Costo en esta compra |
| `created_at`, `updated_at` | timestamps | **Sin `deleted_at`** — no usa soft delete |

---

### `movimientos_stock`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `lote_id` | bigint, FK → lotes | |
| `fecha` | datetime | Fecha del movimiento |
| `tipo` | enum(`Entrada`, `Salida`) | |
| `motivo` | enum(`Compra`, `Venta`, `Devolucion`, `Ajuste`) | |
| `cantidad` | integer | Siempre positivo (ver nota) |
| `referencia` | varchar, nullable | Texto libre, ej: `Venta #5` |
| `created_at`, `updated_at` | timestamps | **Sin `deleted_at`** en la migración |

**Nota importante:** El modelo usa `SoftDeletes` pero la migración no tiene columna `deleted_at`. Intentar un soft delete causaría error SQL. Además, el campo `cantidad` se almacena siempre como positivo, tanto para entradas como para salidas. Los `scopes` `scopeSalidas()` (filtra `cantidad < 0`) nunca devuelven resultados con el código actual.

**Rol:** Esta tabla es exclusivamente un **log de auditoría**. El stock real lo determina `lotes.stock`, no esta tabla.

---

### `recibos`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `venta_id` | bigint, unique, FK → ventas (cascade delete) | |
| `monto` | decimal(10,2), default 0 | Agregada en `add_monto_to_recibos_table` (2026-07-27, `PEND-01`) |
| `created_at`, `updated_at` | timestamps | |

**Resuelto (2026-07-27):** el modelo tenía `fillable` con `nro_recibo`, `fecha`, `metodo_pago`, `observacion`, `estado`, ninguno existente en la migración, y además usaba `SoftDeletes` sin columna `deleted_at` (mismo bug que `movimientos_stock`, ver más abajo) — cualquier lectura del modelo lanzaba una excepción SQL real. Se quitó `SoftDeletes`, se redujo `fillable` a `venta_id`/`monto` (los únicos campos reales, este último ya usado por `VentaController::store()`), y se agregó la columna faltante. Detalle completo en `docs/pendientes.md` (`PEND-01`, `ESQ-01`).

---

### `devolucions`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `fecha` | datetime | |
| `user_id` | bigint, FK → users | |
| `motivo` | text, nullable | |
| `created_at`, `updated_at` | timestamps | |

**Desajuste crítico:** El modelo declara en `fillable` los campos `venta_id`, `cliente_id`, `fecha_devolucion`, `observacion`, `estado`, ninguno de los cuales existe en la migración real.

---

### `detalles_devolucion`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `devolucion_id` | bigint, FK → devolucions (cascade delete) | |
| `lote_id` | bigint, FK → lotes | |
| `cantidad` | integer | |
| `created_at`, `updated_at` | timestamps | |

**Desajuste crítico:** El modelo declara en `fillable` los campos `producto_id`, `precio_unitario` y `razon`, ninguno de los cuales existe en la migración.

---

### `configuraciones`

> **Estado:** Implementado (2026-07-07). Ver `docs/arquitectura.md` § Sistema de Configuración.

Tabla genérica de tipo clave/valor para ajustes del sistema editables por el administrador en tiempo de ejecución.

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint, PK | |
| `clave` | varchar, unique | Identificador del ajuste, ej. `dias_alerta_vencimiento` |
| `valor` | text, nullable | Almacenado como string; se castea en el consumidor (`Configuracion::obtener()`) |
| `descripcion` | text, nullable | Explicación legible del ajuste, para mostrar en la UI de edición |
| `created_at`, `updated_at` | timestamps | |

**Sin relaciones** — es una tabla independiente, no referenciada por FK desde ninguna otra tabla.

**Acceso:** exclusivamente a través de `Configuracion::obtener($clave, $default)` y `Configuracion::establecer($clave, $valor)` (`app/Models/Configuracion.php`). Ningún controlador debe consultarla directamente.

**Valores sembrados (`database/seeders/ConfiguracionSeeder.php`):**

| Clave | Valor por defecto | Uso |
|---|---|---|
| `dias_alerta_vencimiento` | `90` | Umbral de días para la alerta de "lotes próximos a vencer" en el dashboard (`InicioController`) y en futuras validaciones |

Preparada para futuros ajustes (`stock_minimo_alerta`, `moneda`, `iva_porcentaje`, etc.) sin necesidad de nuevas migraciones.

---

## Desajustes Modelo / Migración

La siguiente tabla resume los casos donde el modelo Eloquent y la migración de BD están desincronizados:

| Modelo | Problema |
|---|---|
| `Rol` | Migración tiene `deleted_at`; el modelo NO usa el trait `SoftDeletes` |
| `Permiso` | Migración tiene `deleted_at`; el modelo NO usa el trait `SoftDeletes` |
| `MovimientoStock` | El modelo usa `SoftDeletes` pero la migración NO tiene `deleted_at` |
| ~~`Recibo`~~ | ~~`fillable` referencia 5 campos que no existen en la tabla~~ ✅ Corregido (2026-07-27), ver `PEND-01` |
| `Devolucion` | `fillable` referencia 5 campos que no existen en la tabla |
| `DetalleDevolucion` | `fillable` referencia 3 campos que no existen en la tabla |
| `Compra` | `fillable` incluye `observacion` y `estado` que no están en la migración |
| `Cliente` | Cast de `activo` a boolean, pero la columna no existe en la migración |

---

## Orden de Seeding

El seeder principal (`DatabaseSeeder`) ejecuta los seeders en el siguiente orden para respetar las dependencias de claves foráneas:

```
PermisoSeeder   →   crea los 24 permisos base
RolSeeder       →   crea roles y asigna permisos con sync()
UserSeeder      →   crea usuarios de prueba y asigna roles
ProductoSeeder  →   crea productos de ejemplo
LoteSeeder      →   crea lotes iniciales para los productos
```

`ProveedorSeeder` existe pero está comentado en `DatabaseSeeder::run()`.

---

## Notas sobre Integridad

- El stock real de un producto es `SUM(lotes.stock)` donde `lotes.producto_id = X AND lotes.deleted_at IS NULL`.
- Las transacciones de compra y venta usan `DB::transaction()` con `lockForUpdate()` para prevenir condiciones de carrera.
- Los lotes soft-deleted pierden su stock del total del producto, aunque físicamente el medicamento podría seguir existiendo. No hay mecanismo para manejar esta situación.
- ~~Un lote vencido (`fecha_vencimiento < hoy`) con `stock > 0` puede ser vendido~~ — **Corregido (2026-07-07):** `VentaController::store()` y `create()` usan el scope `Lote::vigentes()`, que excluye lotes vencidos de la consulta FIFO y del cálculo de stock disponible. Ver `docs/arquitectura.md` § Control de Vencimientos.
