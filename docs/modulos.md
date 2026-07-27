# Módulos del Sistema — Farmacia Katy

## Resumen de Módulos

| Módulo | Estado | Controlador | Rutas | Vistas |
|---|---|---|---|---|
| Autenticación | Completo | `AuthController`, Fortify | Fortify registra las propias | `auth/login`, `auth/register`, `auth/forgot-password`, `auth/reset-password` |
| Dashboard | Completo | `InicioController` | `GET /inicio` | `inicio.blade.php` |
| Productos | Completo | `ProductoController` | `/productos` | `productos/index.blade.php` |
| Lotes / Stock | Completo | `LoteController` | `/productos/{producto}/lotes`, `/lotes` | `productos/lotes/index.blade.php` |
| Compras | Completo | `CompraController` | `/compras` | `compras/index.blade.php`, `compras/create.blade.php` |
| Ventas | Completo | `VentaController` | `/ventas` | `ventas/index.blade.php`, `ventas/create.blade.php` |
| Clientes | Completo | `ClienteController` | `/clientes` | `clientes/index.blade.php` |
| Proveedores | Completo | `ProveedorController` | `/proveedors` | `proveedors/index.blade.php` |
| Usuarios | Completo | `UserController` | `/users` | `users/index.blade.php` |
| Roles | Completo | `RolController` | `/rols` | `rols/index.blade.php` |
| Permisos | Esqueleto | `PermisoController` | `/permiso` | (ninguna) |
| Recibos | Esqueleto | `ReciboController` | (ninguna) | (ninguna) |
| Devoluciones | Esqueleto | `DevolucionController` | (ninguna) | (ninguna) |
| Reportes | Completo | `ReporteController` | `/reportes` | `reportes/index.blade.php` + 6 subvistas |
| Movimientos de Stock (vista) | Completo | `ReporteController::movimientos()` | `GET /reportes/movimientos` | `reportes/movimientos.blade.php` |

---

## Módulo: Autenticación

### Descripción
Gestiona el ciclo de vida de la sesión del usuario: acceso al sistema, cierre de sesión y recuperación de contraseña.

### Tecnología
Laravel Fortify con vistas Blade personalizadas configuradas en `app/Providers/FortifyServiceProvider.php`.

### Flujo
- `GET /login` → formulario de login (`auth/login.blade.php`)
- `POST /login` → Fortify valida credenciales, crea sesión, redirige a `/inicio`
- `POST /logout` → destruye la sesión (ruta `logout`, formulario POST en el sidebar)
- `GET /register` → formulario de registro (`auth/register.blade.php`)
- `POST /register` → `Actions/Fortify/CreateNewUser.php` valida y crea el usuario
- `GET /forgot-password` → formulario de recuperación
- `POST /forgot-password` → envía email de restablecimiento
- `GET /reset-password/{token}` → formulario para nueva contraseña
- `POST /reset-password` → `ResetUserPassword.php` actualiza la contraseña

### Limitaciones actuales
- El formulario de registro no incluye el campo `username`, que es requerido (unique) en la tabla `users`. El registro público puede fallar.
- 2FA está preparado en BD y en Fortify, pero no hay interfaz de usuario para activarlo ni para verificarlo.

---

## Módulo: Dashboard

### Descripción
"Centro de acción diario": pantalla de inicio del sistema, pensada para responder de un vistazo qué pasó hoy, qué requiere atención inmediata y qué se hizo por última vez en el stock. Migrado al Design System en UI-05 (`docs/design-system.md`).

### Rutas
- `GET /` → `AuthController::welcome()` (redirige a `inicio` vía `redirect()->intended()`). **Nota:** existe una segunda definición de `GET /` apuntando a `InicioController::index()` (`routes/web.php`, con `->middleware('auth')`), pero por el orden de registro de rutas nunca se alcanza — es código muerto preexistente, no introducido en este sprint.
- `GET /inicio` → `InicioController::index()`, solo requiere `auth` (sin `permiso:` — accesible a cualquier usuario autenticado).

### Datos calculados (bloques de la vista, en orden)
`InicioController::index()` reutiliza en su totalidad lógica ya existente (scopes de modelo o el mismo SQL de `ReporteController`), sin reimplementar consultas:
1. **Qué pasó hoy** — `Venta::delDia()` / `Compra::delDia()` (scopes ya existentes/agregados en este sprint) para cantidad, más una suma vía `DetalleVenta`/`DetalleCompra` (mismo patrón `SUM(cantidad * precio)` que usan los reportes de ventas/compras) para el monto.
2. **Alertas críticas** — reutiliza `Producto::scopeConAlertaVencimiento()`, `Producto::loteVencidoRelevante()` y `Producto::loteProximoRelevante()` (la misma composición que ya usaba este controlador desde el sprint del widget de vencimientos), separando el resultado en dos listas (`vencidos`, `proximosAVencer`, top 5 cada una) en vez de una sola lista mezclada. Incluye también **stock bajo** (top 5), que repite la subquery de `ReporteController::stockBajo()` — deuda técnica documentada, ver `docs/pendientes.md`.
3. **Últimos movimientos** — misma base que `ReporteController::movimientos()`, sin filtros, `limit(10)` en vez de paginación.
4. **Accesos rápidos** — enlaces a Nueva venta, Registrar compra, Productos; "Registrar devolución" se muestra deshabilitado (`x-button :disabled="true"`) porque el módulo de devoluciones no existe (`PEND-02`).

### Vista
`inicio.blade.php` usa exclusivamente componentes del Design System (`x-card`, `x-badge`, `x-button`, `x-empty-state`) y clases globales ya existentes (`.info-list`, `.money`, `.two`, `.sb-section`); no tiene `<style>` embebido ni CSS propio. Las variantes de badge replican exactamente el criterio ya usado en Reportes (`danger` para vencido/sin stock, `warn` para próximo a vencer/stock bajo/salida, `ok` para entrada).

### Estado
Completo para el alcance definido en UI-05. Sin gráficos (se descartó el placeholder que tenía la versión anterior).

**Nota:** Existe también un `DashboardController` con código similar pero independiente (filtra ventas por `estado='confirmada'`, añade umbral de stock). Sigue **sin ninguna ruta registrada** y nunca se ejecuta. Es código preparatorio o en desuso.

---

## Módulo: Reportes

### Descripción
Seis reportes de solo lectura (vencimientos, stock valorizado, stock bajo, compras, ventas, movimientos de stock), cada uno con filtros propios y paginación. Detalle completo de cada reporte (filtros, ordenamiento, reglas de negocio, lógica reutilizada) en `docs/pendientes.md`, sección `AUS-01`.

### Rutas
Todas bajo `Route::middleware('permiso:reportes.ver')->prefix('reportes')->name('reportes.')`:
- `GET /reportes` → `ReporteController::index()` — índice de navegación.
- `GET /reportes/vencimientos`, `/stock-valorizado`, `/stock-bajo`, `/compras`, `/ventas`, `/movimientos` → un método por reporte en `ReporteController`.

### Vistas
- `reportes/index.blade.php` — migrada al Design System en `UI-05` (2026-07-14): `x-card` como contenedor único, seis `x-button variant="secondary"` (uno por reporte, con icono + ruta) en vez de la lista de `<a class="btn-outline">` con estilos inline que tenía antes. Decisión grid-vs-lista de `UI-02` resuelta a favor de lista — motivo: cada ítem es solo icono+título sin metadata adicional, y el patrón reutiliza exactamente el ya usado en "Accesos rápidos" del Dashboard, sin CSS nuevo.
- Las 6 subvistas (`vencimientos.blade.php`, `stock_valorizado.blade.php`, `stock_bajo.blade.php`, `compras.blade.php`, `ventas.blade.php`, `movimientos.blade.php`) **siguen sin migrar** — usan tablas y filtros con estilos inline, pendientes para el siguiente turno de `UI-05`.

### Estado
Completo funcionalmente (6/6 reportes). Índice migrado al Design System; subvistas pendientes de migración visual.

---

## Módulo: Productos

### Descripción
Gestión del catálogo de productos farmacéuticos. Cada producto tiene un código único, nombre, precio de venta y una bandera que indica si es inyectable.

### Rutas
Bajo el prefijo `/productos`, con el nombre base `productos.*`:

| Método | Ruta | Acción | Permiso requerido |
|---|---|---|---|
| GET | `/productos` | `ProductoController::index()` | `productos.ver` |
| POST | `/productos` | `ProductoController::store()` | `productos.crear` |
| PUT | `/productos/{producto}` | `ProductoController::update()` | `productos.editar` |
| DELETE | `/productos/{producto}` | `ProductoController::destroy()` | `productos.eliminar` |

### Funcionalidad de la Vista
La vista `productos/index.blade.php` concentra toda la interacción:
- **Tabla de productos**: código, nombre, tipo (inyectable/oral), precio, stock total (calculado via `withSum` de lotes), acciones.
- **Stock total**: calculado en el controlador con `withSum('lotes as stock_total', 'stock')`.
- **Buscador con autocomplete**: JS puro (`.search-wrap`/`.sugg`), sin dependencias del Design System, sin cambios en `UI-05`.
- **Modal de creación**: formulario inline para crear un producto nuevo.
- **Modal de edición**: formulario inline para editar un producto existente.
- **Modal de stock ("Editar stock")**: permite editar los lotes del producto directamente, incluido agregar nuevos lotes y modificar cantidades existentes (usa `LoteController::bulkUpdate`).

### Migración al Design System (`UI-05`, 2026-07-14)
La estructura visual se migró a componentes; el comportamiento no cambió:
- `<section class="card">` → `<x-card>` (mismo precedente que Dashboard e índice de Reportes: el encabezado de página, `<h1 class="page-title">`+subtítulo, queda dentro del card sin usar el prop `title`, para no degradarlo a `<h3>`).
- Botón "Nuevo producto", botón "Buscar", y todos los botones de ambos modales (crear/editar, stock) → `<x-button>` con el variant correspondiente, conservando `id`, `type` y `href="#"` donde existían (el `$attributes` bag de Blade pasa esos atributos sin necesidad de declararlos).
- Alertas de sesión (`alert alert-success`/`alert-danger`) → `<x-alert variant="success">`/`<x-alert variant="danger">`.
- Estado vacío (`<div class="empty">`, clase que no estaba definida en el CSS — bug invisible ya existente) → `<x-empty-state>`.
- **Deliberadamente sin cambios** (decisión explícita del usuario, para no ampliar el alcance de una migración puramente visual):
  - Acciones de fila (`.action.edit`/`.action.view.stock`/`.action.delete`) — patrón de "chips de acción compacta" sin equivalente todavía en `x-button`; anotado como posible componente futuro (`x-action` o variante nueva).
  - Chip `Inyectable: No` (`chip chip-neutral`) — `x-badge` no tiene variante `neutral`; se decidió no ampliar la API del componente por un solo caso de uso.
  - Campos de formulario (`<input>`, `<textarea>`, `<label>`, checkbox) — sin estilo propio más allá de un `outline` de foco genérico; `docs/design-system.md` §10 ya tiene la especificación escrita pero no implementada. Queda para un sprint dedicado a "Formularios", compartido por todo el sistema.
  - Confirmación de "Eliminar producto" — sigue usando `confirm()` nativo del navegador, no un modal, aunque `docs/design-system.md` §13 lo pide. No existe todavía un componente de modal de confirmación reutilizable (`x-modal` fue descartado explícitamente en `UI-04`).
- **Pruebas:** `tests/Feature/ProductosIndexTest.php` (nuevo, 10 casos) — verificación de renderizado (estado vacío, alertas, stock total, ids conservados para el JS), no cobertura de validaciones/CRUD (no existía antes de este sprint y queda fuera de su alcance).

### Limitación
`ProductoController::update()` no incluye `precio_venta` en las reglas de validación ni en los campos actualizados. No es posible cambiar el precio de un producto desde la UI de edición de producto.

---

## Módulo: Lotes / Stock

### Descripción
Gestión de los lotes farmacéuticos de cada producto. Cada lote tiene un número de lote, fecha de vencimiento, costo unitario y cantidad en stock.

### Rutas

**Anidadas bajo productos (principal):**

| Método | Ruta | Acción | Permiso |
|---|---|---|---|
| GET | `/productos/{producto}/lotes` | `LoteController::index()` | `productos.stock` |
| POST | `/productos/{producto}/lotes` | `LoteController::store()` | `productos.stock` |
| PUT | `/productos/{producto}/lotes/{lote}` | `LoteController::update()` | `productos.stock` |
| DELETE | `/productos/{producto}/lotes/{lote}` | `LoteController::destroy()` | `productos.stock` |
| POST | `/productos/{producto}/lotes/bulk` | `LoteController::bulkUpdate()` | `productos.stock` |

**Nota (corrección, 2026-07-14):** esta sección documentaba una ruta independiente `GET /lotes` "con bug" (`LoteController::index()` sin `{producto}` en la URL). Verificado durante la auditoría de `UI-05`/Productos: **esa ruta ya no existe** en `routes/web.php` — no hay ningún `Route::get('/lotes', ...)` registrado. El bug documentado ya no es reproducible porque la ruta que lo causaba fue eliminada en algún momento no registrado en esta bitácora.

### Hallazgos de la auditoría `UI-05` (2026-07-14, sin corregir — fuera de alcance de ese sprint)

- **`productos/lotes/index.blade.php` (alcanzable vía `productos.lotes.index`):** bug visual real — `<h1 style="color:white">` y `<p style="color:white">`, texto invisible sobre fondo claro. Usa además `.h-top` (clase que no existe en `public/css/style.css`) y `.panel`. Sin buscador ni acciones de crear/editar/eliminar en la UI pese a que `LoteController` las soporta (`store`, `update`, `destroy`). En la práctica el flujo real de edición de stock no pasa por esta vista: pasa por el modal "Editar stock" de `productos/index.blade.php` (`LoteController::bulkUpdate`).
- **`resources/views/lotes/index.blade.php` (vista huérfana):** más completa que la anterior (buscador, modal crear/editar, eliminar), apunta a rutas `productos.lotes.*`, pero **ninguna ruta la sirve** — no existe `Route::get('/lotes', ...)` ni ningún `name('lotes.index')` de nivel superior. El sidebar (`app.blade.php:58-59,129,147`) intenta enlazarla vía `Route::has('lotes.index')`, que siempre evalúa `false`, por lo que ese ítem del menú nunca se muestra. **No se elimina todavía** — decisión explícita del usuario: confirmar primero que no haya ningún flujo externo (enlaces directos, bookmarks, integraciones) que dependa de ella antes de borrarla.

### `bulkUpdate`
Recibe un array de lotes y los procesa uno por uno: si tienen `id` los actualiza, si no los crea. No genera `MovimientoStock` — los ajustes son silenciosos y no quedan en el historial de auditoría.

---

## Módulo: Compras

### Descripción
Registro de adquisiciones de mercancía a proveedores. Ver documento `flujo_compra.md` para el flujo detallado.

### Rutas

| Método | Ruta | Acción | Permiso |
|---|---|---|---|
| GET | `/compras` | `CompraController::index()` | `compras.ver` |
| GET | `/compras/create` | `CompraController::create()` | `compras.crear` |
| POST | `/compras` | `CompraController::store()` | `compras.crear` |

### Vista de Listado (`compras/index.blade.php`)
Tabla paginada con: ID, fecha, proveedor, usuario que registró, y "Total items" (suma de `cantidad` de los detalles — **no** es un total monetario, pese a que `Compra::getTotalAttribute()` existe y podría usarse; es una limitación/oportunidad de mejora no corregida en `UI-05`, ya que alterar columnas mostradas está fuera del alcance de una migración puramente visual). No tiene panel de detalles expandible por compra (corrección a esta documentación, que lo mencionaba y no existe en el código actual).

**Migrada al Design System en `UI-05` (2026-07-14):** `<div class="card">` → `<x-card>`, botón "Nueva compra" y alerta de éxito → `<x-button>`/`<x-alert>`, fila `@empty` → `<x-empty-state>` dentro de un `<td colspan="5">`. Encabezado (`<h2>Compras</h2>`) sin cambios, mismo criterio que Dashboard/Reportes/Productos. Tabla, paginación y `CompraController` sin ningún cambio. Pruebas: `tests/Feature/ComprasIndexTest.php` (nuevo, 7 casos).

### Vista de Registro (`compras/create.blade.php`)
Formulario dinámico con JavaScript vanilla. Incluye:
- Autocomplete de productos y proveedores (sobre arrays JSON del backend).
- Modal para crear productos al vuelo (AJAX).
- Modal para crear proveedores al vuelo (AJAX).
- Tabla de ítems dinámica con agregar/eliminar filas.
- Cálculo automático de subtotales y total.
- Reconstrucción de la tabla y errores por fila tras un fallo de validación/regla de negocio (`PEND-08`, ver `docs/pendientes.md`).

**Migrada al Design System en `UI-05` (2026-07-24):** los 3 `.card` (`.venta-left` + los 2 de `.venta-right`) → `<x-card title icon>`; el encabezado `<h3>🧾 Registrar compra</h3>` (emoji, sin `.card-title`) se resolvió con el prop `title`/`icon` de `x-card` (`ri-file-list-3-line`), quedando consistente con los otros dos cards de la misma vista. Botones "Agregar", "Cancelar compra", "Guardar compra" y los de ambos modales (Nuevo producto/Nuevo proveedor) → `<x-button>` con la variante correspondiente. **Sin cambios:** `.tabla-box.soft`, el autocomplete, los cálculos, `OLD_ITEMS`/`FIELD_ERRORS`, todo el JavaScript, y el botón de eliminar fila (generado dinámicamente por JS, fuera del alcance de una migración de Blade). `CompraController` sin ningún cambio.

---

## Módulo: Ventas

### Descripción
Registro de despacho de productos a clientes. Ver documento `flujo_venta.md` para el flujo detallado.

### Rutas

| Método | Ruta | Acción | Permiso |
|---|---|---|---|
| GET | `/ventas` | `VentaController::index()` | `auth` |
| GET | `/ventas/create` | `VentaController::create()` | `auth` |
| POST | `/ventas` | `VentaController::store()` | `auth` |
| GET | `/api/productos/{producto}/lotes` | `VentaController::lotesPorProducto()` | `auth` |

**Nota:** La ruta `lotesPorProducto` está registrada pero actualmente no se usa en las vistas (la vista carga todos los lotes en el JSON inicial de PHP).

### Vista de Listado (`ventas/index.blade.php`)
Tabla paginada con: ID, fecha, cliente (o "—" si es público general), usuario que registró, chip de estado, y total calculado (`Venta::getTotalAttribute()`). No tiene panel de detalles expandible por venta (corrección a esta documentación, que lo mencionaba y no existe en el código actual — mismo tipo de inexactitud ya corregida antes en la documentación de Compras).

**Chip de estado — hallazgo funcional (`BUG-11`, sin corregir):** la vista mapea `estado` a un color (`pagada`→verde, `pendiente`→ámbar, `anulada`→rojo, cualquier otro valor→neutral), pero `VentaController::store()` siempre guarda `'confirmada'`, que no está en ese mapa — hoy el chip **siempre** se muestra en su variante neutral. Ver `docs/pendientes.md`.

**Migrada al Design System en `UI-05` (2026-07-24):** `<div class="card panel">` → `<x-card>`, botón "Nueva venta" → `<x-button variant="primary" icon="ri-add-line">`, fila `@empty` → `<x-empty-state>` dentro de un `<td colspan="6">`. El chip de estado se dejó **sin migrar** — mismo criterio que el chip "Inyectable" de Productos: `x-badge` no tiene variante `neutral`, y mezclar `x-badge` para `ok`/`warn`/`bad` con un `<span>` crudo para `neutral` habría partido en dos el mismo conjunto de estados. Encabezado (`.toolbar`+`<h1 class="title">`), tabla, paginación y `VentaController` sin cambios. `.ventas-page` confirmada como clase huérfana (sin regla en `public/css/style.css`), documentada sin corregir. Pruebas: `tests/Feature/VentasIndexTest.php` (nuevo, 5 casos).

### Vista de Registro (`ventas/create.blade.php`)
El formulario más complejo del sistema:
- Autocomplete de productos con stock dinámico (basado en JSON precargado).
- Campo de descuento visible solo para administradores.
- Cálculo en tiempo real del total.
- Búsqueda de cliente con autocomplete y modal de creación rápida.
- Sección de recibo (tipo comprobante, folio, monto recibido, cambio) — no completamente funcional.
- Reconstrucción de la tabla y errores por fila tras un fallo de validación/regla de negocio (`PEND-08`, ver `docs/pendientes.md`).

**Migrada al Design System en `UI-05` (2026-07-24):** los 3 `.card` (`.venta-left` sin encabezado propio, "Datos de la venta", "Realizar venta") → `<x-card>` (los dos últimos con `title`/`icon`). Todos los botones Blade (Agregar, Cancelar venta, Nuevo cliente, Público en general, Aceptar, Imprimir recibo, y los del modal Nuevo cliente) → `<x-button>` con su variante. El botón "Imprimir recibo" usaba un emoji (🧾) en el texto en vez de un ícono Remix — se resolvió con `icon="ri-printer-line"`, mismo criterio que el encabezado de `compras/create.blade.php`. **Sin cambios:** autocomplete de productos/clientes, cálculos, FEFO (vive en `VentaController`, no en esta vista), `OLD_ITEMS`/`FIELD_ERRORS`, el botón de eliminar fila (generado por JS), los badges `.badge-ok`/`.badge-bad` del dropdown de sugerencias (también generados por JS), y los campos sin estilo propio (`select`/`input` de tipo de comprobante y folio, diferidos al sprint de "Formularios"). Se detectó `.row` como clase huérfana (sin ninguna regla en `public/css/style.css`) — documentada, no corregida por no ser parte del alcance. `VentaController` sin ningún cambio.

---

## Módulo: Clientes

### Descripción
Gestión del directorio de clientes de la farmacia.

### Rutas (prefijo `/clientes`, nombre `clientes.*`)

| Método | Ruta | Acción | Permiso |
|---|---|---|---|
| GET | `/clientes` | `ClienteController::index()` | `clientes.ver` |
| POST | `/clientes` | `ClienteController::store()` | `clientes.crear` |
| PUT | `/clientes/{cliente}` | `ClienteController::update()` | `clientes.editar` |
| DELETE | `/clientes/{cliente}` | `ClienteController::destroy()` | `clientes.eliminar` |

### Doble respuesta en `store()`
`ClienteController::store()` detecta si la petición viene de AJAX (`$request->expectsJson()`):
- **Si es AJAX** (desde el modal en ventas): devuelve `{id, nombre}` en JSON con código 201.
- **Si es formulario normal**: redirige de vuelta con flash de éxito.

Este patrón se repite en `ProveedorController::store()`.

**Migrada al Design System en `UI-05` (2026-07-24):** `<section class="panel">` → `<x-card>` (aceptando el cambio visual: `.panel` solo aplicaba `padding`, sin el borde/sombra que sí trae `.card`/`x-card` — se unificó con el resto de los listados administrativos). Botón "Nuevo cliente" y botones del modal (Cancelar/Guardar) → `<x-button>`. Estado vacío (`.empty`, clase sin definición en el CSS) → `<x-empty-state>`. Se eliminaron dos duplicaciones encontradas durante la auditoría: un `<link rel="stylesheet" href="...style.css">` redundante (el CSS global ya se carga en `app.blade.php`) y un bloque local `session('success')`/`session('error')` que duplicaba el banner global — mismo criterio de `UI-04A`, ahora extendido explícitamente a los flashes de sesión, no solo a `$errors->any()`. **Sin cambios:** el modal (mismo patrón que Productos/Proveedores/Roles/Usuarios), el buscador con sugerencias y filtrado client-side, las acciones de fila (`.action.edit`/`.action.delete`), `@error()` de `UI-04A`, y `ClienteController` completo. `<h1 class="h-top">` (clase inexistente en el CSS) y la tabla sin `.table-wrap`/`.table-soft` quedaron documentadas sin corregir, fuera del alcance de esta migración. Pruebas: `tests/Feature/ClientesIndexTest.php` (nuevo, 8 casos).

---

## Módulo: Proveedores

### Descripción
Gestión del directorio de proveedores de la farmacia.

### Rutas (prefijo `/proveedors`, nombre `proveedors.*`)

| Método | Ruta | Acción | Permiso |
|---|---|---|---|
| GET | `/proveedors` | `ProveedorController::index()` | `proveedors.ver` |
| POST | `/proveedors` | `ProveedorController::store()` | `proveedors.crear` |
| PUT | `/proveedors/{proveedor}` | `ProveedorController::update()` | `proveedors.editar` |
| DELETE | `/proveedors/{proveedor}` | `ProveedorController::destroy()` | `proveedors.eliminar` |

**Nota sobre slugs:** El seeder de permisos define los slugs como `proveedores.*` (con `e`), pero las rutas usan el middleware con `proveedors.*` (sin `e`). Esta discrepancia hace que los permisos de proveedor definidos en el seeder no coincidan con los que verifican las rutas.

**Migrada al Design System en `UI-05` (2026-07-24):** `<section class="panel">` → `<x-card>` (mismo criterio que Clientes). Botón "Nuevo proveedor", botón "Buscar" (antes un `<button>` sin ninguna clase) y botones del modal (Cancelar/Guardar) → `<x-button>`. Estado vacío → `<x-empty-state>`, corrigiendo de paso el typo "No hay proveedors registrados." → "No hay proveedores registrados.". Se eliminaron las mismas dos duplicaciones ya encontradas en Clientes: el `<link rel="stylesheet">` redundante y el bloque local `session('success')`/`session('error')`. **Corregido como bug de visibilidad, no como decisión de diseño:** el `<h1>`/`<p>` del encabezado tenían `style="color:white"` sobre un fondo claro — texto prácticamente invisible, mismo tipo de bug ya documentado en `PEND-07` para `productos/lotes/index.blade.php`. **Sin cambios:** el modal (mismo patrón que Productos/Clientes), la clase `.search` (huérfana, sin regla en el CSS, documentada sin corregir), la tabla (sin `.table-wrap`/`.table-soft`, fuera de alcance), las acciones de fila y `ProveedorController`. Pruebas: `tests/Feature/ProveedoresIndexTest.php` (nuevo, 9 casos).

---

## Módulo: Usuarios

### Descripción
Gestión de las cuentas de acceso al sistema.

### Rutas (prefijo `/users`, nombre `users.*`)

| Método | Ruta | Acción | Permiso |
|---|---|---|---|
| GET | `/users` | `UserController::index()` | `usuarios.ver` |
| POST | `/users` | `UserController::store()` | `usuarios.crear` |
| PUT | `/users/{user}` | `UserController::update()` | `usuarios.editar` |
| DELETE | `/users/{user}` | `UserController::destroy()` | `usuarios.eliminar` |

### Comportamientos especiales
- `store()`: genera `username` automáticamente como slug del email si no se proporciona.
- `update()`: si el campo `password` viene vacío, no actualiza la contraseña (la deja igual).
- `destroy()`: impide que el usuario elimine su propia cuenta.

---

## Módulo: Roles

### Descripción
Gestión de los roles del sistema RBAC.

### Rutas (recurso `rols`, nombre `rols.*`, solo `index`, `store`, `update`, `destroy`)

| Método | Ruta | Acción |
|---|---|---|
| GET | `/rols` | `RolController::index()` |
| POST | `/rols` | `RolController::store()` |
| PUT | `/rols/{rol}` | `RolController::update()` |
| DELETE | `/rols/{rol}` | `RolController::destroy()` |

La vista `rols/index.blade.php` presenta una tabla de roles con sus permisos asociados, y un modal para crear o editar roles con checkboxes de permisos.

### Bug Crítico — ✅ Corregido (ver `BUG-01` en `docs/pendientes.md`, 2026-07-07)
Esta sección indicaba que `RolController::store()` y `update()` no sincronizaban los permisos seleccionados. Al auditar el controlador durante la migración `UI-05` (2026-07-24) se confirmó que **ambos métodos ya llaman a `$rol->permisos()->sync($request->input('permisos', []))`** — el bug fue corregido el 2026-07-07 (`BUG-01`, marcado como tal en `docs/pendientes.md`) pero esta sección de `modulos.md` nunca se había actualizado para reflejarlo. Se corrige acá esa desactualización.

**Migrada al Design System en `UI-05` (2026-07-24):** el bloque decorativo `<div class="hero">` (fondo azul con `style="background:#1157c2;color:#fff"`) → `<x-card>`. Las clases `.hero`, `.grid`, `.shadow`, `.bubble`/`.b1`-`.b5` no tenían ninguna regla en `style.css` (verificado por grep): el efecto de "burbujas" decorativas nunca llegó a implementarse, solo el `style` inline producía algún efecto visual, así que se eliminó todo ese marcado muerto junto con el `<div class="shadow">` y los 5 `<span class="bubble">`. Botón "Nuevo rol" y botones del modal (Guardar/Cancelar) → `<x-button>`. Estado vacío (`.empty`) → `<x-empty-state>`. **Corregido como bug de marcado, no como cambio de diseño:** la columna de acciones tenía un `<td>` anidado dentro de otro `<td>` (HTML inválido, tolerado silenciosamente por los navegadores vía cierre implícito de etiquetas) — se corrigió a un único `<td>`. **Eliminado como limpieza de código muerto:** el `<script src="{{ asset('js/rols.js') }}">` y la línea `window.routesRolsStore = "..."` del `@push('scripts')`. El archivo `public/js/rols.js` **nunca existió** en el proyecto (ni el archivo ni el directorio `public/js/`), por lo que cada carga de `/rols` producía un 404 silencioso; toda la funcionalidad del modal (crear/editar/ver, autogeneración de slug, cierre al hacer click fuera) ya estaba implementada en el `<script>` inline de la misma vista, así que su eliminación no cambia ningún comportamiento. **Sin cambios:** `RolController` completo, el modal, el buscador (ya migrado en `UI-06`), la tabla y `.h-top` del encabezado (se quitó únicamente `color:#fff`, que habría dejado el texto invisible sobre el nuevo fondo blanco del `<x-card>`). Pruebas: `tests/Feature/RolesIndexTest.php` (nuevo, 8 casos) + `tests/Feature/RolesBuscadorTest.php` (`UI-06`, 3 casos).

---

## Módulo: Permisos

### Descripción
Gestión del catálogo de permisos disponibles en el sistema.

### Rutas (prefijo `/permiso`, sin protección `auth`)

| Método | Ruta | Nombre |
|---|---|---|
| GET | `/permiso` | `mostrar.permiso` |
| POST | `/permiso` | `crear.permiso` |
| PATCH | `/permiso` | `editar.permiso` |
| DELETE | `/permiso` | `eliminar.permiso` |

### Estado
`PermisoController` tiene declarados los métodos `index()`, `store()`, `update()` y `destroy()`, pero todos están **completamente vacíos**. No existe ninguna vista para permisos. Las rutas están registradas pero sin lógica ni protección de autenticación.

---

## Módulos Esqueleto (sin implementar)

### Recibos
- **Modelo**: `Recibo` — existe, con relación a `Venta`.
- **Migración**: existe, pero solo tiene `venta_id` (los campos del modelo como `nro_recibo`, `metodo_pago` no están en la BD).
- **Controlador**: `ReciboController` — existe, todos los métodos vacíos.
- **Rutas**: ninguna registrada.
- **Vistas**: ninguna.
- **Vinculación parcial**: `VentaController::store()` intenta crear un recibo al finalizar la venta, pero falla silenciosamente por el desajuste entre el modelo y la migración.

### Devoluciones
- **Modelos**: `Devolucion` y `DetalleDevolucion` — existen.
- **Migraciones**: existen, pero la mayoría de campos del `fillable` no están en las tablas.
- **Controlador**: `DevolucionController` — existe, todos los métodos vacíos.
- **Rutas**: ninguna registrada.
- **Vistas**: ninguna.
- **Efecto**: no hay forma de registrar ni visualizar devoluciones desde el sistema.

---

## Layout y Navegación

El sidebar de `app.blade.php` muestra los accesos a módulos condicionalmente usando `Route::has('nombre.index')`. Esto significa que si en algún momento una ruta no está registrada, su entrada desaparece del menú sin error.

El sidebar incluye entradas para: Dashboard, Productos, Lotes, Proveedores, Clientes, Ventas, Roles y Usuarios. No hay entradas para Compras (debe accederse por URL directa o agregar el acceso al sidebar).
