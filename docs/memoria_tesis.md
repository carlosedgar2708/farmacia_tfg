# Memoria de Tesis — Registro de Consistencia con el Sistema

> **Qué es este archivo:** un registro técnico de trabajo, no el documento final de la tesis (ese sigue viviendo en Word). Aquí se anota, capítulo por capítulo, qué partes de la memoria ya fueron verificadas contra el código real de este repositorio, cuáles siguen pendientes, y qué inconsistencias se encontraron y cómo se resolvieron.
>
> **Reglas de mantenimiento de este archivo:**
> - Se actualiza cada vez que se revisa un capítulo de la memoria con Claude.
> - Nada se documenta aquí sin estar respaldado por código real (rutas, controladores, modelos, migraciones, tests) o por una decisión ya registrada en `docs/pendientes.md` / `docs/diario_desarrollo.md`.
> - Un capítulo se marca `✅ Verificado` solo cuando cada afirmación de esa sección fue contrastada contra el código.
> - Un apartado que depende de un diagrama, imagen o captura que todavía no existe se marca `⏳ Pendiente`.
> - Toda inconsistencia detectada entre memoria y código se documenta en una subsección **Observaciones** del capítulo correspondiente — nunca se oculta ni se corrige en silencio.

---

## Índice de capítulos

| Capítulo | Título (según la memoria) | Estado |
|---|---|---|
| 1 | Introducción | ⏳ Pendiente — no revisado todavía |
| 2 | Marco Teórico / Antecedentes | ⏳ Pendiente — no revisado todavía |
| 3 | Materiales y Métodos (Análisis del Sistema) | ✅ **Verificado** (2026-07-28) |
| — | Diagramas UML | 🟡 **Parcial** — Diagrama General de Casos de Uso 🟡 (falta CU12 en EA); resto de diagramas (clases, secuencia, etc.) pendientes |
| 4 | Modelo de Prueba | 🟡 **Parcial** (2026-08-05) — §4.1 y §4.2 (casos de prueba de Registrar Ventas y Registrar Compras) verificados; faltan más casos de uso en §4.2, y conclusiones/recomendaciones aún no escritas |
| 5+ | (resto de la memoria) | ⏳ Pendiente — no revisado todavía |

---

## Capítulo 3 — Materiales y Métodos (Análisis del Sistema)

**Estado: ✅ Verificado (2026-07-28)**

Verificado sección por sección contra rutas (`routes/web.php`), middleware (`app/Http/Middleware/PermisoMiddleware.php`), controladores, modelos, migraciones, seeders (`database/seeders/PermisoSeeder.php`, `RolSeeder.php`) y tests. Todas las afirmaciones de esta sección tienen respaldo directo en código citado en `docs/pendientes.md` (`BUG-12`, `BUG-13`) y en la auditoría de sesión registrada en `docs/diario_desarrollo.md` (entradas del 2026-07-28).

### 3.1. Modelo de negocio

**3.1.1. Procesos de negocio (P1-P5)** — coinciden con el sistema implementado:
- P1 Gestión de Inventario → módulos Productos + Lotes/Stock.
- P2 Registro de Compras → `CompraController`.
- P3 Registro de Ventas → `VentaController` (FIFO por vencimiento, ver `docs/flujo_venta.md`).
- P4 Generación de Reportes → `ReporteController` (6 reportes).
- P5 Registro de Movimientos de Inventario → `MovimientoStock`, generado automáticamente por Compras/Ventas únicamente (no por ajustes manuales, ver `PEND-06`).

**3.1.2. Actores del negocio** — Administrador, Vendedor, Proveedor, Cliente. Proveedor y Cliente son actores de negocio sin cuenta de acceso al sistema (correcto: no existen roles de login para ellos).

### 3.2. Requerimientos

**3.2.1. Requerimientos Funcionales (RF01-RF14)** — los 14 tienen implementación real verificada. Ninguno promete una funcionalidad inexistente en la versión actual del texto (ya corregidos en sesión previa: RF09 sin "método de pago", RF12 con filtros reales fecha/usuario/cliente, RF13 con filtros reales proveedor/fecha).

**3.2.2. Requerimientos No Funcionales (RNF01-RNF10)** — son metas de diseño, no verificables por lectura estática de código (tiempo de respuesta, concurrencia, disponibilidad). RNF03 (bcrypt) y RNF04 (control de accesos por rol) sí tienen respaldo directo en código y están confirmados.

### 3.3. Casos de Uso (CU01-CU14)

Renumerado (2026-07-28): la vieja `CU14` "Registrar movimientos de stock" se eliminó por completo de la lista (no es un CU independiente accionado por un actor — `MovimientoStock` se genera como efecto automático de Compras/Ventas, ver `PEND-06`/`P5`), y "Asociar cliente a una venta" pasó de `CU15` a `CU14`. Los 14 casos de uso vigentes tienen correspondencia real con rutas + middleware + controlador, y el actor documentado coincide exactamente con el actor real permitido por el sistema. Detalle relevante:

- **CU03/CU04 (Gestionar roles / Asignar permisos):** actor Administrador — confirmado exclusivo tras `BUG-12`.
- **CU10/CU11 (Registrar ventas / Generar recibo):** actor Vendedor, Administrador — ambos pueden ejecutarlo (rutas de Ventas/Recibos solo exigen `auth`, sin permiso granular; ver nota en Observaciones).
- **CU14 (Asociar cliente a una venta):** actor Vendedor — confirmado que puede tanto seleccionar un cliente existente como registrar uno nuevo al vuelo, tras `BUG-13`.

### 3.4. Roles y permisos (RBAC)

Catálogo real verificado en `database/seeders/PermisoSeeder.php`: **29 permisos** en 9 módulos (Usuarios, Roles, Proveedores, Clientes, Productos/Stock, Compras, Ventas, Devoluciones, Reportes).

| Rol | Permisos reales (`database/seeders/RolSeeder.php`) |
|---|---|
| Administrador (`admin`) | Todos los 29, vía `sync(Permiso::pluck('id'))`, más bypass total en `PermisoMiddleware` (`esAdmin()`). |
| Vendedor (`vendedor`) | `ventas.ver`, `ventas.crear`, `productos.ver`, `clientes.crear`, `devoluciones.registrar`. |

### Observaciones

Dos inconsistencias críticas fueron detectadas durante la auditoría de este capítulo, ambas corregidas en código (con autorización explícita del usuario) antes de cerrar el capítulo:

1. **`BUG-12` — Módulo de Roles sin protección de acceso real.** La memoria (CU03/CU04, RF03) documentaba "Gestionar roles" y "Asignar permisos" como exclusivos de Administrador, pero `routes/web.php` no aplicaba ningún middleware `permiso:` a las rutas de `rols.*` — cualquier usuario autenticado, incluido un Vendedor, podía crear, editar (incluida la asignación de permisos) y eliminar cualquier rol. **Resuelto:** se aplicó el mismo patrón de middleware por ruta ya usado en Usuarios/Clientes/Proveedores (`permiso:rols.ver/crear/editar/eliminar`). Verificado con la suite completa (239 passed) y manualmente contra la BD real: `admin@farmacia.com` → 200, `vendedor@farmacia.com` → 403. Detalle completo en `docs/pendientes.md` (`BUG-12`) y `docs/diario_desarrollo.md` (2026-07-28).

2. **`BUG-13` — Los permisos `clientes.*` nunca se sembraron.** CU15 documentaba que el Vendedor puede registrar un cliente nuevo durante la venta, pero `PermisoSeeder.php` nunca definió ningún permiso `clientes.*`, pese a que las rutas de Clientes ya los exigían — el módulo solo funcionaba para Administrador por el bypass de `esAdmin()`. **Resuelto:** se agregó la sección `CLIENTES` al seeder de permisos y se otorgó `clientes.crear` (no el CRUD completo) al rol Vendedor, coherente con el alcance exacto de CU15. Verificado manualmente: `vendedor@farmacia.com` → `POST /clientes` responde `201` (antes `403`). Detalle completo en `docs/pendientes.md` (`BUG-13`) y `docs/diario_desarrollo.md` (2026-07-28).

**Nota de arquitectura, no corregida (no impide que la memoria sea verdadera, se deja como está):** las rutas de Ventas y Recibos (`routes/web.php`) están protegidas solo por `auth`, no por `permiso:ventas.*` — a diferencia de todos los demás módulos administrativos. Los permisos `ventas.ver`/`ventas.crear` existen y están asignados a Vendedor, pero ningún middleware los verifica. Hoy no causa una contradicción con la memoria porque los dos roles existentes (Admin, Vendedor) deberían tener acceso de todas formas, pero es una diferencia arquitectónica frente al resto del sistema que conviene tener presente si se agrega un tercer rol en el futuro.

---

## Capítulo 4 — Modelo de Prueba

**Estado: 🟡 Parcial (2026-08-05)**

Verificados hasta ahora: §4.1 (Concepto/Objetivos/Principios de la Prueba de Software, Caja Negra/Caja Blanca — contenido teórico, no verificable contra código) y §4.2 con dos casos de prueba (Registrar Ventas, Registrar Compras), contrastados línea por línea contra `VentaController::store()`, `CompraController::store()` y `ventas/index.blade.php`. Faltan por escribir/verificar: el resto de casos de uso de §4.2 (el capítulo cubre por ahora solo 2 de los 14 CU vigentes, ver `docs/requisitos.md`), y las secciones de Conclusiones y Recomendaciones.

### 4.2.1. Caso de Uso: Registrar Ventas

✅ Verificado contra `app/Http/Controllers/VentaController.php::store()`:
- Precio unitario siempre recalculado desde `Producto::precio_venta` (línea 112), el valor enviado por el formulario se ignora — coincide con el texto.
- Descuento forzado a `0` si `!auth()->user()->esAdmin()` (línea 114) — coincide.
- FEFO: consulta de lotes con `Lote::vigentes()` + `orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento ASC')` + `lockForUpdate()` (líneas 117-122) — coincide, excluye lotes vencidos antes de aplicar el orden.
- Stock insuficiente → `ValidationException::withMessages(["items.$i.cantidad" => "Stock insuficiente para {producto}. Disponible: {stock} unidades."])` (líneas 128-131) — el sistema no registra nada y devuelve al formulario conservando los demás datos (`PEND-08`), coincide con el flujo alterno descrito.
- Por cada lote descontado: `DetalleVenta::create()` + `Lote::decrement('stock')` + `MovimientoStock::create()` con `tipo='Salida'`/`motivo='Venta'` (líneas 148-165) — coincide.
- Recibo generado incondicionalmente al final de la transacción, `reciboId` devuelto en el redirect (líneas 184-195) — coincide con "genera automáticamente el recibo" y los botones "Ver recibo"/"Nueva venta" (`docs/modulos.md` § Recibos).

**Observación (matiz de redacción, no un error funcional):** el texto dice que la venta "queda registrada como 'público en general'" cuando no se indica cliente. En la implementación real, `cliente_id` simplemente queda `NULL` — no se persiste ningún literal "público en general"; el listado de ventas (`ventas/index.blade.php:42`) muestra `—` para ese caso. Sí existe un botón **"Público en general"** en el formulario (`ventas/create.blade.php:84`) que el usuario pulsa para dejar la venta sin cliente, así que la idea de fondo es correcta — es una simplificación aceptable para la redacción del caso de prueba, no una afirmación falsa sobre el sistema.

### 4.2.2. Caso de Uso: Registrar Compras

✅ Verificado contra `app/Http/Controllers/CompraController.php::store()`:
- `proveedor_id` obligatorio, valida existencia en `proveedors` (línea 66) — coincide.
- Lote con fecha de vencimiento pasada → `ValidationException::withMessages(["items.$i.fecha_vencimiento" => "El lote {$nroLote} está vencido. No puedes ingresarlo."])` (líneas 98-102) — el mensaje citado en el caso de prueba coincide **textualmente, carácter por carácter**, con el mensaje real del código. Toda la transacción se revierte (nada se registra), coincide con el flujo alterno descrito.
- Lote nuevo → se crea con `stock=0` y se incrementa después (líneas 110-118, 138) — coincide.
- Lote existente → actualiza `costo_unitario` siempre ("último costo registrado") y `fecha_vencimiento` solo si vino informada (líneas 119-127) — coincide.
- Por cada ítem: `DetalleCompra::create()` + incremento de stock + `MovimientoStock` `tipo='Entrada'`/`motivo='Compra'` — coincide.
- Redirect a `compras.index` con `'Compra registrada correctamente.'` — coincide.

Sin observaciones — no se encontró ninguna discrepancia entre el texto y el código.

---

## Diagramas UML

Los diagramas se mantienen en Enterprise Architect, fuera de este repositorio. Este archivo solo registra el resultado de auditar cada uno contra el código; la corrección del diagrama en sí (EA) queda a cargo del usuario.

### Diagrama General de Casos de Uso

**Estado: 🟡 Verificado con 1 pendiente menor (2026-07-28, versión 2 del diagrama, cruzado contra el cuadro 3.3 ya renumerado) — falta agregar el óvalo "Gestionar configuración" (CU12) en EA**

Verificado contra `routes/web.php`, `database/seeders/RolSeeder.php`, `database/seeders/PermisoSeeder.php` y los controladores correspondientes.

**✅ Correcto — todo el diagrama:** "Gestionar proveedores", "Gestionar Usuarios", "Generar reportes", "Gestionar Producto", "Administrar Roles", "Registrar compras" y "Controlar stock" — los siete exclusivos de Administrador, coincide exactamente con las rutas y permisos reales. "Registrar ventas" con actores Vendedor y Administrador — correcto (las rutas de Ventas solo exigen `auth`, ambos roles pasan). "Asociar Cliente «extend» Registrar ventas" — correcto, asociación opcional (venta a público general es válida). "Registrar ventas «include» Generar recibo de venta" — correcto, `VentaController::store()` genera el recibo de forma incondicional en toda venta. "Gestionar clientes «extend»→ Asociar Cliente", con Administrador como único actor directo de "Gestionar clientes" — correcto, coincide con `RolSeeder.php` (Vendedor solo tiene `clientes.crear`, sin acceso al directorio completo).

#### Observaciones

Versión 1 del diagrama (revisada primero) tenía 2 hallazgos críticos y 3 de notación — ver historial más abajo. **Todos quedaron corregidos en esta versión 2**, verificado de nuevo contra el mismo código: se quitó "Registrar devolución" (módulo no implementado, fuera de alcance), se quitó la línea directa Vendedor→"Gestionar clientes" (RBAC real no lo permite), se quitó el caso de uso "Registrar movimientos de stock" junto con sus includes (ya no forma parte del diagrama, consistente con que tampoco es un CU independiente en el listado textual), y "Generar recibo de venta" pasó de `«extend»` a `«include»` (coincide con que el recibo se genera siempre, no condicionalmente). No se encontró ninguna inconsistencia nueva.

**Cruce contra el cuadro 3.3 (2026-07-28):** con la lista de CU ya renumerada (CU01-CU14, ver arriba), se comparó cada fila contra el diagrama:
- Coinciden en actor y alcance: CU02, CU05, CU06, CU07, CU08, CU09, CU10, CU11, CU13, CU14.
- CU01 (Autenticarse) no tiene óvalo propio en el diagrama — omisión estándar en diagramas de casos de uso de negocio (login/logout suele modelarse aparte), no se considera un error.
- **CU12 "Gestionar configuración" no está representado en el diagrama** — `ConfiguracionController`/ruta `/configuracion` sí existen y están implementados (verificado, protegidos por `abort_unless(esAdmin())`), así que el cuadro 3.3 es correcto; es el diagrama el que quedó desactualizado en este punto (versión antigua de EA, según el propio usuario). **Pendiente: agregar en EA un óvalo "Gestionar configuración" conectado solo a Administrador.**
- CU03 y CU04 están representados como un único óvalo "Administrar Roles" en el diagrama, en vez de dos óvalos separados. No es incorrecto (ambos son Administrador-only), pero no hay trazabilidad 1:1 CU↔óvalo en ese punto — a criterio del usuario si conviene separarlos.

<details>
<summary>Historial — hallazgos de la versión 1 (ya corregidos)</summary>

1. **(Crítico)** "Registrar devolución" aparecía como caso de uso activo del Vendedor — módulo no implementado, sin rutas.
2. **(Crítico)** Vendedor conectado directamente a "Gestionar clientes" — RBAC real solo le da `clientes.crear`.
3. **(Notación)** Include "Controlar stock → Registrar movimientos de stock" no ocurre hoy (`PEND-06`).
4. **(Notación)** Include "Registrar devolución → Registrar movimientos de stock" — dependía del hallazgo 1.
5. **(Notación)** "Generar recibo de venta" modelado como `«extend»` debiendo ser `«include»` (comportamiento incondicional).

</details>

---

## Próxima revisión

Resto de diagramas UML (clases, secuencia, etc. — a definir cuáles existen en la memoria) — pendientes de recibir el contenido correspondiente para auditar contra el modelo de datos y los flujos reales (`docs/base_de_datos.md`, `docs/flujo_compra.md`, `docs/flujo_venta.md`).
