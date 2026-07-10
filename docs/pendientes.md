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

### BUG-07 — Rutas de permisos sin protección de autenticación ✅ CORREGIDO (2026-07-10)

**Módulo:** Permisos
**Archivo:** `routes/web.php`

Las rutas bajo el prefijo `/permiso` estaban registradas fuera del grupo `middleware('auth')`. Cualquier petición HTTP sin sesión activa podía acceder a ellas.

**Efecto:** Aunque los métodos del controlador están vacíos actualmente, si se implementan, cualquier usuario no autenticado podría manipular los permisos del sistema.

**Corregido:** se envolvió el grupo `Route::prefix('permiso')` en `Route::middleware('auth')->prefix('permiso')->group(...)`. Verificado con `route:list` (las 4 rutas muestran `auth` en su pila de middleware) y con una petición real sin sesión a través del kernel HTTP, que ahora responde `302` hacia `/login` en lugar de ejecutar el controlador.

---

### BUG-08 — `User::esAdmin()` devolvía `true` para cualquier usuario ✅ CORREGIDO (2026-07-10)

**Módulo:** Autenticación / RBAC
**Archivo:** `app/Models/User.php`

Detectado durante la verificación de BUG-04 (mismatch de slugs de proveedores). `esAdmin()` estaba escrito como:

```php
$this->rols()->where('nombre', 'Administrador')->orWhere('slug', 'admin')->exists();
```

`$this->rols()` ya agrega `WHERE rol_user.user_id = ?` como parte de la consulta de la relación (no como parte del `JOIN ON`). Por precedencia SQL (`AND` liga más fuerte que `OR`), la condición final quedaba agrupada como `(rol_user.user_id = ? AND nombre = 'Administrador') OR slug = 'admin'`. El segundo término no hereda el filtro de usuario, y como el `JOIN` con `rol_user` no está filtrado por usuario, ese `OR` pasa a preguntar "¿existe en toda la tabla `rol_user`, para cualquier usuario, algún rol con `slug = 'admin'`?" — algo que nada tiene que ver con el usuario que llama al método.

**Efecto:** en cualquier instalación con al menos un usuario administrador seedeado (que es el caso normal), `esAdmin()` devolvía `true` para **todos** los usuarios del sistema, incluso uno recién creado sin ningún rol asignado (verificado). Esto anulaba de facto el RBAC completo:
- `PermisoMiddleware` dejaba pasar a cualquier usuario autenticado en todas las rutas protegidas con `permiso:*` (productos, lotes/stock, usuarios, clientes, proveedores, compras), sin importar sus permisos reales.
- `ConfiguracionController::edit()`/`update()` (`/configuracion`) eran accesibles para cualquier usuario, no solo administradores.
- El campo de descuento en `VentaController::create()`/`store()` y en la vista `ventas/create.blade.php` se habilitaba para cualquier vendedor, aunque está documentado como exclusivo de administradores.
- El enlace "Configuración" del sidebar (`app.blade.php`) se mostraba a todos los usuarios.

**Corregido:** se agrupó la condición dentro de un closure para que quede ANDada correctamente con el filtro de usuario:

```php
$this->rols()->where(function ($q) {
    $q->where('nombre', 'Administrador')->orWhere('slug', 'admin');
})->exists();
```

Único archivo modificado: `app/Models/User.php`. No se tocaron `PermisoMiddleware`, controladores, seeders, migraciones, rutas ni vistas.

**Verificado** contra la base de datos real: `esAdmin()` correcto para `admin@farmacia.com` (`true`), `vendedor@farmacia.com` y `Xhaka` (`false` ambos, antes `true`), y para un usuario sin roles (`false`). `PermisoMiddleware` simulado con estos usuarios reales respeta ahora sus permisos reales (vendedor bloqueado en `clientes.ver`/`proveedors.ver`, permitido en `productos.ver`/`ventas.crear`; admin sigue con bypass total). `/configuracion` bloqueado para vendedor, permitido para admin. Flag `esAdmin` en `ventas.create` correcto (`false` para vendedor, `true` para admin). Suite de tests: mismo resultado que antes del fix (sin regresiones nuevas).

**Efecto secundario esperado (no es un bug):** ahora que el RBAC real está activo, `vendedor@farmacia.com` y `Xhaka` (rol Vendedor, con permisos reales limitados a `ventas.ver`, `ventas.crear`, `productos.ver`, `devoluciones.registrar`) pierden el acceso de facto que tenían antes a `usuarios`, `clientes`, `proveedores`, `compras`, edición/eliminación de productos, gestión de stock y `/configuracion`, y ya no pueden aplicar descuentos en ventas. Es el comportamiento correcto, pero visible de inmediato si se prueba con esas cuentas.

**Problema de datos detectado (no corregido, fuera de alcance):** ver `DATA-01` más abajo — el rol "Supervisor 1" quedó con una asignación de permisos incompleta que este fix hizo visible.

---

## Problemas de Datos (asignaciones de roles/permisos, no bugs de código)

### DATA-01 — Rol "Supervisor 1" con permisos de proveedores incompletos

**Módulo:** Roles / Proveedores
**Detectado:** 2026-07-10, durante la verificación de BUG-08.

El rol no-admin `Supervisor 1` (id 3, sin usuarios asignados actualmente) tiene en `permiso_rol` los permisos `proveedors.crear` y `proveedors.editar`, pero **no** `proveedors.ver` ni `proveedors.eliminar`. Antes de corregir BUG-08 esto era invisible (todos bypaseaban el RBAC). Ahora, si se asigna algún usuario a este rol, podría crear/editar proveedores pero no vería el listado (`/proveedors` bloqueado por falta de `proveedors.ver`).

**No es un bug de código** — es una asignación de datos incompleta, probablemente hecha antes de que `RolController::sync()` funcionara (BUG-01) o antes de que existiera el permiso `proveedors.ver` con el slug correcto (BUG-04/C-05). Corrección sugerida (pendiente de decisión, no aplicada): agregar `proveedors.ver` (y evaluar `proveedors.eliminar`) al rol desde la UI de Roles, o vía seeder si se documenta como parte del catálogo de roles del proyecto.

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
- Agregar alertas de stock bajo con umbral configurable — **deuda técnica anotada:** el widget "Productos con menos stock" sigue con el `30` hardcodeado en `inicio.blade.php`; el reporte "Productos con stock bajo" de `AUS-01` (2026-07-10) ya lee `Configuracion::obtener('stock_bajo_umbral', 30)`. Cuando se aborde este punto, el dashboard debe leer la misma clave para tener una única fuente de verdad.
- Mostrar lotes próximos a vencer con columores de alerta.

---

### PEND-06 — Ajustes de Stock sin Auditoría

**Estado:** `LoteController::bulkUpdate()` modifica el campo `stock` directamente sin crear `MovimientoStock`.

**Trabajo pendiente:**
- Agregar creación de `MovimientoStock` con `tipo='Entrada'` o `tipo='Salida'` y `motivo='Ajuste'` en `bulkUpdate()`.
- Esto permite tener el historial completo de todos los cambios de inventario.

---

## Módulos Completamente Ausentes (no existe nada)

### AUS-01 — Reportes 🔶 EN PROGRESO (módulo iniciado 2026-07-10)

El seeder define el permiso `reportes.ver`. El módulo se está implementando de forma incremental, un reporte por sprint, reutilizando al máximo la lógica ya existente.

**Reportes mínimos esperados para una farmacia:**
- Ventas por período (diario, mensual, anual) con filtro por usuario y cliente. *(pendiente)*
- Compras por período con filtro por proveedor. *(pendiente)*
- Stock valorizado (stock actual × costo unitario por lote). ✅ **implementado (2026-07-10)**
- Productos con stock bajo (por debajo de un umbral). ✅ **implementado (2026-07-10)**
- Lotes próximos a vencer (por rango de fechas). ✅ **implementado (2026-07-10)**
- Historial de movimientos de stock por producto o lote. *(pendiente — se solapa con `AUS-02`, ver nota ahí)*

**Andamiaje común del módulo:** `GET /reportes` (nombre `reportes.index`) — landing con enlaces a los reportes disponibles. El sidebar apunta aquí (antes apuntaba directo al primer reporte). Todas las rutas viven bajo `Route::middleware('permiso:reportes.ver')->prefix('reportes')->name('reportes.')` en `routes/web.php`, y todos los métodos están en el mismo `ReporteController`.

**Reporte 1 — "Lotes próximos a vencer" (implementado):**
- **Ruta:** `GET /reportes/vencimientos` (nombre `reportes.vencimientos`).
- **Controlador:** `app/Http/Controllers/ReporteController.php`, método `vencimientos()`.
- **Vista:** `resources/views/reportes/vencimientos.blade.php`. Solo funcional, sin trabajo visual (a mejorar en una etapa posterior junto con el resto del sistema).
- **Filtros:** estado (todos/vencidos/próximos), rango de fechas (`desde`/`hasta`, solo afecta a los "próximos"; los vencidos con stock se muestran siempre) y producto. Los filtros se conservan tras la búsqueda.
- **Orden:** vencidos primero (más antiguos primero), luego próximos (más cercanos primero).
- **Estado vacío:** mensaje amigable en vez de tabla vacía cuando no hay resultados.
- **Reutilización (sin duplicar comparaciones de fechas):** el caso por defecto usa `Lote::vigentes()->proximosAVencer($diasAlerta)` tal cual. Se agregó un único scope nuevo, `Lote::scopeVenceEntre($desde, $hasta)` (`app/Models/Lote.php`), necesario solo para el caso de rango arbitrario elegido por el usuario, que ningún scope existente podía expresar. Los vencidos usan `Lote::vencidos()` sin cambios. El umbral por defecto sigue viniendo de `Configuracion::obtener('dias_alerta_vencimiento', 90)`. Cada fila se etiqueta `vencido`/`proximo` según el scope que la trajo (mismo patrón que ya usa `InicioController` para el widget del dashboard), no por una comparación de fecha nueva.
- **Corrección posterior (2026-07-10, en revisión previa al Reporte 2):** si `desde`/`hasta` llegaban con una fecha no parseable por la URL (p. ej. `?desde=no-es-una-fecha`), `Carbon::parse()` lanzaba una excepción no capturada y la petición terminaba en `500`. Se agregó `ReporteController::fechaValida()`, que valida/normaliza la fecha antes de usarla y la ignora (en vez de romper la consulta) si no es parseable — mismo criterio ya usado para el rango invertido.
- **Pruebas:** `tests/Feature/ReporteVencimientosTest.php` (Pest, 18 casos).

**Reporte 2 — "Stock valorizado" (implementado):**
- **Ruta:** `GET /reportes/stock-valorizado` (nombre `reportes.stockValorizado`).
- **Controlador:** `ReporteController::stockValorizado()`.
- **Vista:** `resources/views/reportes/stock_valorizado.blade.php`. Igual que el reporte anterior, solo funcional.
- **Filtro:** por producto (opcional). Se conserva tras la búsqueda (`->paginate()->withQueryString()`, mismo patrón que `ProductoController::index()`).
- **Orden:** por valor descendente (`stock × costo_unitario`), calculado y ordenado en SQL (`orderByRaw`), no en PHP — evita cargar todo el catálogo en memoria como sí requirió el reporte anterior (ahí era necesario por combinar dos scopes con etiquetado; aquí es una sola consulta filtrada, así que se usó el patrón de paginación estándar del proyecto).
- **Total general:** `SUM(stock * costo_unitario)` calculado en SQL sobre el conjunto ya filtrado (no sobre la página actual), en una consulta aparte clonando el query base.
- **Reutilización:** nuevo accessor `Lote::getValorAttribute()` (`stock * costo_unitario`), siguiendo el mismo patrón ya usado en `Venta::getTotalAttribute()`, `Compra::getTotalAttribute()`, `DetalleVenta::getSubtotalAttribute()` y `DetalleCompra::getSubtotalAttribute()` — no es lógica nueva, es aplicar la convención ya existente al modelo que le faltaba.
- **Pruebas:** `tests/Feature/ReporteStockValorizadoTest.php` (Pest, 12 casos).

**Reporte 3 — "Productos con stock bajo" (implementado):**
- **Ruta:** `GET /reportes/stock-bajo` (nombre `reportes.stockBajo`).
- **Controlador:** `ReporteController::stockBajo()`.
- **Vista:** `resources/views/reportes/stock_bajo.blade.php`. Igual que los reportes anteriores, solo funcional.
- **Nivel de agregación:** por producto (no por lote), a diferencia de los dos reportes anteriores — así lo pide `AUS-01` ("Productos con stock bajo").
- **Umbral:** `Configuracion::obtener('stock_bajo_umbral', 30)` como valor por defecto (mismo patrón que `dias_alerta_vencimiento`), con override puntual por query string (`?umbral=`). No se agregó campo a `/configuracion` en este sprint (decisión explícita del usuario) — `Configuracion::obtener()` ya resuelve el default sin que la clave exista en la tabla.
- **Regla de negocio confirmada:** los productos con `stock_total = 0`, incluidos los que no tienen ningún lote registrado, **sí aparecen** (es el caso más crítico — "requieren reposición"). Se etiquetan `sin_stock` (badge `SIN STOCK`) distinto de `bajo` (badge `STOCK BAJO`) para los que tienen stock > 0 pero por debajo del umbral.
- **Filtro:** por producto (opcional), igual que los otros dos reportes.
- **Orden:** ascendente por stock total (los más críticos primero).
- **Reutilización:** `Producto::withSum('lotes as stock_total', 'stock')`, el mismo patrón ya usado en `ProductoController::index()` para la columna "Stock total" del catálogo — no la versión de `leftJoin`+`groupBy` manual que usa `InicioController` para el widget del dashboard (ambas calculan lo mismo; se reutilizó la más idiomática y ya probada).
- **Problema encontrado y corregido durante la implementación:** `withSum()` deja `stock_total` en `NULL` (no `0`) para productos sin ningún lote — confirmado también en `ProductoController`/`productos/index.blade.php`, que por eso hace `{{ $p->stock_total ?? 0 }}` en la vista. Filtrar directamente por `stock_total < $umbral` habría excluido justo los productos sin lotes, violando la regla de negocio de este reporte. Se probó primero con `HAVING`/`GROUP BY` sobre el alias (funciona en MySQL) pero falló en SQLite (motor de los tests: *"HAVING clause on a non-aggregate query"*) y, ajustado con `GROUP BY productos.id`, volvió a fallar en MySQL real (*"'productos.codigo' isn't in GROUP BY"*, con `ONLY_FULL_GROUP_BY`). Se resolvió reemplazando el `HAVING` por un `WHERE`/`ORDER BY` con la misma suma correlacionada expresada como subquery explícita (`COALESCE((select sum(stock) from lotes where...), 0)`), portable entre ambos motores — verificado en ambos (SQLite vía los tests, MySQL real vía `tinker` con datos temporales revertidos).
- **Pruebas:** `tests/Feature/ReporteStockBajoTest.php` (Pest, 16 casos).

**Deuda técnica anotada (no corregida en este sprint):** el widget "Productos con menos stock" del dashboard (`InicioController`/`inicio.blade.php`) sigue usando el umbral `30` hardcodeado directamente en la vista, en vez de `Configuracion::obtener('stock_bajo_umbral', 30)`. Cuando se decida hacer editable `stock_bajo_umbral` desde `/configuracion` (como ya lo es `dias_alerta_vencimiento`), el dashboard y este reporte deben leer la misma configuración para no tener dos fuentes de verdad del mismo número.

**Cambio transversal:** los helpers de prueba (`crearUsuarioDePrueba`, `crearProductoDePrueba`, `crearLoteDePrueba`) se movieron de `ReporteVencimientosTest.php` a `tests/Pest.php` para compartirlos entre los dos archivos de test de Reportes sin duplicar código (y sin colisión de nombres de función entre archivos, que habría roto la suite).

Detalle completo de ambas sesiones en `docs/diario_desarrollo.md` (entradas 2026-07-10).

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
| **Crítica** | ~~BUG-07~~ | ~~Rutas de permisos sin autenticación~~ ✅ 2026-07-10 |
| **Crítica** | ~~BUG-08~~ | ~~`User::esAdmin()` devolvía `true` para cualquier usuario (RBAC anulado)~~ ✅ 2026-07-10 |
| **Alta** | BUG-02 | No se puede editar el precio de un producto |
| **Alta** | ~~BUG-04~~ | ~~Ventas pueden despachar lotes vencidos~~ ✅ 2026-07-07 |
| **Alta** | ~~BUG-05~~ | ~~Registro público de usuarios puede fallar~~ ✅ 2026-07-07 |
| **Alta** | PEND-01 | Implementar recibos completamente |
| **Alta** | PEND-02 | Implementar devoluciones completamente |
| **Media** | PEND-03 | Anulación de ventas |
| **Media** | AUS-01 | Módulo de reportes 🔶 en progreso — "Lotes próximos a vencer", "Stock valorizado" y "Productos con stock bajo" ✅ 2026-07-10 |
| **Media** | AUS-02 | Vista de movimientos de stock |
| **Media** | PEND-05 | Dashboard con datos reales y alertas |
| **Baja** | BUG-06 | Scopes de movimientos de stock |
| **Baja** | PEND-04 | Anulación de compras |
| **Baja** | PEND-06 | Auditoría en ajustes de stock |
| **Baja** | AUS-03 | Gestión de permisos desde UI |
| **Baja** | AUS-04 | Enlace de compras en el sidebar |
| **Baja** | DATA-01 | Rol "Supervisor 1" sin `proveedors.ver`/`proveedors.eliminar` |
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

### Sprint 2 — Errores críticos de seguridad ✅ COMPLETADO (2026-07-10)

Estos dos problemas no producen crashes visibles pero comprometen la integridad del sistema de permisos.
Resolverlos en la misma sesión ya que ambos afectan el módulo de permisos y proveedores.

- [x] **C-04 · SEC-01 — Proteger las rutas de `/permiso` con middleware `auth`**
  El grupo `Route::prefix('permiso')` está registrado fuera de cualquier middleware `auth`.
  Las rutas de gestión de permisos son accesibles por usuarios no autenticados.
  La corrección es mover ese grupo dentro del bloque `middleware('auth')` en `web.php`.
  Referencia de auditoría: `routes/web.php` — grupo `/permiso`.
  **Corregido:** ver detalle en BUG-07 más arriba.

- [x] **C-05 · BUG-04 — Corregir el mismatch de slugs de permisos de proveedores**
  `PermisoSeeder` crea los permisos con prefijo `proveedores.*` (con `e`), pero `web.php`
  aplica el middleware con `proveedors.*` (sin `e`). `PermisoMiddleware` nunca encuentra
  el permiso correcto y bloquea el acceso a proveedores para todos los roles no-admin.
  La corrección es unificar los slugs en el seeder a `proveedors.*` y re-ejecutar `db:seed`.
  Referencia de auditoría: `database/seeders/PermisoSeeder.php` · `routes/web.php`.
  **Corregido:** se agregó un paso de renombrado en sitio (`Permiso::where('slug', $antiguo)->update(['slug' => $nuevo])`)
  al inicio de `PermisoSeeder::run()`, antes del `updateOrCreate` habitual, para preservar los IDs de permiso existentes
  en lugar de crear filas nuevas. Esto era necesario porque el rol no-admin "Supervisor 1" (id 3) ya tenía
  `proveedores.crear`/`proveedores.editar` asignados en `permiso_rol`; un `updateOrCreate` directo con el slug nuevo
  habría creado permisos huérfanos y roto esa asignación silenciosamente. Verificado contra la base de datos real:
  mismos IDs (9-12), mismo conteo de filas en `permiso_rol` (37) antes y después, y `tienePermiso()` responde
  correctamente para el rol afectado con los slugs nuevos.

  **Hallazgo no relacionado detectado durante la verificación:** `User::esAdmin()` (`app/Models/User.php`) tenía
  un bug de precedencia SQL que hacía que devolviera `true` para **cualquier** usuario del sistema, incluso sin
  roles asignados, anulando de facto el RBAC. Catalogado y corregido como `BUG-08` (ver más arriba) en una sesión
  posterior el mismo día (2026-07-10).

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
