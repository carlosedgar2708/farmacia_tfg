# Requisitos y Casos de Uso — Trazabilidad con la Memoria de Tesis

Este documento mapea el catálogo de requerimientos funcionales (RF) y casos de uso (CU) del Capítulo III de la memoria de tesis contra el estado real de la implementación. Es un documento de **referencia cruzada**, no reemplaza a `flujo_venta.md`, `flujo_compra.md` ni `modulos.md`, que siguen siendo la fuente técnica primaria.

Leyenda de estado: ✅ Completo · ⚠️ Parcial · ❌ No implementado

---

## Actores del sistema (tesis §3.1.2 / §3.3.1)

| Actor | Rol en el sistema real |
|---|---|
| Administrador | Bypass total de permisos (`User::esAdmin()`), ver `arquitectura.md` § RBAC |
| Vendedor | Rol `vendedor` sembrado por `RolSeeder`, permisos vía `permiso_rol` |
| Proveedor | No es un usuario del sistema — es una entidad de datos (`proveedors`), vinculada a compras |
| Cliente | No es un usuario del sistema — es una entidad de datos (`clientes`), opcional en ventas |

---

## Procesos de negocio (tesis §3.1.1)

| ID | Proceso | Módulo real |
|---|---|---|
| P1 | Gestión de Inventario | Productos + Lotes/Stock (`modulos.md`) |
| P2 | Registro de Compras | Compras (`flujo_compra.md`) |
| P3 | Registro de Ventas | Ventas (`flujo_venta.md`) |
| P4 | Generación de Reportes | Reportes (6 subvistas, `modulos.md` § Reportes) |
| P5 | Auditoría del Sistema | **No implementado** — no existe tabla ni mecanismo de auditoría de acciones de usuario (logins, modificaciones, eliminaciones) más allá de los timestamps estándar de Eloquent |

---

## Requerimientos Funcionales (tesis §3.2.1)

| RF | Nombre | Estado | Notas |
|---|---|---|---|
| RF01 | Autenticación de Usuarios | ✅ Completo | Fortify (login/logout). Recuperación de contraseña deshabilitada (`Features::resetPasswords()` no activo) — no forma parte del enunciado de RF01, que solo pide inicio/cierre de sesión. |
| RF02 | Gestión de Usuarios | ✅ Completo | `UserController` CRUD completo, asignación de rol. |
| RF03 | Gestión de Roles y Permisos | ⚠️ Parcial | CRUD de roles y asignación de permisos a roles: ✅ completo (`RolController`, sync corregido en `BUG-01`). CRUD del **catálogo** de permisos (crear/editar/eliminar permisos nuevos desde la UI): ❌ `PermisoController` tiene los 4 métodos vacíos, sin vista (ver `modulos.md` § Permisos). |
| RF04 | Gestión de Proveedores | ✅ Completo | `ProveedorController`. Ver discrepancia de slugs `proveedores.*`/`proveedors.*` en `modulos.md`. |
| RF05 | Gestión de Clientes | ✅ Completo | `ClienteController`, incluye alta rápida vía AJAX desde ventas. |
| RF06 | Gestión de Productos | ⚠️ Parcial | CRUD completo salvo que `ProductoController::update()` no permite modificar `precio_venta` desde la UI (ver `modulos.md` § Productos § Limitación). |
| RF07 | Control de Stock | ⚠️ Parcial (mecanismo distinto al descrito) | La tesis pide "notificar al administrador". La implementación real es **pasiva**: reportes de solo lectura (`stock-bajo`, `vencimientos`) y badges en el Dashboard (`Producto::scopeConAlertaVencimiento()`), no hay notificaciones push/email activas. |
| RF08 | Registro de Compras | ✅ Completo | `flujo_compra.md`. |
| RF09 | Registro de Ventas | ⚠️ Parcial | Registro, FEFO y descuento (admin-only) completos. El **método de pago** que menciona el enunciado no se captura ni persiste — no existe columna `metodo_pago` en ninguna tabla (se quitó de `Recibo::$fillable` en `PEND-01` por no existir en la migración). |
| RF10 | Generación de Comprobantes | ✅ Completo | Cerrado 2026-07-28 (ver `PEND-01`/`modulos.md` § Recibos). Impresión vía `window.print()` en `recibos/show.blade.php`. |
| RF11 | Registro de Devoluciones | ❌ No implementado | Ver `PEND-02`. |
| RF12 | Reportes de Ventas | ✅ Completo | `reportes/ventas.blade.php`. |
| RF13 | Reportes de Compras | ✅ Completo | `reportes/compras.blade.php`. |
| RF14 | Reportes de Devoluciones | ❌ No implementado | Depende de RF11. |

---

## Casos de Uso (tesis §3.3)

| CU | Caso de uso | Actor(es) | Estado | Referencia |
|---|---|---|---|---|
| CU01 | Autenticarse (login/logout) | Admin, Vendedor | ✅ Completo | Fortify |
| CU02 | Gestionar usuarios | Admin | ✅ Completo | `modulos.md` § Usuarios |
| CU03 | Gestionar roles | Admin | ✅ Completo | `modulos.md` § Roles |
| CU04 | Asignar permisos a roles | Admin | ✅ Completo | Checkboxes en modal de Roles |
| CU05 | Gestionar proveedores | Admin | ✅ Completo | `modulos.md` § Proveedores |
| CU06 | Gestionar clientes | Admin | ✅ Completo | `modulos.md` § Clientes |
| CU07 | Gestionar productos | Admin | ⚠️ Parcial | Ver RF06 |
| CU08 | Controlar stock | Admin | ⚠️ Parcial | Ver RF07 |
| CU09 | Registrar compras | Admin | ✅ Completo | `flujo_compra.md` |
| CU10 | Registrar ventas | Vendedor, Admin | ⚠️ Parcial | `flujo_venta.md` — ver RF09 |
| CU11 | Generar comprobante/recibo de venta | Vendedor | ✅ Completo | `modulos.md` § Recibos |
| CU12 | Registrar devoluciones | Vendedor | ❌ No implementado | Ver `PEND-02` |
| CU13 | Generar reportes | Admin | ⚠️ Parcial | 5/6 reportes con datos reales; el de devoluciones no existe (depende de RF14) |
| CU14 | Registrar movimientos de stock | Admin | ⚠️ Parcial | Automático vía compras/ventas: ✅. Ajuste manual de stock (`LoteController::bulkUpdate`) **no genera** `MovimientoStock` — ajustes silenciosos sin auditoría (ver `modulos.md` § Lotes/Stock). |
| CU15 | Asociar cliente a una venta | Vendedor | ✅ Completo | Corregido en `BUG-13` (permiso `clientes.crear` para Vendedor) |

---

## Nota sobre la numeración RF en la tabla de Casos de Uso de la tesis

La tabla "Listado de Casos de Uso" (§3.3) de la memoria referencia los RF con un desfase de uno respecto a la tabla de Requerimientos Funcionales (§3.2.1) — por ejemplo, asigna `RF09` a CU09 "Registrar compras" cuando la definición real de RF09 es "Registro de Ventas" (el de compras es RF08), y CU13 "Generar reportes" cita `RF15`, que no existe (el catálogo llega hasta RF14). Este documento usa como fuente autoritativa la tabla de definiciones de RF (§3.2.1) y hace coincidir cada CU por **descripción**, no por el número citado en la tabla de CU. No es un problema de la implementación — es una inconsistencia interna del documento de tesis, señalada acá para que quede registrada.
