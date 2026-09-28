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
| Configuración | Completo | `ConfiguracionController` | `/configuracion` | `configuracion/edit.blade.php` |
| Permisos | Esqueleto | `PermisoController` | `/permiso` | (ninguna) |
| Recibos | Completo (solo `show`) | `ReciboController` | `GET /recibos/{recibo}` | `recibos/show.blade.php` |
| Devoluciones | Esqueleto | `DevolucionController` | (ninguna) | (ninguna) |
| Reportes | Completo | `ReporteController` | `/reportes` | `reportes/index.blade.php` + 6 subvistas |
| Movimientos de Stock (vista) | Completo | `ReporteController::movimientos()` | `GET /reportes/movimientos` | `reportes/movimientos.blade.php` |

---

## Módulo: Autenticación

### Descripción
Gestiona el ciclo de vida de la sesión del usuario: acceso al sistema y cierre de sesión. La recuperación de contraseña **no está activa** (ver nota abajo).

### Tecnología
Laravel Fortify con vistas Blade personalizadas configuradas en `app/Providers/FortifyServiceProvider.php`.

### Flujo
- `GET /login` → formulario de login (`auth/login.blade.php`)
- `POST /login` → Fortify valida credenciales, crea sesión, redirige a `/inicio`
- `POST /logout` → destruye la sesión (ruta `logout`, formulario POST en el sidebar)
- `GET /register` → formulario de registro (`auth/register.blade.php`)
- `POST /register` → `Actions/Fortify/CreateNewUser.php` valida y crea el usuario

### Recuperación de contraseña — deshabilitada

`Features::resetPasswords()` **no está incluida** en el array `features` de `config/fortify.php` (a diferencia de `registration`, `updateProfileInformation`, `updatePasswords`, `twoFactorAuthentication`, que sí lo están). Por eso Fortify no registra ninguna ruta `password.request`/`password.email`/`password.reset`/`password.update` — confirmado con `route:list` — y ningún link de la UI apunta a `/forgot-password`. Las vistas `auth/forgot-password.blade.php` y `auth/reset-password.blade.php` existen en el proyecto pero son **inalcanzables** (código huérfano, análogo a `resources/views/lotes/index.blade.php`, ver `PEND-07`). Ambas tenían además el mismo bug de `BUG-10` (`@extends('layouts.app')`, layout inexistente); se corrigió (`@extends('app')`) para que no queden como bug latente si la feature se habilita en el futuro, pero no se activó la funcionalidad ni se migraron al Design System.

**Migrado al Design System en `UI-05` (2026-07-27):** decisión de `UI-02` (una columna vs. split-screen) resuelta a favor de **una columna** — se eliminó el layout ilustrado de `login.blade.php` (imagen, degradado, sombra `0 30px 80px`, radius 28px, `:root` local duplicando tokens ya globales) en favor de `<x-card class="login-card">` centrada, mismo criterio del resto del sistema. `register.blade.php` (ya usaba `.hero`/`.panel` decorativo) migrado al mismo patrón: `<x-card title="Crear cuenta" icon="ri-user-add-line">`, botones → `<x-button>`. Las reglas de centrado (`.login-shell`/`.login-card`) se agregaron una sola vez a `public/css/style.css` (§17) por ser compartidas entre ambas vistas — el resto del CSS específico de login (`.field`, `.remember`, `.form-head`) sigue local a esa vista, sin necesidad real de compartirse. Sin cambios en Fortify ni en `CreateNewUser.php`.

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

**Cierre de `RF10`/`CU11` (2026-07-28):** `VentaController::store()` captura el `id` del `Recibo` ya creado (sin tocar ningún cálculo de `$total`/FEFO/stock) y lo agrega al redirect: `->with('recibo_id', $reciboId)`. Detalle completo en la nueva sección "Módulo: Recibos" más abajo.

### Vista de Listado (`ventas/index.blade.php`)
Tabla paginada con: ID, fecha, cliente (o "—" si es público general), usuario que registró, chip de estado, total calculado (`Venta::getTotalAttribute()`) y una columna "Recibo" con acceso directo al recibo de esa fila (ver nota 2026-08-16 más abajo). No tiene panel de detalles expandible por venta (corrección a esta documentación, que lo mencionaba y no existe en el código actual — mismo tipo de inexactitud ya corregida antes en la documentación de Compras).

**Impresión de recibo por venta individual (2026-08-16):** cada fila tiene su propio botón `Imprimir recibo` (`<x-button icon="ri-printer-line">`), visible solo si esa venta tiene `recibo` cargado (`Venta::with([...,'recibo'])` en `VentaController::index()`), que enlaza a `route('recibos.show', $v->recibo->id)` — usa la relación real `Venta::recibo()` (`hasOne`), no `session('recibo_id')`. Esto es adicional al flash de sesión (punto siguiente), que se mantiene sin cambios como acceso rápido a la venta recién registrada. `colspan` del estado vacío ajustado de 6 a 7 por la columna nueva.

**Acciones tras registrar una venta (2026-07-28, `RF10`/`CU11`):** si `session('recibo_id')` está presente (lo setea `VentaController::store()` justo después de crear la venta), se muestra un bloque con `<x-button icon="ri-receipt-line">Ver recibo</x-button>` y `<x-button variant="secondary" icon="ri-add-line">Nueva venta</x-button>` debajo del banner de éxito — **sin redirigir automáticamente** al recibo, para no interrumpir el flujo de caja del vendedor (decisión explícita del usuario: el cajero normalmente sigue registrando ventas). Es un flash de un solo uso — desaparece en la siguiente navegación. Deliberadamente **no** se tocó el banner global de `app.blade.php` (que es genérico para todo el sistema); el bloque vive únicamente en esta vista.

**Chip de estado — hallazgo funcional (`BUG-11`, sin corregir):** la vista mapea `estado` a un color (`pagada`→verde, `pendiente`→ámbar, `anulada`→rojo, cualquier otro valor→neutral), pero `VentaController::store()` siempre guarda `'confirmada'`, que no está en ese mapa — hoy el chip **siempre** se muestra en su variante neutral. Ver `docs/pendientes.md`.

**Migrada al Design System en `UI-05` (2026-07-24):** `<div class="card panel">` → `<x-card>`, botón "Nueva venta" → `<x-button variant="primary" icon="ri-add-line">`, fila `@empty` → `<x-empty-state>` dentro de un `<td colspan="6">` (colspan ajustado a 7 el 2026-08-16, ver arriba). El chip de estado se dejó **sin migrar** — mismo criterio que el chip "Inyectable" de Productos: `x-badge` no tiene variante `neutral`, y mezclar `x-badge` para `ok`/`warn`/`bad` con un `<span>` crudo para `neutral` habría partido en dos el mismo conjunto de estados. Encabezado (`.toolbar`+`<h1 class="title">`), tabla, paginación y `VentaController` sin cambios. `.ventas-page` confirmada como clase huérfana (sin regla en `public/css/style.css`), documentada sin corregir. Pruebas: `tests/Feature/VentasIndexTest.php` (nuevo, 5 casos).

### Vista de Registro (`ventas/create.blade.php`)
El formulario más complejo del sistema:
- Autocomplete de productos con stock dinámico (basado en JSON precargado).
- Campo de descuento visible solo para administradores.
- Cálculo en tiempo real del total.
- Búsqueda de cliente con autocomplete y modal de creación rápida.
- Sección de recibo (tipo comprobante, folio, cantidad recibida, cambio) — visual únicamente, estos campos no se persisten en `Recibo` (no son parte de su `fillable`); no completamente funcional. Ya no incluye un checkbox "¿Emitir recibo?" (ver nota 2026-08-16 más abajo) — el recibo se genera siempre automáticamente.
- Reconstrucción de la tabla y errores por fila tras un fallo de validación/regla de negocio (`PEND-08`, ver `docs/pendientes.md`).

**Migrada al Design System en `UI-05` (2026-07-24):** los 3 `.card` (`.venta-left` sin encabezado propio, "Datos de la venta", "Realizar venta") → `<x-card>` (los dos últimos con `title`/`icon`). Todos los botones Blade (Agregar, Cancelar venta, Nuevo cliente, Público en general, Aceptar, y los del modal Nuevo cliente) → `<x-button>` con su variante. **Sin cambios:** autocomplete de productos/clientes, cálculos, FEFO (vive en `VentaController`, no en esta vista), `OLD_ITEMS`/`FIELD_ERRORS`, el botón de eliminar fila (generado por JS), los badges `.badge-ok`/`.badge-bad` del dropdown de sugerencias (también generados por JS), y los campos sin estilo propio (`select`/`input` de tipo de comprobante y folio, diferidos al sprint de "Formularios"). Se detectó `.row` como clase huérfana (sin ninguna regla en `public/css/style.css`) — documentada, no corregida por no ser parte del alcance. `VentaController` sin ningún cambio.

**Botón "Imprimir recibo" eliminado (2026-07-28, `RF10`/`CU11`):** este botón (`id="btnTicket"`) no tenía ningún `addEventListener` ni llamaba a `window.print()` — era código muerto, confirmado por auditoría antes de tocarlo. Además, este formulario se completa *antes* de que la venta (y su recibo) existan, así que no había forma correcta de que "imprimiera" nada real. Se eliminó el botón y su referencia JS muerta (`$btnTicket`, `hayItems()`). La impresión real ahora vive en `recibos/show.blade.php`, accesible después de registrar la venta vía el botón "Ver recibo" de `ventas/index.blade.php` (y, desde el 2026-08-16, también desde el botón "Imprimir recibo" de cualquier fila de `ventas/index.blade.php`).

**Eliminación del checkbox "¿Emitir recibo?" y campos numéricos vacíos (2026-08-16):** se quitó el checkbox `#emitirRecibo` (y su hidden `emitir_recibo`), confirmado como puramente cosmético — no estaba en las reglas de `$request->validate()` de `VentaController::store()` ni se usaba en ningún otro punto del controlador; solo mostraba/ocultaba el bloque de tipo de comprobante/folio vía `syncReciboUI()`, función eliminada junto con sus listeners por quedar huérfana. `#comprobantesBox` (tipo de comprobante/folio) se dejó **sin cambios**, siempre visible — es una funcionalidad aparte, ya documentada como incompleta, fuera del alcance de este cambio. Además, `#ctrl_desc` (Descuento) y `#recibido` (Cantidad recibida) dejaron de iniciar en `value="0.00"` — ahora aparecen vacíos con `placeholder="0.00"`, y el reset tras "Agregar" ya no repone `0.00`. `#ctrl_cantidad` conserva `value="1"` (default útil, no un valor a limpiar). El JS de cálculo (`recalcTotal()`, `agregarFila()`) no se modificó — ya era tolerante a campos vacíos vía `parseFloat(x.value||'0')`. Detalle completo, incluida la verificación manual, en `docs/diario_desarrollo.md` (entrada 2026-08-16).

---

## Módulo: Recibos

### Descripción
Consulta e impresión del comprobante de una venta ya registrada. Cierra `RF10`/`CU11`. Implementado con el alcance mínimo necesario — no es un módulo de gestión de recibos (sin listado, edición, eliminación, búsqueda, numeración automática ni estados).

### Rutas (dentro del grupo `auth`, sin permiso granular — mismo criterio que `ventas.*`)

| Método | Ruta | Acción |
|---|---|---|
| GET | `/recibos/{recibo}` | `ReciboController::show()` |

### Flujo completo (2026-07-28)

1. El vendedor registra una venta normalmente (`ventas/create.blade.php`) — sin cambios en FEFO, stock, descuentos ni validaciones.
2. `VentaController::store()` crea la `Venta` y el `Recibo` (ya resuelto en `PEND-01`), captura su `id` sin tocar ningún cálculo, y redirige a `ventas.index` con `->with('recibo_id', $reciboId)` además del flash de éxito habitual.
3. **Sin redirección forzada al recibo** — decisión explícita del usuario: el cajero normalmente sigue registrando ventas, no se lo interrumpe.
4. `ventas/index.blade.php` muestra, solo si `session('recibo_id')` está presente, los botones "Ver recibo" y "Nueva venta" debajo del banner de éxito. Es un flash de un solo uso.
5. `ReciboController::show()` carga `venta.cliente`, `venta.user`, `venta.detalles.producto` (evita N+1) y renderiza `recibos/show.blade.php`.
6. La vista muestra número de recibo (`Recibo::id`, no hay numeración propia — coincide con lo pedido), fecha, cliente (`—` si es público general, mismo criterio que el resto del sistema), vendedor, tabla de productos/cantidad/precio unitario/subtotal, y el total (`Recibo::monto`, ya persistido correctamente desde `PEND-01`, incluye descuento si aplicó). Reutiliza `<x-card>`, `<x-button>` y las clases `.table`/`.table-wrap`/`.money`/`.ta-right` ya existentes — sin CSS nuevo salvo el punto siguiente.
7. Botón "Imprimir" (`<x-button onclick="window.print()">`) — verificado que dispara `window.print()` (revisado el atributo `onclick` real, sin ejecutar el diálogo nativo dentro de la sesión de automatización del navegador, que lo habría bloqueado).
8. Acceso: además del flujo de sesión (paso 4), cualquier venta con recibo es accesible desde su propia fila en `ventas/index.blade.php` vía el botón "Imprimir recibo" (2026-08-16, ver "Módulo: Ventas").

### `@media print` (agregado localmente en `recibos/show.blade.php` vía `@push('styles')`)

**Estado hasta el 2026-07-28:** ocultaba `.sidebar`, `.main-top`, `.flash`, `footer` y `.print-actions`, y reseteaba el padding/margin de `.main` a 0, pero imprimía la tarjeta normal en formato de hoja Letter (sin `@page` propio).

**Ticket térmico (2026-08-16):** el `<x-card>` de la vista normal se envolvió en `<x-card class="recibo-normal">`, oculto también al imprimir (se agregó a la misma regla que ya ocultaba sidebar/topbar/botones). Se agregó un bloque nuevo, `.ticket-print` (mismos datos que la vista normal — número, fecha, cliente, vendedor, productos/cantidad/precio/subtotal, total; nada nuevo), oculto en pantalla (`display:none`) y visible solo dentro de `@media print`. El `@media print` ahora también define:
- `@page { size: 80mm auto; margin: 0 }` — sin margen de hoja, ancho de rollo térmico en vez de Letter.
- Un ancho de ticket controlado por la variable `--ticket-width: 72mm` (declarada en `:root`, fuera del media query). El propio CSS documenta cómo pasar a una impresora de 58mm: cambiar `--ticket-width` a `48mm` y el valor literal de `@page` a `"58mm auto"` — ambos a mano, porque `var()` dentro de `@page` no tiene soporte confiable entre navegadores/impresoras.
- `.ticket-print { max-width:100%; margin:0 auto; ... }` como resguardo: si el navegador o la impresora ignoran `@page` y usan Letter por defecto, el contenido del ticket sigue angosto y centrado en vez de estirarse a toda la hoja.
- Tipografía monoespaciada (`Courier New`), separadores punteados (`.t-sep`) y filas `cantidad × precio — subtotal` en vez de la tabla con bordes de la vista normal — más compacto y legible en papel de 58-80mm.

Verificado que los selectores `.sidebar`, `.main-top`, `.print-actions` (ya cubiertos por tests existentes) siguen presentes literalmente en el CSS pese a los cambios. Detalle de la verificación manual (impresión sin formato Letter, datos idénticos entre vista normal y ticket) en `docs/diario_desarrollo.md` (entrada 2026-08-16).

### Verificación

- `tests/Feature/RecibosTest.php` (nuevo, 6 casos): flujo completo (venta → recibo persistido → redirect a `ventas.index` → botones "Ver recibo"/"Nueva venta" presentes con el enlace correcto), datos del recibo (número, fecha, cliente, vendedor, producto, total), cliente nulo muestra "—", presencia del `@media print` y sus selectores, botón "Imprimir recibo" del formulario de venta confirmado eliminado, acceso sin sesión redirige a login.
- Suite completa: 237 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- **Verificación manual contra MySQL real** (vía Chrome, `php artisan serve` temporal): login real, 2 ventas registradas de punta a punta desde la interfaz — en ambas apareció el banner con "Ver recibo"/"Nueva venta", el recibo abierto coincidió exactamente con los datos de cada venta (producto, cantidad, precio, total), el segundo "Ver recibo" apuntó al recibo correcto (no al anterior), y volver a `/ventas` para registrar una segunda venta no tuvo ninguna fricción. Se confirmó el `onclick="window.print()"` del botón y la presencia real de los 6 selectores del `@media print` sin ejecutar el diálogo de impresión (habría bloqueado la sesión de automatización). Datos de prueba limpiados de la BD real al finalizar (ventas, detalles, recibos, movimientos revertidos; stock restaurado a sus valores originales).

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

**Migrada al Design System en `UI-05`:** `<section class="hero"><div class="panel">` → `<x-card>` (mismo criterio que Roles); alertas de sesión → `<x-alert>`; botón "Nuevo usuario" → `<x-button variant="primary">`; fila `@empty` → `<x-empty-state>`; botones de ambos modales (Guardar/Cancelar/Cerrar) → `<x-button>`. Buscador ya migrado en `UI-06`. **Sin cambios:** `UserController`, el modal (JS de crear/editar/ver), la tabla, `@error()` de `UI-04A`.

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

### Bug Crítico de seguridad — ✅ Corregido (ver `BUG-12` en `docs/pendientes.md`, 2026-07-28)
Detectado durante la auditoría de consistencia entre la memoria de tesis y el código: las rutas `rols.*` (`GET/POST/PUT/DELETE /rols`) estaban registradas vía `Route::resource(...)->only([...])` **sin ningún middleware `permiso:`**, a diferencia de todos los demás módulos administrativos (Productos, Compras, Clientes, Proveedores, Usuarios, Reportes). `RolController` tampoco tenía ninguna verificación interna (`esAdmin()`/`tienePermiso()`), y la vista no ocultaba ningún botón. Efecto real: cualquier usuario autenticado, incluido un Vendedor, podía gestionar roles y permisos — incluyendo asignarse a sí mismo cualquier permiso. Corregido aplicando el mismo patrón de middleware por ruta que ya usan Usuarios/Clientes/Proveedores (`permiso:rols.ver/crear/editar/eliminar`, slugs ya sembrados en `PermisoSeeder`). Verificado con la suite completa y manualmente contra la BD real con `admin@farmacia.com` (200) y `vendedor@farmacia.com` (403). El ocultamiento de botones según permiso en la vista y en el sidebar se dejó **fuera de este fix**, a la espera de un sprint de UX/RBAC que unifique ese patrón también en Productos/Clientes/Proveedores (hoy tampoco lo tienen). Detalle completo en `docs/pendientes.md` (`BUG-12`).

**Migrada al Design System en `UI-05` (2026-07-24):** el bloque decorativo `<div class="hero">` (fondo azul con `style="background:#1157c2;color:#fff"`) → `<x-card>`. Las clases `.hero`, `.grid`, `.shadow`, `.bubble`/`.b1`-`.b5` no tenían ninguna regla en `style.css` (verificado por grep): el efecto de "burbujas" decorativas nunca llegó a implementarse, solo el `style` inline producía algún efecto visual, así que se eliminó todo ese marcado muerto junto con el `<div class="shadow">` y los 5 `<span class="bubble">`. Botón "Nuevo rol" y botones del modal (Guardar/Cancelar) → `<x-button>`. Estado vacío (`.empty`) → `<x-empty-state>`. **Corregido como bug de marcado, no como cambio de diseño:** la columna de acciones tenía un `<td>` anidado dentro de otro `<td>` (HTML inválido, tolerado silenciosamente por los navegadores vía cierre implícito de etiquetas) — se corrigió a un único `<td>`. **Eliminado como limpieza de código muerto:** el `<script src="{{ asset('js/rols.js') }}">` y la línea `window.routesRolsStore = "..."` del `@push('scripts')`. El archivo `public/js/rols.js` **nunca existió** en el proyecto (ni el archivo ni el directorio `public/js/`), por lo que cada carga de `/rols` producía un 404 silencioso; toda la funcionalidad del modal (crear/editar/ver, autogeneración de slug, cierre al hacer click fuera) ya estaba implementada en el `<script>` inline de la misma vista, así que su eliminación no cambia ningún comportamiento. **Sin cambios:** `RolController` completo, el modal, el buscador (ya migrado en `UI-06`), la tabla y `.h-top` del encabezado (se quitó únicamente `color:#fff`, que habría dejado el texto invisible sobre el nuevo fondo blanco del `<x-card>`). Pruebas: `tests/Feature/RolesIndexTest.php` (nuevo, 8 casos) + `tests/Feature/RolesBuscadorTest.php` (`UI-06`, 3 casos).

---

## Módulo: Configuración

### Descripción
Permite al administrador ajustar parámetros globales del sistema almacenados en la tabla clave/valor `configuraciones` (ver `Configuracion::obtener()`/`establecer()`). Hoy solo expone `dias_alerta_vencimiento`.

### Rutas (prefijo `/configuracion`, solo admins vía `esAdmin()`)

| Método | Ruta | Acción |
|---|---|---|
| GET | `/configuracion` | `ConfiguracionController::edit()` |
| PUT | `/configuracion` | `ConfiguracionController::update()` |

**Migrada al Design System en `UI-05` (2026-07-27):** `<div class="hero"><div class="panel" style="background:#1157c2;color:#fff">` → `<x-card title="Configuración del sistema" icon="ri-settings-3-line">` (a diferencia de los listados administrativos, el encabezado decorativo aquí era un `<h1>` de uso único, sin la clase `page-title` compartida por el resto del sistema, así que se colapsó en el prop `title` del card en vez de dejarlo fuera). Alerta de éxito → `<x-alert variant="success">`. Botón "Guardar" → `<x-button variant="primary">`. **Sin cambios:** `ConfiguracionController`, el campo `dias_alerta_vencimiento` y su `@error()` (`UI-04A`).

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

El sidebar incluye entradas para: Dashboard, Productos, Proveedores, Clientes, Ventas, Reportes, Roles, Usuarios y Configuración (esta última solo si `esAdmin()`). **No hay entrada para Lotes** — el `@if (Route::has('lotes.index'))` de `app.blade.php` nunca es verdadero (no existe ninguna ruta con ese nombre; la gestión de lotes se hace vía el modal "Editar stock" de Productos, ver `PEND-07`), así que ese ítem nunca se renderiza pese a estar en el Blade. Tampoco hay entrada para Compras (debe accederse por URL directa o agregar el acceso al sidebar, ver `AUS-04`).
