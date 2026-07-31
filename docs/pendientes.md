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

### BUG-02 — No se puede editar el precio de venta de un producto ✅ CORREGIDO (2026-07-27)

**Módulo:** Productos
**Archivo:** `app/Http/Controllers/ProductoController.php`, `resources/views/productos/index.blade.php`

`ProductoController::update()` no incluía `precio_venta` en sus reglas de validación ni en los campos que actualiza. La vista tampoco tenía este campo en el modal.

**Hallazgo ampliado durante la corrección (más grave que lo documentado):** el modal de **creación** (mismo formulario, compartido con edición) tampoco tenía el campo `precio_venta`, pese a que `ProductoController::store()` ya lo exige como `required`. Verificado con una petición real (`POST /productos` sin `precio_venta`): la validación fallaba con "El campo precio de venta es obligatorio.", por lo que **crear un producto desde la UI ya estaba roto**, no solo editar el precio.

**Corregido:** se agregó el input `precio_venta` (con su `@error()`) al modal compartido, se agregó `data-precio_venta` al botón "Editar" y su lectura en el JS de apertura del modal, y se agregó `precio_venta` a la validación/actualización de `ProductoController::update()` (mismas reglas que ya usaba `store()`). Sin cambios de lógica de negocio: se completó un campo que ya existía en el modelo, la migración y `store()`, simplemente ausente en la vista y en `update()`.

**Verificado:** `tests/Feature/ProductosIndexTest.php` (+4 casos): creación con `precio_venta` persiste el valor, creación sin `precio_venta` falla la validación (reproduce el bug encontrado), edición actualiza `precio_venta`, el campo está presente en el HTML del formulario. Suite completa sin regresiones.

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

### BUG-06 — `MovimientoStock::scopeSalidas()` nunca devolvía resultados ✅ CORREGIDO (2026-07-27)

**Módulo:** Movimientos de Stock
**Archivo:** `app/Models/MovimientoStock.php`

El modelo definía `scopeSalidas()` filtrando `cantidad < 0`, pero los movimientos de salida (ventas) se crean con `cantidad` siempre positiva en `VentaController::store()`. De igual forma, `scopeEntradas()` filtraba `cantidad > 0`, lo que hacía que ambos scopes devolvieran los mismos datos.

**Corregido:** ambos scopes ahora filtran por la columna `tipo` (`'Entrada'`/`'Salida'`, poblada correctamente por `CompraController`/`VentaController`) en vez del signo de `cantidad`. Se confirmó por búsqueda exhaustiva que ningún controlador ni vista llamaba a estos scopes (`ReporteController::movimientos()` ya los evitaba deliberadamente, filtrando `tipo` directamente — ver `AUS-01`/Reporte 6), así que el fix no tiene ningún efecto secundario sobre código existente.

**Verificado:** `tests/Feature/MovimientoStockScopesTest.php` (nuevo): un movimiento `Entrada` y uno `Salida` con `cantidad` positiva en ambos — `scopeEntradas()`/`scopeSalidas()` los distinguen correctamente. Suite completa sin regresiones.

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

### BUG-09 — `MovimientoStock` no podía consultarse (era `ESQ-05`) ✅ CORREGIDO (2026-07-12)

**Módulo:** Movimientos de Stock
**Archivo:** `app/Models/MovimientoStock.php`

Detectado como bloqueante al implementar el Reporte 6 ("Historial de movimientos de stock") de `AUS-01`. Ya estaba catalogado como `ESQ-05` ("El modelo usa `SoftDeletes` pero la migración no tiene columna `deleted_at`"), pero esa nota subestimaba el impacto real: el modelo declara `use SoftDeletes`, que registra un global scope aplicado a **toda** consulta `SELECT` (`WHERE deleted_at IS NULL`). Como la tabla `movimientos_stock` nunca tuvo esa columna, **cualquier lectura del modelo, sin excepción, fallaba con un error SQL real** — confirmado tanto en SQLite (tests) como en MySQL real (`farmacia_tfg`) con un simple `MovimientoStock::count()`.

**Por qué era "latente":** el scope solo afecta lecturas, no escrituras. `CompraController::store()` y `VentaController::store()` solo hacen `MovimientoStock::create(...)`, nunca leen — por eso el sistema pudo escribir movimientos con normalidad durante meses sin que nadie lo notara, hasta que se construyó la primera pantalla que efectivamente consulta esta tabla.

**Análisis de alternativas realizado antes de corregir** (a pedido del usuario, tratando esto como bug previo a resolver, no como ampliación del alcance del Reporte 6):
- **Opción A — Agregar `deleted_at` vía migración:** conserva el trait, pero habilita soft-delete sobre registros de auditoría — una capacidad no solicitada y conceptualmente cuestionable para una tabla de trazabilidad.
- **Opción B — Quitar el trait `SoftDeletes`:** sin migración, sin cambio de esquema. Se confirmó por búsqueda exhaustiva en `app/` que **ningún** punto del código llama a `->delete()`, `->restore()`, `->withTrashed()` u `->onlyTrashed()` sobre `MovimientoStock` — solo se usa `::create()`. Coherente con el diseño ya acordado para `PEND-03` (anulación de ventas), que contempla generar **movimientos compensatorios**, no ocultar/eliminar los originales.

**Corregido:** se eligió la Opción B. Único cambio: se quitó `use SoftDeletes;` (y su import) de `MovimientoStock.php`. Sin migraciones, sin cambios de esquema, sin tocar `Compra`, `Venta`, `Devolucion`, `CompraController` ni `VentaController`.

**Impacto sobre datos existentes:** ninguno. Se confirmó que en la BD real solo existía **1 fila** en `movimientos_stock` antes del fix (la única venta real registrada); el cambio no requiere backfill ni pone en riesgo esa fila.

**Verificado:** `MovimientoStock::count()`/`::query()->get()` funcionan correctamente contra SQLite (tests) y MySQL real (antes fallaban en ambos). Se simuló, en una transacción revertida, la creación de un `MovimientoStock` igual a como lo hacen `CompraController`/`VentaController` — funciona idéntico a antes del fix (las escrituras nunca dependieron del trait). Suite completa sin regresiones.

---

### BUG-10 — `auth/register.blade.php` extendía una vista inexistente ✅ CORREGIDO (2026-07-21)

**Módulo:** Autenticación
**Archivo:** `resources/views/auth/register.blade.php`

Detectado durante la auditoría de `UI-04A` (estandarización de errores de validación), no relacionado con esa tarea. La vista hacía `@extends('layouts.app')`, pero `resources/views/layouts/` **no existe** en el proyecto — el layout real es `resources/views/app.blade.php`, extendido en el resto del sistema como `@extends('app')`. Con `Features::registration()` habilitado en `config/fortify.php`, cualquier visitante que abriera `GET /register` recibía una excepción (`View [layouts.app] not found`).

**Corregido:** `@extends('layouts.app')` → `@extends('app')`. Se verificó primero que `app.blade.php` no asume un usuario autenticado (el bloque de navegación que sí llama `auth()->user()->esAdmin()` está envuelto en `@auth`/`@endauth`), así que el fix no cambiaba un error por otro. Como la vista pasó a heredar el shell autenticado completo (sidebar incluido, pensado para usuarios logueados), se le agregó el mismo mecanismo que ya usa `auth/login.blade.php` para ocultar el sidebar en pantallas de invitado (`body.auth` + CSS/JS locales a la vista) — sin eso, `/register` se habría visto rota dentro del sidebar en vez de fallar, un regresión distinta pero igual de real.

**Verificado:** `GET /register` responde `200` (antes: excepción). Suite completa sin regresiones (`tests/Feature/FormErrorsTest.php`).

---

### BUG-11 — El chip de estado de Ventas nunca muestra `pagada`/`pendiente`/`anulada` — encontrado en `UI-05` (2026-07-24)

**Módulo:** Ventas
**Archivos:** `app/Http/Controllers/VentaController.php`, `resources/views/ventas/index.blade.php`

**Estado:** No corregido — decisión explícita del usuario de dejarlo fuera de una migración puramente visual.

`ventas/index.blade.php` mapea el campo `estado` a un color de chip:
```php
$map = ['pagada' => 'ok', 'pendiente' => 'warn', 'anulada' => 'bad'];
$clase = $map[$estado] ?? 'neutral';
```
Pero `VentaController::store()` **siempre** guarda `'estado' => 'confirmada'` — un valor que no aparece en `$map`. En la práctica, **toda venta real cae en el fallback `neutral`**; el chip nunca muestra `ok` (pagada), `warn` (pendiente) ni `bad` (anulada) con los datos que el sistema genera hoy.

**Trabajo pendiente:** decidir si `estado` debería reflejar el ciclo de vida real de una venta (pagada/pendiente/anulada) — lo cual requeriría además implementar `PEND-03` (anulación de ventas, ya documentada) — o si el mapa de colores de la vista debería ajustarse al único valor que el sistema produce actualmente (`confirmada`). Ninguna opción se implementó; es una decisión de negocio, no de presentación.

---

### BUG-12 — El módulo de Roles no tiene ninguna protección de acceso real (RBAC anulado) ✅ CORREGIDO (2026-07-28)

**Módulo:** Roles
**Archivos:** `routes/web.php`, `app/Http/Controllers/RolController.php`, `resources/views/rols/index.blade.php`

**Estado:** Corregido en la misma sesión en que se detectó, durante la auditoría de consistencia de la memoria de tesis.

**Diagnóstico exacto (verificado leyendo cada capa, no asumido):**

- `routes/web.php:100` registra `Route::resource('rols', RolController::class)->only(['index','store','update','destroy'])` **dentro** del grupo `Route::middleware('auth')` (líneas 51-142), pero **sin ningún `->middleware('permiso:rols.*')` encadenado** — a diferencia de Productos, Compras, Clientes, Proveedores, Usuarios y Reportes, que sí protegen cada ruta (o el grupo completo) con el middleware `permiso:`.
- `RolController.php` no compensa la ausencia de middleware: ninguno de sus métodos (`index`, `store`, `update`, `destroy`) llama a `esAdmin()` ni a `tienePermiso()`.
- No existe ningún `Gate::` definido en `app/Providers/`, y `bootstrap/app.php` solo registra el alias `permiso` sin aplicarlo de forma global.
- `resources/views/rols/index.blade.php` tampoco oculta ningún control según permiso: el botón "Nuevo rol" (línea 32), los botones "Editar"/"Ver" (líneas 221-246) y el formulario "Eliminar" (línea 249) son incondicionales — a diferencia de `resources/views/users/index.blade.php`, que sí envuelve sus botones equivalentes en `@if(auth()->user()->tienePermiso(...))`.
- Los permisos `rols.ver`, `rols.crear`, `rols.editar`, `rols.eliminar` **ya existen** en el catálogo (`database/seeders/PermisoSeeder.php:36-39`) y están asignados al rol Administrador (`sync()` de todos los permisos), pero ningún punto del código los verifica jamás.
- Confirmado en los propios tests: el comentario de `tests/Feature/RolesBuscadorTest.php:9` dice literalmente *"rols.index no tiene middleware de permiso propio, basta con estar autenticado"* — la ausencia de protección ya era conocida (o al menos observada) al escribir ese test, sin haberse registrado como bug hasta ahora.

**Efecto:** cualquier usuario autenticado, incluido un Vendedor real (sin ningún permiso `rols.*`), puede listar todos los roles con sus permisos, crear roles nuevos, editar cualquier rol existente — incluyendo asignarle todos los permisos disponibles, por ejemplo a su propio rol — y eliminar cualquier rol, incluido el de Administrador. **Es una vía de escalación de privilegios**, no solo una inconsistencia de documentación: contradice directamente `RF03`/`CU03`/`CU04` de la memoria de tesis, que describen "Gestionar roles" y "Asignar permisos a roles" como operaciones exclusivas del actor Administrador.

**Corrección aplicada (solo la brecha de seguridad; el resto queda explícitamente diferido):**
- `routes/web.php`: se reemplazó `Route::resource('rols', RolController::class)->only([...])` por rutas explícitas, mismo patrón que Usuarios/Clientes/Proveedores: `GET /rols` → `permiso:rols.ver`, `POST /rols` → `permiso:rols.crear`, `PUT /rols/{rol}` → `permiso:rols.editar`, `DELETE /rols/{rol}` → `permiso:rols.eliminar`. Los 4 slugs ya existían en `PermisoSeeder.php`, no se sembró nada nuevo.
- `RolController.php`: sin cambios — no necesita verificación interna, igual que Usuarios/Clientes/Proveedores, ya cubiertos solo por el middleware de ruta.
- **Deliberadamente sin cambios (decisión explícita del usuario, para no ampliar el alcance de una corrección de seguridad puntual):** `rols/index.blade.php` sigue sin ocultar botones según permiso (`@if(tienePermiso(...))`, patrón que hoy solo usa `users/index.blade.php`). Productos, Clientes y Proveedores tampoco lo hacen — queda como un sprint futuro de UX/RBAC uniforme para todo el sistema, no solo para Roles. El sidebar (`app.blade.php`) tampoco se tocó: su enlace "Roles" sigue mostrado vía `Route::has('rols.index')`, exactamente igual que Usuarios/Productos/Proveedores/Clientes (ninguno de esos enlaces está filtrado por permiso; la única excepción de todo el sistema es "Configuración"). No tocar el sidebar no reintroduce el bug: el acceso ya está bloqueado por el middleware aunque el enlace sea visible.
- **Tests actualizados:** `tests/Feature/RolesIndexTest.php` y `tests/Feature/RolesBuscadorTest.php` autenticaban usuarios sin ningún rol asignado — se les agregó un rol Administrador con los 4 permisos `rols.*` (mismo patrón que `ProveedoresIndexTest.php`), más un rol Vendedor sin ellos para el caso negativo. Se agregó un test nuevo en cada archivo ("acceso sin permiso rols.ver es bloqueado con 403"). También se corrigieron dos tests de otros archivos que dependían de la ausencia de protección: `FieldErrorsTest.php` ("roles: campo nombre vacío...") y `FormErrorsTest.php` ("un formulario autenticado (crear rol)...") — ambos autenticaban un usuario sin rol y ahora necesitan `rols.crear`/`rols.ver` para no recibir 403 antes de llegar a la validación.
- El test "sin roles se muestra el estado vacío" de `RolesIndexTest.php` ya no puede vaciar la tabla `rols` por completo (`Rol::query()->delete()`), porque el propio rol del administrador de la prueba pasaría a no existir y el admin perdería `rols.ver` — se cambió a filtrar con una búsqueda sin resultados (`?q=inexistente-xyz`), que ejercita el mismo estado vacío del Design System sin depender de borrar el rol necesario para la propia autenticación.

**Verificado:**
- Suite completa: `php artisan test` → 239 passed, 1 failed (`ExampleTest`, mismo fallo preexistente no relacionado, sin conexión con este cambio).
- Verificación manual contra la base de datos real (`farmacia_tfg`, vía `php artisan serve` temporal + `curl` con las cuentas reales del seeder): login como `admin@farmacia.com` → `GET /rols` responde `200` con el contenido real de la vista ("LISTA DE ROLES"); login como `vendedor@farmacia.com` → `GET /rols` responde `403` con el cuerpo "No autorizado" del `PermisoMiddleware`. Servidor temporal detenido y cookies de sesión eliminadas al finalizar.

**Nota para trabajo futuro (no forma parte de este fix):** el gating de botones por permiso en la vista (`tienePermiso()`) sigue sin ser un patrón uniforme del sistema — solo `users/index.blade.php` lo implementa hoy. Si se decide generalizarlo, debería abordarse en un sprint dedicado que toque Productos, Clientes, Proveedores y Roles a la vez, no solo uno de ellos.

---

### BUG-13 — Los permisos `clientes.*` nunca se sembraron, pese a que las rutas ya los exigían ✅ CORREGIDO (2026-07-28)

**Módulo:** Clientes / RBAC
**Archivos:** `database/seeders/PermisoSeeder.php`, `database/seeders/RolSeeder.php`

**Estado:** Corregido en la misma sesión en que se detectó, durante la auditoría de consistencia del Capítulo 3 de la memoria de tesis (CU15 "Asociar cliente a una venta").

**Diagnóstico exacto:** `routes/web.php` protege `/clientes` con `permiso:clientes.ver`, `permiso:clientes.crear`, `permiso:clientes.editar` y `permiso:clientes.eliminar` (confirmado en las 4 rutas del prefijo `clientes.`). Sin embargo, `PermisoSeeder.php` **nunca definió ningún permiso con prefijo `clientes.`** — el módulo de Clientes es el único de todo el sistema sin su propia sección en el seeder de permisos (confirmado también en `docs/base_de_datos.md`, cuya tabla de "24 permisos" nunca incluyó una fila "Clientes"). El módulo solo era utilizable por el rol Administrador gracias al bypass total de `esAdmin()` en `PermisoMiddleware`; para cualquier otro rol era **imposible** otorgar acceso a Clientes, porque el permiso ni siquiera existía en la tabla `permisos` para asignarlo.

**Efecto concreto detectado:** el rol Vendedor necesita poder registrar un cliente nuevo durante la venta (botón "Nuevo cliente" de `ventas/create.blade.php`, visible para cualquier usuario, que llama a `POST /clientes`). Sin el permiso `clientes.crear` sembrado, esa acción fallaba con 403 para `vendedor@farmacia.com` — contradiciendo `CU15` de la memoria de tesis ("Asociar cliente a una venta", actor Vendedor).

**Corregido:**
- `PermisoSeeder.php`: se agregó la sección `CLIENTES` con los 4 slugs ya exigidos por las rutas (`clientes.ver/crear/editar/eliminar`).
- `RolSeeder.php`: se agregó `clientes.crear` a la lista de permisos del rol Vendedor (no `clientes.ver`/`editar`/`eliminar` — el Vendedor no gestiona el directorio de clientes, solo crea uno nuevo al vuelo durante la venta, igual que documenta `CU15`; el directorio completo sigue siendo exclusivo de Administrador vía `CU06`).
- Se re-sembraron ambos seeders contra la base de datos real (`php artisan db:seed --class=PermisoSeeder` seguido de `--class=RolSeeder`, en ese orden).

**Verificado:**
- Catálogo de permisos: 29 en total (antes 25; el conteo de "24" que traía `docs/base_de_datos.md` ya estaba desactualizado desde antes de esta sesión).
- `php artisan test`: 239 passed, 1 failed (`ExampleTest`, mismo fallo preexistente no relacionado) — los tests de Clientes/Proveedores no se ven afectados porque fabrican sus propios `Permiso`/`Rol` de prueba, independientes del seeder real.
- Verificación manual contra la BD real: login como `vendedor@farmacia.com` (vía `php artisan serve` temporal + `curl`), `POST /clientes` con un cliente de prueba → `201 Created` (antes: `403`). El registro de prueba se eliminó de la BD real al finalizar (`forceDelete()`).

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
| ~~ESQ-01~~ | `Recibo` | ~~`fillable` incluía `nro_recibo`, `fecha`, `metodo_pago`, `observacion`, `estado`. Ninguno existe en la migración.~~ ✅ Corregido junto con `PEND-01` (2026-07-27) — `fillable` reducido a `venta_id`/`monto`, únicos campos reales. |
| ESQ-02 | `Devolucion` | `fillable` incluye `venta_id`, `cliente_id`, `fecha_devolucion`, `observacion`, `estado`. Ninguno existe en la migración. |
| ESQ-03 | `DetalleDevolucion` | `fillable` incluye `producto_id`, `precio_unitario`, `razon`. Ninguno existe en la migración. |
| ESQ-04 | `Compra` | `fillable` incluye `observacion` y `estado`. Ninguno existe en la migración. |
| ~~ESQ-05~~ | `MovimientoStock` | ~~El modelo usa `SoftDeletes` pero la migración no tiene columna `deleted_at`.~~ ✅ Corregido como `BUG-09` (2026-07-12) — se quitó el trait, ver detalle arriba. |
| ESQ-06 | `Rol` | La migración tiene `deleted_at`, pero el modelo no usa el trait `SoftDeletes`. |
| ESQ-07 | `Permiso` | La migración tiene `deleted_at`, pero el modelo no usa el trait `SoftDeletes`. |
| ESQ-08 | `Cliente` | El modelo castea el campo `activo` a boolean, pero esa columna no existe en la migración. |

---

## Funcionalidades Incompletas (el módulo existe pero no está terminado)

### PEND-01 — Recibos ✅ COMPLETO (persistencia 2026-07-27, `RF10`/`CU11` 2026-07-28)

**Diagnóstico exacto de por qué no persistía (verificado empíricamente, no asumido):**
- La migración real de `recibos` solo tenía `id`, `venta_id` (FK única a `ventas`), `created_at`, `updated_at`.
- El modelo `Recibo` usaba `SoftDeletes`, pero la migración **no tenía `deleted_at`** — el mismo bug que `BUG-09` (`MovimientoStock`), nunca detectado antes para este modelo porque nadie lo había leído hasta ahora. Confirmado con una consulta real: `SQLSTATE[HY000]: no such column: recibos.deleted_at`. Esto rompe **cualquier lectura** (`Recibo::count()`, `$venta->recibo`), no la escritura.
- `VentaController::store()` ya llamaba a `$venta->recibo()->create(['venta_id' => .., 'monto' => $total])`. Se confirmó que la fila **sí se insertaba** (`venta_id` es fillable y existe), pero **sin `monto`** — no estaba en `$fillable` (que en cambio declaraba `nro_recibo`, `fecha`, `metodo_pago`, `observacion`, `estado`, ninguno existente en la migración) ni era una columna real, así que Eloquent lo descartaba en el mass-assignment.
- **Efecto neto:** el recibo se creaba a medias (sin monto) y quedaba **ilegible para siempre** por el bug de `SoftDeletes` — cualquier vista o reporte que intentara leerlo habría lanzado un error 500 real.

**Corrección aplicada (solo persistencia, sin tocar lógica de negocio ni crear funcionalidades nuevas):**
- Migración `add_monto_to_recibos_table`: agrega `decimal('monto', 10, 2)` — la única columna que `VentaController::store()` ya necesitaba y nunca pudo guardar. No se agregaron `nro_recibo`/`fecha`/`metodo_pago`/`observacion`/`estado`: ningún código los usa hoy, agregarlos habría sido introducir campos sin funcionalidad real.
- `app/Models/Recibo.php`: se quitó `SoftDeletes` (mismo criterio que `BUG-09` — confirmado por búsqueda exhaustiva que nada llama a `delete()`/`restore()`/`withTrashed()` sobre este modelo); `$fillable` → `['venta_id', 'monto']`; cast `'fecha' => 'datetime'` (columna inexistente) → `'monto' => 'decimal:2'` (mismo patrón que `DetalleVenta::precio_unitario`).
- `VentaController::store()`: **sin cambios** — el `$venta->recibo()->create([...])` que ya existía pasa a persistir con éxito tal cual estaba escrito.

**Verificado:** `tests/Feature/ReciboPersistenciaTest.php` (nuevo, 3 casos): registrar una venta persiste el recibo con el monto correcto, `Venta::recibo()` es legible sin excepción, `Recibo::count()` ya no lanza el error de `SoftDeletes`. Suite completa: 231 passed, mismo único fallo preexistente no relacionado. Verificado además contra la base de datos real (`farmacia_tfg`, migración aplicada, prueba en una transacción revertida sin residuos): recibo creado, monto correcto, relación `Venta::recibo()` legible.

**`RF10`/`CU11` cerrado por completo (2026-07-28):** se implementó `ReciboController::show()`, la ruta `GET /recibos/{recibo}` y la vista `recibos/show.blade.php`, con el flujo "Ver recibo"/"Nueva venta" desde `ventas/index.blade.php` tras registrar una venta (sin redirección forzada). Se eliminó el botón "Imprimir recibo" de `ventas/create.blade.php` (confirmado como código muerto: no tenía ningún `addEventListener` ni llamaba a `window.print()`). Detalle completo, incluida la verificación manual contra MySQL real, en `docs/modulos.md` (sección "Módulo: Recibos").

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

### PEND-05 — Dashboard con Datos Reales ✅ RESUELTO (2026-07-14, sprint `UI-05`)

**Estado anterior:** la vista existía, el controlador calculaba algunos datos, pero el módulo era básico (sin alertas configurables, stock bajo con umbral hardcodeado).

**Resuelto por la migración del Dashboard al Design System (`UI-05`):**
- `InicioController::index()` fue reescrito para reutilizar exclusivamente lógica ya existente (scopes de modelo y el mismo SQL de `ReporteController`) en 4 bloques: qué pasó hoy (ventas/compras de hoy), alertas críticas (vencidos / próximos a vencer / stock bajo), últimos movimientos, accesos rápidos. Detalle completo en `docs/modulos.md` (sección "Módulo: Dashboard").
- Stock bajo ahora lee `Configuracion::obtener('stock_bajo_umbral', 30)`, la misma clave que usa el reporte — ya no hay umbral hardcodeado. La única fuente de verdad quedó unificada.
- Vencidos/próximos a vencer se muestran con badges de color (`danger`/`warn`), usando el mismo criterio ya validado en el reporte de vencimientos.
- Filtros por rango de fechas: **descartado deliberadamente** — no forma parte del alcance de un dashboard ("centro de acción diario"); quien necesite filtrar por fecha usa los Reportes.
- El `DashboardController` sin rutas sigue sin conectarse — se evaluó y se descartó explícitamente: `InicioController` ya cubre el mismo terreno con el patrón de reuso establecido en este proyecto, migrar el código de un controlador muerto habría ido en contra de esa disciplina. Sigue documentado como código en desuso (ver `docs/modulos.md`).

**Deuda técnica:** el bloque de stock bajo del dashboard repite la subquery de `ReporteController::stockBajo()` en vez de compartir un scope (`Producto::scopeConStockBajo()` pendiente de extracción) — ver nota junto al Reporte 3 en la sección `AUS-01` más abajo. No se extrajo en este sprint para no ampliar su alcance.

---

### PEND-06 — Ajustes de Stock sin Auditoría

**Estado:** `LoteController::bulkUpdate()` modifica el campo `stock` directamente sin crear `MovimientoStock`.

**Trabajo pendiente:**
- Agregar creación de `MovimientoStock` con `tipo='Entrada'` o `tipo='Salida'` y `motivo='Ajuste'` en `bulkUpdate()`.
- Esto permite tener el historial completo de todos los cambios de inventario.

---

### PEND-07 — Vistas de Lotes duplicadas/rotas (encontrado en auditoría `UI-05`/Productos, 2026-07-14)

**Estado:** No corregido — decisión explícita del usuario de dejarlo fuera del alcance de la migración de Productos.

- **`productos/lotes/index.blade.php`** (alcanzable vía `GET /productos/{producto}/lotes`, `LoteController::index()`): `<h1>` y `<p>` con `style="color:white"` — texto invisible sobre fondo claro. Usa `.h-top`, clase que no existe en `public/css/style.css`. Sin buscador ni acciones de crear/editar/eliminar en la UI, aunque el controlador las soporta.
- **`resources/views/lotes/index.blade.php`**: vista huérfana, más completa que la anterior, pero **sin ninguna ruta que la sirva** — no existe `Route::get('/lotes', ...)` en `routes/web.php`. El sidebar la referencia vía `Route::has('lotes.index')`, que siempre es `false`, por eso ese ítem del menú nunca aparece.
- El flujo real de edición de stock no usa ninguna de las dos: pasa por el modal "Editar stock" de `productos/index.blade.php` (`LoteController::bulkUpdate`).

**Trabajo pendiente:**
1. Confirmar que `resources/views/lotes/index.blade.php` no tenga ningún consumidor externo (enlaces directos, bookmarks, integraciones) antes de eliminarla.
2. Decidir el destino de `productos/lotes/index.blade.php`: ¿se corrige y se conserva como página independiente de gestión de lotes, o se elimina en favor del modal "Editar stock" que ya cubre el mismo flujo?
3. Corregir la nota desactualizada que existía en `docs/modulos.md` sobre una ruta `GET /lotes` "con bug" — esa ruta ya no existe en el código.

---

### PEND-08 — Errores por campo y preservación de datos en formularios dinámicos ✅ RESUELTO (2026-07-24)

**Problema original:** `compras/create.blade.php`, `ventas/create.blade.php` y el modal "Editar stock" de `productos/index.blade.php` arman sus filas con JavaScript y las serializan a inputs ocultos recién al enviar. Al fallar la validación, ninguna de las tres repoblaba la tabla desde `old()` — el usuario perdía todo lo cargado.

**Hallazgo que amplió el alcance durante la auditoría:** `CompraController::store()` y `VentaController::store()` usaban `abort(422, "mensaje")` para errores de **regla de negocio** (lote vencido, stock insuficiente) — a diferencia de los errores de `$request->validate()`. `abort()` no dispara `withInput()`/`withErrors()`: el usuario caía en una página de error genérica, sin volver al formulario en absoluto. Se resolvió como una corrección de arquitectura del flujo de validación completo, no solo como reconstrucción visual.

**Solución implementada:**
- **`app/Support/FormRecovery.php`** (nuevo): `items($key, $lookups)` (old() enriquecido con nombres resueltos vía Eloquent, ej. `producto_id_label`), `label($key, $modelClass)` (para campos de cabecera como `proveedor_id`/`cliente_id`), `fieldErrors()` (mismos mensajes que `$errors->messages()`, sin acoplar la vista a `$errors` directamente).
- **`CompraController`**: el `abort(422, ...)` de lote vencido → `throw ValidationException::withMessages(["items.$i.fecha_vencimiento" => "..."])`. `create()` prepara `oldItems`/`oldProveedorNombre`/`fieldErrors` con `FormRecovery`.
- **`VentaController`**: el `abort(422, ...)` de stock insuficiente (condición esperable) → `ValidationException` atada a `items.$i.cantidad`. El segundo `abort(422, ...)` ("problema al descontar por lotes") — **matemáticamente inalcanzable** en operación correcta, dado el `lockForUpdate()` ya tomado antes de validar `$stockTotal >= $cantidadSolicitada` — se reclasificó como excepción real (`\RuntimeException`, no error de formulario).
- **`LoteController::bulkUpdate()`**: envuelto en `DB::transaction()` (antes no lo estaba — una fila a mitad de la lista podía fallar dejando las anteriores ya guardadas); sus dos `back()->with('error', ...)` → `ValidationException` con `->redirectTo(route('productos.index', ['stock_error' => $producto->id]))`, ya que el modal de stock es compartido por todos los productos y hacía falta saber cuál era.
- **`ProductoController::index()`**: si recibe `?stock_error={id}`, resuelve ese producto (nombre + `old('lotes')`) independientemente de si está en la página/búsqueda actual.
- **Blade/JS (los 3 archivos):** una sola función por vista construye cada fila (`crearFilaCompra`/`crearFilaVenta`/`crearFilaLote`), reutilizada tanto para agregar filas interactivamente como para reconstruir desde `OLD_ITEMS`/`FIELD_ERRORS` (variables expuestas al JS en vez de que la vista dependa de `$errors->messages()` directo). Campos de cabecera (`proveedor_id`/`cliente_id`/`observacion`) resueltos con el patrón estático de `UI-04A` (`old()` + `@error()`).
- `@json(..., JSON_UNESCAPED_UNICODE)` en los 3 bloques nuevos — sin esto, los acentos llegaban como `á` al HTML (funciona igual en JS, pero se prefirió el texto plano por legibilidad del código fuente).

**Verificado:** 13 tests nuevos (`FormRecoveryTest`, `CompraStockRecoveryTest`, `VentaStockRecoveryTest`, `LoteStockRecoveryTest`) — incluye una prueba específica de que `bulkUpdate` ya no deja escrituras parciales. Suite completa: 182 passed, mismo único fallo preexistente no relacionado. Verificado además contra MySQL real (`tinker`, transacción revertida): el flujo completo de lote vencido lanza `ValidationException`, con el mensaje correcto y sin persistir la compra.

---

### PEND-09 — Extraer el código compartido de los buscadores a `<x-search-box>` (o mecanismo equivalente)

**Estado:** Diferido deliberadamente — decisión explícita del usuario al cerrar `UI-06` (2026-07-24).

`UI-06` unificó el HTML/CSS/JS de los 10 buscadores del sistema (5 "filtro": Productos/Clientes/Proveedores/Usuarios/Roles; 4 "selector": Compras producto/proveedor, Ventas producto/cliente), pero **cada vista sigue teniendo su propia copia** del JS de sugerencias (debounce, navegación por teclado, render de la lista, cierre al hacer clic afuera) — no hay ningún archivo ni componente compartido todavía. Ver `docs/design-system.md` §19.1 para el patrón exacto que hoy se repite 10 veces.

**Trabajo pendiente:** una vez que el patrón esté validado en uso real (sin errores reportados durante un tiempo razonable), extraer el JS reutilizable — evaluando en ese momento si conviene un archivo `public/js/buscador.js` compartido, un componente Blade `<x-search-box>`, o ambos. Debe soportar los dos modos ("filtro" que navega vía GET, "selector" que llena un campo oculto) sin forzar uno a comportarse como el otro, y sin perder las 3 excepciones ya documentadas (filtrado en vivo de Clientes, "crear nuevo" embebido de Compras, badge de disponibilidad de Ventas) — ninguna de las tres debe generalizarse al resto por accidente durante la extracción.

---

## Módulos Completamente Ausentes (no existe nada)

### AUS-01 — Reportes ✅ COMPLETADO (2026-07-10 a 2026-07-12) — 6/6 reportes

El seeder define el permiso `reportes.ver`. El módulo se está implementando de forma incremental, un reporte por sprint, reutilizando al máximo la lógica ya existente.

**Reportes mínimos esperados para una farmacia:**
- Ventas por período (diario, mensual, anual) con filtro por usuario y cliente. ✅ **implementado (2026-07-12)**
- Compras por período con filtro por proveedor. ✅ **implementado (2026-07-12)**
- Stock valorizado (stock actual × costo unitario por lote). ✅ **implementado (2026-07-10)**
- Productos con stock bajo (por debajo de un umbral). ✅ **implementado (2026-07-10)**
- Lotes próximos a vencer (por rango de fechas). ✅ **implementado (2026-07-10)**
- Historial de movimientos de stock por producto o lote. ✅ **implementado (2026-07-12)** — resuelve conjuntamente `AUS-02`, ver nota abajo.

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

**Deuda técnica anotada — resuelta parcialmente en `UI-05` (2026-07-14):** el bloque de stock bajo del dashboard (`InicioController`/`inicio.blade.php`) ya lee `Configuracion::obtener('stock_bajo_umbral', 30)`, la misma clave que este reporte — la fuente de verdad del umbral quedó unificada. Lo que **sigue** como deuda técnica: `InicioController` repite la misma subquery correlacionada (`COALESCE((select sum(stock) from lotes where...), 0)`) en vez de compartir un scope con este controlador. Extraer `Producto::scopeConStockBajo(int $umbral)` reutilizable por ambos queda pendiente, deliberadamente fuera de alcance de `UI-05` para no ampliarlo.

**Reporte 4 — "Compras por período" (implementado):**
- **Ruta:** `GET /reportes/compras` (nombre `reportes.compras`).
- **Controlador:** `ReporteController::compras()`.
- **Vista:** `resources/views/reportes/compras.blade.php`. Igual que los reportes anteriores, solo funcional.
- **Nivel de agregación:** una fila por compra (no por detalle/ítem), igual que ya lista `compras/index.blade.php`. Columnas: fecha, proveedor, usuario que registró, cantidad de ítems y total de la compra.
- **Filtros:** rango de fechas (`desde`/`hasta`, ambos opcionales, sin período por defecto — decisión explícita del usuario: sin fechas se muestran **todas** las compras, igual que hoy `CompraController::index()`) y proveedor.
- **Orden:** fecha descendente (mismo criterio que `CompraController::index()->latest('fecha')`).
- **Reutilización:** relaciones `Compra::proveedor()`/`Compra::user()` sin cambios; `ReporteController::fechaValida()` (ya existente desde el Reporte 1) reutilizado desde el diseño para `desde`/`hasta`, evitando repetir el bug de fecha inválida que tuvimos que corregir después la vez pasada.
- **`Compra::getTotalAttribute()` no se reutilizó:** sirve para una compra individual (suma en PHP sobre `$this->detalles` cargados), pero en un listado paginado de muchas compras generaría N+1/cálculo en memoria innecesario. Se calculó en SQL en su lugar: el total por compra vía subquery correlacionada (`SUM(cantidad * costo_unitario)` sobre `detalles_compra`, mismo patrón que "Stock valorizado"), y el total general vía un `JOIN` + `SUM` agregado sobre todas las compras filtradas (no solo la página actual). No se usó `HAVING`/`GROUP BY` sobre un alias, aplicando desde el diseño la lección de portabilidad MySQL/SQLite aprendida en el Reporte 3.
- **Vacío de datos señalado, no corregido:** `Compra` tiene `estado` en `$fillable` pero la migración de `compras` no tiene esa columna (`ESQ-04`, ya documentado) — no existe concepto de "compra anulada" persistido, así que el reporte no aplica ningún filtro de estado (no hay nada que filtrar). `proveedor_id` nunca es nulo en `compras`, a diferencia de `ventas.cliente_id`.
- **Pruebas:** `tests/Feature/ReporteComprasTest.php` (Pest, 16 casos).

**Reporte 5 — "Ventas por período" (implementado):**
- **Ruta:** `GET /reportes/ventas` (nombre `reportes.ventas`).
- **Controlador:** `ReporteController::ventas()`.
- **Vista:** `resources/views/reportes/ventas.blade.php`. Igual que los reportes anteriores, solo funcional.
- **Nivel de agregación:** una fila por venta (no por detalle/ítem), mismo criterio que "Compras por período" y que `ventas/index.blade.php`.
- **Columnas:** fecha, cliente (`—` si es venta a público general, mismo criterio que ya usa `ventas/index.blade.php` — nota: `docs/modulos.md` dice "Público general" pero el código real muestra `—`; no se corrigió, fuera de alcance), usuario que registró, estado (**informativo, sin filtro** — `PEND-03`/anulación de ventas no existe todavía, así que no se adelantó esa funcionalidad, según instrucción explícita del usuario), unidades vendidas y total bruto.
- **Filtros:** rango de fechas (`desde`/`hasta`, ambos opcionales, sin período por defecto — mismo criterio que "Compras": sin fechas se muestran todas las ventas), usuario (`user_id`) y cliente (`cliente_id`). Sin filtro de estado.
- **Orden:** fecha descendente (mismo criterio que `VentaController::index()->latest('fecha_venta')`).
- **"Unidades vendidas" en vez de conteo de filas:** se usa `SUM(detalles_venta.cantidad)`, no `COUNT(*)` de `detalles_venta`. Un mismo producto puede generar varios `DetalleVenta` si el FIFO de `VentaController::store()` tomó stock de más de un lote — `COUNT(*)` habría mostrado un número inflado por ese detalle de implementación, no la cantidad real de unidades vendidas. Verificado con un caso de prueba dedicado (producto repartido en 2 lotes dentro de la misma venta).
- **`Venta::getTotalAttribute()` no se reutilizó**, por el mismo motivo que en "Compras" (evitar cálculo en PHP en un listado paginado) y por consistencia con el resto del módulo: total por venta y total general calculados en SQL, sin `HAVING`/`GROUP BY` sobre alias.
- **Limitación de datos — descuento no persistido en `detalles_venta` (analizada en detalle, no corregida por instrucción explícita):** `VentaController::store()` calcula un total con descuento restado y sigue sin persistirlo en `detalles_venta` (esa tabla no tiene columna `descuento`). El total de este reporte es, por lo tanto, el **bruto** reconstruido desde `detalles_venta` (`SUM(cantidad × precio_unitario)`), no el neto realmente cobrado en ventas con descuento. **No es una limitación nueva de este reporte** — `ventas/index.blade.php` ya muestra hoy el mismo cálculo bruto vía `Venta::getTotalAttribute()`. **Nota (2026-07-27):** desde que se corrigió `PEND-01`, el total neto (con descuento) sí queda persistido en `recibos.monto` — pero este reporte no lo usa (fuera de su alcance original, sin cambios de lógica de negocio); sería la fuente correcta si en el futuro se decide mostrar el neto en vez del bruto. Se agregó una nota visible en `reportes/ventas.blade.php` explicando esta limitación, y la columna se etiqueta "Total (bruto)".
- **Pruebas:** `tests/Feature/ReporteVentasTest.php` (Pest, 20 casos).

**Reporte 6 — "Historial de movimientos de stock" (implementado) — resuelve conjuntamente `AUS-01` y `AUS-02`:**
- **Ruta:** `GET /reportes/movimientos` (nombre `reportes.movimientos`).
- **Controlador:** `ReporteController::movimientos()`.
- **Vista:** `resources/views/reportes/movimientos.blade.php`. Igual que los reportes anteriores, solo funcional.
- **Bug previo necesario para desbloquear este reporte:** ver `BUG-09` más arriba — `MovimientoStock` no podía consultarse en absoluto (`ESQ-05`). Se corrigió (quitando `SoftDeletes` del modelo) como paso previo, no como parte del alcance funcional de este reporte.
- **`AUS-01` y `AUS-02` consolidados en una sola implementación:** ambos ítems del backlog pedían la misma funcionalidad (historial de `movimientos_stock` filtrable por producto/lote/tipo/fecha) — `AUS-02` se había escrito en la auditoría original, antes de que existiera el módulo de Reportes. Se implementó una sola vez, dentro de la estructura ya establecida (`ReporteController`, `/reportes`, `reportes.*`, permiso `reportes.ver`), en vez de construir una segunda pantalla duplicada. `AUS-02` se marca resuelto por esta misma implementación (ver su entrada más abajo).
- **Nivel de agregación:** una fila por movimiento (registro crudo de `movimientos_stock`), a diferencia de los otros 5 reportes (que agregan a nivel de transacción de negocio). Es el único reporte pensado como libro de auditoría, no como resumen — agregar destruiría la trazabilidad que pide `AUS-02`. También es el único de los 6 que no requiere ningún cálculo (`SUM`/`COUNT`), así que no hereda el riesgo de portabilidad MySQL/SQLite de los reportes 3-5.
- **Columnas:** fecha, producto (vía `lote.producto`), lote, tipo (badge Entrada/Salida), motivo, cantidad, referencia (texto libre).
- **Filtros:** producto, lote, tipo (Entrada/Salida) y rango de fechas (`desde`/`hasta`, sin período por defecto) — exactamente los 4 filtros pedidos entre `AUS-01` ("por producto o lote") y `AUS-02` ("lote, producto, tipo, fecha"). Sin filtro de `motivo` (no estaba en ninguno de los dos backlogs, decisión explícita del usuario de no ampliar el alcance).
- **No se usan `MovimientoStock::user()` ni `MovimientoStock::referencia()`:** ambas relaciones están rotas (referencian `user_id`/`referencia_tipo`/`referencia_id`, columnas que no existen en la migración). El campo `referencia` se muestra tal cual, como el texto libre que es. No se corrigieron estas relaciones (fuera de alcance).
- **`BUG-06` no se corrigió en este sprint** (se filtró por la columna `tipo` directamente para no depender de los scopes rotos) — corregido después, el 2026-07-27, ver su propia entrada más arriba.
- **Vacío de datos señalado, no corregido:** no hay columna `user_id` real en `movimientos_stock`, así que el reporte no puede mostrar quién generó cada movimiento. Además, hoy en datos reales solo existen movimientos con `motivo IN ('Compra','Venta')` — `Devolucion` y `Ajuste` son valores válidos en el enum pero nunca se generan (`DevolucionController` está vacío — `PEND-02` — y `LoteController::bulkUpdate()` no crea `MovimientoStock` — `PEND-06`). El reporte los soporta igual, por si se implementan más adelante.
- **Pruebas:** `tests/Feature/ReporteMovimientosTest.php` (Pest, 18 casos).

**Cambio transversal:** los helpers de prueba (`crearUsuarioDePrueba`, `crearProductoDePrueba`, `crearLoteDePrueba`) se movieron de `ReporteVencimientosTest.php` a `tests/Pest.php` para compartirlos entre los archivos de test de Reportes sin duplicar código (y sin colisión de nombres de función entre archivos, que habría roto la suite). Los helpers específicos de compras, ventas y movimientos quedaron locales en sus respectivos archivos de test — no hubo duplicación real entre ellos, así que centralizarlos habría sido una abstracción prematura.

Detalle completo de las seis sesiones en `docs/diario_desarrollo.md` (entradas 2026-07-10 y 2026-07-12). Módulo de Reportes (`AUS-01`) completo: 6/6 reportes implementados y verificados.

---

### AUS-02 — Vista de Movimientos de Stock ✅ RESUELTO POR CONSOLIDACIÓN (2026-07-12)

La tabla `movimientos_stock` se llena correctamente para todas las compras y ventas, pero no había ninguna pantalla en el sistema que permitiera consultarla.

**No se implementó como pantalla independiente.** Al analizar el Reporte 6 de `AUS-01` ("Historial de movimientos de stock por producto o lote"), se determinó que `AUS-01` y `AUS-02` describen exactamente la misma funcionalidad (mismo dato, mismos filtros: lote, producto, tipo, fecha) — `AUS-02` se había escrito en la auditoría original (2026-07-01), antes de que existiera el módulo de Reportes (diseñado el 2026-07-10). Construir las dos por separado habría duplicado consulta, filtros y vista sin ningún beneficio.

**Resuelto por consolidación, no por omisión:** la funcionalidad que pedía `AUS-02` (vista + filtros + ruta protegida) está implementada como el Reporte 6 de `AUS-01` — ver el detalle completo ahí (`GET /reportes/movimientos`, permiso `reportes.ver`). El requisito *"corregir previamente `BUG-06`"* no fue necesario: se filtra por la columna `tipo` directamente (correctamente poblada por `CompraController`/`VentaController`) en vez de depender de los scopes rotos — `BUG-06` sigue sin corregirse, deliberadamente fuera de alcance.

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
| **Alta** | ~~BUG-09~~ | ~~`MovimientoStock` no podía consultarse (`ESQ-05`)~~ ✅ 2026-07-12 |
| **Alta** | ~~BUG-02~~ | ~~No se puede editar el precio de un producto~~ ✅ 2026-07-27 (además, la creación estaba rota por el mismo motivo) |
| **Alta** | ~~BUG-04~~ | ~~Ventas pueden despachar lotes vencidos~~ ✅ 2026-07-07 |
| **Alta** | ~~BUG-05~~ | ~~Registro público de usuarios puede fallar~~ ✅ 2026-07-07 |
| **Alta** | ~~PEND-01~~ | ~~Recibos: persistencia rota + sin vista para consultarlos~~ ✅ Completo (persistencia 2026-07-27, `ReciboController::show()`/vista/flujo "Ver recibo" 2026-07-28) — `RF10`/`CU11` cerrados |
| **Alta** | PEND-02 | Implementar devoluciones completamente |
| **Media** | PEND-03 | Anulación de ventas |
| **Media** | ~~AUS-01~~ | ~~Módulo de reportes~~ ✅ 6/6 reportes — completado 2026-07-12 |
| **Media** | ~~AUS-02~~ | ~~Vista de movimientos de stock~~ ✅ resuelto por consolidación con `AUS-01` (Reporte 6) — 2026-07-12 |
| **Media** | ~~PEND-05~~ | ~~Dashboard con datos reales y alertas~~ ✅ resuelto por migración `UI-05` — 2026-07-14 |
| **Baja** | ~~BUG-06~~ | ~~Scopes de movimientos de stock~~ ✅ 2026-07-27 |
| **Baja** | PEND-04 | Anulación de compras |
| **Baja** | PEND-06 | Auditoría en ajustes de stock |
| **Baja** | PEND-07 | Vistas de lotes duplicadas/rotas (huérfana + bug visual) |
| **Baja** | ~~PEND-08~~ | ~~Errores por campo en formularios dinámicos (Compras/Ventas/Lotes)~~ ✅ 2026-07-24 |
| **Baja** | PEND-09 | Extraer JS compartido de buscadores a `<x-search-box>` u otro mecanismo |
| **Alta** | ~~BUG-10~~ | ~~`auth/register.blade.php` extendía `layouts.app` inexistente~~ ✅ 2026-07-21 |
| **Baja** | BUG-11 | Chip de estado de Ventas nunca refleja pagada/pendiente/anulada |
| **Crítica** | ~~BUG-12~~ | ~~Módulo de Roles sin protección de acceso real~~ ✅ 2026-07-28 |
| **Alta** | ~~BUG-13~~ | ~~Permisos `clientes.*` nunca sembrados (CU15 no ejecutable por Vendedor)~~ ✅ 2026-07-28 |
| **Baja** | AUS-03 | Gestión de permisos desde UI |
| **Baja** | AUS-04 | Enlace de compras en el sidebar |
| **Baja** | DATA-01 | Rol "Supervisor 1" sin `proveedors.ver`/`proveedors.eliminar` |
| **Baja** | ESQ-02 a ESQ-08 (excepto ~~ESQ-01~~ ✅, ~~ESQ-05~~ ✅) | Desajustes modelo/migración |

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

### Sprint 3 — Decisión técnica sobre `MovimientoStock` ✅ RESUELTO (2026-07-12)

- [x] **C-06 · ERR-03 — Revisar si `MovimientoStock` debe conservar `SoftDeletes`**
  El modelo usa el trait `SoftDeletes` pero la migración no tiene columna `deleted_at`.
  Esto hace que cualquier SELECT sobre la tabla falle con error SQL de columna inexistente.
  En su momento se registró como latente ("no hay UI que lea esta tabla"); dejó de serlo
  al implementar el Reporte 6 de `AUS-01` ("Historial de movimientos de stock"), que sí
  necesita leerla — el bloqueo real forzó a tomar esta decisión.

  Opciones evaluadas:
  - **Opción A — Conservar SoftDeletes:** migración nueva con `$table->softDeletes()`.
  - **Opción B — Eliminar el trait:** sin migración, hard deletes (ninguno usado hoy).

  **Decisión tomada: Opción B.** Se confirmó por búsqueda exhaustiva que ningún punto del
  código llama a `->delete()`/`->restore()`/`->withTrashed()` sobre `MovimientoStock`, y que
  el diseño ya acordado para `PEND-03` (anulación de ventas) contempla movimientos
  compensatorios, no eliminar los originales — coherente con tratar la tabla como un log
  de auditoría inmutable. Corregido y documentado como `BUG-09` (ver más arriba).
