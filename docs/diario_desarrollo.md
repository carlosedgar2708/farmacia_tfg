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

## 2026-07-13 — Sprint UI-01: Design System (documentación, sin código)

Se inició una nueva línea de trabajo, independiente de `docs/pendientes.md`: el rediseño visual del sistema, planificado en 6 sprints (`UI-01` a `UI-06`) con la misma metodología ya probada en Reportes (análisis → aprobación → implementación → pruebas → documentación). `UI-01` es puramente documental — sin tocar código, CSS ni vistas.

### Análisis realizado (sin modificar nada)

Se revisó `public/css/style.css` completo (479 líneas), `app.blade.php`, `welcome.blade.php`, `auth/login.blade.php`, `package.json`/`vite.config.js`/`resources/css/app.css`, y se contaron los atributos `style=""` inline en todo `resources/views`. Hallazgos relevantes documentados en el informe de análisis (no en `pendientes.md`, sino como contexto de `docs/design-system.md`):

- **Duplicación real de CSS:** `.card`, `.card-title`, `.search-wrap`, `.sugg*`, `.badge-ok`/`.badge-bad`, `.tabla-box.soft`, `.footer-left`, `.total-box`, `.total-badge`, `.btn.add`, `.btn.danger` están definidos 2-3 veces casi verbatim en el mismo archivo.
- **Conflicto real (no solo duplicación):** `.table` tiene dos definiciones contradictorias de `border-spacing` (una con filas "flotantes", otra sin espacio) — la última en el archivo gana la cascada, así que el comportamiento de filas flotantes que describe el comentario original nunca se ve en pantalla. `.table thead th` tiene el mismo problema con el color de fondo.
- **Tres sistemas de badge distintos** (`.chip-*`, `.badge-ok`/`.badge-bad`, `.badge.*`) representando los mismos estados con nombres distintos.
- **`.alert-danger` usada en 10 vistas pero nunca definida en el CSS** — los errores de validación se renderizan hoy con el estilo `.alert` genérico (celeste/info), no como error. Bug activo, no solo inconsistencia estética.
- **Poppins cargada solo en `welcome.blade.php`**, no en `app.blade.php` — la marca tipográfica no existe realmente en ninguna vista autenticada (el 100% del sistema real).
- **Remix Icon cargado dos veces** en `app.blade.php` (duplicado).
- **Tailwind CSS 4 + Vite instalados pero sin usar** en ninguna vista (cero `@vite` en todo el proyecto) — scaffolding muerto de la instalación base de Laravel.
- **~200 atributos `style=""` inline** en 25 de 26 vistas, incluidas las 6 vistas de Reportes construidas en sprints recientes.
- **Login con `<style>` propio embebido** (115 líneas), variables `:root` duplicadas, degradados y sombras grandes — la pantalla que más se aparta de la identidad del resto del sistema.

### Decisiones de negocio/diseño consultadas antes de escribir el documento

Se presentaron 3 decisiones que afectaban directamente el contenido del Design System, en vez de asumirlas:
1. **Verde de éxito** (`--success`, no está en la paleta oficial de 5 colores) → se mantiene como excepción semántica justificada, de uso limitado a estados de éxito/disponibilidad, separada de la paleta de marca.
2. **Página de login** (identidad visual distinta al resto del sistema) → se incluye en el rediseño, no se deja fuera de alcance.
3. **Base técnica** (CSS plano vs. migrar a Tailwind, ya instalado pero sin usar) → se continúa con CSS plano; Tailwind queda documentado como dependencia sin uso, candidata a eliminar en `UI-06`.

### Documento creado

`docs/design-system.md` (22 secciones): filosofía, identidad visual, paleta oficial (azul petróleo `#357C90` identidad/marca, turquesa `#12A594` acciones principales, gris `#F5F7FA` fondos, coral `#F27D72` error/destructivo, rojo oscuro `#991B1B` uso muy limitado/crítico, verde `#C4E6B0`/`#166534` como excepción semántica), regla de severidad con un solo hue (coral en distintas intensidades + rojo oscuro como techo, para no introducir ámbar fuera de la paleta), tipografía (propuesta de cambio de Poppins a Inter, justificada), espaciado/radios/sombras en escala formal, iconografía (Remix Icon, corrección del `<link>` duplicado), especificación de botones/formularios/tablas/cards/modales/alertas/badges/colores de gráficos/responsive/accesibilidad, catálogo de componentes Blade previstos, reglas de consistencia, **checklist de 16 reglas estrictas no negociables** (sin degradados, sin glassmorphism/neumorphism, sin sombras exageradas, sin animaciones innecesarias, un solo acento por pantalla, espacio en blanco generoso, bordes 8-12px, jerarquía por tamaño/peso no por color, solo Remix Icons, máximo 2 tamaños de botón, máximo 3 niveles visuales, "todo debe sentirse ligero", una sola estructura de tabla, un solo patrón de formulario, todo componente nuevo se agrega primero al sistema — prohibido CSS específico de una vista si un componente compartido puede resolverlo), y la hoja de ruta completa `UI-01` a `UI-06` acordada con el usuario.

### Estado al cierre

`UI-01` completo. Nada de código, CSS ni vistas fue modificado en este sprint — es 100% documental. Pendiente: `UI-02` (propuesta visual/mockups de cada pantalla, sin escribir CSS todavía, a la espera de que el usuario lo inicie).

---

## 2026-07-14 — Sprint UI-03: Design System en código

### Nota de secuencia

`UI-02` (propuesta visual) se trabajó en profundidad para el Dashboard — quedó completamente analizado, discutido y aprobado (auditoría widget por widget, mapeo explícito de qué lógica de cada bloque reutiliza qué reporte, wireframe funcional final con las 4 prioridades: actividad de hoy, alertas críticas, últimos movimientos, accesos rápidos). Las otras 3 decisiones de `UI-02` (Listados modal-vs-página, índice de Reportes grid-vs-lista, layout de Login) siguen **pendientes**, sin resolver. El usuario decidió explícitamente avanzar a `UI-03` (la base de código, que no depende de esas 3 decisiones) antes de cerrarlas, para no bloquear la infraestructura compartida en discusiones que solo afectan a módulos puntuales. `UI-02` se documentará en esta bitácora cuando quede completamente cerrado, siguiendo el mismo criterio ya usado en el resto del proyecto (documentar al finalizar, no a mitad de sprint).

### Alcance aprobado

Reescribir `public/css/style.css` usando los tokens de `docs/design-system.md`: unificar `.card`, sistema de botones, sistema de badges/chips, tablas, alertas, modal y paginación; consolidar `.empty-box`; eliminar duplicados literales. Regla explícita del usuario: **normalización, no rediseño** — ningún módulo cambia de apariencia salvo dos excepciones aprobadas por afectar primitivas globales imposibles de migrar de forma parcial sin duplicar el sistema:
1. Tipografía → Inter (antes Poppins, que nunca cargaba en vistas autenticadas — el `<link>` solo existía en `welcome.blade.php`).
2. Botones primarios (`.btn`, `.btn.add`, `.btn.primary`) → turquesa (antes petróleo), dejando el petróleo exclusivo de identidad/marca, según el Design System.

También se autorizó, como ajuste transversal trivial: corregir el `<link>` de Remix Icon duplicado en `app.blade.php`.

Explícitamente fuera de alcance: ningún archivo de vista de módulo, ningún cambio de espaciado/layout/tamaño en CSS específico de Ventas/Compras/Login — eso queda para `UI-05`.

### Implementación

- **`resources/views/app.blade.php`:** se quitó el `<link>` duplicado de Remix Icon y de `style.css` (estaban cargados dos veces: una en `<head>`, otra repetida justo después de `<body>`). Se agregó la carga de Inter (Google Fonts, pesos 400/500/600/700) en `<head>`.
- **`public/css/style.css`:** reescrito completo (479 → 464 líneas). Se agregó una capa de tokens en `:root` (paleta oficial + tokens semánticos de badge + tipografía + escalas de espaciado/radio/sombra) **sin cambiar el valor de ningún token ya existente** (`--primary`, `--accent`, `--success`, `--ink`, `--muted`, `--bg`, `--card`, `--line` quedan idénticos). Se unificaron `.card` (definido 3 veces), el sistema de botones (`.btn`/`.btn.add`/`.btn.primary`/`.btn.danger`/`.btn-outline`, con conflictos reales de cascada entre dos definiciones de `.btn` que se reconstruyeron propiedad por propiedad), los 3 sistemas de badge/chip (`.chip-*`, `.badge-ok`/`.badge-bad`, `.badge.*` — coexisten, ahora comparten los mismos tokens de color en vez de repetir hex), las tablas (`.table`/`.table thead th` tenían definiciones en conflicto real, no solo duplicadas — se conservó la que efectivamente gana la cascada hoy), el modal, la paginación y `.empty-box`. Se agregaron `.alert-danger` y `.alert-warning` (bug ya documentado en `UI-01`: se usaban en 10 vistas pero nunca estuvieron definidas — dentro del alcance aprobado como "sistema de alertas").

### Verificación

El usuario pidió explícitamente **no** hacer verificación automática en navegador para este sprint; en su lugar, revisión estática línea por línea del CSS viejo contra el nuevo. Se hizo:
- Reconstrucción manual, selector por selector, del valor **realmente resuelto por la cascada** del archivo original (no el de cada bloque individual) para cada caso con definiciones repetidas o en conflicto, comparado contra el archivo nuevo.
- Script de diff de selectores (Node) comparando el conjunto completo de selectores de ambos archivos.
- **Regresión real encontrada y corregida antes de cerrar el sprint:** `.table tr td:first-child`/`:last-child` (esquinas redondeadas + borde lateral en la primera/última celda de fila) y el `border-top` de `.table tbody td` se habían perdido en la primera reescritura — estaban activos hoy en 11 vistas que usan `.table` sin `.table-soft` (Roles, Lotes, Compras, Proveedores, Usuarios, Clientes, formularios de ítems de Ventas/Compras, modal de stock de Productos). Se restauraron con los valores originales exactos antes de dar el sprint por terminado.
- Dos selectores eliminados sin corregir por ser inofensivos, verificado con grep sobre las vistas reales: `.table-soft tbody tr:hover td` (siempre coexiste con `.table` en las 26 vistas, la regla que sí se conservó ya ganaba la cascada) y `.venta-left .card, .venta-right .card` (producía exactamente los mismos valores que la regla `.card` base conservada).
- Confirmado explícitamente que `.btn.danger` sigue en coral (no se coló turquesa fuera de los botones primarios).

### Deuda técnica anotada (no corregida, según instrucción explícita)

`.chip-warn`/`.badge.warn` mezclan hue coral (fondo) con texto ámbar (`#b45309`, fuera de la paleta oficial) — ya señalado en `docs/design-system.md` §3. Queda registrado para resolverse en `UI-05`/`UI-06`, no se tocó en este sprint por ser una decisión de color, no de normalización.

### Estado al cierre

`UI-03` completo. Cambios aplicados únicamente en `app.blade.php` (2 líneas) y `public/css/style.css` (reescritura completa). Ninguna otra vista fue tocada. Próximo paso: `UI-04` (componentes Blade reutilizables).

---

## 2026-07-14 (continuación) — Sprint UI-04: componentes Blade reutilizables

### Auditoría previa (sin código)

Se midió con grep, no por estimación, la repetición real de cada patrón candidato a componente en las 26 vistas del proyecto: badges/chips (9 vistas, 16 usos semánticos), botones (24 vistas, 75 usos — incluido un hallazgo relevante: el combo `class="btn btn-outline"`, usado 10 veces, hoy solo se ve como botón outline por el orden de declaración en el CSS, no por diseño explícito), alertas de flash (13+ vistas, con al menos 6 duplicando innecesariamente el flash global que ya muestra `app.blade.php`), estado vacío (7 vistas, 9 usos), cards (12 vistas, 24 usos). Se descartó explícitamente construir `x-modal` (estructuras demasiado distintas entre módulos para diseñar una API sin un caso real concreto delante) y `x-table-filters` (no lo necesita el Dashboard; se construirá al migrar Reportes en `UI-05`, con los 6 casos reales ya disponibles como referencia).

Se acordó con el usuario un alcance de exactamente 5 componentes — ni más, para evitar la sobre-fragmentación en decenas de componentes difíciles de mantener, ni menos: `x-badge`, `x-button`, `x-alert`, `x-empty-state`, `x-card`. Las APIs (props y slots) se definieron y aprobaron explícitamente antes de escribir código, incluyendo un prop `title` en `x-alert` y `x-card`, y una variante `compact` en `x-empty-state` — precisamente para no tener que romper la interfaz de los componentes durante `UI-05`.

### CSS nuevo (agrupado en una sola revisión antes de implementar)

Tres de los cinco componentes requerían una variante que nunca existió en el CSS (no era normalización de `UI-03`, sino una decisión visual nueva). Se agruparon las tres en una sola consulta al usuario en vez de interrumpir varias veces:
- **`.btn-ghost`** — aprobada sin necesidad de decisión visual: geometría idéntica a `.btn-outline` ya existente, color de texto = token turquesa ya definido. Cero valores inventados.
- **`.badge.critical`** — sí requería una decisión real (el Design System solo decía "rojo oscuro tenue" sin valor exacto). Aprobado: `rgba(153,27,27,.12)` de fondo, mismo criterio de opacidad `.12` que `--badge-warning-bg`.
- **`x-empty-state` variante `compact`** — layout de una fila (ícono + texto), sin tarjeta propia, apoyada en el contenedor. Aprobado con la condición explícita de que el comportamiento por defecto (sin `compact`) reproduzca exactamente el `.empty-box` actual.

También se agregó, sin necesitar consulta (tratamiento estándar, sin color nuevo): `opacity:.5; cursor:not-allowed` para el estado `:disabled` de los tres tipos de botón.

### Implementación

- **`public/css/style.css`:** nueva sección "15) Componentes Blade — variantes nuevas (UI-04)", claramente separada de la normalización de `UI-03`. Se agregó también el token `--badge-critical-bg`.
- **`resources/views/components/`** (carpeta nueva): `badge.blade.php`, `button.blade.php`, `alert.blade.php`, `empty-state.blade.php`, `card.blade.php` — los 5 como componentes Blade anónimos (sin clase PHP), puramente de presentación. `x-button` maneja el caso "deshabilitado con `href`" renderizando siempre `<button disabled>` en vez de un `<a>` (un enlace deshabilitado no es un patrón HTML válido) — pensado explícitamente para el acceso rápido "Registrar devolución" del Dashboard.
- **Ninguna vista existente fue tocada.** Es exactamente la separación que pidió el usuario: `UI-04` crea, `UI-05` migra.

### Verificación

`tests/Feature/ComponentesBladeTest.php` (nuevo, 19 casos vía `$this->blade()`): cada variante de cada componente genera la clase esperada, `x-button` con `href` renderiza `<a>` y con `disabled` renderiza `<button>` sin importar si se pasó `href`, `x-empty-state` sin `compact` reproduce el `.empty-box` actual byte a byte (salvo espacios en blanco insignificantes, ajustado en el test tras el primer fallo), `x-card` solo renderiza `<h3>` si se pasa `title`. Los 19 pasaron (uno requirió un ajuste menor de aserción, no de componente, por espacios en blanco del `@if` de Blade). Suite completa: 125 passed, 1 fallo preexistente y no relacionado (`ExampleTest`).

### Estado al cierre

`UI-04` completo. Cambios en `public/css/style.css` (una sección nueva, aditiva), `app.blade.php` sin cambios adicionales a los de `UI-03`, y los 5 componentes + su suite de tests como archivos nuevos. Ninguna vista de negocio migrada todavía. Próximo paso: cerrar las 3 decisiones de `UI-02` que siguen pendientes (Listados modal-vs-página, índice de Reportes, layout de Login) antes de `UI-05`.

---

## 2026-07-14 (continuación) — Sprint UI-05 (parte 1): migración del Dashboard

### Decisión de secuencia

El usuario decidió explícitamente **no** cerrar las 3 decisiones de `UI-02` que seguían pendientes (Listados modal-vs-página, índice de Reportes grid-vs-lista, layout de Login) antes de empezar `UI-05` — ninguna de las tres bloquea la migración del Dashboard, cada una se resolverá cuando llegue el turno de migrar su propio módulo.

### Auditoría previa (sin código)

Se auditaron `InicioController::index()` y `resources/views/inicio.blade.php` tal como estaban: 5 tarjetas de conteo, 3 `.info-box` (menos stock, gráfico placeholder nunca implementado, más vendidos) más el widget de vencidos/próximos ya construido en un sprint anterior, y 108 líneas de `<style>` embebido con valores de color ligeramente distintos a los tokens globales (`.badge.danger` en `#ffe2e2`/`#b70000` en vez de los tokens `--badge-danger-*`, `.empty-box` local con padding distinto al global). Se mapeó explícitamente qué lógica de cada bloque nuevo reutiliza qué reporte o scope ya existente, confirmando que ningún bloque requería una consulta nueva.

### Alcance aprobado

1. **`Compra::scopeDelDia()`** — nuevo, calcado exactamente de `Venta::scopeDelDia()` (que existía desde antes sin usarse en ningún lado).
2. **Reescritura de `InicioController::index()`** en 4 bloques: qué pasó hoy (ventas/compras de hoy — cantidad y monto), alertas críticas (vencidos / próximos a vencer, separados en dos listas top-5 en vez de una mezclada; stock bajo top-5), últimos movimientos (top-10, misma base que el Reporte 6), accesos rápidos.
3. **Reescritura completa de `inicio.blade.php`** usando `x-card`, `x-badge`, `x-button`, `x-empty-state`; eliminación total del `<style>` embebido.
4. **Stock bajo:** se mantiene la deuda técnica de duplicar la subquery de `ReporteController::stockBajo()` (documentada en `docs/pendientes.md`, sección `AUS-01`/Reporte 3) — no se extrajo `Producto::scopeConStockBajo()` en este sprint para no ampliar el alcance.

Condición explícita del usuario, igual que en los sprints de Reportes: detenerse y consultar ante cualquier bloqueo real o decisión de diseño no prevista antes de ampliar el alcance.

### Implementación

- **`app/Models/Compra.php`:** agregado `scopeDelDia()`.
- **`app/Http/Controllers/InicioController.php`:** reescrito completo. `ventasHoy`/`comprasHoy` combinan el scope `delDia()` (cantidad) con una suma vía `DetalleVenta`/`DetalleCompra` (mismo patrón `JOIN` + `SUM(cantidad * precio)` que los reportes de ventas/compras). Vencidos/próximos a vencer reutilizan sin cambios `Producto::scopeConAlertaVencimiento()`, `loteVencidoRelevante()` y `loteProximoRelevante()`, ahora repartidos en `$vencidos`/`$proximosAVencer` en vez de una sola colección `$proximosVencer`. Stock bajo repite la subquery correlacionada ya usada en `ReporteController::stockBajo()`. Últimos movimientos: misma base que el Reporte 6, sin filtros, `limit(10)`.
- **`resources/views/inicio.blade.php`:** reescrita completa con `x-card`/`x-badge`/`x-button`/`x-empty-state` y clases globales ya existentes (`.info-list`, `.money`, `.two`, `.sb-section`, `.actions`) — sin `<div>` contenedor propio (`.card + .card` en el CSS global ya resuelve el espaciado vertical) y sin ningún CSS nuevo. Las variantes de badge se alinearon con el criterio **ya usado en Reportes**, no inventado: `badge danger` para vencido/sin stock (no `critical`, que el sistema reserva para severidad mayor), `badge warn` para próximo a vencer/stock bajo/salida, `badge ok` para entrada — verificado contra `reportes/vencimientos.blade.php`, `reportes/stock_bajo.blade.php` y `reportes/movimientos.blade.php` antes de fijar cada variante. Los montos (`ventasHoy`/`comprasHoy`) se muestran con `<span class="money">`, no con badge — un badge es un indicador de severidad, no un formato de dato numérico; ningún otro reporte usa badge para montos.
- El botón "Registrar devolución" del bloque de accesos rápidos se renderiza deshabilitado (`x-button :disabled="true"`), ya que el módulo de devoluciones no existe (`PEND-02`).

### Errores encontrados y corregidos antes de cerrar el sprint

- Primer borrador usaba `<x-badge variant="ok">` para los montos de ventas/compras de hoy — corregido a `<span class="money">` al confirmar contra las vistas de Reportes que los montos nunca se muestran como badge.
- Primer borrador usaba `variant="critical"` para vencidos y `sin_stock`, inconsistente con `reportes/vencimientos.blade.php` y `reportes/stock_bajo.blade.php`, que usan `danger` para esos mismos estados — corregido a `danger` en ambos casos.
- `<div class="dashboard-blocks">` (envoltorio inicial de las 4 tarjetas) no tenía ninguna clase CSS definida — eliminado; `<section class="content">` de `app.blade.php` y `.card + .card` del CSS global ya bastan.

### Verificación

- `tests/Feature/DashboardVencimientoTest.php` (preexistente, 5 casos): rompió con `Undefined array key "proximosVencer"` porque el controlador ahora expone `vencidos`/`proximosAVencer` por separado en vez de una sola colección. Actualizado para usar las nuevas claves, preservando exactamente las mismas aserciones de lógica de negocio (deduplicación, lote más próximo, exclusión de lotes vencidos sin stock, reactividad a `dias_alerta_vencimiento`). Los 5 vuelven a pasar.
- `tests/Feature/DashboardInicioTest.php` (nuevo, 9 casos): ventas/compras de hoy (cantidad y monto correctos, ignorando otros días), stock bajo respeta el umbral configurado y el límite de 5, producto sin lotes se marca `sin_stock`, últimos movimientos ordenados desc. y limitados a 10, estados vacíos del Design System se renderizan, botón de devolución deshabilitado, acceso sin sesión redirige a login.
- Suite completa: **134 passed**, 1 fallo preexistente y no relacionado (`ExampleTest` — `GET /` devuelve 302 porque `AuthController::welcome()` siempre redirige; hay además una segunda definición muerta de `GET /` en `routes/web.php` por orden de registro. Comportamiento previo a este sprint, no tocado).
- Verificación contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`): `InicioController::index()` sin excepciones con datos reales, y render completo de la vista (simulando el stack de middleware para compartir `$errors`) sin componentes `<x-...>` sin resolver.

### Documentación actualizada

- `docs/modulos.md`: sección "Módulo: Dashboard" reescrita para reflejar los 4 bloques actuales y la migración a componentes; estado del módulo pasado de "Parcial" a "Completo".
- `docs/pendientes.md`: `PEND-05` marcado resuelto; nota de deuda técnica junto al Reporte 3 actualizada (el umbral ya está unificado vía `Configuracion`, queda pendiente solo compartir el scope SQL).
- `docs/design-system.md` §22: `UI-02`, `UI-03`, `UI-04` marcados ✅ Completo (ya lo estaban en la práctica, la tabla no se había actualizado); `UI-05` marcado 🔶 En curso con el Dashboard como primer módulo migrado.

### Estado al cierre

Dashboard migrado y verificado. Próximo paso: continuar `UI-05` con el módulo de Reportes (siguiente en el orden acordado), incluyendo la decisión pendiente de `UI-02` sobre el índice de Reportes (grid vs. lista).

---

## 2026-07-14 (continuación) — Sprint UI-05 (parte 2): decisión grid-vs-lista e índice de Reportes

### Decisión de diseño: índice de Reportes

Antes de escribir código se cerró la decisión de `UI-02` que seguía pendiente para este módulo. Auditoría: `ReporteController::index()` es trivial (`return view('reportes.index')`, sin datos); la vista era una sola `<section class="card">` con un `<h1>`+subtítulo y una columna vertical de 6 `<a class="btn-outline">` con estilos inline, cada uno con icono Remix + título de un reporte, sin ninguna metadata adicional (sin descripción, contador ni estado).

Se presentó una comparación grid vs. lista con ventajas/desventajas concretas para este caso (tabla completa en la conversación con el usuario). Puntos clave: el contenido real de cada ítem es solo icono+título, sin datos que justifiquen el espacio de una tarjeta individual; un grid de tarjetas habría requerido al menos una decisión visual nueva (layout de grid, tratamiento de "tarjeta-enlace") y hubiera chocado con la regla 13 del Design System ("todo debe sentirse ligero" — bloques grandes sin contenido real que los justifique). La lista, en cambio, reutiliza exactamente el patrón ya validado en "Accesos rápidos" del Dashboard (`x-card` + `x-button`), sin CSS nuevo.

**Decisión aprobada:** lista, con `x-button variant="secondary"` (no `ghost`) — conserva la apariencia actual de "botón" (`.btn-outline`), dando jerarquía visual adecuada a una pantalla cuyo propósito es la navegación principal hacia los reportes; `ghost` queda reservado para acciones secundarias dentro de una vista, no para navegación primaria.

### Alcance aprobado

- `ReporteController::index()` sin cambios.
- Reescribir `resources/views/reportes/index.blade.php` con `x-card` como contenedor y seis `x-button variant="secondary"` (icono + ruta correspondiente).
- Eliminar los estilos inline de la lista de enlaces.
- Sin CSS nuevo, sin tocar el Design System.

Condición: sin necesidad de otra aprobación si no aparecía ningún bloqueo imprevisto durante la implementación.

### Implementación

- **`resources/views/reportes/index.blade.php`:** reescrita. El contenedor `<div style="display:flex;flex-direction:column;gap:10px;max-width:420px">` y los 6 `<a class="btn-outline" style="justify-content:flex-start">` se reemplazaron por `<x-card>` envolviendo un `<div class="info-list">` con 6 `<x-button variant="secondary">` (icono + ruta). `.info-list` (ya existente, `display:flex;flex-direction:column;gap:8px`) se reutilizó puramente por su layout vertical — es el mismo mecanismo que ya usa el Dashboard para listar filas, aplicado aquí a botones en vez de filas de datos; no se creó ninguna clase nueva.
- **Encabezado de página (`<h1 class="page-title" style="margin:0">Reportes</h1>` + subtítulo con `style="color:#64748b;font-weight:600;margin:2px 0 16px"`) se dejó exactamente igual**, dentro del mismo `<x-card>` (no como `x-card :title`, para no degradar visualmente un `<h1>` de página a un `<h3 class="card-title">`). Esos dos inline styles **no** se eliminaron — es el mismo patrón de encabezado usado, sin excepción, en las 6 subvistas de Reportes que todavía no se migran; tocarlo solo aquí habría fragmentado la consistencia del módulo (regla 14/15 del Design System) sin resolver nada, ya que no hay clase `.subtitle`/`.text-muted` definida todavía. Queda para cuando el módulo completo (índice + 6 subvistas) tenga su propio turno de tratamiento de encabezado.

### Verificación

- Los 5 tests `'el indice de reportes lista...'` (uno por cada `Reporte*Test.php`, que hacen `assertSee(route('reportes.X'), false)`) siguen pasando sin cambios — la ruta de cada reporte sigue apareciendo en el HTML.
- Suite completa: 134 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`, simulando el stack de middleware): sin componentes `<x-...>` sin resolver, los 6 `route()` presentes en el HTML generado.

### Documentación actualizada

- `docs/modulos.md`: se corrigió una laguna de documentación no relacionada con este sprint pero descubierta al tocar el módulo — la tabla resumen marcaba "Reportes" y "Movimientos de Stock (vista)" como **Ausente**, desactualizado desde antes de `AUS-01` (2026-07-12). Se agregó la sección "Módulo: Reportes" (rutas, estado de migración de índice vs. subvistas, cross-referencia a `docs/pendientes.md` para el detalle de cada reporte).
- `docs/design-system.md` §22: `UI-05` actualizado con el índice de Reportes migrado y la decisión grid-vs-lista resuelta.

### Estado al cierre

Índice de Reportes migrado y verificado. Las 6 subvistas de Reportes (vencimientos, stock valorizado, stock bajo, compras, ventas, movimientos) quedan pendientes — se migrarán en un turno posterior dentro del mismo módulo, antes de pasar a Productos según el orden acordado.

---

## 2026-07-14 (continuación) — Sprint UI-05 (parte 3): auditoría y migración de Productos

### Decisión de secuencia

El usuario decidió pasar directamente a Productos y dejar la migración visual de las 6 subvistas de Reportes para después. Motivo explícito: esas subvistas ya usan el Design System de forma indirecta (cards, tablas, badges, botones de `UI-03`/`UI-04`), así que migrarlas ahora es sobre todo limpieza; Productos es un módulo más usado y sirve para validar los componentes en una pantalla con formularios, tablas, filtros, acciones y modales — el patrón que salga de ahí se reutiliza en el resto de módulos.

### Auditoría previa (sin código)

Se auditaron `ProductoController`, `LoteController` y las vistas relacionadas. Hallazgo principal: existen **tres** archivos de vista de "lotes", no uno solo — `productos/lotes/index.blade.php` (alcanzable, pero con un bug visual real: `<h1>`/`<p>` con `style="color:white"`, texto invisible, y una clase `.h-top` que no existe en el CSS), `resources/views/lotes/index.blade.php` (más completa, pero **huérfana**: ninguna ruta la sirve — no existe `Route::get('/lotes', ...)` en `routes/web.php`, y el sidebar la referencia vía `Route::has('lotes.index')`, que siempre es `false`), y el modal "Editar stock" embebido en `productos/index.blade.php` (el que realmente se usa en la práctica, vía `LoteController::bulkUpdate`). De paso se confirmó que una nota de `docs/modulos.md` sobre una ruta `GET /lotes` "con bug" ya estaba desactualizada — esa ruta no existe más en el código actual.

Se identificaron además 5 puntos de fricción entre `productos/index.blade.php` y el catálogo de componentes de `UI-04`, presentados al usuario como decisiones a resolver antes de implementar:
1. Las acciones de fila (`.action.edit`/`.action.view.stock`/`.action.delete`) son chips de color sólido sin equivalente en `x-button` (que solo tiene `primary`/`secondary`/`danger`/`ghost`).
2. El chip "Inyectable: No" usa `chip-neutral`, variante que `x-badge` no tiene (`ok|warn|danger|critical` únicamente).
3. Los campos de formulario de ambos modales no tienen ningún estilo propio — `docs/design-system.md` §10 ("Formularios") está escrito pero nunca se implementó en CSS.
4. La confirmación de "Eliminar producto" usa `confirm()` nativo, no un modal, aunque `docs/design-system.md` §13 lo exige — pero `UI-04` descartó explícitamente construir `x-modal`.
5. Qué hacer con las dos vistas de lotes encontradas.

### Decisiones aprobadas para cada punto

1. **Sin cambios.** Anotado como futura evolución del Design System (posible `x-action` o variante nueva), fuera de este sprint.
2. **Sin cambios.** Se mantiene `<span class="chip chip-neutral">` tal cual — no se amplía la API de `x-badge` recién cerrada por un solo caso de uso.
3. **Sin cambios.** Los `<input>`/`<select>`/`<textarea>` quedan con su implementación actual; "Formularios" será un sprint independiente que construya la base una sola vez para todo el sistema.
4. **Sin cambios.** El `confirm()` nativo se queda; el modal de confirmación es infraestructura nueva, no migración de vista, y se reserva para su propio sprint.
5. **Distinción explícita:** la vista huérfana (`resources/views/lotes/index.blade.php`) **no se elimina todavía** — se documenta, pero primero hay que confirmar que no exista ningún flujo externo que dependa de ella (decisión reversible, evita borrar código por una suposición). `productos/lotes/index.blade.php` tampoco se corrige — no forma parte del flujo principal de Productos y la gestión de lotes como tal no se está migrando en este sprint; el bug de color queda documentado para cuando le toque su turno.

### Alcance final aprobado

Cambio limitado exclusivamente a `resources/views/productos/index.blade.php`. `ProductoController` sin modificaciones. `LoteController` y las vistas de Lotes completamente fuera de alcance. Migración puramente estructural — sin alterar comportamiento: el buscador con autocomplete, la tabla, la paginación y ambos modales debían conservar exactamente su funcionalidad, y todo el JavaScript de la vista debía permanecer intacto.

### Implementación

- `<section class="card">` → `<x-card>`, envolviendo todo el bloque original (encabezado, alertas, buscador, tabla, paginación, estado vacío) — mismo precedente que Dashboard e índice de Reportes. El `<h1 class="page-title">`+subtítulo se dejó exactamente igual dentro del card (mismo criterio ya aprobado para Reportes: es un patrón de encabezado compartido por todo el sistema, se migra cuando le toque su turno conjunto).
- Botón "Nuevo producto" (`<a href="#" class="btn" id="btn-open-create">`) → `<x-button variant="primary" icon="ri-add-circle-line" href="#" id="btn-open-create">` — conserva `href="#"` y el `id`, por lo que el `e.preventDefault()` + `addEventListener` existente sigue funcionando sin tocar una línea de JS.
- Botón "Buscar" (`<button class="btn-outline" style="white-space:nowrap">`) → `<x-button type="submit" variant="secondary" icon="ri-filter-2-line">` — se eliminó el `style="white-space:nowrap"` inline (sin reemplazo en CSS, por instrucción de no agregar CSS nuevo); riesgo cosmético menor y aceptado si el texto llegara a partirse en pantallas muy angostas.
- `@if(session('success'))<div class="alert alert-success">`/`alert-danger` → `<x-alert variant="success">`/`<x-alert variant="danger">`, mismo `@if` guard.
- `<div class="empty">No hay productos registrados.</div>` (clase `.empty` no definida en el CSS — bug invisible preexistente) → `<x-empty-state message="No hay productos registrados." />`.
- Los 6 botones de ambos modales (Cancelar/Guardar del modal crear-editar; Añadir lote/Cancelar/Guardar cambios del modal de stock) → `<x-button>` con el variant y `type` correspondientes, conservando todos los `id` (`btn-cancel`, `btn-add-lote`, `btn-cancel-stock`) de los que depende el `@push('scripts')`.
- **Sin tocar:** `ProductoController`, `LoteController`, el bloque `@push('scripts')` completo, los `<input>`/`<textarea>`/`<label>`/checkbox de ambos formularios, las acciones de fila (`.action.*`), el chip `chip-neutral`, los contenedores `.modal`/`.modal-content` (no son `x-card`), y las dos vistas de lotes.

### Verificación

- `tests/Feature/ProductosIndexTest.php` (nuevo, 10 casos — no existía ningún test de `ProductoController` antes de este sprint): estado vacío con `x-empty-state`, tabla con código/nombre, stock total sumado correctamente vía `withSum`, alertas de éxito/error con las clases `alert alert-success`/`alert alert-danger`, ningún `<x-` sin resolver en el HTML, los 4 ids clave (`btn-open-create`, `btn-cancel`, `btn-add-lote`, `btn-cancel-stock`) presentes para el JS, control de acceso por permiso `productos.ver` (403/200/redirect a login). Alcance deliberadamente acotado a renderizado — no se agregó cobertura de validaciones/CRUD, que sería un esfuerzo aparte no cubierto por este sprint.
- Suite completa: 144 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`, simulando el stack de middleware): sin componentes `<x-...>` sin resolver, los 6 ids/selectores clave presentes en el HTML generado.
- **Limitación reconocida:** no hay herramienta de navegador/Playwright disponible en este entorno para verificar visualmente el comportamiento real de los modales y el autocompletado en un navegador. Se lo señaló explícitamente al usuario en vez de asumir que la verificación estática (render + ids + tests) equivale a una prueba de UI real.

### Documentación actualizada

- `docs/modulos.md`: sección "Módulo: Productos" ampliada con el detalle de la migración y las 4 decisiones de "sin cambios"; sección "Módulo: Lotes / Stock" corregida (la nota sobre `GET /lotes` "con bug" ya no aplica, esa ruta no existe) y ampliada con los hallazgos sobre las dos vistas de lotes.
- `docs/pendientes.md`: nuevo `PEND-07` (vistas de lotes duplicadas/rotas) documentando ambos hallazgos y el trabajo pendiente (confirmar huérfana antes de borrar, decidir destino de la vista con bug visual).
- `docs/design-system.md` §22: `UI-05` actualizado con Productos migrado.

### Estado al cierre

`productos/index.blade.php` migrado y verificado (dentro de las limitaciones de entorno señaladas). Gestión de lotes (`PEND-07`) queda pendiente como su propio tema, no como parte de esta migración. Próximo paso: a decidir con el usuario — continuar `UI-05` con Lotes, o seguir el orden original hacia Compras.

---

## 2026-07-14 (continuación) — Sprint UI-05 (parte 4): Compras (solo índice)

### Decisión de secuencia

El usuario verificó visualmente por su cuenta la migración de Productos (modales, autocomplete, botones, responsive) y no reportó regresiones — cierra ese sprint de forma definitiva. Para el siguiente paso, decide seguir el orden original hacia Compras en vez de abordar `PEND-07` (Lotes) ahora, para no desviarse del plan de migración ya acordado.

### Auditoría previa (sin código)

Se auditaron `CompraController` y las dos vistas del módulo. `compras/index.blade.php` es una vista simple (listado + botón + alerta + tabla), migración directa sin sorpresas. `compras/create.blade.php` resultó ser una vista mucho más compleja: layout de dos columnas (`.venta-wrap`/`.venta-left`/`.venta-right` — nombrado así porque, según el propio comentario del controlador, se copió del formulario de Ventas), 3 cards, 2 modales AJAX (crear producto/proveedor al vuelo), 2 buscadores con autocomplete, y ~450 líneas de JS. Dado el tamaño y la complejidad, se presentó el análisis completo de ambas vistas pero se dejó a criterio del usuario si incluir `create.blade.php` en este sprint.

Se identificaron 3 puntos a confirmar antes de implementar, propios de `create.blade.php`:
1. `.tabla-box.soft` (wrapper de tabla, distinto de `.table-wrap` usado en Productos/Reportes) — normalizar o dejar como está.
2. Encabezados de página (`<h2>Compras</h2>`) — mismo criterio ya usado en Dashboard/Reportes/Productos: se preserva.
3. El card `.venta-left` usa un `<h3 style="margin-bottom:10px">🧾 Registrar compra</h3>` sin `.card-title` ni ícono Remix (usa un emoji, violando la regla "solo Remix Icons" ya escrita en el Design System) — migrarlo a `<x-card title icon>` le agregaría el estilo `.card-title` que hoy no tiene, un cambio visual real aunque corrige una inconsistencia ya documentada.

### Alcance aprobado

El usuario aprobó **únicamente** `compras/index.blade.php` en este sprint — `compras/create.blade.php` queda explícitamente fuera de alcance, para un turno posterior (compartirá patrón con Ventas). Los 3 puntos de fricción de `create.blade.php` quedan sin resolver por ahora, ya que no aplican al índice. Alcance del índice: `<div class="card">` → `<x-card>`, botón "Nueva compra" → `<x-button variant="primary" icon="ri-add-circle-line">`, alerta de sesión → `<x-alert>`, fila `@empty` → `<x-empty-state>` con el `colspan` correspondiente. Tabla, paginación, `CompraController` y el encabezado `<h2>Compras</h2>` sin cambios.

### Implementación

- `resources/views/compras/index.blade.php`: los 4 elementos aprobados migrados exactamente como se planteó. `<x-empty-state message="No hay compras registradas." />` se colocó dentro de un `<td colspan="5">` (el componente renderiza un `<div>`, válido como contenido de una celda).
- Se descartó el `+ ` literal delante de "Nueva compra" — redundante con el ícono del botón, mismo criterio ya aplicado en Reportes/Productos.
- Al documentar el cierre se encontró una inexactitud preexistente en `docs/modulos.md`: describía un "total calculado (cantidad × costo_unitario)" y un "panel de detalles expandible por compra" que **no existen** en el código actual — la tabla solo muestra "Total items" (suma de `cantidad`, no un monto) y no tiene ningún panel expandible. Se corrigió la documentación para reflejar el código real, sin tocar el código (fuera de alcance — cambiar qué columnas se muestran no es parte de una migración puramente visual).

### Verificación

- `tests/Feature/ComprasIndexTest.php` (nuevo, 7 casos — no existía ningún test de `CompraController` antes de este sprint): estado vacío con `x-empty-state`, tabla con proveedor y total de ítems, alerta de éxito con `alert alert-success`, ningún `<x-` sin resolver, control de acceso por permiso `compras.ver` (403/200/redirect a login).
- Suite completa: 151 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`, simulando el stack de middleware): sin componentes `<x-...>` sin resolver, la ruta `compras.create` presente en el HTML.
- Misma limitación de entorno ya señalada en el sprint de Productos: sin herramienta de navegador disponible para clic real; verificación puramente estática (render + tests).

### Documentación actualizada

- `docs/modulos.md`: sección "Módulo: Compras" corregida (inexactitud sobre total/panel expandible) y ampliada con el detalle de la migración del índice y la nota de que `create.blade.php` queda pendiente.
- `docs/design-system.md` §22: `UI-05` actualizado con Compras (índice) migrado.

### Estado al cierre

`compras/index.blade.php` migrado y verificado. `compras/create.blade.php` queda pendiente, junto con los 3 puntos de fricción identificados en su auditoría, para cuando le toque su turno — probablemente en conjunto con Ventas, dado que comparten el mismo patrón `.venta-wrap`. Próximo paso: a decidir con el usuario.

---

## 2026-07-21 — Sprint UI-04A: estandarización global de errores de validación

### Pedido del usuario

Reemplazar el patrón de Laravel por defecto (lista de errores técnicos en inglés, dentro de un bloque rojo) por un patrón único y consistente en todo el sistema: un banner genérico en español (`x-alert variant="danger"`, sin lista) más el detalle de cada error debajo de su campo vía `@error`. Traducir los mensajes de validación al español. Alcance global — todas las vistas migradas y sin migrar.

### Auditoría previa (sin código)

- **Duplicación del banner de errores:** `app.blade.php` ya renderizaba `$errors->any()` globalmente para toda vista que hiciera `@extends('app')` (casi todas). Tres vistas además tenían su propio bloque local, duplicando el mensaje: `ventas/create.blade.php`, `compras/create.blade.php`, `auth/login.blade.php` (esta última también extiende `app`).
- **`auth/register.blade.php` rota** (`BUG-10`, no relacionado con el pedido pero descubierto durante la auditoría): `@extends('layouts.app')`, archivo inexistente en el proyecto — `GET /register` fallaba con excepción para cualquier visitante, con el registro público habilitado en `config/fortify.php`.
- **Cero `@error()` en toda la aplicación** — ningún formulario mostraba errores por campo, todo dependía del banner.
- **Locale en inglés** (`APP_LOCALE=en`, sin carpeta `lang/`) — de ahí los mensajes tipo "The proveedor id field is required.".
- **Mapa de los 9 controladores que validan:** 7 formularios "estáticos" (campo fijo con `name` en el Blade: Productos, Proveedores, Clientes, Roles, Usuarios, Configuración, Auth) vs. 3 "dinámicos" (Compras, Ventas, Lotes/stock — filas armadas por JS, sin campo fijo al cual anclar `@error`, y sin repoblar la tabla desde `old()` tras un error).

### Plan aprobado (3 fases)

1. Componente global `x-form-errors` + desduplicación + corrección de `auth/register.blade.php` (bug independiente, se corrige de una vez ya que se está tocando esa zona).
2. Traducción combinada: `lang/es/validation.php`/`auth.php`/`passwords.php` con la traducción estándar completa (mismas claves que `vendor/laravel/framework/.../en/`) + mensajes `custom` a medida para los campos de mayor uso (`proveedor_id`, `cliente_id`, `items.*.producto_id`, `items.*.cantidad`, `nro_lote`, `fecha_vencimiento`).
3. `@error` + `.field-error` — solo los 7 formularios estáticos en esta pasada; los 3 dinámicos (Compras/Ventas/Lotes) quedan pendientes de un análisis de diseño aparte (repoblar `old()` en una tabla armada por JS).

### Implementación — Fase 1

- **`resources/views/components/form-errors.blade.php`** (nuevo): `@if ($errors->any())` envolviendo un `<x-alert variant="danger">` con el mensaje fijo en dos líneas ("No se pudo guardar la información." + "Corrige los campos marcados e inténtalo nuevamente."), sin lista.
- **`app.blade.php`:** el bloque `$errors->any()` inline se reemplazó por `<x-form-errors />` — sigue siendo el único punto de verdad para todas las vistas que extienden `app`.
- **`ventas/create.blade.php`, `compras/create.blade.php`, `auth/login.blade.php`:** se eliminaron sus bloques locales duplicados (en login, también la clase CSS `.login-alert` que quedó huérfana).
- **`auth/register.blade.php`:** `@extends('layouts.app')` → `@extends('app')`; se verificó primero que `app.blade.php` no llama `auth()->user()` fuera de un `@auth`/`@endauth` (no iba a cambiar un error por otro al renderizar para un invitado). Se le agregó el mismo mecanismo `body.auth` que ya usa `login.blade.php` para ocultar el sidebar — si no, la vista habría quedado visualmente rota dentro del shell autenticado en vez de fallar.
- **Hallazgo durante la verificación:** un primer intento de test con `followingRedirects()->post('/register', [...])` sin `->from(...)` daba un falso negativo — sin header `Referer`, el `redirect()->back()` de Laravel/Fortify cae a `/`, que redirige a `/inicio`, que por no haber sesión redirige a `/login`. No es un bug de la app; se corrigió el test agregando `->from('/register')` (y análogo para `/login` y `/rols`) para simular la navegación real de un navegador.

### Implementación — Fase 2

- **`lang/es/validation.php`:** traducción completa de las ~90 claves de Laravel 12 (copiadas de `vendor/laravel/framework/.../en/validation.php` para no omitir ninguna), más `attributes` (nombre en español de cada campo usado en el sistema) y `custom` con las 6 frases naturales pedidas explícitamente (`proveedor_id`, `cliente_id`, `items.*.producto_id`, `items.*.cantidad`, `nro_lote`/`items.*.nro_lote`/`lotes.*.nro_lote`, `fecha_vencimiento`/`items.*.fecha_vencimiento`/`lotes.*.fecha_vencimiento`) — aplicadas también a las variantes con wildcard (`items.*.…`, `lotes.*.…`) para que cubran Compras/Ventas/Lotes, aunque esos formularios no se toquen todavía en la Fase 3.
- **`lang/es/auth.php`, `lang/es/passwords.php`:** traducción directa de las claves de Fortify (credenciales inválidas, throttle, reseteo de contraseña).
- **`.env`:** `APP_LOCALE=en` → `es` (se dejó `APP_FALLBACK_LOCALE=en` sin tocar, como red de seguridad ante cualquier clave sin traducir).

### Implementación — Fase 3 (solo formularios estáticos)

- **`public/css/style.css`**, nueva sección §16: `.field-error` (`font-size:12px; color:var(--accent)`, mismo tamaño ya usado para texto secundario en el resto del sistema) — únicamente el texto del mensaje, no el borde/foco de input que describe `docs/design-system.md` §10 completo (eso queda para el futuro sprint de Formularios).
- `@error('campo')<small class="field-error">{{ $message }}</small>@enderror` agregado debajo de cada input relevante en: `productos/index.blade.php` (codigo, nombre, es_inyectable, description), `proveedors/index.blade.php` (nombre, contacto, telefono), `clientes/index.blade.php` (nombre, documento, telefono), `rols/index.blade.php` (nombre, slug, descripcion), `users/index.blade.php` (username, name, apellido, email, telefono, password, activo, roles), `configuracion/edit.blade.php` (dias_alerta_vencimiento), `auth/login.blade.php` (email, password), `auth/register.blade.php` (name, email, password, password_confirmation).
- Ningún botón, buscador, tabla ni JS de estas vistas se tocó — solo se agregó la línea `@error` después del campo correspondiente.

### Verificación

- `tests/Feature/FormErrorsTest.php` (nuevo, 4 casos): registro responde 200 (regresión de `BUG-10`), banner aparece **una sola vez** (no duplicado) en registro/login/un formulario autenticado (Roles), con el texto nuevo en español.
- `tests/Feature/LocalizacionValidacionTest.php` (nuevo, 5 casos): locale de la app es `es`; mensaje genérico traducido para un campo sin `custom` (`nombre` → "El campo nombre es obligatorio."); mensajes personalizados exactos para `proveedor_id`/`items.*.producto_id`/`items.*.nro_lote`; el mensaje `custom` que ya existía directo en `VentaController::store()` (`'items.required' => 'Agrega al menos un renglón de venta.'`) se sigue respetando sin cambios.
- `tests/Feature/FieldErrorsTest.php` (nuevo, 8 casos): verifica el HTML renderizado (`class="field-error"` presente + mensaje visible) para los 7 formularios estáticos — Productos, Roles, Configuración, Login, Register, Proveedores, Clientes, Usuarios.
- Suite completa: **168 passed**, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Verificado contra la base de datos real (MySQL, vía `tinker`): `app()->getLocale()` es `es`, `trans('validation.required', ...)` con el atributo de `proveedor_id` devuelve "El campo proveedor es obligatorio.", render completo de Productos sin excepciones y sin `field-error` presente cuando no hay errores (confirma que el `@error` no se activa falsamente).

### Documentación actualizada

- `docs/design-system.md`: `x-form-errors` agregado al catálogo de componentes (§19, marcado construido); nueva subsección 10.1 documentando qué parte de "Formularios" ya está implementada (`.field-error`) y qué sigue pendiente; §22 con la fila `UI-04A` completa.
- `docs/pendientes.md`: nuevo `BUG-10` (`auth/register.blade.php` roto, corregido); nuevo `PEND-08` (errores por campo en los 3 formularios dinámicos, con el problema de `old()`/reconstrucción de filas explicado); ambos agregados a la tabla de prioridades.

### Estado al cierre

`UI-04A` completo para los 7 formularios estáticos. Los 3 formularios dinámicos (Compras, Ventas, Lotes/stock) quedan pendientes en `PEND-08`, a la espera de una decisión de diseño sobre cómo repoblar sus tablas desde `old()` antes de agregar `@error` ahí. Próximo paso: a decidir con el usuario — retomar `UI-05` (Ventas/Compras `create`) o abordar `PEND-08` primero, ya que ambos tocan las mismas vistas.

---

## 2026-07-24 — PEND-08: reconstrucción de filas dinámicas y unificación del flujo de validación

### Decisión de secuencia

El usuario eligió abordar `PEND-08` antes de continuar `UI-05` sobre `compras/create`/`ventas/create` — motivo explícito: migrar el aspecto visual sin resolver primero la pérdida de datos ante un error habría sido "mejorar el aspecto y empeorar la experiencia".

### Auditoría — primera pasada (solo reconstrucción visual)

Se auditó cómo llegan los datos al backend en los 3 formularios dinámicos (Compras, Ventas, modal de stock de Productos): las filas se arman 100% por JavaScript y se serializan a `items[i][campo]`/`lotes[i][campo]` recién al enviar — no hay ningún `<input>` fijo en el Blade al cual anclar `@error()`. Durante esta auditoría se encontró que `CompraController::store()` (1 punto) y `VentaController::store()` (2 puntos) usaban `abort(422, "mensaje")` para condiciones de **regla de negocio** (lote vencido, stock insuficiente, e "inconsistencia al descontar por lotes"), no para validación de Laravel — `abort()` no dispara `withInput()`/`withErrors()`, así que esos casos mostraban una página de error genérica y perdían toda la información cargada, un problema más grave que la falta de reconstrucción visual.

### Auditoría — segunda pasada, a pedido del usuario (flujo completo de validación)

Se clasificó cada `abort(422, ...)` uno por uno:
- Compras (lote vencido, línea 86) y Ventas (stock insuficiente, línea 114): condiciones esperables y corregibles por el usuario → deben tratarse como error de validación.
- Ventas (línea 154, "problema al descontar stock por lotes"): matemáticamente inalcanzable en operación correcta — los lotes ya están bloqueados (`lockForUpdate()`) desde antes de validar que `$stockTotal >= $cantidadSolicitada`, así que si esto dispara es un bug real de reparto, no una condición del usuario → debe seguir siendo una excepción real, no un error de formulario.
- `LoteController::bulkUpdate()` (no tenía `abort()`, pero sí `back()->with('error', ...)` sin `withInput()` — mismo problema con otro síntoma) y sin transacción (una fila a mitad de la lista podía quedar guardada si una posterior fallaba).

Se definió la estrategia de unificación: todos los errores corregibles por el usuario pasan a `throw ValidationException::withMessages([...])` con el índice real de la fila (`items.$i.campo`) — el mismo mecanismo que ya usa `$request->validate()` internamente, de forma que `$errors->messages()` queda con la misma forma sin importar el origen del error. Se comparó reconstruir las filas 100% en Blade vs. que JS lea un JSON preparado por Blade — se descartó la primera por duplicar la definición de "cómo se ve una fila" en dos lugares (PHP y JS), el mismo antipatrón que ya causó 3 sistemas de badge distintos en este proyecto; se eligió la segunda, con los nombres (producto/proveedor/cliente) resueltos en PHP vía Eloquent antes de serializar, para que JS nunca dependa de que su array local esté completo/actualizado.

### Ajustes pedidos antes de implementar

1. Errores corregibles → `ValidationException::withMessages(...)`.
2. La excepción inalcanzable de Ventas → `\RuntimeException`, no error de formulario.
3. `LoteController::bulkUpdate()` envuelto en `DB::transaction()`.
4. Una única función JS por vista para construir cada fila.
5. Preparación del JSON enriquecido movida a una clase dedicada, no al controlador directamente.
6. JS expone `OLD_ITEMS`/`FIELD_ERRORS`, no `$errors->messages()` directo.

### Implementación

- **`app/Support/FormRecovery.php`** (nuevo): `items($key, $lookups, $labelColumn)` — `old($key, [])` enriquecido con `"<campo>_label"` resuelto vía `whereIn` (una consulta por campo, no una por fila); `label($key, $modelClass)` — resuelve un campo de cabecera simple (`proveedor_id`/`cliente_id`); `fieldErrors()` — misma forma que `$errors->messages()` leyendo `session('errors')`, para no acoplar la vista a `$errors` directamente.
- **`CompraController`**: `abort(422, "El lote {$nroLote} está vencido...")` → `throw ValidationException::withMessages(["items.$i.fecha_vencimiento" => "..."])` (se agregó el índice `$i` al `foreach`, antes no se usaba). `create()` prepara `oldItems` (enriquecido con `producto_id_label`), `oldProveedorNombre`, `fieldErrors`.
- **`VentaController`**: `abort(422, "Stock insuficiente...")` → `ValidationException` atada a `items.$i.cantidad`, con el mensaje usando `$producto->nombre` (ya disponible) en vez de "producto ID {$productoId}". El segundo `abort()` → `throw new \RuntimeException(...)`, con un comentario explicando por qué es inalcanzable. `create()` prepara `oldItems`/`oldClienteNombre`/`fieldErrors`.
- **`LoteController::bulkUpdate()`**: todo el cuerpo envuelto en `DB::transaction()`; los dos `back()->with('error', ...)` → `ValidationException::withMessages(["lotes.$i.nro_lote" => "..."])->redirectTo(route('productos.index', ['stock_error' => $producto->id]))` — se usa `redirectTo()` explícito (no el `back()` implícito) porque el modal de stock es compartido por todos los productos de la tabla y hace falta saber determinísticamente cuál era, sin depender del header `Referer`.
- **`ProductoController::index()`**: si la query trae `stock_error`, resuelve ese producto (nombre + `old('lotes')` vía `FormRecovery::items('lotes')`) de forma independiente a la paginación/búsqueda actual — así funciona aunque el producto no esté en la página que se esté viendo.
- **`compras/create.blade.php` / `ventas/create.blade.php`**: se extrajo la construcción del `<tr>` de `agregarFila()` a una función propia (`crearFilaCompra`/`crearFilaVenta`), reutilizada también por un bloque nuevo al final del script que recorre `OLD_ITEMS` y llama a esa misma función con los errores de cada fila (filtrando `FIELD_ERRORS` por el prefijo `items.{i}.`). Campos de cabecera (`proveedor_id`/`cliente_id`/`observacion`) con el patrón estático ya usado en `UI-04A`: `value="{{ old(...) }}"` + `@error()`, y el nombre visible resuelto server-side (`$oldProveedorNombre`/`$oldClienteNombre`) en vez de depender de un cruce en JS.
- **`productos/index.blade.php`**: mismo refactor en el modal de stock (`crearFilaLote`, reutilizada también por `addEmptyRow`, que antes tenía su propio HTML duplicado) más una función `abrirStockPara(...)` que ahora usan tanto el clic en "Editar stock" como un bloque nuevo que, si `STOCK_ERROR` viene poblado (desde `?stock_error=`), reabre el modal automáticamente con los datos y errores reconstruidos.
- Los tres `@json(...)` nuevos (`OLD_ITEMS`, `FIELD_ERRORS`, `STOCK_ERROR`) se escribieron con el flag `JSON_UNESCAPED_UNICODE` — sin él, los acentos se serializan como `á` (funciona igual en JS, pero se prefirió el texto plano legible en el HTML fuente; se descubrió al escribir los tests, ver abajo).

### Errores encontrados y corregidos antes de cerrar el sprint

- Primeros tests de `FormRecoveryTest` fallaban al intentar seedear `old()` manualmente vía `session()->flash('_old_input', ...)` sin pasar por un ciclo HTTP real — `Request::old()` depende de `hasSession()` sobre la instancia de `Request` ligada al ciclo actual, no simplemente de escribir en el store de sesión. Se simplificó ese archivo a solo lo verificable de forma aislada (casos vacíos, `fieldErrors()`) y se movió la verificación de `items()`/`label()` a los tests de controlador, que sí ejercitan un `POST`/redirect real.
- Un test de "no debe quedar rastro de field-error" fallaba porque esa cadena aparece siempre en el **código fuente** de la función JS (`crearFilaCompra` la usa como nombre de clase CSS), independientemente de si hay errores — no es un dato dinámico. Se corrigió el test para verificar `const OLD_ITEMS    = [];` en su lugar.
- Un test de "el mensaje aparece en la vista" fallaba porque `@json()` escapa acentos a `á` por defecto — el texto buscado no existía tal cual en el HTML. Se corrigió agregando `JSON_UNESCAPED_UNICODE` a los `@json()` nuevos (mejora real, no solo un ajuste de test).

### Verificación

- `tests/Feature/FormRecoveryTest.php` (4 casos), `CompraStockRecoveryTest.php` (3), `VentaStockRecoveryTest.php` (3), `LoteStockRecoveryTest.php` (4) — 14 tests nuevos en total. Cubren: la excepción ya no produce página de error genérica, el mensaje queda atado a la clave de fila correcta (`items.0.fecha_vencimiento`, `items.0.cantidad`, `lotes.0.nro_lote`), la vista reconstruye la fila con el nombre del producto resuelto (no el id crudo) y el error visible, ningún dato persiste cuando falla (incluye una prueba específica de que `bulkUpdate` ya no deja escrituras parciales), y que un envío exitoso no deja rastros de `OLD_ITEMS` en la recarga siguiente.
- Suite completa: **182 passed**, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Verificado contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`): las 3 vistas renderizan sin errores previos; el flujo completo de lote vencido lanza `ValidationException` con el mensaje correcto y no persiste ninguna compra.

### Documentación actualizada

- `docs/pendientes.md`: `PEND-08` marcado resuelto, con el detalle completo de la reclasificación de cada `abort()` y la solución implementada.
- `docs/design-system.md` §10.1: actualizado — `.field-error` ahora cubre también los 3 formularios dinámicos, generado desde JS en vez de `@error()` de Blade, mismo token y aspecto.

### Estado al cierre

`PEND-08` resuelto por completo: los 3 formularios dinámicos preservan la información ingresada y muestran errores por fila ante cualquier fallo (de validación o de regla de negocio), y ya no existe ningún camino en Compras/Ventas/Lotes que termine en una página de error HTTP genérica para una condición de uso normal. Próximo paso: retomar `UI-05` sobre `compras/create.blade.php` y `ventas/create.blade.php`, ahora sí con la base funcional resuelta.

---

## 2026-07-24 (continuación) — Sprint UI-05: migración de `compras/create.blade.php`

### Auditoría

Con `PEND-08` ya resuelto, se retomó la auditoría de esta vista hecha originalmente durante el análisis de `PEND-08`. Estructura: `.venta-left.card` (controles de carga, tabla de ítems, pie con total) con un `<h3 style="margin-bottom:10px">🧾 Registrar compra</h3>` sin `.card-title` ni ícono Remix; `.venta-right` con dos `.card` que ya usan `<h3 class="card-title"><i class="ri-...">` (mapeo directo a `x-card`); 2 modales (Nuevo producto, Nuevo proveedor) sin migrar; botones sueltos "Agregar"/"Cancelar compra"/"Guardar compra" con clases sin componente. Se retomaron las 3 decisiones abiertas del audit original de `PEND-08`:
- `.tabla-box.soft` vs `.table-wrap`: se mantiene sin cambios, no es parte de esta migración.
- Encabezados de página: no aplica, esta vista no tiene `<h1>`/`<h2>` de página.
- El `<h3>` de "Registrar compra": se resolvió migrándolo a `<x-card title="Registrar compra" icon="ri-file-list-3-line">`, reemplazando el emoji y aplicando `.card-title` — mismo tratamiento que ya tienen los otros dos cards de la vista.

### Alcance aprobado

Migrar los 3 `.card` a `<x-card>` y todos los botones (incluidos los de los 2 modales) a `<x-button>`, sin tocar `.tabla-box.soft`, el autocomplete, los cálculos, `OLD_ITEMS`/`FIELD_ERRORS`, el JavaScript, ni `CompraController`. El botón de eliminar fila (generado por `crearFilaCompra()` en JS) explícitamente fuera de alcance.

### Implementación

- `.venta-left.card` → `<x-card class="venta-left" title="Registrar compra" icon="ri-file-list-3-line">` (Blade permite pasar clases extra vía `$attributes`, igual que en Reportes/Productos).
- Los 2 `.card` de `.venta-right` → `<x-card title="Datos de la compra" icon="ri-truck-line">` / `<x-card title="Confirmar compra" icon="ri-cash-line">` — mapeo 1:1 sin cambios visuales.
- Botón "Agregar" (`.btn.add`) → `<x-button variant="primary" icon="ri-add-circle-line" id="btnAgregar">`.
- Botón "Cancelar compra" (`.btn.danger`, `onclick`) → `<x-button variant="danger" icon="ri-close-line" onclick="history.back()">`.
- Botón "Guardar compra" (`.btn.primary`, `style="width:100%;margin-top:10px"`) → `<x-button type="submit" variant="primary" icon="ri-check-line" style="width:100%;margin-top:10px">` — el estilo inline se conserva vía passthrough de atributos, tal como se acordó explícitamente ("mantener los estilos inline estrictamente necesarios hasta que exista un componente o utilidad compartida").
- Botones de ambos modales (Cancelar/Guardar) → `<x-button variant="secondary">`/`<x-button variant="primary">`, mismo criterio que Productos.
- Todos los `id` (`btnAgregar`, `cancelProducto`, `cancelProveedor`, etc.) se conservaron idénticos — el JS de `PEND-08` (reconstrucción desde `OLD_ITEMS`/`FIELD_ERRORS`) sigue funcionando sin cambios.

### Verificación

- Suite completa: 182 passed, mismo único fallo preexistente no relacionado (`ExampleTest`) — incluye los tests de `PEND-08` (`CompraStockRecoveryTest`), que siguen pasando sin cambios sobre la nueva marcación.
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`): sin componentes `<x-...>` sin resolver, ids clave e ícono nuevo presentes en el HTML generado.

### Documentación actualizada

- `docs/modulos.md`: sección "Vista de Registro (`compras/create.blade.php`)" actualizada con el detalle de la migración.
- `docs/design-system.md` §22: Compras marcado completo en `UI-05` (índice + `create`).

### Estado al cierre

`compras/create.blade.php` migrado y verificado. Módulo de Compras completo en `UI-05`. Próximo paso: `ventas/create.blade.php`, que comparte el mismo patrón `.venta-wrap` y ya tiene `PEND-08` resuelto de este lado también.

---

## 2026-07-24 (continuación) — Sprint UI-05: migración de `ventas/create.blade.php`

### Auditoría

Misma estructura que `compras/create.blade.php` (`.venta-wrap`/`.venta-left`/`.venta-right`), con diferencias puntuales: `.venta-left` no tiene ningún encabezado (a diferencia del emoji de Compras); dos botones extra sin equivalente en Compras ("Nuevo cliente", "Público en general"); y el botón "Imprimir recibo" usaba un emoji (🧾) como parte de su texto visible, mismo tipo de inconsistencia que el encabezado ya resuelto en Compras. Se encontraron además: `.badge-ok`/`.badge-bad` en el dropdown de sugerencias de producto (generados por JS, ya definidos en el CSS desde `UI-03` — no requieren corrección) y `.row` (usada dos veces para agrupar botones/controles) **sin ninguna regla en `public/css/style.css`** — clase huérfana, documentada, no corregida por no ser parte del alcance.

### Alcance aprobado

Mismo criterio que Compras: los 3 `.card` → `<x-card>` (con `title`/`icon` solo donde ya existía un encabezado real), todos los botones Blade → `<x-button>`, sin tocar autocomplete, cálculos, FEFO (en `VentaController`, no en la vista), `OLD_ITEMS`/`FIELD_ERRORS`, ni el botón de eliminar fila (generado por JS). Ícono confirmado para "Imprimir recibo": `ri-printer-line`.

### Implementación

- `.venta-left.card` → `<x-card class="venta-left">` (sin `title`, no había encabezado que migrar).
- Los 2 `.card` de `.venta-right` → `<x-card title="Datos de la venta" icon="ri-file-list-2-line">` / `<x-card title="Realizar venta" icon="ri-cash-line">`.
- Botones: "Agregar" → `variant="primary"`; "Cancelar venta" → `variant="danger"`; "Nuevo cliente"/"Público en general" → `variant="secondary"`; "Aceptar" (submit) → `variant="primary"`, estilo inline conservado; "Imprimir recibo" → `variant="secondary" icon="ri-printer-line"` (reemplaza el emoji), estilo inline conservado; modal "Nuevo cliente" (Cancelar/Guardar) → `secondary`/`primary`.
- Todos los `id` conservados (`btnAgregar`, `btnNuevoCliente`, `btnPublico`, `btnTicket`, `cancelCliente`, etc.) — el JS de `PEND-08` y el resto de la lógica siguen funcionando sin cambios.

### Verificación

- Suite completa: 182 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`): sin componentes `<x-...>` sin resolver, todos los ids clave y el nuevo ícono presentes en el HTML generado.

### Documentación actualizada

- `docs/modulos.md`: sección "Vista de Registro (`ventas/create.blade.php`)" actualizada con el detalle de la migración y el hallazgo de `.row` huérfana.
- `docs/design-system.md` §22: Ventas actualizado — `create` migrado, `index` sigue pendiente.

### Estado al cierre

`ventas/create.blade.php` migrado y verificado. Ambos formularios dinámicos (Compras y Ventas `create`) quedan completos en el Design System, con `PEND-08` ya resuelto de base. Pendiente dentro de `UI-05`: `ventas/index.blade.php`, y luego Lotes, Clientes, Proveedores, Usuarios, Roles, Configuración, Login, según el orden acordado.

---

## 2026-07-24 (continuación) — Sprint UI-05: migración de `ventas/index.blade.php` (cierre del módulo Ventas)

### Auditoría

Vista simple, mismo patrón que `compras/index.blade.php`: `<div class="card panel">` con `.toolbar` (`<h1 class="title">Ventas</h1>` + botón "Nueva venta"), tabla paginada, fila `@empty`. Se encontró que la documentación (`docs/modulos.md`) mencionaba un "panel de detalles expandible por venta" que **no existe** en el código actual — misma clase de inexactitud ya corregida antes en la documentación de Compras.

**Hallazgo principal:** el chip de estado (`$map = ['pagada' => 'ok', 'pendiente' => 'warn', 'anulada' => 'bad']`, con `neutral` de fallback) nunca puede mostrar `ok`/`warn`/`bad` con los datos reales, porque `VentaController::store()` siempre guarda `'estado' => 'confirmada'` — un valor ausente del mapa. Documentado como `BUG-11`, sin corregir por ser una decisión de negocio (¿el sistema debería tener estados reales de pago, lo cual requeriría además `PEND-03`? ¿o el mapa de colores debería ajustarse al único valor que existe hoy?), no de presentación.

Se confirmó además que, igual que con el chip "Inyectable" de Productos, `chip-neutral` no tiene variante en `x-badge` — migrar `ok`/`warn`/`bad` a `x-badge` dejando `neutral` como `<span>` crudo habría partido en dos el mismo conjunto de estados.

### Alcance aprobado

`<div class="card panel">` → `<x-card>`, botón "Nueva venta" → `<x-button variant="primary" icon="ri-add-line">`, fila `@empty` → `<x-empty-state>`. Chip de estado **sin migrar** (mismo criterio que Productos). `.toolbar`, encabezado, tabla, paginación y `VentaController::index()` sin cambios. `.ventas-page` (huérfana, sin regla en el CSS) documentada, no corregida.

### Implementación

Exactamente el alcance aprobado, sin desviaciones — mismo patrón mecánico ya aplicado en `compras/index.blade.php`.

### Verificación

- `tests/Feature/VentasIndexTest.php` (nuevo, 5 casos — no existía ningún test de `ventas/index` antes): estado vacío con `x-empty-state`, tabla con total calculado, ningún `<x-` sin resolver, el chip de estado se sigue viendo `neutral` con el estado actual `confirmada` (confirma `BUG-11` en la práctica), acceso sin sesión redirige a login.
- Suite completa: 187 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`): sin componentes `<x-...>` sin resolver.

### Documentación actualizada

- `docs/pendientes.md`: nuevo `BUG-11` (chip de estado nunca refleja pagada/pendiente/anulada), agregado a la tabla de prioridades.
- `docs/modulos.md`: sección "Vista de Listado (`ventas/index.blade.php`)" corregida (panel expandible inexistente) y ampliada con el detalle de la migración y `BUG-11`.
- `docs/design-system.md` §22: Ventas marcado completo en `UI-05` (`create` + `index`).

### Estado al cierre

Módulo de Ventas completo en `UI-05` (`create` e `index` migrados). Próximo paso: continuar con Lotes (pendiente desde `PEND-07`), o Clientes/Proveedores/Usuarios/Roles/Configuración/Login según el orden acordado — a decidir con el usuario.

---

## 2026-07-24 (continuación) — Sprint UI-05: migración de `clientes/index.blade.php`

### Decisión de secuencia

El usuario decidió cerrar primero todos los módulos administrativos (Clientes, Proveedores, Roles, Usuarios, Configuración, y luego Login/Register) antes de retomar Lotes — su migración implica revisar arquitectura, vistas huérfanas (`PEND-07`) y decisiones funcionales que exceden una migración al Design System, y el usuario prefiere no interrumpir el ritmo de los listados administrativos por eso.

### Auditoría

Estructura similar a Productos/Proveedores/Roles/Usuarios (index + modal), pero con 3 diferencias reales encontradas:
1. `<link rel="stylesheet" href="...style.css">` duplicado — el CSS global ya se carga en `app.blade.php`.
2. `@if(session('success')) ... @elseif(session('error')) ...` local, duplicando el banner que `app.blade.php` ya muestra globalmente — el mismo tipo de problema resuelto en `UI-04A` Fase 1, pero en su momento solo se buscaron duplicados de `$errors->any()`, no de `session('success')`/`session('error')`; esta vista quedó fuera de ese barrido.
3. `<section class="panel">` en vez de `<div class="card">` — `.panel` solo aplica `padding:18px`, sin el borde/sombra que sí tiene `.card`/`x-card`. Migrar implicaba un cambio visual real, no neutro.

Se verificó que `ClienteController` no necesitaba ningún cambio, y que el modal sigue exactamente el mismo patrón ya migrado en Productos/Proveedores/Roles/Usuarios.

### Decisiones aprobadas

Las 3 confirmadas: eliminar el `<link>` duplicado, eliminar el banner de sesión local (dejando `app.blade.php` como único punto de verdad — mismo criterio a aplicar en Proveedores/Roles/Usuarios/Configuración salvo excepción justificada), y migrar `.panel` → `<x-card>` aceptando el borde/sombra nuevo como parte de la normalización de `UI-05`.

### Implementación

- Eliminado el `<link rel="stylesheet">` duplicado y el bloque `session('success')`/`session('error')` local.
- `<section class="panel">` → `<x-card>`.
- Botón "Nuevo cliente" → `<x-button variant="primary" href="#" id="btn-open-create">`.
- `<div class="empty">` → `<x-empty-state message="No hay clientes registrados." />`.
- Botones del modal (Cancelar/Guardar) → `<x-button variant="secondary">`/`<x-button variant="primary">`.
- Sin cambios: modal (JS, prellenado de campos), buscador con sugerencias y filtrado client-side, acciones de fila, `@error()` de `UI-04A`, `ClienteController`.
- Documentado sin corregir: `<h1 class="h-top">` (clase inexistente) y la tabla sin `.table-wrap`/`.table-soft`.

### Verificación

- `tests/Feature/ClientesIndexTest.php` (nuevo, 8 casos — no existía ningún test de este listado antes): estado vacío, tabla con datos, el mensaje de éxito aparece **una sola vez** (confirma que ya no hay duplicación), ningún `<x-` sin resolver, `style.css` aparece **una sola vez** en el HTML (confirma la eliminación del `<link>` duplicado), ids clave conservados, control de acceso por permiso `clientes.ver`, redirect a login sin sesión.
- Suite completa: 195 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`): sin componentes `<x-...>` sin resolver, `style.css` presente una sola vez.

### Documentación actualizada

- `docs/modulos.md`: sección "Módulo: Clientes" ampliada con el detalle completo de la migración y las 2 duplicaciones eliminadas.
- `docs/design-system.md` §22: Clientes marcado completo en `UI-05`; se dejó anotado que el chequeo de duplicados de sesión se extiende a los módulos administrativos restantes.

### Estado al cierre

`clientes/index.blade.php` migrado y verificado. Próximo paso, según el orden acordado con el usuario: Proveedores.

---

## 2026-07-24 (continuación) — Sprint UI-05: migración de `proveedors/index.blade.php`

### Auditoría

Mismo patrón que Clientes (index + modal), con las mismas dos duplicaciones (`<link>` de `style.css`, banner de sesión local) más un hallazgo nuevo y más serio: `<h1 class="h-top" style="color:white;">` y `<p style="color:white;">` sobre un fondo claro (`.panel`/`.card` no tienen fondo oscuro) — texto prácticamente invisible, el mismo tipo de bug ya documentado en `PEND-07` para `productos/lotes/index.blade.php`, pero encontrado ahora en un módulo que sí se está migrando activamente. También se encontró: el botón "Buscar" del formulario de búsqueda no tenía ninguna clase CSS (a diferencia de Productos, que usa `.btn-outline`), y el texto del estado vacío tenía un typo ("No hay proveedors registrados.", sin la "e").

Se verificó que `ProveedorController` no necesitaba ningún cambio y que el modal sigue el mismo patrón ya migrado en Productos/Clientes.

### Decisión sobre el texto blanco

Se presentó como hallazgo a confirmar, distinguiéndolo explícitamente del criterio ya establecido de "encabezados se dejan igual, se migran todos juntos en su turno" — ese criterio aplica a diferencias de *estilo* entre encabezados que igual se leen bien; acá el texto directamente no se ve, es un bug de visibilidad. El usuario confirmó corregirlo como parte de este sprint, junto con el typo.

### Alcance aprobado e implementado

- Eliminados el `<link rel="stylesheet">` duplicado y el bloque `session('success')`/`session('error')` local.
- `<section class="panel">` → `<x-card>` (mismo criterio que Clientes).
- Quitado `style="color:white"` del `<h1>` y el `<p>` del encabezado.
- Botón "Nuevo proveedor" → `<x-button variant="primary" href="#">`; botón "Buscar" (antes sin clase) → `<x-button type="submit" variant="secondary">`; botones del modal → `<x-button variant="secondary">`/`<x-button variant="primary">`.
- `<div class="empty">No hay proveedors registrados.</div>` → `<x-empty-state message="No hay proveedores registrados." />` (typo corregido).
- Sin cambios: modal (JS, prellenado de campos), acciones de fila, `@error()` de `UI-04A`, `ProveedorController`.
- Documentado sin corregir: `.search` (clase huérfana, sin regla en el CSS) y la tabla sin `.table-wrap`/`.table-soft`.

### Verificación

- `tests/Feature/ProveedoresIndexTest.php` (nuevo, 9 casos — no existía ningún test de este listado antes): estado vacío con el texto corregido, tabla con datos, ausencia de `color:white` en el HTML, mensaje de éxito una sola vez, ningún `<x-` sin resolver, `style.css` aparece una sola vez, ids clave conservados, control de acceso por permiso `proveedors.ver`, redirect a login sin sesión.
- Suite completa: 204 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`): sin componentes `<x-...>` sin resolver, `style.css` una sola vez, sin `color:white` en el HTML generado.

### Documentación actualizada

- `docs/modulos.md`: sección "Módulo: Proveedores" ampliada con el detalle completo de la migración, las duplicaciones eliminadas y el bug de visibilidad corregido.
- `docs/design-system.md` §22: Proveedores marcado completo en `UI-05`.

### Estado al cierre

`proveedors/index.blade.php` migrado y verificado. Próximo paso, según el orden acordado: Roles.

---

## 2026-07-24 (continuación) — UI-06: estandarización global de buscadores

### Alcance de este sprint — distinto de `UI-05`

A pedido del usuario, se abrió una tarea nueva (`UI-06`) enfocada exclusivamente en unificar la apariencia y el comportamiento de **todos los buscadores del sistema**, tomando `productos/index.blade.php` como referencia — **no** es la migración completa de Roles/Usuarios a `UI-05` (esos módulos siguen con su `.panel`/`.hero`, alertas y botones sin migrar; solo sus buscadores se tocaron acá). Se corrigió la numeración del roadmap: `UI-06` ya estaba reservado para "Limpieza final" desde `UI-01` — ese sprint se corrió a `UI-07`.

### Auditoría

Se relevaron **10 buscadores en 6 vistas**: 5 de tipo "filtro" (navegan vía GET: Productos —referencia—, Clientes, Proveedores, Usuarios, Roles) y 4 de tipo "selector" (Compras: producto/proveedor; Ventas: producto/cliente — llenan un campo oculto, nunca navegan). Se comparó HTML, CSS y JS de los 10 caso por caso (ver tabla completa en la conversación): quién tiene dropdown, quién usa `.search-wrap.xl`, qué iconografía usa cada uno (Remix vs. emoji), quién ya usa `x-button`, y qué JS se repite literalmente vs. qué es específico de cada contexto.

Se identificaron 3 características que existen en un solo caso y **no** forman parte del patrón común: el filtrado de tabla en vivo de Clientes, la opción "crear nuevo" embebida en el dropdown de Compras (vs. un botón aparte en Ventas), y el badge de disponibilidad de Ventas. Se decidió no generalizarlas ni quitarlas — quedan documentadas como excepciones de sus vistas.

### Decisión de alcance del propio sprint (dos ajustes pedidos por el usuario)

1. **No crear `<x-search-box>` todavía.** Primero normalizar HTML/CSS/JS dentro de cada vista (sin compartir código real), verificar que todo funcione, y recién en un sprint posterior extraer lo reutilizable — para no propagar un eventual error a los 6 módulos a la vez.
2. **No extraer tampoco a un archivo `public/js/buscador.js` compartido todavía** (se había propuesto como paso intermedio) — mismo motivo: primero validar el patrón repetido, después compartir el código.

### Implementación — patrón unificado aplicado módulo por módulo

- **Proveedores, Usuarios, Roles** (antes: `<form class="search">` + `<button>` sin clase, sin dropdown): se agregó el HTML completo del patrón de Productos (`.search-wrap`, ícono, `<x-button icon="ri-filter-2-line">`, dropdown `#sugg`) y el JS de sugerencias (debounce implícito vía evento `input`, navegación por teclado, cierre al hacer clic afuera), calculando `$suggData` **en la vista** (`@php`) a partir de la colección ya paginada — sin tocar ningún controlador. Iconos por ítem: `ri-truck-line` (Proveedores), `ri-user-3-line` (Usuarios), `ri-lock-2-line` (Roles, mismo ícono que ya usa el sidebar para ese módulo).
- **Clientes** (ya tenía dropdown): se normalizó el tamaño (`.search-wrap.xl` → `.search-wrap`, igual que Productos), se agregó el botón "Buscar" con `x-button` que no tenía, y se cambió el cierre-al-clic-afuera de depender de un `id` propio (`#search-clients`) a depender de la clase `.search-wrap` (igual que Productos) — el filtrado de tabla en vivo se dejó intacto, como excepción documentada.
- **Compras y Ventas** (los 4 selectores): único cambio, reemplazar los iconos emoji por Remix (`ri-archive-2-line` para producto, `ri-truck-line` para proveedor, `ri-user-3-line` para cliente, `ri-add-circle-line` para "crear nuevo"). No se tocó la lógica de selección ni nada relacionado con `PEND-08`.

### Incidente técnico durante la implementación (sin impacto en el resultado final)

Al escribir el regex de normalización de acentos (`replace(/[̀-ͯ]/g,'')`) en los archivos nuevos, la secuencia de escape se guardó como caracteres Unicode combinantes literales en vez de la notación `\u...` (aunque funcionalmente equivalente, ya que esos caracteres SON los códigos U+0300/U+036F). Se detectó por inspección y se corrigió con un script PHP puntual que reescribe la expresión regular byte a byte, para que el código fuente quede idéntico al de `productos/index.blade.php` en vez de solo "equivalente". Verificado con `grep` en cada archivo tras la corrección.

### Verificación

- Tests nuevos: `ProveedoresIndexTest.php` (+1 caso sobre el patrón de buscador), `UsuariosBuscadorTest.php` (3 casos, alcance acotado a UI-06), `RolesBuscadorTest.php` (3 casos), `ClientesIndexTest.php` (+2 casos: patrón sin `.xl`/con botón, y filtrado en vivo preservado), `ComprasVentasIconosBuscadorTest.php` (2 casos: ausencia de emoji + presencia de iconos Remix en los 4 selectores).
- Se re-corrieron explícitamente `CompraStockRecoveryTest.php`/`VentaStockRecoveryTest.php` (`PEND-08`) para confirmar que el cambio de iconos no afectó la reconstrucción de filas — sin cambios, todo sigue pasando.
- Suite completa: **215 passed**, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`): las 4 vistas "filtro" nuevas muestran ícono y dropdown sin componentes sin resolver; Compras/Ventas confirmados sin ningún emoji restante y con los 4 íconos Remix presentes.

### Documentación actualizada

- `docs/design-system.md`: nueva sección 19.1 documentando el patrón unificado (los dos modos, HTML de referencia, excepciones no generalizadas); `x-search-box` agregado al catálogo de componentes previstos (§19); §22 con `UI-06` completo y la renumeración de "Limpieza final" a `UI-07`.

### Estado al cierre

Los 10 buscadores del sistema comparten ahora el mismo HTML/CSS/comportamiento (salvo las 3 excepciones documentadas y deliberadamente no generalizadas). El código sigue sin compartirse entre vistas — eso queda para un sprint posterior, una vez validado en uso real. Próximo paso: retomar el orden de `UI-05` que quedó en pausa — Roles (migración completa a `x-card`/`x-button`/`x-alert`), luego Usuarios, Configuración, y Login/Register.

---

## 2026-07-24 (continuación) — Sprint UI-05: migración de `rols/index.blade.php` (cierre del módulo Roles)

### Auditoría

Se revisó `rols/index.blade.php` y `RolController` completos antes de proponer ningún cambio. Hallazgos:

- Un bloque decorativo `<div class="hero">` con fondo azul (`style="background:#1157c2;color:#fff"`) envolvía todo el contenido, junto con un `<div class="shadow">` y 5 `<span class="bubble b1">`…`<b5>` ("burbujas" decorativas). Se verificó por `grep` en `public/css/style.css` que **ninguna** de las clases `.hero`, `.grid`, `.shadow`, `.bubble`, `.b1`-`.b5` tiene alguna regla definida: el efecto de burbujas nunca llegó a implementarse, y el único efecto visual real venía del `style` inline.
- La columna de acciones tenía un `<td>` anidado dentro de otro `<td>` (`<td><td class="actions">...</td></td>`) — HTML inválido que los navegadores toleran cerrando implícitamente la etiqueta, pero incorrecto.
- El `@push('scripts')` cargaba `<script src="{{ asset('js/rols.js') }}">` y exponía `window.routesRolsStore`. Se confirmó por búsqueda en todo el proyecto que **`public/js/rols.js` nunca existió** — ni el archivo ni el directorio `public/js/` existen en el repositorio. Cada carga de `/rols` producía un 404 silencioso (sin romper la página, porque toda la funcionalidad del modal ya estaba implementada en el `<script>` inline de la misma vista).
- Se confirmó que `RolController` no necesitaba ningún cambio y que el modal sigue el mismo patrón ya migrado en Productos/Clientes/Proveedores.
- Al leer el controlador se notó que la nota de "Bug Crítico" en `docs/modulos.md` (permisos no sincronizados en `store()`/`update()`) no coincidía con el código actual: ambos métodos sí llaman a `$rol->permisos()->sync(...)`. Se verificó en `docs/pendientes.md` que ese bug (`BUG-01`) ya está marcado **✅ Corregido (2026-07-07)** — la sección de `docs/modulos.md` simplemente nunca se había actualizado tras esa corrección. Se aprovechó esta migración para corregir esa desactualización de la documentación.

### Alcance aprobado e implementado

El usuario aprobó los tres hallazgos con criterios explícitos:

- **A)** Migrar a `<x-card>` y eliminar `.hero`/`.grid`/`.shadow`/`.bubble` y todo su HTML asociado, dado que esas clases no tienen ningún efecto CSS real.
- **B)** Corregir el `<td>` anidado, como bug de marcado (no como cambio de diseño).
- **C)** Eliminar el `<script src="js/rols.js">` y `window.routesRolsStore`, como limpieza de código muerto, dejando constancia en este diario de que el archivo nunca existió y nunca fue utilizado.

Implementación:
- `<div class="hero"><div class="panel" style="background:#1157c2;color:#fff">` → `<x-card>`. Se quitó únicamente `color:#fff` del `<span class="h-top">` del encabezado (habría quedado invisible sobre el nuevo fondo blanco); no se tocó nada más del encabezado, mismo criterio de "los encabezados se normalizan todos juntos en su propio turno" ya aplicado en Clientes/Proveedores.
- Botón "Nuevo rol" → `<x-button variant="primary" icon="ri-add-line">`; botones del modal (Guardar/Cancelar) → `<x-button variant="primary">`/`<x-button variant="secondary">`.
- `<div class="empty">No hay roles registrados.</div>` → `<x-empty-state message="No hay roles registrados." />`.
- `<td><td class="actions">...</td></td>` → un único `<td>` con el mismo contenido interno (Ver/Editar/Eliminar) sin cambios.
- Eliminado el `@push('scripts')` completo (el script externo y `window.routesRolsStore`); el archivo termina en el `@endpush` del modal.
- Sin cambios: `RolController`, el modal (JS de crear/editar/ver, autogeneración de slug, cierre al clic afuera), el buscador (ya migrado en `UI-06`), la tabla y `@error()` de `UI-04A`.

### Verificación

- `tests/Feature/RolesIndexTest.php` (nuevo, 8 casos): estado vacío, tabla con datos, ningún `<x-` sin resolver, ausencia de `js/rols.js`/`routesRolsStore`, ausencia de `<td><td>`, patrón de buscador `UI-06`, ids del modal conservados, redirect a login sin sesión.
- Suite completa: **215 passed**, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Render contra la base de datos real (MySQL, vía `tinker` con `DB::beginTransaction()`/`rollBack()`, compartiendo manualmente `errors` como `ViewErrorBag` vacío): sin componentes `<x-...>` sin resolver, sin `js/rols.js`, sin `routesRolsStore`, sin `<td><td>`, sin clase `.hero`, sin `bubble`, con `.card` y `.search-wrap` presentes.

### Documentación actualizada

- `docs/modulos.md`: sección "Módulo: Roles" ampliada con el detalle completo de la migración; la nota de "Bug Crítico" se marcó como "a reverificar" en vez de eliminarla, señalando la discrepancia encontrada con el código actual.
- `docs/design-system.md` §22: Roles marcado completo en `UI-05`.

### Estado al cierre

`rols/index.blade.php` migrado y verificado. Con Clientes, Proveedores y Roles cerrados, el orden acordado por el usuario para los módulos administrativos continúa con: Usuarios (migración completa, no solo su buscador) → Configuración → Login/Register. Después de eso, retomar Lotes (`PEND-07`) y los pendientes funcionales (`BUG-11`, `PEND-09`).

---

## 2026-07-27 — Sprint UI-05: migración de `users/index.blade.php`, cambio de ritmo del proyecto

A partir de esta sesión el usuario pidió acelerar el cierre de `UI-05`: sin auditorías/reportes largos por módulo, implementación directa cuando no hay decisión arquitectónica real, mismo criterio ya validado (Design System, `UI-05`/`UI-06`), sin tocar lógica de negocio/controladores/BD ni CSS nuevo salvo indispensable.

`users/index.blade.php`: mismo patrón mecánico ya aplicado en Roles/Clientes/Proveedores. `<section class="hero"><div class="panel">` → `<x-card>`; alertas de sesión → `<x-alert>`; botón "Nuevo usuario" → `<x-button variant="primary">`; fila `@empty` → `<x-empty-state>`; botones de ambos modales (Guardar/Cancelar/Cerrar) → `<x-button>`. Buscador ya migrado en `UI-06`, sin tocar. Sin cambios en `UserController` ni en el JS de los modales.

Suite completa: 223 passed, mismo único fallo preexistente no relacionado (`ExampleTest`). Sin tests nuevos (vista ya cubierta por `UsuariosBuscadorTest`; no se detectó necesidad de cobertura adicional para un cambio puramente estructural).

Próximo paso: Configuración (`configuracion/edit.blade.php`).

### Configuración (`configuracion/edit.blade.php`)

Único cambio real: el `<div class="hero"><div class="panel" style="background:#1157c2;color:#fff">` (un `<h1>` decorativo de uso único, sin la clase `page-title` que sí comparten los listados) se colapsó directamente en `<x-card title="Configuración del sistema" icon="ri-settings-3-line">`, a diferencia del criterio de "header fuera del prop `title`" usado en los listados — acá no había ningún patrón de encabezado compartido que preservar. Alerta de éxito → `<x-alert>`, botón "Guardar" → `<x-button variant="primary">`. `ConfiguracionController` sin cambios. De paso se agregó la sección "Módulo: Configuración" a `docs/modulos.md`, que nunca había existido pese a que el módulo se implementó en el sprint de `BUG-04`.

Suite completa: 223 passed, mismo único fallo preexistente no relacionado. Próximo paso: Login/Register.

### Login/Register (cierre de `UI-05`)

`login.blade.php` tenía un layout split-screen con ilustración, degradado y sombra grande (`0 30px 80px`) — la decisión de `UI-02` sobre este módulo ("una columna vs. split-screen") había quedado abierta desde `UI-01`. Se consultó al usuario antes de tocarlo (única pausa de esta sesión: es una decisión de diseño real, no un swap mecánico) y se resolvió a favor de **una columna**: se eliminó la ilustración, el degradado, la sombra exagerada y el bloque `:root` local que duplicaba tokens ya globales; el formulario pasó a `<x-card class="login-card">` centrada. `register.blade.php` (ya tenía `.hero`/`.panel` decorativo) se migró al mismo patrón: `<x-card title="Crear cuenta" icon="ri-user-add-line">`, botones → `<x-button>`. Las reglas de centrado compartidas por ambas vistas (`.login-shell`/`.login-card`) se agregaron una sola vez a `public/css/style.css` (nueva sección §17) en vez de duplicarse en las dos vistas. El resto del CSS de login (`.field`, `.remember`, `.form-head`) sigue local, sin necesidad real de compartirse. Sin cambios en Fortify, `CreateNewUser.php` ni ningún controlador.

Suite completa: 223 passed, mismo único fallo preexistente no relacionado.

### Estado al cierre

**`UI-05` (migración de vistas al Design System) completo.** Todos los módulos administrativos, Dashboard, Reportes (índice), Productos, Compras, Ventas, Configuración y Auth migrados. Pendiente, fuera de este sprint: Lotes (`PEND-07`, bloqueado por una decisión funcional previa, no solo visual) y las 6 subvistas de detalle de Reportes (funcionales, sin migrar visualmente). Próximo paso: a decidir con el usuario — `UI-07` (limpieza final), las subvistas de Reportes, o Lotes.

---

## 2026-07-27 (continuación) — Auditoría final antes de la memoria de tesis

Se pidió una auditoría de cierre (sin refactors, solo bugs funcionales reales, rutas huérfanas, CSS/JS muerto, documentación desactualizada) antes de pasar a la etapa de documentación de la tesis. Resultado: **sin bugs críticos que bloqueen la entrega**. Se aprobaron 4 hallazgos 🟡 para corregir antes de cerrar:

1. **`auth/forgot-password.blade.php` y `auth/reset-password.blade.php` extendían `layouts.app`** (inexistente) — mismo bug que `BUG-10`, nunca replicado el fix a estas dos vistas. Confirmado que `Features::resetPasswords()` no está en `config/fortify.php`, por lo que hoy son código huérfano (sin ruta, sin link en la UI). Se corrigió `@extends('app')` en ambas, sin activar la funcionalidad ni migrarlas al Design System — el pedido explícito era solo eliminar el bug latente.
2. **`docs/modulos.md`** actualizado: se quitó la descripción del flujo de recuperación de contraseña como si estuviera activo, se agregó una nota explícita de que `Features::resetPasswords()` está deshabilitada y de que ambas vistas son huérfanas (mismo tipo de caso que `PEND-07`).
3. **`docs/modulos.md` → "Layout y Navegación"** corregida: se quitó "Lotes" de la lista de entradas del sidebar (el `@if (Route::has('lotes.index'))` de `app.blade.php` nunca es verdadero, ese ítem nunca se renderiza) y se agregaron "Reportes" y "Configuración", que sí aparecen y no estaban listados.
4. **`BUG-02`, `BUG-06`, `BUG-11` revisados:**
   - `BUG-02` — **corregido**, y con un hallazgo más grave de lo documentado: el modal de **creación** de productos (mismo formulario que edición) tampoco tenía el campo `precio_venta`, pese a que `store()` ya lo exige `required` — confirmado con una petición real que crear un producto desde la UI ya estaba roto, no solo editar el precio. Se agregó el campo al modal compartido (crear + editar), su `data-precio_venta` en el botón "Editar", y la validación en `ProductoController::update()` (mismas reglas que `store()`). Sin cambios de lógica de negocio: se completó un campo que ya existía en modelo/BD/`store()`.
   - `BUG-06` — **corregido**: `scopeEntradas()`/`scopeSalidas()` de `MovimientoStock` filtraban por el signo de `cantidad` (siempre positivo); ahora filtran por la columna `tipo`. Confirmado por búsqueda exhaustiva que ningún controlador ni vista llamaba a estos scopes (`ReporteController::movimientos()` ya los evitaba deliberadamente), así que el fix no tiene efectos secundarios.
   - `BUG-11` — **no se tocó**, por instrucción explícita: requiere una decisión de negocio (¿el chip de estado debería reflejar un ciclo de vida real de pago, lo cual implica `PEND-03`? ¿o el mapa de colores debería ajustarse al único valor que el sistema produce hoy?). Se verificó que sigue correctamente documentado como limitación conocida en `docs/pendientes.md`.

**Pruebas nuevas:** `tests/Feature/ProductosIndexTest.php` (+4 casos: creación con precio, creación sin precio falla validación, edición actualiza precio, campo presente en el HTML) y `tests/Feature/MovimientoStockScopesTest.php` (nuevo, 1 caso: `entradas()`/`salidas()` distinguen correctamente por `tipo`).

**Suite completa:** 228 passed, mismo único fallo preexistente no relacionado (`ExampleTest`, `GET /` siempre redirige por diseño).

### Estado al cierre

Implementación funcional cerrada. No quedan hallazgos que requieran código antes de pasar a la memoria de tesis (diagramas, documentación formal). Los ítems 🟢 de la auditoría (vistas huérfanas de Lotes, módulos esqueleto Recibos/Devoluciones/Permisos, `AUS-04`, `ESQ-*`, subvistas de Reportes sin migrar, `BUG-11`) quedan documentados como limitaciones conocidas, sin acción pendiente de código para la entrega.

---

## 2026-07-27 (continuación) — `PEND-01`: corrección de la persistencia del recibo

Se retomó `PEND-01` a raíz de la revisión de RF10/CU11 de la memoria (recibos/comprobantes de venta). El usuario pidió explícitamente diagnóstico primero, sin asumir nada, y corregir **solo la persistencia** — sin tocar ventas, FEFO, stock, `PEND-08`, impresión ni UI, y sin crear funcionalidades nuevas.

### Diagnóstico (verificado empíricamente, no asumido)

Con una prueba temporal (registrar una venta real y leer la tabla `recibos` sin pasar por el modelo, luego eliminada) se confirmó la causa exacta, más grave que lo ya documentado:
- La migración real de `recibos` solo tenía `id`, `venta_id` (FK única), `created_at`, `updated_at`.
- El modelo `Recibo` usaba `SoftDeletes` **sin que la migración tuviera `deleted_at`** — el mismo bug que `BUG-09` (`MovimientoStock`), nunca detectado antes para este modelo. Confirmado con una consulta real: `SQLSTATE[HY000]: no such column: recibos.deleted_at`. Esto rompe cualquier **lectura** (`Recibo::count()`, `$venta->recibo`), no la escritura.
- `VentaController::store()` ya llamaba a `$venta->recibo()->create(['venta_id'=>.., 'monto'=>$total])`. Se confirmó que la fila **sí se insertaba** (con `venta_id`), pero **sin `monto`** — no era `fillable` (que en cambio declaraba `nro_recibo`/`fecha`/`metodo_pago`/`observacion`/`estado`, ninguno real) ni columna existente, así que Eloquent lo descartaba en el mass-assignment.
- Efecto neto: el recibo se creaba a medias y quedaba **ilegible para siempre** — cualquier vista que lo leyera habría lanzado un error 500 real.

Se confirmó por búsqueda exhaustiva que nada llama a `delete()`/`restore()`/`withTrashed()` sobre `Recibo`, habilitando el mismo tipo de fix que `BUG-09` (quitar el trait sin riesgo).

### Corrección aplicada (aprobada antes de escribir código, dado que implica una migración)

- Nueva migración `add_monto_to_recibos_table`: agrega `decimal('monto', 10, 2)` — la única columna que el controlador ya necesitaba. No se agregaron los otros 4 campos fantasma del `fillable` viejo: ningún código los usa.
- `app/Models/Recibo.php`: se quitó `SoftDeletes`; `$fillable` → `['venta_id', 'monto']`; cast `fecha` (columna inexistente) → `'monto' => 'decimal:2'` (mismo patrón que `DetalleVenta::precio_unitario`).
- `VentaController::store()`: **sin cambios** — el `create()` que ya existía pasa a persistir con éxito.

### Verificación

- `tests/Feature/ReciboPersistenciaTest.php` (nuevo, 3 casos): venta crea recibo con monto correcto, `Venta::recibo()` legible sin excepción, `Recibo::count()` ya no lanza el error de `SoftDeletes`.
- Suite completa: 231 passed, mismo único fallo preexistente no relacionado (`ExampleTest`).
- Se levantó MySQL de XAMPP (no estaba corriendo) para migrar y verificar contra la base real `farmacia_tfg`: migración aplicada, y una venta real registrada dentro de una transacción revertida confirmó recibo creado (monto correcto) y `Venta::recibo()` legible — sin residuos en la BD real.

### Documentación actualizada

`docs/pendientes.md` (`PEND-01` marcado resuelto en su parte de persistencia, con el diagnóstico completo; `ESQ-01` marcado resuelto; nota de la limitación de descuento en Reporte de Ventas actualizada — el neto ahora sí se persiste en `recibos.monto`, aunque el reporte no lo usa), `docs/base_de_datos.md` (esquema de `recibos` actualizado), `docs/modulos.md` (sección Recibos actualizada).

### Estado al cierre

`PEND-01` resuelto en su parte de persistencia: el recibo se crea, persiste el monto y es legible sin errores. **Sigue pendiente, fuera de este alcance:** `ReciboController` continúa vacío, sin rutas ni vista — no hay forma de ver/imprimir el recibo desde los datos ya persistidos (el botón "Imprimir recibo" sigue siendo solo del navegador). Si se quiere cerrar `RF10`/`CU11` por completo, ese es el trabajo que falta.

---

## 2026-07-28 — Cierre de `RF10`/`CU11`: visualización e impresión del recibo persistido

### Auditoría previa (sin código), a pedido explícito del usuario

Se auditó el flujo completo antes de tocar nada: `ReciboController` (7 métodos vacíos), `Recibo`/`Venta` (relaciones ya correctas desde `PEND-01`), rutas (`grep` confirmó cero rutas `/recibos`), y el botón "Imprimir recibo" de `ventas/create.blade.php`. Hallazgo que corrigió un supuesto del propio pedido: el botón **no imprime "solo del lado del navegador" — no hace absolutamente nada**. Tiene `id="btnTicket"` y una referencia `const $btnTicket = ...`, pero cero `addEventListener` y cero `window.print()`; el propio comentario del código lo delata (`// dejo tu código de ticket igual`). Además, el botón vive en el formulario de creación de la venta, *antes* de que exista el recibo — no hay `venta_id` que enlazar en ese momento.

Se presentó un primer plan (redirigir automáticamente al recibo tras registrar la venta) que el usuario rechazó explícitamente: un cajero de farmacia normalmente sigue registrando ventas, y forzar una redirección al recibo rompería ese flujo. Se rediseñó el plan según instrucción del usuario:

1. Registrar venta → 2. Volver a `ventas.index` (como hoy) → 3. Banner de éxito + botones "Ver recibo"/"Nueva venta" → 4. El usuario decide si entra o sigue vendiendo.

También se corrigió un segundo supuesto durante la auditoría: `ventas/index.blade.php` **no tiene su propio renderizado de `session('success')`** — el único punto de todo el proyecto que lo muestra es el banner global de `app.blade.php` (`{{ session('success') }}`, escapado). El usuario decidió explícitamente **no** tocar ese banner global (mezclaría lógica específica de Ventas en el layout genérico) y en su lugar agregar el bloque "Ver recibo"/"Nueva venta" únicamente en `ventas/index.blade.php`, condicionado a una clave de sesión aparte (`recibo_id`) — sin tocar el mecanismo de flash existente.

### Implementación

- **`app/Http/Controllers/VentaController.php`:** único cambio de lógica — se capturó el `id` del `Recibo` ya creado dentro del `DB::transaction()` (variable por referencia `&$reciboId`, sin tocar `$total`/FEFO/stock/descuentos) y se agregó al redirect: `->with('recibo_id', $reciboId)`.
- **`routes/web.php`:** `GET /recibos/{recibo}` (`recibos.show`), dentro del grupo `auth` ya existente — mismo criterio que `ventas.*` (sin permiso granular nuevo).
- **`app/Http/Controllers/ReciboController.php`:** único método implementado, `show()` — carga `venta.cliente`, `venta.user`, `venta.detalles.producto` (evita N+1). Los otros 6 métodos (`index`, `create`, `store`, `edit`, `update`, `destroy`) quedan vacíos, deliberadamente, tal como pidió el usuario.
- **`resources/views/recibos/show.blade.php`** (nueva): `<x-card title="Recibo N° {{ $recibo->id }}">`, datos (fecha, cliente, vendedor), tabla de productos/cantidad/precio/subtotal, total (`Recibo::monto`), botón `<x-button onclick="window.print()">Imprimir</x-button>`. Reutiliza `.table`/`.table-wrap`/`.money`/`.ta-right` ya existentes, sin CSS nuevo salvo el `@media print` (ver abajo). Único CSS aprobado explícitamente por el usuario ("no es CSS innecesario, es parte de la funcionalidad de impresión"): oculta `.sidebar`, `.main-top`, `.flash`, `footer` y `.print-actions`; resetea el padding de `.main` a 0. Sin cambios de color ni tipografía, agregado vía `@push('styles')` local a esta vista (no al CSS global).
- **`resources/views/ventas/index.blade.php`:** bloque `@if(session('recibo_id'))` con `<x-button icon="ri-receipt-line">Ver recibo</x-button>` + `<x-button variant="secondary" icon="ri-add-line">Nueva venta</x-button>`, exactamente como lo especificó el usuario. Flash de un solo uso — no persiste tras la siguiente navegación.
- **`resources/views/ventas/create.blade.php`:** eliminado el botón "Imprimir recibo" (inerte, confirmado en la auditoría) y su referencia JS muerta (`$btnTicket`, `hayItems()`).

### Verificación

- **`tests/Feature/RecibosTest.php`** (nuevo, 6 casos): flujo completo end-to-end (POST `/ventas` → `Recibo` persistido con el monto correcto → redirect a `ventas.index` con `recibo_id` en sesión → la página siguiente muestra "Ver recibo"/"Nueva venta" con el enlace correcto → `GET /recibos/{id}` muestra número/fecha/cliente/vendedor/producto/total → botón "Imprimir" con `onclick="window.print()"` → cliente nulo muestra "—" → `@media print` presente con los selectores esperados → botón inerte de `ventas/create` confirmado eliminado → sin sesión redirige a login).
- Suite completa: **237 passed**, mismo único fallo preexistente no relacionado (`ExampleTest`).
- **Verificación manual contra MySQL real:** se levantó `php artisan serve` temporal + Chrome (MCP), login real (`admin@farmacia.com`), 2 ventas registradas de punta a punta desde la interfaz real. Ambas mostraron el banner "Venta registrada correctamente." con los botones "Ver recibo"/"Nueva venta"; el recibo abierto coincidió exactamente con los datos de cada venta (producto, cantidad, precio unitario, total); el segundo "Ver recibo" apuntó al recibo N° 3, no al N° 2 anterior (confirmado leyendo el `href` real); volver a `/ventas` y registrar una segunda venta no tuvo ninguna fricción (flujo de caja intacto). **No se hizo clic real en "Imprimir"** — dispararía el diálogo nativo de impresión del SO, que habría bloqueado la sesión de automatización del navegador (riesgo señalado explícitamente por la skill de automatización). En su lugar se verificó, vía JavaScript en la página, que el botón tiene exactamente `onclick="window.print()"` y que los 6 selectores del `@media print` corresponden a elementos reales del layout. Datos de prueba (2 ventas, sus detalles, recibos y movimientos de stock) limpiados de la BD real al finalizar, con el stock de los productos restaurado a sus valores originales.

### Documentación actualizada

`docs/pendientes.md` (`PEND-01` marcado completo — persistencia + `RF10`/`CU11`), `docs/modulos.md` (nueva sección "Módulo: Recibos"; secciones de Ventas actualizadas: nota sobre `recibo_id` en la ruta `POST /ventas`, bloque "Ver recibo"/"Nueva venta" en el listado, botón "Imprimir recibo" documentado como eliminado en el formulario de creación).

### Estado al cierre

`RF10`/`CU11` cerrados por completo, con el alcance mínimo pedido: sin listado de recibos, sin edición/eliminación/búsqueda/filtros, sin numeración automática, sin estados, sin campos nuevos. El sistema y la memoria de la tesis ya coinciden en este punto.

---

## 2026-07-28 — Auditoría de consistencia memoria/código y corrección de `BUG-12` (Roles sin protección RBAC)

### Contexto

Antes de continuar redactando la memoria de tesis, el usuario pidió auditar capítulo por capítulo la consistencia entre lo documentado (capítulo 3: modelo de negocio, requerimientos, casos de uso) y el sistema real, verificando siempre contra el código y citando archivo/línea, sin asumir nada. Se auditaron actores, requerimientos funcionales, casos de uso, roles y permisos, navegación, reportes y recibos.

### Hallazgo principal: `BUG-12` — Roles sin ninguna protección de acceso real

Durante la verificación de "qué middleware protege cada ruta", se detectó que `routes/web.php` registraba `rols.*` con `Route::resource('rols', RolController::class)->only([...])` **sin ningún `middleware('permiso:rols.*')`** — a diferencia de Productos, Compras, Clientes, Proveedores, Usuarios y Reportes, todos protegidos por ruta. Se verificó exhaustivamente que no había ninguna otra capa de defensa: `RolController` sin `esAdmin()`/`tienePermiso()` internos, sin `Gate::` en ningún Provider, `bootstrap/app.php` sin middleware global, y `rols/index.blade.php` sin ningún botón condicionado por permiso. Efecto real: cualquier usuario autenticado —incluido un Vendedor— podía crear, editar (incluida la asignación de permisos) y eliminar cualquier rol, incluido "Administrador". Se registró primero en `docs/pendientes.md` como `BUG-12` (siguiendo el formato habitual de bugs del proyecto) antes de tocar nada, y se presentó un plan de corrección que el usuario confirmó con dos ajustes explícitos: no tocar `rols/index.blade.php` (el gating de botones por permiso no es un patrón uniforme todavía en Productos/Clientes/Proveedores, se difiere a un sprint de UX/RBAC) y no tocar el sidebar (ya es consistente con el resto del sistema, que tampoco filtra el menú por permiso salvo Configuración).

### Corrección aplicada

- **`routes/web.php`:** se reemplazó el `Route::resource(...)->only([...])` por rutas explícitas, mismo patrón que Usuarios/Clientes/Proveedores: `permiso:rols.ver` (index), `permiso:rols.crear` (store), `permiso:rols.editar` (update), `permiso:rols.eliminar` (destroy). Los 4 slugs ya existían en `PermisoSeeder`, no se sembró nada nuevo.
- **`RolController.php`:** sin cambios.
- **Tests:** `tests/Feature/RolesIndexTest.php` y `tests/Feature/RolesBuscadorTest.php` autenticaban usuarios sin ningún rol asignado — se actualizaron con el mismo patrón ya usado en `ProveedoresIndexTest.php` (rol Administrador con los 4 permisos `rols.*`, rol Vendedor sin ellos, test nuevo de bloqueo 403 en ambos archivos). También se corrigieron dos tests de otros archivos que dependían de la ausencia de protección (`FieldErrorsTest.php`, `FormErrorsTest.php`), y se ajustó el test "sin roles se muestra el estado vacío" de `RolesIndexTest.php`, que ya no puede vaciar la tabla `rols` por completo sin romper el propio permiso del admin de la prueba — se cambió a filtrar por una búsqueda sin resultados.

### Verificación

- Suite completa: `php artisan test` → 239 passed, 1 failed (`ExampleTest`, mismo fallo preexistente no relacionado).
- Verificación manual contra la BD real (`farmacia_tfg`): `php artisan serve` temporal + `curl` con las cuentas reales del seeder. Login `admin@farmacia.com` → `GET /rols` → `200`, contenido real de la vista ("LISTA DE ROLES"). Login `vendedor@farmacia.com` → `GET /rols` → `403`, cuerpo "No autorizado" del `PermisoMiddleware`. Servidor temporal detenido y cookies eliminadas al finalizar.

### Documentación actualizada

`docs/pendientes.md` (`BUG-12` registrado y marcado ✅ corregido, con el diagnóstico completo y la corrección aplicada; fila agregada en la Tabla de Priorización), `docs/modulos.md` (nueva nota de corrección en "Módulo: Roles", mismo estilo que la nota ya existente de `BUG-01`).

### Estado al cierre

El módulo de Roles queda protegido por permiso, igual que el resto de módulos administrativos. Queda pendiente, deliberadamente fuera de este fix, un sprint futuro de UX/RBAC que unifique el ocultamiento de botones por permiso en las vistas (hoy solo lo hace `users/index.blade.php`) y evalúe si el sidebar debería filtrar por permiso en vez de por existencia de ruta. La auditoría de la memoria de tesis continúa en la próxima sesión.

---

## 2026-07-28 — Inicio de la revisión capítulo a capítulo de la memoria + `BUG-13` (permisos de Clientes nunca sembrados)

### Cambio de enfoque

A partir de esta sesión el usuario pidió dejar el desarrollo en segundo plano y centrarse en dejar la memoria de tesis (capítulo por capítulo, empezando por el Capítulo 3 — Análisis del Sistema) exactamente alineada con el sistema implementado. Regla acordada: no tocar código salvo que aparezca una inconsistencia crítica que impida que la memoria sea verdadera.

### Hallazgo durante el cierre del Capítulo 3: `BUG-13`

Al revisar `CU15` ("Asociar cliente a una venta", actor Vendedor) contra el RBAC ya corregido por `BUG-12`, se detectó que agregar `clientes.crear` al rol Vendedor (según lo acordado) no bastaba: el permiso **no existía en absoluto** en `PermisoSeeder.php`. El módulo de Clientes es el único de todo el sistema cuyas rutas (`permiso:clientes.ver/crear/editar/eliminar` en `routes/web.php`) verifican permisos que el seeder nunca definió — confirmado también por `docs/base_de_datos.md`, cuya tabla de "24 permisos" nunca tuvo una fila "Clientes". El módulo solo funcionaba para Administrador por el bypass de `esAdmin()`; para cualquier otro rol era estructuralmente imposible otorgar acceso, porque el permiso ni siquiera existía para asignarlo.

Se presentó la disyuntiva al usuario (corregir el código vs. reformular `CU15` en la memoria) vía pregunta explícita; el usuario eligió corregir el código por ser el cambio mínimo y porque la memoria ya describía el comportamiento deseado correctamente.

### Corrección aplicada

- `database/seeders/PermisoSeeder.php`: nueva sección `CLIENTES` con los 4 slugs ya exigidos por las rutas.
- `database/seeders/RolSeeder.php`: se agregó `clientes.crear` (no `ver`/`editar`/`eliminar`) a los permisos del rol Vendedor — coherente con `CU15` (crea un cliente al vuelo durante la venta) sin darle acceso al directorio completo, que sigue siendo de Administrador (`CU06`).
- Re-sembrado contra la base de datos real: `php artisan db:seed --class=PermisoSeeder` seguido de `--class=RolSeeder`.

### Verificación

- Catálogo de permisos: 29 en total (antes 25).
- `php artisan test`: 239 passed, 1 failed (`ExampleTest`, preexistente, no relacionado).
- Verificación manual contra la BD real: login `vendedor@farmacia.com` vía `php artisan serve` temporal + `curl`, `POST /clientes` → `201` (antes `403`). Cliente de prueba eliminado (`forceDelete()`) al finalizar.

### Documentación actualizada

`docs/pendientes.md` (`BUG-13` registrado y marcado ✅ corregido; fila agregada en la Tabla de Priorización), `docs/base_de_datos.md` (tabla de permisos: se agregó la fila "Clientes", se corrigió el conteo a 29, y se corrigió de paso el slug documentado de Proveedores de `proveedores.*` a `proveedors.*`, que ya era el real desde antes).

### Estado al cierre

Con `BUG-12` y `BUG-13` corregidos, el Capítulo 3 de la memoria (procesos de negocio, actores, RF, CU, roles y permisos) ya coincide con el sistema real sin reservas pendientes. Continúa la revisión de la memoria con los diagramas UML en la próxima sesión.

---
