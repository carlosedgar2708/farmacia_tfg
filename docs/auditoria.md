# Auditoría de Código — Farmacia Katy

Revisión completa del código fuente: controladores, modelos, migraciones, seeders, vistas y rutas.
Fecha: 2026-06-30. Sin modificaciones al código.

---

## Índice rápido

| Categoría | Ítems |
|---|---|
| [Errores](#1-errores) | 22 |
| [Código muerto](#2-código-muerto) | 9 |
| [TODOs y placeholders](#3-todos-y-placeholders) | 5 |
| [Funcionalidades incompletas](#4-funcionalidades-incompletas) | 11 |
| [Riesgos de seguridad](#5-riesgos-de-seguridad) | 5 |
| [Bugs potenciales](#6-bugs-potenciales) | 13 |

---

## 1. Errores

Problemas que causan o causarán fallos concretos en tiempo de ejecución.

### Controladores y acciones

- [ ] **ERR-01** — `app/Actions/Fortify/CreateNewUser.php`  
  El proceso de registro no establece `username`. La columna `username` en `users` tiene restricción `unique` y puede ser `NOT NULL`. El registro público desde `/register` puede lanzar una excepción de integridad de BD.

- [ ] **ERR-02** — `app/Http/Controllers/RolController.php` (`store()`, `update()`)  
  Nunca se llama a `$rol->permisos()->sync()`. La vista `rols/index.blade.php` envía `permisos[]` pero el controlador los ignora. La asignación de permisos a roles desde la interfaz no funciona.

- [ ] **ERR-03** — `app/Http/Controllers/LoteController.php` (`index()`) / `routes/web.php`  
  `GET /lotes` está registrado via `Route::resource('lotes', ...)`, pero `LoteController::index()` requiere route model binding `Producto $producto`. Como la URL `/lotes` no tiene el segmento `{producto}`, Laravel lanza `TypeError` al acceder.

- [ ] **ERR-04** — `app/Http/Controllers/PermisoController.php`  
  Todos los métodos (`index`, `store`, `show`, `update`, `destroy`) están completamente vacíos. Las rutas existen pero no hacen nada.

- [ ] **ERR-05** — `app/Http/Controllers/ReciboController.php`  
  Todos los métodos (7) están completamente vacíos.

- [ ] **ERR-06** — `app/Http/Controllers/DevolucionController.php`  
  Todos los métodos (7) están completamente vacíos.

- [ ] **ERR-07** — `routes/web.php`  
  `GET /` está registrada dos veces: `AuthController::welcome` e `InicioController::index`. Laravel usa la primera definición, pero la segunda queda muerta y puede generar confusión.

### Modelos

- [ ] **ERR-08** — `app/Models/MovimientoStock.php`  
  El modelo usa el trait `SoftDeletes` pero la migración `create_movimientos_stock_table` no tiene columna `deleted_at`. Cualquier llamada a `delete()` sobre un movimiento lanzará error SQL.

- [ ] **ERR-09** — `app/Models/Recibo.php`  
  Mismo problema: usa `SoftDeletes` pero la migración no tiene `deleted_at`.

- [ ] **ERR-10** — `app/Models/DetalleDevolucion.php`  
  Mismo problema: usa `SoftDeletes` pero la migración no tiene `deleted_at`.

- [ ] **ERR-11** — `app/Models/Venta.php` (`movimientosStock()`) / `app/Models/Compra.php` (`movimientosStock()`)  
  Ambos métodos filtran por columnas `referencia_id` y `referencia_tipo` que no existen en la migración `movimientos_stock`. La tabla solo tiene `referencia` (varchar). Llamar a estos métodos lanza error SQL.

- [ ] **ERR-12** — `app/Models/MovimientoStock.php` (`user()`)  
  La relación `user()` referencia la columna `user_id`, que no existe en la migración.

- [ ] **ERR-13** — `app/Models/MovimientoStock.php` (`referencia()`)  
  Relación `morphTo` configurada con `referencia_tipo` y `referencia_id`. Ninguna de estas columnas existe en la migración.

- [ ] **ERR-14** — `app/Models/Devolucion.php` (`getTotalRetornadoAttribute()`)  
  El accessor accede a `$d->precio_unitario` en cada detalle, pero ese campo no existe en la migración de `detalles_devolucion`.

- [ ] **ERR-15** — `app/Models/DetalleCompra.php` (`producto()`)  
  Relación `hasOneThrough` con parámetros `('id','id','lote_id','producto_id')`. Los primeros dos parámetros `'id','id'` son probablemente incorrectos; deberían ser las claves que vinculan `DetalleCompra → Lote → Producto`. La relación probablemente devuelve datos incorrectos o lanza error.

- [ ] **ERR-16** — `app/Models/Recibo.php`  
  `fillable` incluye `nro_recibo`, `fecha`, `metodo_pago`, `observacion`, `estado`. Ninguno existe en la migración `create_recibos_table` (que solo tiene `venta_id`). `VentaController::store()` intenta crear un recibo al finalizar la venta; el recibo no se guarda correctamente.

- [ ] **ERR-17** — `app/Models/Devolucion.php`  
  `fillable` incluye `venta_id`, `cliente_id`, `fecha_devolucion`, `observacion`, `estado`. Ninguno existe en la migración (que solo tiene `fecha`, `user_id`, `motivo`).

- [ ] **ERR-18** — `app/Models/DetalleDevolucion.php`  
  `fillable` incluye `producto_id`, `precio_unitario`, `razon`. Ninguno existe en la migración (que solo tiene `devolucion_id`, `lote_id`, `cantidad`).

- [ ] **ERR-19** — `app/Models/Compra.php`  
  `fillable` incluye `observacion` y `estado`. Ninguno existe en la migración `create_compras_table`.

- [ ] **ERR-20** — `app/Models/Cliente.php`  
  `casts` incluye `'activo' => 'boolean'`, pero la columna `activo` no existe en la migración `create_clientes_table`. Acceder al atributo devuelve `null`.

- [ ] **ERR-21** — `app/Models/Rol.php` / `app/Models/Permiso.php`  
  Las migraciones de ambas tablas tienen columna `deleted_at`, pero los modelos no usan el trait `SoftDeletes`. El soft delete no está disponible aunque la columna exista.

### Migraciones

- [ ] **ERR-22** — `database/migrations/0001_01_01_000000_create_users_table.php`  
  Línea 6 importa `use PHPUnit\Framework\Constraint\Constraint;`. Dependencia de test en un archivo de migración de producción. No causa error de ejecución, pero añade una dependencia innecesaria.

---

## 2. Código muerto

Código que existe en el repositorio pero nunca se ejecuta.

- [ ] **CD-01** — `app/Http/Controllers/DashboardController.php` — clase completa  
  El controlador tiene lógica más completa que `InicioController` (filtra por `estado='confirmada'`, tiene umbral de stock configurable), pero **ninguna ruta apunta a él**. Nunca se ejecuta.

- [ ] **CD-02** — `app/Http/Controllers/RolController.php` → `create()`, `show()`, `edit()`  
  Devuelven vistas `rols.create`, `rols.show`, `rols.edit` que probablemente no existen. No hay rutas `GET /rols/create` ni `GET /rols/{id}` registradas como named routes.

- [ ] **CD-03** — `app/Http/Controllers/ClienteController.php` → `create()`, `show()`, `edit()`  
  Métodos declarados pero completamente vacíos. No tienen rutas registradas.

- [ ] **CD-04** — `app/Http/Controllers/VentaController.php` → `lotesPorProducto()`  
  Ruta `GET /api/productos/{producto}/lotes` registrada, pero la vista `ventas/create.blade.php` carga todos los lotes en el JSON PHP inicial y nunca llama a este endpoint.

- [ ] **CD-05** — `app/Providers/AppServiceProvider.php`  
  Los métodos `register()` y `boot()` están completamente vacíos.

- [ ] **CD-06** — `routes/api.php`  
  Solo contiene un comentario. Completamente vacío.

- [ ] **CD-07** — `app/Actions/Fortify/UpdateUserProfileInformation.php`  
  Solo actualiza `name` y `email`. Ignora `apellido`, `telefono` y `username` que existen en la tabla `users` y en el formulario de edición de usuarios.

- [ ] **CD-08** — `database/migrations/0001_01_01_000000_create_users_table.php`  
  Import `use PHPUnit\Framework\Constraint\Constraint;` sin uso alguno.

- [ ] **CD-09** — `database/migrations/..._create_detalles_venta_table.php`  
  Línea comentada `// $table->softDeletes();` inmediatamente seguida de la llamada real `$table->softDeletes();`. La línea comentada es confusa y redundante.

---

## 3. TODOs y placeholders

Código con funcionalidad explícitamente pendiente o marcada como incompleta.

- [ ] **TODO-01** — `resources/views/inicio.blade.php`  
  Sección "Rendimiento del personal" contiene solo `<div class="chart-placeholder">Aquí va tu gráfico</div>`. Gráfico no implementado.

- [ ] **TODO-02** — `resources/views/inicio.blade.php`  
  Botón "Ver más" en la sección de lotes próximos a vencer apunta a `route('lotes.index')`, que es la ruta rota (ERR-03).

- [ ] **TODO-03** — `resources/views/ventas/create.blade.php`  
  El código JavaScript para impresión de ticket tiene el comentario `// ... resto del ticket igual que ya lo tienes ...`. La funcionalidad de impresión de ticket **no está implementada**.

- [ ] **TODO-04** — `app/Http/Controllers/VentaController.php` (`store()`)  
  Los campos de recibo enviados por el formulario (`tipo_comprobante`, `folio`, `monto_recibido`) son recibidos pero no se procesan ni se guardan. La lógica de recibo está parcialmente implementada.

- [ ] **TODO-05** — `app/Http/Controllers/ProveedorController.php`  
  No tiene método `create()`. La vista de compras tiene un modal para crear proveedores al vuelo via AJAX, pero no hay ruta `GET /proveedors/create` para el flujo normal.

---

## 4. Funcionalidades incompletas

Módulos que existen en el código pero no están terminados.

- [ ] **INC-01** — **Recibos**  
  Modelo y migración existen pero el esquema está incompleto (la tabla `recibos` solo tiene `venta_id`; los campos `nro_recibo`, `metodo_pago`, `monto` etc. no existen). `ReciboController` está vacío. Sin rutas ni vistas. `VentaController::store()` intenta crear el recibo pero falla silenciosamente.

- [ ] **INC-02** — **Devoluciones**  
  Modelos (`Devolucion`, `DetalleDevolucion`) y migraciones existen pero con graves desajustes de esquema. `DevolucionController` está vacío. Sin rutas ni vistas. No hay forma de registrar ni consultar devoluciones.

- [ ] **INC-03** — **Anulación de ventas**  
  El permiso `ventas.anular` está seedeado y el campo `estado` existe en la tabla `ventas`, pero no hay ruta, método ni lógica para anular una venta. La vista de listado muestra el estado pero no tiene acción de anulación.

- [ ] **INC-04** — **Anulación de compras**  
  El permiso `compras.anular` está seedeado, pero no hay ruta ni lógica. El campo `estado` que debería indicar si una compra está anulada está en el `fillable` del modelo pero no en la migración.

- [ ] **INC-05** — **Asignación de permisos a roles (UI)**  
  `RolController::store()` y `update()` no sincronizan permisos. Los checkboxes del modal de roles en `rols/index.blade.php` son decorativos: el servidor los ignora. Los permisos solo se asignan correctamente ejecutando el seeder. (Ver también ERR-02.)

- [ ] **INC-06** — **Gestión de permisos (CRUD)**  
  `PermisoController` existe pero está completamente vacío. No hay vistas para crear, editar ni eliminar permisos. Las rutas `/permiso` están registradas pero sin funcionalidad.

- [ ] **INC-07** — **Dashboard con datos completos**  
  `InicioController::index()` calcula datos básicos. `DashboardController` (código muerto — CD-01) tiene lógica más completa con filtros por estado y umbral de stock configurable. El dashboard activo es funcional pero básico — sin filtros por fecha, sin alertas configurables, sin gráficos.

- [ ] **INC-08** — **Ajuste de stock con auditoría**  
  `LoteController::bulkUpdate()` modifica el campo `stock` de los lotes directamente sin crear registros en `movimientos_stock`. Los ajustes manuales de inventario no quedan registrados en el historial.

- [ ] **INC-09** — **Módulo de Reportes**  
  El permiso `reportes.ver` está seedeado, pero no existe ningún controlador, ruta ni vista de reportes.

- [ ] **INC-10** — **Vista de Movimientos de Stock**  
  La tabla `movimientos_stock` se llena correctamente en compras y ventas, pero no hay ninguna pantalla para consultarla.

- [ ] **INC-11** — **Autenticación de dos factores (2FA)**  
  Las columnas `two_factor_secret`, `two_factor_recovery_codes` y `two_factor_confirmed_at` existen en `users` y Fortify tiene la infraestructura. No hay interfaz para activarlo ni para verificar el segundo factor.

---

## 5. Riesgos de seguridad

- [ ] **SEC-01** — `routes/web.php` — Rutas `/permiso` sin protección de autenticación  
  El grupo de rutas con prefijo `/permiso` está registrado **fuera** del grupo `middleware('auth')`. Cualquier usuario sin sesión activa puede enviar peticiones a estas rutas. Actualmente los controladores están vacíos, pero si se implementan, el vector de ataque ya existe.

- [ ] **SEC-02** — `database/seeders/UserSeeder.php` — Credenciales hardcodeadas  
  Contraseñas `admin123` y `vendedor123` están escritas en texto plano en el seeder. Si el seeder se ejecuta en producción, el sistema tendrá cuentas con contraseñas débiles y conocidas.

- [ ] **SEC-03** — `resources/views/ventas/create.blade.php` — Catálogo completo expuesto al cliente  
  `@json($productosForJs)` serializa todos los productos del sistema con sus precios de venta y lotes disponibles (incluyendo costos de adquisición, fechas de vencimiento, cantidades en stock). Esta información queda visible para cualquier usuario autenticado que inspeccione el código fuente de la página.

- [ ] **SEC-04** — `app/Actions/Fortify/CreateNewUser.php` — Registro público accesible  
  La ruta `GET /register` está accesible y muestra un formulario. El registro fallará con error de BD (por `username` faltante), pero la ruta de registro público está habilitada sin ningún mecanismo de control de acceso (sin invitación, sin aprobación de admin).

- [ ] **SEC-05** — `app/Http/Controllers/AuthController.php` (`welcome()`)  
  El método usa `redirect()->intended(route('inicio'))` en una página pública. El uso de `intended()` en una ruta no protegida puede generar redirecciones inesperadas si el usuario llegó desde una URL protegida anterior.

---

## 6. Bugs potenciales

Problemas que no causan error inmediato pero producirán comportamiento incorrecto bajo ciertas condiciones.

- [ ] **BUG-01** — `app/Http/Controllers/VentaController.php` (`store()`) — Lotes vencidos en ventas  
  La consulta FIFO filtra solo por `stock > 0`, sin verificar `fecha_vencimiento >= hoy`. Un lote con stock positivo y fecha de vencimiento pasada puede ser seleccionado y despachado al cliente.

- [ ] **BUG-02** — `app/Models/MovimientoStock.php` → `scopeSalidas()` y `scopeEntradas()`  
  `scopeSalidas()` filtra `cantidad < 0`, pero `VentaController::store()` guarda las salidas con `cantidad` **positiva**. El scope nunca devuelve resultados. `scopeEntradas()` filtra `cantidad > 0`, haciendo que ambos scopes devuelvan los mismos datos.

- [ ] **BUG-03** — `database/seeders/ProductoSeeder.php`  
  Usa `Producto::create()` en lugar de `updateOrCreate()`. Ejecutar `php artisan db:seed` por segunda vez fallará con violación de unicidad en la columna `codigo`.

- [ ] **BUG-04** — `database/seeders/LoteSeeder.php`  
  Usa `rand()` para generar cantidades de stock. Los resultados son no determinísticos: cada ejecución genera datos diferentes, dificultando pruebas reproducibles.

- [ ] **BUG-05** — `app/Http/Controllers/ProveedorController.php` (`index()`)  
  La variable se pasa a la vista como `['proveedores' => $proveedors]` (clave con 'e'). Si la vista usa `$proveedors` (sin 'e') puede no renderizar los proveedores correctamente.

- [ ] **BUG-06** — Permisos de proveedores — discrepancia de slug  
  El seeder define los slugs como `proveedores.*` (con 'e'), pero el middleware en las rutas verifica `proveedors.*` (sin 'e'). Los permisos seedeados no coinciden con los que verifican las rutas, haciendo que el sistema de permisos de proveedores no funcione para roles no-administradores.

- [ ] **BUG-07** — `app/Models/Venta.php` → `getTotalAttribute()`  
  Suma los detalles de la venta accediendo a la relación `detalles`. Si se lista un conjunto de ventas sin eager loading de detalles, se producen N+1 queries (una consulta por cada venta).

- [ ] **BUG-08** — `app/Http/Controllers/ProductoController.php` (`update()`)  
  La validación no incluye `precio_venta` y el campo no se actualiza al editar. El precio de venta no puede modificarse desde la interfaz una vez creado el producto.

- [ ] **BUG-09** — `app/Http/Controllers/LoteController.php` (`bulkUpdate()`)  
  Modifica el stock de los lotes directamente sin generar registros en `movimientos_stock`. Los ajustes manuales de inventario son silenciosos y no quedan auditados.

- [ ] **BUG-10** — `resources/views/rols/index.blade.php` — HTML inválido  
  Alrededor de la línea 155 hay celdas `<td>` anidadas (un `<td>` dentro de otro `<td>`). HTML inválido que los navegadores parsean de formas inconsistentes.

- [ ] **BUG-11** — `app/Http/Controllers/RolController.php` → `create()`, `show()`, `edit()`  
  Devuelven `view('rols.create')`, `view('rols.show')` y `view('rols.edit')`. Estas vistas no existen. Si se accede a estas rutas, Laravel lanza `InvalidArgumentException: View not found`.

- [ ] **BUG-12** — `app/Models/Cliente.php`  
  La línea `return $this->hasMany(Venta::class);;` tiene doble punto y coma. PHP lo acepta pero es sintaxis incorrecta que puede confundir parsers y analizadores estáticos.

- [ ] **BUG-13** — `resources/views/clientes/index.blade.php`  
  La vista incluye `<link rel="stylesheet">` directamente dentro de `@section('content')` en lugar de en el head. El CSS se inserta en mitad del body, lo que puede causar FOUC (Flash of Unstyled Content).

---

## Resumen de impacto

| Prioridad | Ítems |
|---|---|
| **Crítica** (falla inmediata al usar el módulo) | ERR-01, ERR-02, ERR-03, ERR-08, ERR-09, ERR-10, ERR-11, ERR-12, ERR-13, ERR-16, SEC-01 |
| **Alta** (funcionalidad clave no operativa) | ERR-04, ERR-05, ERR-06, ERR-14, ERR-15, ERR-17, ERR-18, INC-01, INC-02, INC-05, BUG-01, SEC-02 |
| **Media** (comportamiento incorrecto en casos específicos) | ERR-07, ERR-19, ERR-20, ERR-21, BUG-02, BUG-06, BUG-07, BUG-08, BUG-09, INC-03, INC-04 |
| **Baja** (limpieza y mejora) | CD-01 a CD-09, TODO-01 a TODO-05, BUG-03, BUG-04, BUG-10, BUG-11, BUG-12, BUG-13, ERR-22 |
