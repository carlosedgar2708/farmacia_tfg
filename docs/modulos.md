# Módulos del Sistema — Farmacia Katy

## Resumen de Módulos

| Módulo | Estado | Controlador | Rutas | Vistas |
|---|---|---|---|---|
| Autenticación | Completo | `AuthController`, Fortify | Fortify registra las propias | `auth/login`, `auth/register`, `auth/forgot-password`, `auth/reset-password` |
| Dashboard | Parcial | `InicioController` | `GET /inicio` | `inicio.blade.php` |
| Productos | Completo | `ProductoController` | `/productos` | `productos/index.blade.php` |
| Lotes / Stock | Completo | `LoteController` | `/productos/{producto}/lotes`, `/lotes` | `productos/lotes/index.blade.php` |
| Compras | Completo | `CompraController` | `/compras` | `compras/index.blade.php`, `compras/create.blade.php` |
| Ventas | Completo | `VentaController` | `/ventas` | `ventas/index.blade.php`, `ventas/create.blade.php` |
| Clientes | Completo | `ClienteController` | `/clientes` | `clientes/index.blade.php` |
| Proveedores | Completo | `ProveedorController` | `/proveedors` | `proveedors/index.blade.php` |
| Usuarios | Completo | `UserController` | `/users` | `users/index.blade.php` |
| Roles | Parcial | `RolController` | `/rols` | `rols/index.blade.php` |
| Permisos | Esqueleto | `PermisoController` | `/permiso` | (ninguna) |
| Recibos | Esqueleto | `ReciboController` | (ninguna) | (ninguna) |
| Devoluciones | Esqueleto | `DevolucionController` | (ninguna) | (ninguna) |
| Reportes | Ausente | (ninguno) | (ninguna) | (ninguna) |
| Movimientos de Stock (vista) | Ausente | (ninguno) | (ninguna) | (ninguna) |

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
Pantalla de inicio del sistema que muestra un resumen del estado actual de la farmacia.

### Rutas
- `GET /` → redirige a `inicio` (si autenticado) o a `login`
- `GET /inicio` → `InicioController::index()`

### Datos calculados
`InicioController::index()` consulta:
- Total de usuarios activos
- Total de roles
- Total de proveedores
- Total de productos
- Total de ventas del día
- Top 5 productos con menos stock (mediante JOIN con lotes)
- Top 5 productos más vendidos del mes (mediante JOIN con detalles_venta y ventas)
- Lotes próximos a vencer en los próximos 4 meses

### Estado
La vista `inicio.blade.php` existe con las tarjetas y tablas. Los datos se calculan en el controlador. El módulo es funcional pero básico — no tiene filtros por fecha, ni gráficos, ni alertas configurables.

**Nota:** Existe también un `DashboardController` con código similar pero más completo (filtra ventas por `estado='confirmada'`, añade umbral de stock). Sin embargo, **no tiene ninguna ruta registrada** y nunca se ejecuta. Es código preparatorio o en desuso.

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
- **Modal de creación**: formulario inline para crear un producto nuevo.
- **Modal de edición**: formulario inline para editar un producto existente.
- **Modal de stock ("Editar stock")**: permite editar los lotes del producto directamente, incluido agregar nuevos lotes y modificar cantidades existentes (usa `LoteController::bulkUpdate`).

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

**Independiente (con bug):**
- `GET /lotes` → `LoteController::index()` — falla en tiempo de ejecución porque el método requiere `Producto $producto` y no hay `{producto}` en la URL.

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
Tabla paginada con: fecha, proveedor, usuario que registró, total calculado (suma de cantidad × costo_unitario de los detalles), y un panel de detalles expandible por compra.

### Vista de Registro (`compras/create.blade.php`)
Formulario dinámico con JavaScript vanilla. Incluye:
- Autocomplete de productos y proveedores (sobre arrays JSON del backend).
- Modal para crear productos al vuelo (AJAX).
- Modal para crear proveedores al vuelo (AJAX).
- Tabla de ítems dinámica con agregar/eliminar filas.
- Cálculo automático de subtotales y total.

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
Tabla paginada con: fecha, cliente (o "Público general"), usuario, total calculado, estado, y panel de detalles expandible que muestra los productos vendidos, lotes y precios.

### Vista de Registro (`ventas/create.blade.php`)
El formulario más complejo del sistema:
- Autocomplete de productos con stock dinámico (basado en JSON precargado).
- Campo de descuento visible solo para administradores.
- Cálculo en tiempo real del total.
- Búsqueda de cliente con autocomplete y modal de creación rápida.
- Sección de recibo (tipo comprobante, folio, monto recibido, cambio) — no completamente funcional.

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

### Bug Crítico
`RolController::store()` y `update()` **NO sincronizan los permisos seleccionados**. Los checkboxes `name="permisos[]"` del modal se envían al servidor pero el controlador no llama a `$rol->permisos()->sync()`. La asignación de permisos a roles desde la interfaz no funciona. Los permisos solo quedan correctamente asignados si se ejecuta el seeder.

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
