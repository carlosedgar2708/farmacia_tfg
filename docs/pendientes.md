# Pendientes y Trabajo Futuro — Farmacia Katy

Este documento categoriza los problemas encontrados en el código y las funcionalidades que faltan por implementar. Se ordenan por impacto sobre el funcionamiento del sistema.

---

## Bugs Confirmados (el sistema falla o se comporta incorrectamente)

### BUG-01 — La asignación de permisos a roles desde la UI no funciona ✅ CORREGIDO (2026-07-07)

**Módulo:** Roles
**Archivo:** `app/Http/Controllers/RolController.php`

`RolController::store()` y `RolController::update()` no llaman a `$rol->permisos()->sync()`. La vista de roles muestra checkboxes `name="permisos[]"` que el usuario selecciona, pero el controlador los ignora completamente al guardar.

**Efecto:** Cualquier configuración de permisos hecha desde la pantalla de roles se pierde. Los permisos solo quedan asignados correctamente ejecutando el seeder.

**Corrección requerida:** Agregar en `store()` y `update()`:
```php
$rol->permisos()->sync($request->input('permisos', []));
```

---

### BUG-02 — No se puede editar el precio de venta de un producto

**Módulo:** Productos
**Archivo:** `app/Http/Controllers/ProductoController.php`

`ProductoController::update()` no incluye `precio_venta` en sus reglas de validación ni en los campos que actualiza. La vista tampoco tiene este campo en el modal de edición.

**Efecto:** Una vez creado un producto, su precio de venta no puede modificarse desde la interfaz.

**Corrección requerida:** Agregar `precio_venta` a la validación del `update()` y al formulario modal.

---

### BUG-03 — La ruta `GET /lotes` falla en tiempo de ejecución ✅ CORREGIDO (2026-07-07)

**Módulo:** Lotes
**Archivo:** `routes/web.php`, `app/Http/Controllers/LoteController.php`

`web.php` registra `Route::resource('lotes', LoteController::class)` generando la ruta `GET /lotes`. Sin embargo, `LoteController::index()` tiene la firma `index(Request $request, Producto $producto)`, que requiere route model binding con `{producto}`. Como la ruta `/lotes` no tiene ese segmento en la URL, Laravel lanza una excepción al acceder.

**Efecto:** Acceder a `/lotes` produce un error 500.

**Corrección requerida:** Eliminar el `Route::resource('lotes', ...)` o crear un método `index` alternativo que no requiera el modelo.

---

### BUG-04 — Las ventas pueden despachar lotes vencidos ✅ CORREGIDO (2026-07-07)

**Módulo:** Ventas
**Archivo:** `app/Http/Controllers/VentaController.php` (método `store`)

La consulta FIFO en `VentaController::store()` filtra lotes solo por `stock > 0`. No verifica que `fecha_vencimiento >= hoy`. Un lote con stock positivo pero fecha de vencimiento pasada puede ser seleccionado y despachado.

**Efecto:** El sistema puede vender medicamentos expirados sin ninguna advertencia.

**Corrección requerida:** Agregar a la consulta:
```php
->where(function($q) {
    $q->whereNull('fecha_vencimiento')
      ->orWhereDate('fecha_vencimiento', '>=', today());
})
```

**Corregido:** se implementó como el scope `Lote::vigentes()` (`app/Models/Lote.php`), aplicado en `VentaController::store()` (consulta FIFO) y `VentaController::create()` (stock disponible mostrado en el formulario). Además se construyó, como parte del mismo trabajo, un sistema de control de vencimientos más amplio:

- **Tabla `configuraciones`** (clave/valor, columna `valor` tipo `TEXT`) — ver `docs/base_de_datos.md`.
- **Modelo `Configuracion`** con `obtener()`/`establecer()` (único punto de lectura/escritura, cacheado).
- **Scopes en `Lote`:** `vigentes()`, `vencidos()`, `proximosAVencer($dias)` — lógica de fechas centralizada, sin duplicación entre controladores.
- **`InicioController`** usa `Configuracion::obtener('dias_alerta_vencimiento', 90)` en vez del umbral hardcodeado (`addMonths(4)`) para el widget "Próximos a vencer" del dashboard.
- **`ConfiguracionController`** (`GET/PUT /configuracion`) permite al administrador (`esAdmin()`) editar `dias_alerta_vencimiento` desde una vista nueva (`resources/views/configuracion/edit.blade.php`), con enlace en el sidebar visible solo para admins.
- Detalle completo del diseño en `docs/arquitectura.md` § Sistema de Configuración y § Control de Vencimientos.

**Ajuste posterior (2026-07-07):** el widget "Próximos a vencer" del dashboard operaba a nivel de **lote**, sin agrupar por producto, lo que permitía que un producto con un lote vencido y otro vigente se mostrara con el estado del lote vigente (ignorando el vencido). Se corrigió agregando `Producto::scopeConAlertaVencimiento()`, `loteVencidoRelevante()` y `loteProximoRelevante()` (`app/Models/Producto.php`), que componen el estado por producto con prioridad **vencido > próximo > ok**, reutilizando exclusivamente los scopes de `Lote` (sin duplicar comparaciones de fecha). `InicioController` y `inicio.blade.php` se actualizaron para trabajar con productos en vez de lotes sueltos, y el badge ahora distingue `VENCIDO` de `Vence pronto`.

**Verificación automatizada (2026-07-10):** se agregó `tests/Feature/DashboardVencimientoTest.php` (Pest) como prueba de regresión permanente de las 8 reglas de negocio del widget: deduplicación por producto, prioridad vencido > próximo, exclusión de lotes vencidos sin stock, exclusión de productos sin alerta, y actualización inmediata al cambiar `dias_alerta_vencimiento`. Corre contra SQLite en memoria (`RefreshDatabase`), sin tocar la base de datos real. Detalle de la sesión en `docs/diario_desarrollo.md` (entrada 2026-07-10).

---

### BUG-05 — El registro público de usuarios probablemente falla ✅ CORREGIDO (2026-07-07)

**Módulo:** Autenticación
**Archivo:** `app/Actions/Fortify/CreateNewUser.php`

La acción de creación de usuarios de Fortify no maneja el campo `username`, que en la tabla `users` tiene una restricción `unique` y puede ser `NOT NULL`. Intentar registrar un usuario desde el formulario público puede resultar en un error de BD.

**Efecto:** El registro público (`/register`) podría no funcionar para nuevos usuarios.

**Corrección requerida:** Agregar generación de `username` (como slug del email o como campo del formulario) en `CreateNewUser.php`.

---

### BUG-06 — `MovimientoStock::scopeSalidas()` nunca devuelve resultados

**Módulo:** Movimientos de Stock
**Archivo:** `app/Models/MovimientoStock.php`

El modelo define `scopeSalidas()` filtrando `cantidad < 0`, pero los movimientos de salida (ventas) se crean con `cantidad` siempre positiva en `VentaController::store()`. De igual forma, `scopeEntradas()` filtra `cantidad > 0`, lo que hace que ambos scopes devuelvan los mismos datos.

**Efecto:** Aunque los movimientos se guardan correctamente, no pueden consultarse de forma diferenciada por tipo usando los scopes del modelo.

**Corrección requerida:** Cambiar la forma en que se guardan las salidas (cantidad negativa) o corregir los scopes para filtrar por el campo `tipo` en lugar de por el signo de `cantidad`.

---

### BUG-07 — Rutas de permisos sin protección de autenticación

**Módulo:** Permisos
**Archivo:** `routes/web.php`

Las rutas bajo el prefijo `/permiso` están registradas fuera del grupo `middleware('auth')`. Cualquier petición HTTP sin sesión activa puede acceder a ellas.

**Efecto:** Aunque los métodos del controlador están vacíos actualmente, si se implementan, cualquier usuario no autenticado podría manipular los permisos del sistema.

**Corrección requerida:** Mover el grupo de rutas `/permiso` dentro del grupo `middleware('auth')`.

---

## Desajustes Modelo / Migración (inconsistencias de esquema)

Estos no son bugs activos (no causan errores ahora mismo) pero causarán errores en cuanto se intente usar los campos afectados.

| ID | Modelo | Problema |
|---|---|---|
| ESQ-01 | `Recibo` | `fillable` incluye `nro_recibo`, `fecha`, `metodo_pago`, `observacion`, `estado`. Ninguno existe en la migración. |
| ESQ-02 | `Devolucion` | `fillable` incluye `venta_id`, `cliente_id`, `fecha_devolucion`, `observacion`, `estado`. Ninguno existe en la migración. |
| ESQ-03 | `DetalleDevolucion` | `fillable` incluye `producto_id`, `precio_unitario`, `razon`. Ninguno existe en la migración. |
| ESQ-04 | `Compra` | `fillable` incluye `observacion` y `estado`. Ninguno existe en la migración. |
| ESQ-05 | `MovimientoStock` | El modelo usa `SoftDeletes` pero la migración no tiene columna `deleted_at`. |
| ESQ-06 | `Rol` | La migración tiene `deleted_at`, pero el modelo no usa el trait `SoftDeletes`. |
| ESQ-07 | `Permiso` | La migración tiene `deleted_at`, pero el modelo no usa el trait `SoftDeletes`. |
| ESQ-08 | `Cliente` | El modelo castea el campo `activo` a boolean, pero esa columna no existe en la migración. |

---

## Funcionalidades Incompletas (el módulo existe pero no está terminado)

### PEND-01 — Recibos

**Estado:** Solo el modelo y la migración existen. El controlador está vacío.

**Trabajo pendiente:**
- Agregar los campos faltantes a la migración (`nro_recibo`, `fecha`, `metodo_pago`, `observacion`, `estado`) o rediseñar el modelo para que coincida con la migración actual.
- Implementar `ReciboController` con al menos `show()` (ver recibo de una venta) y opcionalmente una vista de impresión.
- Corregir la creación del recibo en `VentaController::store()` para que persista los datos correctamente.
- Registrar las rutas.

---

### PEND-02 — Devoluciones

**Estado:** Modelos y migraciones existen pero con desajuste grave. Controlador vacío. Sin rutas ni vistas.

**Trabajo pendiente:**
- Corregir las migraciones de `devolucions` y `detalles_devolucion` para agregar los campos que el modelo espera (`venta_id`, `cliente_id`, `fecha_devolucion`, `observacion`, `estado`, `producto_id`, `precio_unitario`, `razon`), o redefinir los modelos para que coincidan con la BD actual.
- Implementar `DevolucionController` completo: `index()`, `create()`, `store()`.
- La lógica de `store()` debe: vincular la devolución a una venta existente, iterar los productos devueltos, reintegrar el stock al lote original (`$lote->increment('stock', $cantidad)`), crear un `MovimientoStock` con `tipo='Entrada'` y `motivo='Devolucion'`.
- Crear las vistas (listado y formulario de registro).
- Registrar las rutas protegidas con el permiso `devoluciones.registrar`.

---

### PEND-03 — Anulación de Ventas

**Estado:** El campo `estado` existe en `ventas` y el permiso `ventas.anular` está definido, pero no hay ninguna acción implementada.

**Trabajo pendiente:**
- Implementar `VentaController::destroy()` o un método específico `anular()`.
- La lógica debe: cambiar `ventas.estado` a `'anulada'`, recuperar todos los `DetalleVenta`, reintegrar el stock de cada lote (`$lote->increment('stock', $detalle->cantidad)`), crear `MovimientoStock` inverso por cada lote.
- Agregar confirmación en la UI antes de anular.
- Proteger la ruta con el middleware `permiso:ventas.anular`.

---

### PEND-04 — Anulación de Compras

**Estado:** Similar a ventas. El permiso `compras.anular` está seedeado, el campo `estado` está en el `fillable` del modelo pero no en la migración.

**Trabajo pendiente:**
- Agregar la columna `estado` a la migración de `compras`.
- Implementar lógica de anulación: reducir el stock de los lotes afectados, crear movimientos de stock inversos.

---

### PEND-05 — Dashboard con Datos Reales

**Estado:** La vista existe, el controlador calcula algunos datos, pero el módulo es básico.

**Trabajo pendiente:**
- El `DashboardController` (actualmente sin rutas) tiene lógica más completa que `InicioController`. Considerar conectarlo o migrar su código al controlador activo.
- Agregar filtros por rango de fechas.
- Agregar alertas de stock bajo con umbral configurable.
- Mostrar lotes próximos a vencer con columores de alerta.

---

### PEND-06 — Ajustes de Stock sin Auditoría

**Estado:** `LoteController::bulkUpdate()` modifica el campo `stock` directamente sin crear `MovimientoStock`.

**Trabajo pendiente:**
- Agregar creación de `MovimientoStock` con `tipo='Entrada'` o `tipo='Salida'` y `motivo='Ajuste'` en `bulkUpdate()`.
- Esto permite tener el historial completo de todos los cambios de inventario.

---

## Módulos Completamente Ausentes (no existe nada)

### AUS-01 — Reportes

El seeder define el permiso `reportes.ver` pero no existe ningún controlador, ruta ni vista de reportes.

**Reportes mínimos esperados para una farmacia:**
- Ventas por período (diario, mensual, anual) con filtro por usuario y cliente.
- Compras por período con filtro por proveedor.
- Stock valorizado (stock actual × costo unitario por lote).
- Productos con stock bajo (por debajo de un umbral).
- Lotes próximos a vencer (por rango de fechas).
- Historial de movimientos de stock por producto o lote.

---

### AUS-02 — Vista de Movimientos de Stock

La tabla `movimientos_stock` se llena correctamente para todas las compras y ventas, pero no hay ninguna pantalla en el sistema que permita consultarla.

**Trabajo pendiente:**
- Crear una vista que muestre el historial de movimientos con filtros por lote, producto, tipo (Entrada/Salida), fecha.
- Crear una ruta protegida con un permiso apropiado.
- Corregir previamente el bug BUG-06 (scopes de salidas/entradas).

---

### AUS-03 — Gestión de Permisos desde UI

El `PermisoController` existe pero está vacío. No hay interfaz para crear o editar permisos.

**Trabajo pendiente:**
- Implementar los métodos del controlador.
- Crear la vista (probablemente un modal en la misma página del módulo de roles).
- Proteger las rutas con autenticación (actualmente son públicas — BUG-07).

---

### AUS-04 — Entrada de Compras en el Sidebar

El sidebar de `app.blade.php` no tiene un enlace al módulo de compras. Para acceder hay que escribir `/compras` directamente en la URL.

**Trabajo pendiente:** Agregar una entrada en el sidebar apuntando a `route('compras.index')`.

---

## Tabla de Priorización

| Prioridad | ID | Descripción |
|---|---|---|
| **Crítica** | ~~BUG-01~~ | ~~Asignación de permisos a roles no funciona~~ ✅ 2026-07-07 |
| **Crítica** | ~~BUG-03~~ | ~~Ruta `/lotes` causa error 500~~ ✅ 2026-07-07 |
| **Crítica** | BUG-07 | Rutas de permisos sin autenticación |
| **Alta** | BUG-02 | No se puede editar el precio de un producto |
| **Alta** | ~~BUG-04~~ | ~~Ventas pueden despachar lotes vencidos~~ ✅ 2026-07-07 |
| **Alta** | ~~BUG-05~~ | ~~Registro público de usuarios puede fallar~~ ✅ 2026-07-07 |
| **Alta** | PEND-01 | Implementar recibos completamente |
| **Alta** | PEND-02 | Implementar devoluciones completamente |
| **Media** | PEND-03 | Anulación de ventas |
| **Media** | AUS-01 | Módulo de reportes |
| **Media** | AUS-02 | Vista de movimientos de stock |
| **Media** | PEND-05 | Dashboard con datos reales y alertas |
| **Baja** | BUG-06 | Scopes de movimientos de stock |
| **Baja** | PEND-04 | Anulación de compras |
| **Baja** | PEND-06 | Auditoría en ajustes de stock |
| **Baja** | AUS-03 | Gestión de permisos desde UI |
| **Baja** | AUS-04 | Enlace de compras en el sidebar |
| **Baja** | ESQ-01 a ESQ-08 | Desajustes modelo/migración |

---

## Próximos pasos

> **Nota:** Este plan fue validado el 2026-07-01 mediante auditoría estática completa del proyecto
> (ver `docs/auditoria.md`) y revisión ítem por ítem de cada error confirmado.
> Es el punto de partida acordado para la próxima sesión de trabajo.

---

### Sprint 1 — Errores críticos con impacto directo en la demo — ✅ COMPLETADO (2026-07-07)

Estos tres errores producen fallos visibles en las primeras interacciones con el sistema.
Deben resolverse juntos antes de hacer cualquier prueba con usuarios o evaluadores.

- [x] **C-01 · ERR-12 — Corregir `RolController` para sincronizar permisos**
  `RolController::store()` y `update()` ignoran los checkboxes `permisos[]` del formulario.
  La corrección requiere agregar `$rol->permisos()->sync($request->input('permisos', []))` en ambos métodos.
  Referencia de auditoría: `app/Http/Controllers/RolController.php`.
  **Corregido:** se agregó `sync()` tras `Rol::create()` en `store()` y tras `$rol->update()` en `update()`.

- [x] **C-02 · ERR-01 — Corregir la ruta `/lotes`**
  `Route::resource('lotes', LoteController::class)` genera `GET /lotes` que lanza `TypeError`
  porque `LoteController::index()` requiere model binding de `{producto}` que no existe en esa URL.
  La corrección es eliminar esa línea de `web.php`; las rutas de lotes correctas ya están definidas
  bajo el prefijo `productos/{producto}/lotes`.
  Referencia de auditoría: `routes/web.php`.
  **Corregido:** se eliminó `Route::resource('lotes', LoteController::class)` de `web.php`.
  **Efecto secundario detectado y corregido:** el widget "Próximos a vencer" del dashboard (`resources/views/inicio.blade.php`) usaba `route('lotes.index')` para su botón "Ver más", lo cual rompía la vista con `Route [lotes.index] not defined`. Se cambió el enlace a `route('productos.index')` (mismo patrón que el widget "Productos con menos stock"). No existe hoy una vista global de lotes (todas las rutas de lotes requieren `{producto}`); crear una vista dedicada de "lotes próximos a vencer" queda pendiente como parte de `PEND-05`/`AUS-01`.

- [x] **C-03 · ERR-11 — Corregir el registro de usuarios**
  `CreateNewUser.php` no asigna `username`, que es `NOT NULL` sin default en la tabla `users`.
  El registro público desde `/register` falla con error de integridad de BD.
  La corrección es generar `username` automáticamente (por ejemplo, como slug del email),
  siguiendo el mismo criterio que ya usa `UserController::store()`.
  Referencia de auditoría: `app/Actions/Fortify/CreateNewUser.php`.
  **Corregido:** se genera `username` como slug de la parte local del email, con sufijo aleatorio si hay colisión, igual que en `UserController::store()`.

---

### Sprint 2 — Errores críticos de seguridad

Estos dos problemas no producen crashes visibles pero comprometen la integridad del sistema de permisos.
Resolverlos en la misma sesión ya que ambos afectan el módulo de permisos y proveedores.

- **C-04 · SEC-01 — Proteger las rutas de `/permiso` con middleware `auth`**
  El grupo `Route::prefix('permiso')` está registrado fuera de cualquier middleware `auth`.
  Las rutas de gestión de permisos son accesibles por usuarios no autenticados.
  La corrección es mover ese grupo dentro del bloque `middleware('auth')` en `web.php`.
  Referencia de auditoría: `routes/web.php` — grupo `/permiso`.

- **C-05 · BUG-04 — Corregir el mismatch de slugs de permisos de proveedores**
  `PermisoSeeder` crea los permisos con prefijo `proveedores.*` (con `e`), pero `web.php`
  aplica el middleware con `proveedors.*` (sin `e`). `PermisoMiddleware` nunca encuentra
  el permiso correcto y bloquea el acceso a proveedores para todos los roles no-admin.
  La corrección es unificar los slugs en el seeder a `proveedors.*` y re-ejecutar `db:seed`.
  Referencia de auditoría: `database/seeders/PermisoSeeder.php` · `routes/web.php`.

---

### Sprint 3 — Decisión técnica sobre `MovimientoStock`

Este ítem requiere una decisión de diseño antes de aplicar cualquier corrección de código.

- **C-06 · ERR-03 — Revisar si `MovimientoStock` debe conservar `SoftDeletes`**
  El modelo usa el trait `SoftDeletes` pero la migración no tiene columna `deleted_at`.
  Esto hace que cualquier SELECT sobre la tabla falle con error SQL de columna inexistente.
  Actualmente no hay UI que lea esta tabla, por lo que el error es latente.

  Antes de corregir, decidir:
  - **Opción A — Conservar SoftDeletes:** crear una nueva migración que añada
    `$table->softDeletes()` a `movimientos_stock`. Apropiado si se quiere poder
    eliminar movimientos de auditoría de forma reversible.
  - **Opción B — Eliminar el trait:** quitar `use SoftDeletes` del modelo y dejar
    solo hard deletes. Apropiado si los movimientos de stock son un log inmutable
    (que es la semántica habitual de una tabla de auditoría).

  La Opción B es la más coherente con el diseño actual (los movimientos son trazabilidad,
  no datos operativos) y no requiere migración. Confirmar con el tutor si aplica.
