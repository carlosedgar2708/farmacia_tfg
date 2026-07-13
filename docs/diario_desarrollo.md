# Diario de Desarrollo — Farmacia Katy

Registro cronológico de las sesiones de trabajo del proyecto.
Cada entrada documenta qué se hizo, qué decisiones se tomaron y cuál es el estado al cierre de la sesión.

---

## 2026-06-24

### Actividades realizadas

- **Análisis completo de la arquitectura.**
  Se revisó el código fuente íntegro del proyecto: controladores, modelos, migraciones, seeders, rutas, vistas y providers. Se identificaron los módulos existentes, las relaciones entre tablas y los flujos principales del sistema.

- **Creación de la documentación técnica.**
  Se generó la carpeta `docs/` con los siguientes archivos:
  - `arquitectura.md` — objetivo del sistema, stack, RBAC, layout y flujo de peticiones.
  - `base_de_datos.md` — todas las tablas, columnas, relaciones y desajustes modelo/migración.
  - `flujo_compra.md` — flujo detallado del registro de una compra.
  - `flujo_venta.md` — flujo detallado del registro de una venta (FIFO, descuentos, stock).
  - `modulos.md` — estado y descripción de cada módulo, rutas, permisos y vistas.
  - `pendientes.md` — bugs confirmados, desajustes de esquema, funcionalidades incompletas y módulos ausentes.

- **Auditoría del proyecto.**
  Se realizó una revisión estática completa del código. Se generó `docs/auditoria.md` con 76 ítems distribuidos en seis categorías: errores, código muerto, TODOs, funcionalidades incompletas, riesgos de seguridad y bugs potenciales.

- **Validación de los 12 errores detectados.**
  Se leyó cada archivo implicado y se verificó cada error individualmente. Resultado: los 12 errores clasificados en la categoría "Errores" son reales. Ninguno resultó falso positivo. Se documentó el mecanismo exacto de fallo y la distinción entre errores duros, silenciosos y latentes.

- **Clasificación por prioridades.**
  Se analizó el impacto de cada error en el contexto de una tesis universitaria (funcionalidad visible, riesgo en demo, coherencia con el diseño documentado) y se definieron cuatro niveles:
  - **Crítico** — 6 items que rompen funcionalidades activas o crean brechas de seguridad.
  - **Importante** — 7 items que afectan la corrección del sistema antes de la entrega.
  - **Opcional** — 4 items de calidad de código.
  - **No corregir todavía** — 3 grupos que pertenecen a módulos incompletos pendientes de diseño.

- **Definición del plan de corrección.**
  Se estableció un plan de tres sprints, registrado en `docs/pendientes.md` bajo la sección "Próximos pasos":
  - Sprint 1: ERR-12 (permisos en roles), ERR-01 (ruta `/lotes`), ERR-11 (registro de usuarios).
  - Sprint 2: SEC-01 (rutas de permisos sin auth), BUG-04 (slug mismatch de proveedores).
  - Sprint 3: Decisión técnica sobre `MovimientoStock` y `SoftDeletes`.

### Decisiones tomadas

- La documentación en `docs/` se establece como **fuente primaria de contexto** para sesiones futuras, antes de volver a analizar el código.
- Los módulos de Devoluciones y Recibos **no se corrigen de forma aislada**; se abordarán cuando se implementen completamente.
- Para `MovimientoStock`, la opción recomendada es **eliminar el trait `SoftDeletes`** (Opción B), ya que los movimientos son un log de auditoría inmutable. Pendiente de confirmar antes del Sprint 3.

### Estado al cierre

**Listo para comenzar el Sprint 1 en la próxima sesión.**

El código del sistema no fue modificado durante esta sesión. Todos los cambios son exclusivamente de documentación.

---

## 2026-07-07

### Actividades realizadas

- **Ejecución del Sprint 1** (errores críticos con impacto directo en la demo), continuando el plan registrado en `docs/pendientes.md`.

- **C-01 · BUG-01 — Sincronización de permisos en roles.**
  Se agregó `$rol->permisos()->sync($request->input('permisos', []))` en `RolController::store()` y `RolController::update()`, tras `Rol::create()` / `$rol->update()`. Los checkboxes `permisos[]` de la vista de roles ahora se guardan correctamente.

- **C-02 · BUG-03 — Ruta `/lotes` con error 500.**
  Se eliminó `Route::resource('lotes', LoteController::class)` de `routes/web.php`. Las rutas de lotes funcionales siguen intactas bajo el prefijo `productos/{producto}/lotes`.

- **C-03 · BUG-05 — Registro público de usuarios.**
  Se modificó `app/Actions/Fortify/CreateNewUser.php` para generar `username` como slug de la parte local del email, con sufijo aleatorio en caso de colisión, replicando el criterio ya usado en `UserController::store()`.

### Alcance

Se modificaron únicamente los tres archivos indicados en el plan (`RolController.php`, `routes/web.php`, `CreateNewUser.php`). No se realizaron refactors adicionales, cambios de estilo, ni modificaciones de esquema o vistas.

### Efecto secundario detectado tras el Sprint 1

Al eliminar `Route::resource('lotes', ...)` (C-02), el dashboard (`resources/views/inicio.blade.php`) quedó roto: el botón "Ver más" del widget "Próximos a vencer" usaba `route('lotes.index')`, ruta que ya no existe, y lanzaba `Route [lotes.index] not defined`.

Se analizaron tres opciones (redirigir a `productos.index`, crear una vista global nueva de lotes por vencer, o quitar el botón) y se optó por la primera: se cambió el enlace a `route('productos.index')`, siguiendo el mismo patrón que ya usa el widget "Productos con menos stock". No se creó ninguna ruta ni controlador nuevo. La opción de una vista dedicada a "lotes próximos a vencer" queda como trabajo futuro, asociada a `PEND-05` (dashboard con alertas) y `AUS-01` (reportes).

### Estado al cierre

Sprint 1 completado. `docs/pendientes.md` actualizado marcando BUG-01, BUG-03 y BUG-05 como corregidos. Pendiente: Sprint 2 (rutas de `/permiso` sin auth y mismatch de slugs `proveedores`/`proveedors`).

---

## 2026-07-07 (continuación) — Sistema de Control de Vencimientos (BUG-04)

Se decidió posponer el Sprint 2 para priorizar `BUG-04` (ventas pueden despachar lotes vencidos), por su impacto directo en una regla de negocio crítica de la farmacia.

### Análisis previo a la implementación

Se identificó el origen exacto del bug en `VentaController::store()` (consulta FIFO sin filtro de `fecha_vencimiento`) y en `VentaController::create()` (el stock mostrado al vendedor incluía lotes vencidos). Se confirmó que corregirlo no altera el orden FIFO existente, solo el conjunto de lotes candidatos.

### Cambio de alcance: de "excluir vencidos" a "sistema de control de vencimientos"

El usuario decidió no limitarse a excluir productos vencidos, sino construir un sistema más completo con alertas configurables. Se diseñó la arquitectura antes de escribir código (documentada primero en `docs/arquitectura.md` y `docs/base_de_datos.md`), con dos decisiones explícitas del usuario:
- Tabla de configuración **clave/valor** (no un singleton tipado), para admitir futuros ajustes sin nuevas migraciones.
- Columna `valor` de tipo **TEXT**.
- Edición restringida a administradores vía `esAdmin()`, sin permiso granular nuevo.

### Implementación

- **Migración** `create_configuraciones_table` (`clave` unique, `valor` TEXT nullable, `descripcion` TEXT nullable).
- **Modelo `Configuracion`** (`app/Models/Configuracion.php`): `obtener($clave, $default)` cacheado indefinidamente, `establecer($clave, $valor)` invalida el cache. Único punto de acceso a la tabla.
- **`ConfiguracionSeeder`** (registrado en `DatabaseSeeder`, antes de los demás): siembra `dias_alerta_vencimiento = 90`.
- **Scopes en `Lote`:** `vigentes()`, `vencidos()`, `proximosAVencer($dias)` — centralizan toda comparación de fechas de vencimiento.
- **`VentaController`:** `store()` y `create()` ahora usan `Lote::vigentes()`; un lote vencido nunca puede aparecer en la consulta FIFO ni en el stock mostrado al vendedor.
- **`InicioController`:** el widget "Próximos a vencer" del dashboard usa `Configuracion::obtener('dias_alerta_vencimiento', 90)` junto con los scopes `vencidos()`/`proximosAVencer()`, en vez del umbral hardcodeado de 4 meses.
- **`ConfiguracionController`** + vista `resources/views/configuracion/edit.blade.php`: permite al admin editar el umbral desde `/configuracion`. Enlace agregado al sidebar (`app.blade.php`), visible solo para administradores.

### Verificación realizada

- Se probaron los tres scopes de `Lote` con datos temporales (lote vencido, próximo a vencer, sin fecha) dentro de una transacción revertida — sin dejar rastros en la BD.
- Se simuló un flujo de venta completo contra un lote vencido y uno vigente: la venta se rechazó correctamente por "stock insuficiente" cuando el vigente no alcanzaba, y se completó tomando solo del lote vigente sin tocar el vencido.
- Se verificó `Configuracion::obtener()`/`establecer()` (lectura, valor por defecto, actualización, invalidación de cache).
- Se confirmó que `InicioController::index()` renderiza sin errores con la nueva lógica.
- Migración y seeder ejecutados sobre la base de datos real (`farmacia_tfg`).

### Documentación actualizada

`docs/arquitectura.md`, `docs/base_de_datos.md` y `docs/flujo_venta.md` (ejemplo de FIFO y consideraciones de integridad) se actualizaron para reflejar el diseño ya implementado. `docs/pendientes.md` marca `BUG-04` como corregido.

### Estado al cierre

`BUG-04` corregido e implementado junto con el sistema de configuración. Sprint 2 (rutas `/permiso` sin auth, mismatch de slugs de proveedores) sigue pendiente.

---

## 2026-07-07 (continuación) — Corrección del widget "Próximos a vencer" (nivel de agregación)

Tras la implementación anterior, se detectó un caso reproducible: un producto con un lote vencido (con stock) y otro vigente podía mostrarse en el dashboard con el estado del lote vigente ("Vence pronto" u "OK"), ignorando que tenía un lote vencido.

### Causa

`InicioController::index()` construía `$proximosVencer` a nivel de **lote** (top 5 lotes por fecha, sin `GROUP BY producto_id`). Como cada fila era un lote independiente, no existía ningún punto que combinara "el peor estado entre todos los lotes de un mismo producto". Además, el estado era binario (`vence_pronto` true/false) — no había una categoría distintiva `VENCIDO`.

### Corrección

Se centralizó la composición del estado por producto en `app/Models/Producto.php` (reutilizando exclusivamente los scopes ya existentes de `Lote`, sin duplicar comparaciones de fecha):
- `scopeConAlertaVencimiento($dias)` — filtra productos con un lote vencido (`stock > 0`) o un lote dentro de `proximosAVencer($dias)`.
- `loteVencidoRelevante()` / `loteProximoRelevante($dias)` — devuelven el lote específico a mostrar.

`InicioController::index()` ahora consulta `Producto` (no `Lote`), determina `tiene_vencido` con `withExists()` sobre `Lote::vencidos()`, y ordena vencidos primero. `resources/views/inicio.blade.php` itera productos en vez de lotes y el badge distingue tres estados: `VENCIDO` (rojo), `Vence pronto` (rojo) y ausencia (productos sin alerta ya no aparecen en este widget, que ahora es estrictamente de alertas).

### Verificación

Se probó con datos temporales (transacción revertida): producto con lote vencido+vigente → aparece como `VENCIDO` con el lote correcto; producto con lote vencido pero `stock = 0` → no genera alerta; producto con vencimiento fuera del umbral configurado → no aparece. Confirmado también que los datos reales sembrados (ej. un lote de Loratadina ya vencido) se clasifican correctamente. Sin residuos de prueba en la base de datos.

### Documentación actualizada

`docs/arquitectura.md` (nueva subsección "Estado de vencimiento por producto") y `docs/pendientes.md` (nota de ajuste bajo BUG-04).

---

## 2026-07-10 — Verificación funcional del widget "Próximos a vencer" con pruebas automatizadas

Se retomó la corrección del widget (ver entrada anterior) para reemplazar la verificación manual por cobertura automatizada permanente, sin modificar código de producción.

### Revisión previa

Se releyó `docs/pendientes.md`, `InicioController`, `Producto` y `Lote` contra el `git diff` del working tree (cambios de la sesión anterior aún sin commitear). Se confirmó, regla por regla, que la implementación existente ya cumplía las 8 reglas de negocio acordadas (una entrada por producto, prioridad vencido > próximo, exclusión de productos sin alerta, reutilización exclusiva de los scopes de `Lote`, sin comparaciones de fecha nuevas en el controlador).

### Pruebas creadas

Se agregó `tests/Feature/DashboardVencimientoTest.php` (Pest, `RefreshDatabase`, corre contra SQLite en memoria — no toca la base de datos real `farmacia_tfg`). Cubre:
- Producto con lote vencido + lote próximo (ambos con stock) → una sola entrada, estado `vencido`, lote mostrado = el vencido.
- Producto con varios lotes próximos a vencer → se muestra únicamente el de fecha más cercana.
- Producto con lote vencido **sin stock** + lote vigente próximo → no se marca como `vencido` (se clasifica `proximo`).
- Producto sin alertas → no aparece en el widget.
- Cambio de `dias_alerta_vencimiento` vía `Configuracion::establecer()` → el resultado del dashboard cambia en la siguiente petición (confirma invalidación de cache).

### Resultado

Las 5 pruebas pasaron en el primer intento; no fue necesario corregir código. Se ejecutó además la suite completa: único fallo preexistente y no relacionado en `Tests\Feature\ExampleTest` (espera `200` en `GET /`, recibe `302` porque la ruta exige `auth`) — no se tocó, es independiente de este trabajo.

### Estado al cierre

Widget "Próximos a vencer" validado funcionalmente y con cobertura de regresión permanente. `docs/pendientes.md` actualizado para reflejar la verificación automatizada.

---

## 2026-07-10 (continuación) — Sprint 3: rutas `/permiso` y mismatch de slugs `proveedores`/`proveedors`

Se ejecutó el Sprint 2 documentado en `docs/pendientes.md` ("Errores críticos de seguridad": C-04 y C-05), retomando el plan sin volver a auditar el proyecto completo.

### Revisión previa

Se releyeron únicamente los archivos involucrados: `routes/web.php`, `database/seeders/PermisoSeeder.php` y `app/Http/Middleware/PermisoMiddleware.php`. Se confirmó el estado exacto descrito en `pendientes.md`: el grupo `/permiso` sin `middleware('auth')`, y `PermisoSeeder` sembrando `proveedores.*` mientras las rutas verifican `proveedors.*`.

Antes de tocar código se inspeccionó la base de datos real (`farmacia_tfg`) y se detectó que el rol no-admin **"Supervisor 1"** (id 3) ya tenía `proveedores.crear` y `proveedores.editar` asignados en `permiso_rol` — confirmando que un simple cambio de string en el seeder (que dispara `updateOrCreate` por slug) habría creado permisos duplicados con IDs nuevos y roto esa asignación existente en silencio.

### C-04 — Rutas `/permiso` sin auth

Se envolvió el grupo `Route::prefix('permiso')` en `Route::middleware('auth')->prefix('permiso')->group(...)` (`routes/web.php`).

### C-05 — Mismatch de slugs de proveedores

Se agregó a `PermisoSeeder::run()` un paso previo que renombra en sitio los 4 slugs `proveedores.*` → `proveedors.*` (`Permiso::where('slug', $antiguo)->update(['slug' => $nuevo])`), preservando los IDs, y luego se actualizó el array de siembra para usar ya los slugs correctos (necesario para instalaciones nuevas). Se ejecutó `php artisan db:seed --class=PermisoSeeder` contra la base real.

### Verificación

- `route:list --path=permiso`: las 4 rutas muestran `auth` en su pila de middleware.
- Petición real sin sesión a `/permiso` a través del kernel HTTP → `302` hacia `/login` (antes habría ejecutado el controlador vacío con `200`).
- Los 4 permisos de proveedores conservan sus IDs originales (9-12) con el slug ya corregido; no se crearon filas duplicadas (conteo de `Permiso` antes/después sin cambios en cantidad, solo en slug).
- `permiso_rol` intacto: mismas 6 filas para esos IDs (mismos `id`, `rol_id`, `permiso_id`), incluida la asignación del rol "Supervisor 1".
- `tienePermiso()` simulado con un usuario temporal en rol "Supervisor 1" (creado y revertido dentro de una transacción, sin residuos): `proveedors.crear`/`proveedors.editar` → `true`; `proveedors.ver` (no asignado) → `false`; el slug viejo `proveedores.crear` → `false` (ya no existe).

### Hallazgo colateral (fuera de alcance, no corregido)

Durante la verificación con un usuario sin roles se detectó que `User::esAdmin()` devuelve `true` para **cualquier** usuario del sistema, por un `orWhere('slug','admin')` sin agrupar que escapa el filtro `user_id` del pivote `rol_user` (precedencia `AND`/`OR` de SQL). Efecto: hoy `PermisoMiddleware` deja pasar a todos los usuarios sin importar sus permisos reales, para cualquier ruta protegida con `permiso:*`. No se corrigió por estar fuera del alcance del Sprint 3. Documentado en `docs/pendientes.md` bajo C-05, pendiente de que el usuario decida si se cataloga como nuevo bug y su prioridad.

### Documentación actualizada

`docs/pendientes.md`: BUG-07 marcado como corregido, Sprint 2 (C-04/C-05) marcado como completado con el detalle de la corrección y el hallazgo colateral.

### Estado al cierre

Sprint 3 (según la numeración de esta sesión con el usuario) completado: rutas `/permiso` protegidas y mismatch de slugs de proveedores corregido sin romper `permiso_rol`. Pendiente fuera de este sprint: bug de `esAdmin()` recién descubierto, y el Sprint 3 documentado internamente en `pendientes.md` (decisión sobre `SoftDeletes` en `MovimientoStock`), que conserva su propia numeración histórica y no se tocó.

---

## 2026-07-10 (continuación) — Corrección de `User::esAdmin()` (BUG-08)

Se retomó el hallazgo colateral detectado en la entrada anterior. El usuario pidió primero un análisis sin código, y solo tras su aprobación se aplicó la corrección.

### Análisis previo (sin código)

Se revisó exclusivamente `User::esAdmin()` y los métodos relacionados del mismo modelo (`hasRol()`, `tienePermiso()`, `permisos()`), sin volver a auditar el resto del proyecto. Con `toSql()` se confirmó la causa exacta: `$this->rols()` ya agrega `WHERE rol_user.user_id = ?` a la consulta de la relación; el `->where('nombre','Administrador')->orWhere('slug','admin')` posterior, al no estar agrupado, produce `(user_id = ? AND nombre = 'Administrador') OR slug = 'admin'` por precedencia SQL — el segundo término no depende del usuario que llama al método y, como el `JOIN` con `rol_user` no está filtrado por usuario, basta con que **cualquier** usuario del sistema tenga el rol admin asignado para que `esAdmin()` devuelva `true` para todos.

Se hizo un barrido de todos los call-sites de `esAdmin()` en el código (no solo `PermisoMiddleware`): `ConfiguracionController` (`/configuracion`), `VentaController` (flag de descuento), `app.blade.php` (enlace de sidebar) y `ventas/create.blade.php` (input de descuento + variable JS). Se confirmó con datos reales de la BD (`admin`, `vendedor`, `Xhaka`) y con un usuario temporal sin roles (transacción revertida) que el bug afecta a todos por igual. `hasRol()`, `tienePermiso()` y `permisos()` se revisaron y no comparten el patrón (no tienen `orWhere` sin agrupar).

El usuario aprobó el plan con alcance estricto: modificar únicamente `app/Models/User.php`, sin tocar middleware, controladores, seeders, migraciones, rutas ni vistas, y sin corregir en este sprint el hueco de datos que quedara expuesto (rol "Supervisor 1").

### Corrección aplicada

Único cambio en `app/Models/User.php`, método `esAdmin()`: se agrupó la condición dentro de un closure (`->where(fn($q) => $q->where(...)->orWhere(...))`) para que quede ANDada con el filtro de usuario del pivote, en vez de escaparlo.

### Verificación

Contra la base de datos real, sin mutar datos permanentes (usuarios temporales creados dentro de transacciones revertidas):
- `esAdmin()`: `admin` → `true`; `vendedor` y `Xhaka` → `false` (antes `true`); usuario sin roles → `false`.
- `PermisoMiddleware` simulado con los usuarios reales: vendedor bloqueado en `clientes.ver`/`proveedors.ver`, permitido en `productos.ver`/`ventas.crear`; admin con bypass total intacto.
- `ConfiguracionController::edit()`: vendedor → `403`; admin → renderiza la vista.
- Flag `esAdmin` devuelto por `VentaController::create()` (controla el campo de descuento): `false` para vendedor, `true` para admin.
- Suite completa (`php artisan test`): mismo resultado que antes del fix (6 passed, 1 fallo preexistente y no relacionado en `ExampleTest`). Sin regresiones.
- Se confirmó también, tal como se anticipó en el análisis, que el rol "Supervisor 1" queda con un hueco de datos visible (`proveedors.crear`/`editar` sin `proveedors.ver`/`eliminar`) — no se corrigió, según instrucción explícita del usuario.

### Documentación actualizada

`docs/pendientes.md`: nuevo `BUG-08` en el catálogo de bugs (corregido), la nota de "hallazgo no relacionado" bajo C-05 actualizada para apuntar a `BUG-08` ya resuelto, nueva sección "Problemas de Datos" con `DATA-01` (hueco de permisos del rol "Supervisor 1", sin corregir), y tabla de priorización actualizada.

### Estado al cierre

RBAC del sistema restaurado: `PermisoMiddleware`, `/configuracion` y el descuento de ventas vuelven a respetar los permisos reales de cada rol. Pendiente, fuera de alcance: `DATA-01` (hueco de datos del rol "Supervisor 1") y el Sprint 3 histórico de `pendientes.md` (decisión sobre `SoftDeletes` en `MovimientoStock`).

---

## 2026-07-10 (continuación) — Inicio del módulo de Reportes (AUS-01): "Lotes próximos a vencer"

Se inició `AUS-01` (módulo de Reportes, hasta ahora completamente ausente), con la decisión explícita del usuario de implementarlo de forma **incremental, un reporte por sprint**, priorizando reutilización sobre cobertura.

### Análisis previo (sin código)

Se revisaron, sin volver a auditar el proyecto completo, los modelos y migraciones relevantes para los 6 reportes que `AUS-01` lista como mínimos: `Venta`, `DetalleVenta`, `Compra`, `DetalleCompra`, `MovimientoStock`, `Lote`, `Producto`, `Cliente`, `Proveedor`. Confirmado: ningún reporte necesita tablas nuevas, todos reutilizan tablas existentes.

Se detectaron y reportaron (sin corregir, fuera de alcance) dos inconsistencias adicionales durante este análisis:
- `MovimientoStock::user()` y `MovimientoStock::referencia()` referencian columnas (`user_id`, `referencia_tipo`, `referencia_id`) que no existen en la migración de `movimientos_stock` (solo tiene `lote_id`, `fecha`, `tipo`, `motivo`, `cantidad`, `referencia` string). Lo mismo afecta a `Venta::movimientosStock()`/`Compra::movimientosStock()`. Candidato a `ESQ-09`, no agregado formalmente al catálogo todavía.
- El descuento de una venta no se persiste en ningún lado: `detalles_venta` no tiene columna `descuento`, y `VentaController::store()` intenta guardar el total con descuento en `recibos.monto`, campo que no existe en `Recibo::$fillable` ni en la migración — se descarta en silencio. Esto acota lo que el futuro reporte "Ventas por período" podrá mostrar honestamente (solo el bruto).

Se eligió **"Lotes próximos a vencer"** como primer reporte: mayor valor operativo (seguridad/cumplimiento en vencimientos) y el único de los 6 sin ningún hueco de datos, con máxima reutilización de código ya construido y verificado en la sesión de BUG-04.

Se presentó un diseño detallado (columnas, filtros, orden, estados, permisos, casos de prueba) y se esperó aprobación explícita antes de escribir código, siguiendo el mismo flujo de las sesiones anteriores.

### Implementación

- **`app/Models/Lote.php`:** nuevo scope `scopeVenceEntre($desde, $hasta)`, aditivo, sin modificar `vigentes()`, `vencidos()` ni `proximosAVencer()`. Necesario únicamente para el caso de rango de fechas arbitrario elegido por el usuario en el reporte (`proximosAVencer($dias)` siempre ancla el límite inferior a "hoy" y no puede expresarlo).
- **`app/Http/Controllers/ReporteController.php`** (nuevo): método `vencimientos()`. El caso por defecto (sin filtros de fecha) usa `Lote::vigentes()->proximosAVencer($diasAlerta)` sin ningún cambio; el caso con `desde`/`hasta` explícitos usa el scope nuevo; los vencidos usan `Lote::vencidos()` sin acotar por fecha (se muestran siempre que el stock sea > 0, sin importar la antigüedad). Cada fila se etiqueta `vencido`/`proximo` según qué consulta la trajo — mismo patrón que ya usa `InicioController` para el dashboard — evitando cualquier comparación de fecha nueva en el controlador. Paginación manual (`LengthAwarePaginator`) sobre la combinación de ambos grupos, preservando la query string para que los filtros persistan tras la búsqueda.
- **`routes/web.php`:** nuevo grupo `Route::middleware('permiso:reportes.ver')->prefix('reportes')->name('reportes.')`, con la única ruta `GET /reportes/vencimientos` por ahora. El grupo queda listo para que los siguientes reportes se agreguen sin reestructurar nada.
- **`resources/views/reportes/vencimientos.blade.php`** (nueva): formulario de filtros (estado, desde, hasta, producto) que conserva sus valores tras la búsqueda, tabla con badges `VENCIDO`/`PRÓXIMO A VENCER` reutilizando las clases `.badge.danger`/`.badge.warn` ya definidas globalmente en `style.css` (las mismas que usa el dashboard), paginación con `->links()` (mismo patrón que `productos`/`compras`), y un mensaje amigable en vez de tabla vacía cuando no hay resultados. Sin trabajo de diseño visual, según lo pedido.
- **`resources/views/app.blade.php`:** una entrada nueva "Reportes" en el sidebar, mismo patrón `@if (Route::has(...))` que el resto de módulos.
- No se creó ningún índice/menú de `/reportes` todavía — con un solo reporte no aporta nada; se construye cuando exista un segundo reporte.

### Verificación

- `tests/Feature/ReporteVencimientosTest.php` (Pest, nuevo, 17 casos): filtros (estado, rango de fechas, producto), prioridad y orden (vencidos antes que próximos, cada grupo por fecha ascendente), casos límite (`stock = 0`, `fecha_vencimiento = null`, rango invertido, página fuera de rango, producto sin alertas → mensaje amigable), invalidación de caché al cambiar `dias_alerta_vencimiento`, y control de acceso (`403` sin `reportes.ver`, `200` con permiso, `302` sin sesión). Los 17 pasaron en el primer intento, contra SQLite en memoria.
- Suite completa: mismo resultado que antes (23 passed, 1 fallo preexistente y no relacionado en `ExampleTest`).
- Verificación adicional contra la base de datos real (`farmacia_tfg`, sin mutar datos): `route:list` confirma `auth` + `permiso:reportes.ver` en la ruta; el reporte devuelve los 5 lotes reales en alerta del sistema (incluyendo un lote de Loratadina ya vencido, listado primero) con el orden y etiquetado esperados; `PermisoMiddleware` simulado con la cuenta real `vendedor@farmacia.com` (sin `reportes.ver`) bloquea correctamente con `403`.

### Documentación actualizada

`docs/pendientes.md`: `AUS-01` marcado como "en progreso", con el detalle del primer reporte implementado y el checklist de los 5 reportes restantes; tabla de priorización actualizada. Las dos inconsistencias detectadas en el análisis previo quedaron documentadas ahí mismo, sin corregir.

### Estado al cierre

Primer reporte del módulo de Reportes completado y verificado: "Lotes próximos a vencer" (`/reportes/vencimientos`). Andamiaje (`ReporteController`, grupo de rutas `reportes.*`, permiso, sidebar) listo para que los siguientes reportes se sumen sin reestructurar. Pendiente, en el orden propuesto (de menor a mayor riesgo/ambigüedad): Stock valorizado, Productos con stock bajo, Compras por período, Ventas por período (con la limitación de datos ya documentada), Historial de movimientos de stock (a decidir su solapamiento con `AUS-02`).

---

## 2026-07-10 (continuación) — Revisión de "Lotes próximos a vencer" + Reporte 2: "Stock valorizado"

### Revisión rápida del reporte ya aprobado

Antes de iniciar el siguiente sprint, se revisó únicamente el código de "Lotes próximos a vencer" (`Lote.php`, `ReporteController.php`, `vencimientos.blade.php`, ruta, sidebar), a pedido del usuario, para confirmar que no había lógica duplicada, consultas innecesarias, ni oportunidades de reutilización sin resolver, y que la estructura era apta para que los siguientes reportes la repliquen.

Resultado: sin lógica duplicada relevante ni N+1 (ambas consultas usan `with('producto')`). Se detectó y anotó, sin corregir todavía, una duplicación menor de la clase CSS `.empty-box` (definida inline tanto en `inicio.blade.php` como en `vencimientos.blade.php`) — de bajo riesgo, diferida a la etapa de mejora visual. Se detectó y **sí se corrigió** (con aprobación explícita) un bug real: `desde`/`hasta` con una fecha no parseable en la query string (p. ej. `?desde=no-es-una-fecha`) hacía que `Carbon::parse()` lanzara una excepción no capturada, terminando en `500`. El caso de rango invertido ya estaba cubierto; el de fecha inválida no.

### Fix: validación de fechas antes de `Carbon::parse()`

`ReporteController::vencimientos()`: se agregó el método privado `fechaValida(?string $valor): ?string`, que intenta parsear la fecha y devuelve `null` si falla, en vez de propagar la excepción. Se aplica a `desde` y `hasta` antes de cualquier otro uso, así que el resto de la lógica (incluida la detección de rango invertido) sigue funcionando sin cambios adicionales. Se agregó un caso de prueba nuevo en `ReporteVencimientosTest.php` que reproduce exactamente el escenario que antes rompía (`desde=no-es-una-fecha&hasta=2026-08-01`) y confirma `200` con el filtro ignorado.

### Análisis de impacto — Reporte 2: "Stock valorizado"

Sin riesgos adicionales detectados: no requiere tablas nuevas, `Lote` ya tiene `stock` y `costo_unitario`. Único hueco encontrado: `Lote` no tenía un accessor de valor derivado, a diferencia de `Venta`, `Compra`, `DetalleVenta` y `DetalleCompra`, que sí siguen ese patrón (`getTotalAttribute()`/`getSubtotalAttribute()`).

### Implementación

- **`app/Models/Lote.php`:** nuevo accessor `getValorAttribute()` (`stock * costo_unitario`), mismo patrón que los accessors ya existentes en `Venta`/`Compra`/`DetalleVenta`/`DetalleCompra`.
- **`app/Http/Controllers/ReporteController.php`:** nuevos métodos `index()` (landing del módulo) y `stockValorizado()`. A diferencia del reporte anterior (que combina dos consultas basadas en scopes y pagina manualmente en PHP porque necesita etiquetar cada fila según el scope de origen), este reporte es una sola consulta filtrada, así que usa el patrón estándar del proyecto: `orderByRaw('(stock * costo_unitario) DESC')->paginate()->withQueryString()` (mismo patrón que `ProductoController::index()`), con el total general calculado aparte vía `SUM(stock * costo_unitario)` en SQL sobre el query clonado (no sobre la página actual ni sumando en PHP).
- **`routes/web.php`:** dentro del mismo grupo `reportes.*` ya existente, se agregaron `GET /reportes` (`reportes.index`) y `GET /reportes/stock-valorizado` (`reportes.stockValorizado`). Mismo permiso `reportes.ver`, sin tocar el seeder.
- **`resources/views/reportes/index.blade.php`** (nueva): landing con enlaces a los dos reportes disponibles — ya tenía sentido construirla, como se anticipó en el sprint anterior, al existir un segundo reporte.
- **`resources/views/reportes/stock_valorizado.blade.php`** (nueva): mismo patrón visual y funcional que `vencimientos.blade.php` (filtro que se conserva, tabla, paginación, mensaje amigable en vacío). Sin trabajo de diseño.
- **`resources/views/app.blade.php`:** el enlace "Reportes" del sidebar ahora apunta a `reportes.index` en vez de directo a `reportes.vencimientos` (único cambio de comportamiento en un archivo fuera de los nuevos, dentro del mismo módulo).
- **`public/css/style.css`:** se agregó la clase `.empty-box` (ya usada por el dashboard y por el reporte de vencimientos, hasta ahora solo definida inline en esas vistas) para que el nuevo reporte la reutilice sin duplicarla una tercera vez. No se tocó `inicio.blade.php` ni `vencimientos.blade.php` (fuera de alcance de este sprint); ambos siguen con su copia local, redundante pero inofensiva.
- **`tests/Pest.php`:** los helpers de prueba (`crearUsuarioDePrueba`, `crearProductoDePrueba`, `crearLoteDePrueba`) se movieron aquí desde `ReporteVencimientosTest.php` para poder reutilizarlos en `ReporteStockValorizadoTest.php` sin duplicar código ni colisionar nombres de función entre archivos de test (PHP no permite declarar dos funciones globales con el mismo nombre).

### Verificación

- `tests/Feature/ReporteVencimientosTest.php`: 18 casos (17 + el nuevo de fecha inválida), todos pasan.
- `tests/Feature/ReporteStockValorizadoTest.php` (nuevo, 12 casos): valor por lote, exclusión de stock cero, filtro por producto, total general vs. suma manual, orden descendente por valor, persistencia de filtros, mensaje amigable en vacío, paginación fuera de rango, enlaces del índice, permisos (`403`/`200`/`302`). Todos pasan.
- Suite completa: 36 passed, 1 fallo preexistente y no relacionado (`ExampleTest`).
- Verificación contra la base de datos real (`farmacia_tfg`, sin mutar datos): `route:list` confirma las 3 rutas con `auth` + `permiso:reportes.ver`; el reporte de stock valorizado devuelve los 13 lotes reales ordenados correctamente por valor descendente con un total de 15,207.00, verificado a mano contra la suma de las filas; `PermisoMiddleware` bloquea a `vendedor@farmacia.com` en la nueva ruta; las tres rutas (`/reportes`, `/reportes/vencimientos`, `/reportes/stock-valorizado`) responden `200` en una petición real de extremo a extremo con el kernel HTTP completo.

### Documentación actualizada

`docs/pendientes.md`: `AUS-01` actualizado con el detalle del Reporte 2, el fix del bug de fecha inválida documentado bajo el Reporte 1, y el andamiaje común (`reportes.index`) documentado aparte.

### Estado al cierre

Dos de los seis reportes de `AUS-01` completados y verificados. Pendiente, en el orden propuesto: Productos con stock bajo, Compras por período, Ventas por período, Historial de movimientos de stock.

---

## 2026-07-10 (continuación) — Reporte 3: "Productos con stock bajo"

### Análisis previo (sin código)

Se revisaron, sin volver a auditar el proyecto completo, `InicioController` (widget "Productos con menos stock"), `ProductoController::index()` (columna "Stock total" del catálogo), `Producto`, `Lote`, `Configuracion` y `ReporteController`. Se detectó que el "stock total por producto" ya se calcula de dos formas distintas en el proyecto: `InicioController` con `leftJoin`+`groupBy` manual, y `ProductoController` con `Producto::withSum('lotes as stock_total','stock')`. Se decidió reutilizar la segunda (más idiomática, ya probada en el catálogo de productos).

Se plantearon dos decisiones de negocio no definidas y se esperó aprobación explícita antes de escribir código:
1. **Origen del umbral** — se propuso `Configuracion::obtener('stock_bajo_umbral', 30)` con override por `?umbral=`, sin tocar `/configuracion` en este sprint (la clave puede no existir; `obtener()` ya resuelve el default). Aprobado tal cual.
2. **Productos con stock 0, incluidos los que no tienen ningún lote** — se preguntó si debían incluirse o excluirse (a diferencia de los dos reportes anteriores, que excluían `stock = 0`). El usuario confirmó que **sí deben incluirse**: es el caso más crítico, el objetivo del reporte es detectar qué requiere reposición.

### Implementación

- **`app/Http/Controllers/ReporteController.php`:** nuevo método `stockBajo()`. Usa `Producto::withSum('lotes as stock_total','stock')` para el cálculo (igual que `ProductoController`), con `?umbral=` sobre `Configuracion::obtener('stock_bajo_umbral', 30)`, filtro opcional por producto, y paginación con `withQueryString()` (mismo patrón que los reportes anteriores). Cada fila se etiqueta `sin_stock`/`bajo` en el controlador (no en la vista), replicando el patrón de etiquetado ya usado en el Reporte 1.
- **Problema detectado y corregido durante la implementación (no en el análisis previo):** `withSum()` deja `stock_total` en `NULL` para productos sin lotes (confirmado también en `productos/index.blade.php`, que por eso hace `{{ $p->stock_total ?? 0 }}`). Filtrar por `stock_total < $umbral` directamente habría excluido justo los productos sin lotes — lo opuesto a la decisión de negocio #2 recién aprobada. Verificado con un producto temporal sin lotes en una transacción revertida antes de escribir el fix.
  - Primer intento: `havingRaw('COALESCE(stock_total,0) < ?', ...)` sin `GROUP BY` — funciona en MySQL (verificado contra la BD real) pero falla en SQLite (motor de los tests) con *"HAVING clause on a non-aggregate query"*.
  - Segundo intento: agregar `groupBy('productos.id')` — corrige SQLite, pero rompe MySQL real con *"'productos.codigo' isn't in GROUP BY"* (modo `ONLY_FULL_GROUP_BY`).
  - Solución final: reemplazar `HAVING`/`GROUP BY` por un `WHERE`/`ORDER BY` con la misma suma correlacionada escrita como subquery explícita (`COALESCE((select sum(stock) from lotes where...), 0)`), portable entre ambos motores. `withSum()` se conserva para el valor que se **muestra** en la tabla (igual que en `ProductoController`); la subquery explícita solo se usa para filtrar/ordenar. Verificado en ambos motores antes de continuar.
- **`routes/web.php`:** `GET /reportes/stock-bajo` (`reportes.stockBajo`), mismo grupo y permiso.
- **`resources/views/reportes/stock_bajo.blade.php`** (nueva): mismo patrón que los reportes anteriores (filtros persistentes, tabla, paginación, mensaje amigable en vacío). Badges `SIN STOCK`/`STOCK BAJO` leyendo el estado ya calculado en el controlador.
- **`resources/views/reportes/index.blade.php`:** se agregó el tercer enlace.
- **`tests/Feature/ReporteStockBajoTest.php`** (nuevo): reutiliza los helpers compartidos de `tests/Pest.php`. Se agregó `Cache::forget('configuracion.stock_bajo_umbral')` en el `beforeEach` porque el cache "array" de testing no se resetea entre tests dentro del mismo proceso (a diferencia de la BD, que sí se revierte por transacción con `RefreshDatabase`), y se necesitaba determinismo para el caso "sin la clave sembrada, usa el default 30".

### Verificación

- `tests/Feature/ReporteStockBajoTest.php`: 16 casos (valor por producto, inclusión de stock 0 con y sin lotes, distinción `sin_stock`/`bajo`, umbral personalizado, orden ascendente, filtro por producto, persistencia de filtros, mensaje amigable, paginación fuera de rango, default/override de `Configuracion`, índice con los tres reportes, permisos). Todos pasan.
- Suite completa: 52 passed, 1 fallo preexistente y no relacionado (`ExampleTest`).
- Verificación contra la base de datos real (`farmacia_tfg`): con los datos actuales, ningún producto tiene `stock_total < 30` (0 resultados, correcto). Se probó además con dos productos temporales en una transacción revertida (uno con stock bajo, otro sin ningún lote) y ambos aparecieron correctamente etiquetados y ordenados; sin residuos en la BD.

### Documentación actualizada

`docs/pendientes.md`: `AUS-01` actualizado con el detalle del Reporte 3 (incluyendo el problema de `withSum`/`HAVING` y su solución), la deuda técnica del umbral hardcodeado del dashboard anotada tanto en `AUS-01` como en `PEND-05` (sin corregir, según lo pedido), y tabla de priorización actualizada.

### Estado al cierre

Tres de los seis reportes de `AUS-01` completados y verificados. Pendiente, en el orden propuesto: Compras por período, Ventas por período, Historial de movimientos de stock.

---

## 2026-07-12 — Reporte 4: "Compras por período"

### Análisis previo (sin código)

Se revisaron, sin volver a auditar el proyecto completo, `Compra`, `DetalleCompra`, `Proveedor`, `CompraController` y las migraciones de `compras`/`detalles_compra`. Hallazgos relevantes:
- `Compra::getTotalAttribute()` existe pero suma en PHP sobre `$this->detalles` cargados — adecuado para una compra individual, no para un listado paginado de muchas (N+1/cálculo en memoria). Se decidió no reutilizarlo, calculando el total en SQL como ya se hizo en "Stock valorizado".
- `ESQ-04` (ya documentado): `Compra` tiene `estado` en `$fillable` pero la migración no tiene esa columna — no hay concepto de "compra anulada" persistido, así que el reporte no filtra por estado (no hay nada que filtrar).
- `proveedor_id` nunca es nulo en `compras` (a diferencia de `ventas.cliente_id`), y `compras.fecha` es `datetime` (se filtra con `whereDate`, igual que los scopes de `Lote`).
- No existe ningún "período por defecto" configurado en el sistema (a diferencia de `dias_alerta_vencimiento`). Se preguntó explícitamente al usuario si el reporte debía tener un período por defecto (p. ej. mes actual) o mostrar todas las compras sin fechas — se prefirió lo segundo, para no ocultar información sin que el usuario lo note.

Se confirmó el nivel de agregación (una fila por compra, no por detalle) y las columnas (fecha, proveedor, usuario, ítems, total), siguiendo el mismo patrón de listado que `compras/index.blade.php`.

### Implementación

- **`app/Http/Controllers/ReporteController.php`:** nuevo método `compras()`. Filtros `desde`/`hasta` (ambos opcionales, sin default — reutilizando `fechaValida()` desde el diseño, no como fix posterior) y `proveedor_id`. Total por compra vía subquery correlacionada (`SUM(cantidad * costo_unitario)` sobre `detalles_compra`), total general vía `JOIN` + `SUM` agregado sobre todas las compras filtradas (una sola consulta, no sumando página por página). Se evitó deliberadamente `HAVING`/`GROUP BY` sobre un alias, aplicando la lección de portabilidad MySQL/SQLite del Reporte 3.
- **`routes/web.php`:** `GET /reportes/compras` (`reportes.compras`), mismo grupo y permiso.
- **`resources/views/reportes/compras.blade.php`** (nueva) y **`resources/views/reportes/index.blade.php`** (modificada, cuarto enlace): mismo patrón que los reportes anteriores.
- **`tests/Feature/ReporteComprasTest.php`** (nuevo): helpers propios (`crearProveedorDePrueba`, `crearCompraDePrueba`, `agregarDetalleCompraDePrueba`) definidos localmente por ahora — se centralizarán en `tests/Pest.php` si un futuro reporte los vuelve a necesitar, siguiendo el mismo criterio ya aplicado con los helpers de producto/lote.

### Verificación

- `tests/Feature/ReporteComprasTest.php`: 16 casos (rango de fechas, sin fechas trae todo, filtro por proveedor, total por compra, total general sobre todo el conjunto filtrado —no solo la página—, fecha inválida, rango invertido, filtros persistentes, mensaje amigable, paginación fuera de rango, orden descendente, índice con los 4 reportes, permisos). Todos pasan.
- Suite completa: 68 passed, 1 fallo preexistente y no relacionado (`ExampleTest`).
- Verificación contra la base de datos real (`farmacia_tfg`): no hay compras reales todavía (`0` filas en `compras`/`detalles_compra`), así que el reporte correctamente muestra 0 resultados por defecto. Se verificó el comportamiento con datos temporales en una transacción revertida: 2 compras de un mismo proveedor (una reciente, una de hace 40 días) — con filtro de los últimos 10 días trae solo la reciente (total 50.00), sin filtro trae ambas (total 90.00); `PermisoMiddleware` bloquea correctamente a `vendedor@farmacia.com`; las rutas `/reportes` y `/reportes/compras` responden `200` en una petición real de extremo a extremo con el kernel HTTP completo. Sin residuos en la BD.

### Documentación actualizada

`docs/pendientes.md`: `AUS-01` actualizado con el detalle del Reporte 4, tabla de priorización actualizada (4/6 reportes).

### Estado al cierre

Cuatro de los seis reportes de `AUS-01` completados y verificados. Pendiente: Ventas por período (con la limitación de datos del descuento no persistido, ya documentada en el análisis inicial del módulo — entrada 2026-07-10), Historial de movimientos de stock (a decidir su solapamiento con `AUS-02`).

---

## 2026-07-12 (continuación) — Reporte 5: "Ventas por período"

### Análisis previo (sin código)

Se revisaron, sin volver a auditar el proyecto completo, `Venta`, `DetalleVenta`, `Cliente`, `VentaController` y `ventas/index.blade.php`. Se confirmó la limitación de datos ya conocida (descuento no persistido) y se explicó su efecto exacto sobre este reporte específicamente: el total solo puede reconstruirse como bruto desde `detalles_venta` (`SUM(cantidad × precio_unitario)`), igual que ya hace `ventas/index.blade.php` hoy vía `Venta::getTotalAttribute()` — no es una limitación nueva introducida por el reporte, es la misma que ya tiene el sistema en todos lados donde se muestra un total de venta.

Se detectó un hallazgo nuevo relevante para el diseño de columnas: en `VentaController::store()`, un mismo producto puede generar varios `DetalleVenta` si el FIFO tomó stock de más de un lote. Por eso se decidió mostrar "unidades vendidas" como `SUM(detalles_venta.cantidad)` en vez de `COUNT(*)` de filas (que habría contado de más por el split de lotes, algo que no ocurre igual en `detalles_compra`).

El usuario aprobó el plan con estas decisiones explícitas: filtros exactamente los de `AUS-01` (fecha, usuario, cliente, sin agregar estado como filtro — solo como columna informativa, para no adelantar `PEND-03`); sin período por defecto (mismo criterio que "Compras"); nota visible en la vista sobre la limitación del descuento; reutilizar `fechaValida()`; sin tocar el módulo de Ventas ni el esquema de la BD.

### Implementación

- **`app/Http/Controllers/ReporteController.php`:** nuevo método `ventas()`. Mismo patrón exacto que `compras()`: filtros `desde`/`hasta` (vía `fechaValida()`, sin default), `user_id`, `cliente_id`; total por venta y total general calculados en SQL con subqueries correlacionadas y un `JOIN`+`SUM` agregado respectivamente (sin `HAVING`/`GROUP BY` sobre alias); orden por fecha descendente.
- **`routes/web.php`:** `GET /reportes/ventas` (`reportes.ventas`), mismo grupo y permiso.
- **`resources/views/reportes/ventas.blade.php`** (nueva) y **`resources/views/reportes/index.blade.php`** (modificada, quinto enlace): mismo patrón que los reportes anteriores. Se agregó una nota visible explicando la limitación del total bruto, y la columna se etiquetó "Total (bruto)".
- **`tests/Feature/ReporteVentasTest.php`** (nuevo): helpers propios (`crearClienteDePrueba`, `crearVentaDePrueba`, `agregarDetalleVentaDePrueba`) locales, sin centralizar en `Pest.php` — no hay duplicación real con los helpers de compras (modelos y reglas distintas).

### Verificación

- `tests/Feature/ReporteVentasTest.php`: 20 casos (rango de fechas, sin fechas trae todo, filtro por usuario, filtro por cliente, venta sin cliente/público general, unidades vendidas correctas pese al split de lotes por FIFO —caso dedicado—, total bruto por venta, total general sobre todo el conjunto filtrado, fecha inválida, rango invertido, filtros persistentes, mensaje amigable, paginación fuera de rango, orden descendente, estado como columna informativa, índice con los 5 reportes, permisos). Todos pasan.
- Suite completa: 88 passed, 1 fallo preexistente y no relacionado (`ExampleTest`).
- Verificación contra la base de datos real (`farmacia_tfg`): existe 1 venta real (10 unidades, total bruto 125.00, cliente "Público general", usuario "Cristiano", estado "confirmada") — el reporte la muestra correctamente. Con un filtro de fecha que la excluye (`desde=2026-07-01`), el reporte correctamente devuelve 0 resultados y total 0.00. `PermisoMiddleware` bloquea a `vendedor@farmacia.com`; `/reportes` y `/reportes/ventas` responden `200` en una petición real de extremo a extremo con el kernel HTTP completo. Sin mutación de datos reales.

### Documentación actualizada

`docs/pendientes.md`: `AUS-01` actualizado con el detalle del Reporte 5, incluyendo la limitación del descuento documentada explícitamente para este reporte, y tabla de priorización actualizada (5/6 reportes).

### Estado al cierre

Cinco de los seis reportes de `AUS-01` completados y verificados. Pendiente, único reporte que falta: Historial de movimientos de stock (a decidir su solapamiento con `AUS-02`).

---

## 2026-07-12 (continuación) — Reporte 6: "Historial de movimientos de stock" — consolidación de AUS-01/AUS-02 y corrección de bug bloqueante (ESQ-05 → BUG-09)

### Análisis previo (sin código)

Se revisaron, sin volver a auditar el proyecto completo, `MovimientoStock`, `Lote`, `Producto`, `Compra`, `Venta`, `Devolucion`/`DevolucionController`, `LoteController::bulkUpdate()` y las secciones `AUS-01`, `AUS-02`, `BUG-06`, `PEND-06`, `ESQ-05` de `pendientes.md`.

**Hallazgo central del análisis:** `AUS-01` (Reporte 6, "historial de movimientos por producto o lote") y `AUS-02` ("vista de movimientos con filtros por lote, producto, tipo, fecha") describen la misma funcionalidad — `AUS-02` se escribió en la auditoría original (2026-07-01), antes de que existiera el módulo de Reportes (2026-07-10). Se presentó el hallazgo al usuario, tal como pidió, en vez de implementar dos pantallas duplicadas. El usuario confirmó consolidar ambos en una sola implementación dentro de la estructura de Reportes ya existente, y marcar `AUS-02` como resuelto por esa misma implementación (no eliminado por descuido).

Se confirmó también que `MovimientoStock::user()` y `MovimientoStock::referencia()` (relación `morphTo`) están rotas — referencian `user_id`/`referencia_tipo`/`referencia_id`, columnas inexistentes en la migración —, y que `BUG-06` (scopes `entradas()`/`salidas()` basados en el signo de `cantidad`, siempre positivo) no haría falta corregirlo si se filtra por la columna `tipo` directamente (correctamente poblada por `CompraController`/`VentaController`).

Filtros acordados: producto, lote, tipo, rango de fechas (los 4 que aparecen, combinados, en `AUS-01` + `AUS-02`). Sin filtro de `motivo` (no pedido por ningún backlog, decisión explícita de no ampliar el alcance).

### Bloqueo descubierto durante la implementación: `ESQ-05`

Al escribir la primera consulta de prueba (`MovimientoStock::count()`), la petición falló con un error SQL real, tanto en SQLite como en MySQL (`farmacia_tfg`):

```
SQLSTATE[42S22]: Column not found: 1054 Unknown column 'movimientos_stock.deleted_at' in 'where clause'
```

Causa: `MovimientoStock` usa `SoftDeletes`, que aplica un global scope a **toda** consulta `SELECT`; la tabla nunca tuvo la columna `deleted_at`. Ya estaba catalogado como `ESQ-05`, con la nota *"no hay UI que lea esta tabla, por lo que el error es latente"* — nota que dejó de ser cierta al construir la primera pantalla que sí la lee. Se confirmó también por qué nunca se había notado: el scope solo afecta lecturas, y `CompraController`/`VentaController` solo escriben (`::create()`), nunca leen.

Siguiendo la instrucción explícita del usuario ("no escribas soluciones alternativas para evitar el problema, detente y consúltame"), se pausó la implementación y se presentó un análisis completo (causa exacta, alternativas, recomendación, archivos, efectos secundarios, impacto en datos existentes) **sin escribir código**, antes de continuar.

### Decisión y corrección (BUG-09)

El usuario aprobó la Opción B (quitar el trait `SoftDeletes`), tratada explícitamente como *"bug previo necesario para desbloquear el sprint"*, no como ampliación del alcance del Reporte 6. Se confirmó por búsqueda exhaustiva en `app/` que ningún punto del código llama a `->delete()`/`->restore()`/`->withTrashed()`/`->onlyTrashed()` sobre `MovimientoStock`, y que se confirmó en la BD real que solo existía **1 fila** en la tabla antes del cambio (sin riesgo de pérdida de datos).

Único cambio: se quitó `use SoftDeletes;` (y su import) de `app/Models/MovimientoStock.php`. Sin migraciones, sin cambios de esquema, sin tocar `Compra`, `Venta`, `Devolucion`, `CompraController` ni `VentaController`.

### Implementación del Reporte 6

- **`app/Http/Controllers/ReporteController.php`:** nuevo método `movimientos()`. Sin agregación (`SUM`/`COUNT`) — es el único de los 6 reportes que no la necesita, así que no hereda el riesgo de portabilidad MySQL/SQLite de los reportes 3-5. Filtros `producto_id` (vía `whereHas('lote', ...)`), `lote_id`, `tipo` (whitelist `Entrada`/`Salida`, cualquier otro valor se ignora) y `desde`/`hasta` (vía `fechaValida()`, sin período por defecto). Orden por fecha descendente. No usa `MovimientoStock::user()` ni `::referencia()`; el campo `referencia` se muestra tal cual.
- **`routes/web.php`:** `GET /reportes/movimientos` (`reportes.movimientos`), mismo grupo y permiso.
- **`resources/views/reportes/movimientos.blade.php`** (nueva) y **`resources/views/reportes/index.blade.php`** (modificada, sexto y último enlace): mismo patrón que los reportes anteriores.
- **`tests/Feature/ReporteMovimientosTest.php`** (nuevo): helper propio (`crearMovimientoDePrueba`) local, reutilizando `crearProductoDePrueba`/`crearLoteDePrueba` de `tests/Pest.php`.

### Verificación

- Tras el fix de `MovimientoStock`: `MovimientoStock::count()`/`::query()->get()` funcionan correctamente contra MySQL real (antes fallaban). `tests/Feature/ReporteMovimientosTest.php`: 18 casos (tipo Entrada/Salida, filtro por producto, filtro por lote, rango de fechas, sin fechas trae todo, fecha inválida, rango invertido, campo `referencia` sin relaciones rotas, filtros persistentes, mensaje amigable, paginación fuera de rango, orden descendente, tipo inválido ignorado, índice con los 6 reportes, permisos). Todos pasan.
- Se verificó, en una transacción revertida, que `CompraController`/`VentaController` siguen creando `MovimientoStock` de forma idéntica a antes del fix (las escrituras nunca dependieron del trait) — 2 movimientos de prueba creados sin problema, revertidos sin residuos.
- Suite completa: 106 passed, 1 fallo preexistente y no relacionado (`ExampleTest`).
- Verificación contra la base de datos real (`farmacia_tfg`): el único movimiento real (`Salida`, `Venta`, 10 unidades, lote `P002-L1`, producto Amoxicilina) se muestra correctamente; `PermisoMiddleware` bloquea a `vendedor@farmacia.com`; `/reportes` y `/reportes/movimientos` responden `200` en una petición real de extremo a extremo con el kernel HTTP completo.

### Documentación actualizada

`docs/pendientes.md`: nuevo `BUG-09` en el catálogo de bugs (corregido, con causa, alternativas evaluadas, decisión e impacto en datos), `ESQ-05` marcado resuelto con referencia a `BUG-09`, "Sprint 3" histórico marcado como resuelto, `AUS-01` marcado **completo (6/6 reportes)**, `AUS-02` marcado **resuelto por consolidación** con referencia cruzada explícita al Reporte 6 (para dejar constancia de que no se eliminó por olvido), y tabla de priorización actualizada.

### Estado al cierre

**Módulo de Reportes (`AUS-01`) completo: 6/6 reportes implementados y verificados** (Lotes próximos a vencer, Stock valorizado, Productos con stock bajo, Compras por período, Ventas por período, Historial de movimientos de stock). `AUS-02` resuelto por consolidación. Deuda técnica pendiente, fuera de este sprint: `BUG-02`, `BUG-06`, `PEND-01` a `PEND-06`, `DATA-01`, `AUS-03`, `AUS-04`, `ESQ-01` a `ESQ-04`/`ESQ-06` a `ESQ-08`, y la deuda del umbral de stock bajo hardcodeado en el dashboard (anotada en el sprint del Reporte 3).

---
