# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Reglas Permanentes del Proyecto

Este proyecto corresponde a una **tesis universitaria**. Las siguientes reglas son de obligado cumplimiento en todas las sesiones:

1. **Explicar antes de modificar.** Antes de tocar cualquier archivo de código, exponer el plan de cambios y esperar confirmación del usuario.
2. **Sin refactors masivos sin autorización.** Cambios de estilo, renombrados globales, reorganización de carpetas o cualquier modificación que afecte a más de un módulo requieren aprobación explícita.
3. **Sin renombrar tablas, rutas ni modelos sin preguntar.** Los nombres actuales (`rols`, `proveedors`, etc.) están documentados y son intencionales. Cualquier cambio de nomenclatura debe ser consultado primero.
4. **Mantener compatibilidad con Laravel 12** y PHP 8.2+. No introducir dependencias ni patrones incompatibles con esta versión.
5. **Respetar la arquitectura documentada en `docs/`.** Los archivos en esa carpeta describen el diseño acordado del sistema. No apartarse de él sin discutirlo.
6. **Usar `docs/` como fuente principal.** Antes de volver a analizar el código para obtener contexto, consultar primero la documentación en `docs/`. Solo leer el código si la documentación no cubre el punto en cuestión.
7. **Actualizar `docs/` tras cambios importantes.** Al finalizar cualquier cambio que afecte la arquitectura, los flujos, los módulos o la base de datos, actualizar el archivo correspondiente en `docs/` para mantener la documentación sincronizada con el código.

### Documentación disponible en `docs/`

| Archivo | Contenido |
|---|---|
| `docs/arquitectura.md` | Objetivo del sistema, stack, RBAC, layout, flujo de peticiones |
| `docs/base_de_datos.md` | Todas las tablas, columnas, relaciones y desajustes modelo/migración |
| `docs/flujo_compra.md` | Flujo detallado del registro de una compra |
| `docs/flujo_venta.md` | Flujo detallado del registro de una venta (FIFO, descuentos, stock) |
| `docs/modulos.md` | Estado y descripción de cada módulo, rutas, permisos y vistas |
| `docs/pendientes.md` | Bugs confirmados, desajustes de esquema, funcionalidades incompletas y módulos ausentes |

## Commands

```bash
# First-time setup
composer run setup

# Development (starts Laravel server + queue + Vite concurrently)
composer run dev

# Run all tests
composer run test

# Run a single test file
php artisan test tests/Feature/ExampleTest.php

# Lint / format (Laravel Pint)
./vendor/bin/pint

# Migrations
php artisan migrate
php artisan migrate:fresh --seed   # wipe + re-seed (dev only)

# Seed only
php artisan db:seed

# Tinker (REPL)
php artisan tinker
```

The app runs on XAMPP (MySQL on port 3306, DB name `farmacia_tfg`, user `root`, no password). No Docker setup exists.

## Architecture

**Stack:** Laravel 12, PHP 8.2+, Blade templates, plain CSS (`public/css/style.css`), no frontend build step for views (Vite is configured but views use CDN assets). Authentication is handled by **Laravel Fortify** (`app/Providers/FortifyServiceProvider.php`).

### RBAC — Roles & Permissions

The permission system is home-grown, not Laravel Gates/Policies:

- `rols` table — roles with a `slug` field (`admin`, `vendedor`, …)
- `permisos` table — granular permission slugs (e.g. `ventas.crear`, `productos.stock`)
- Pivot tables: `rol_user` (User↔Rol), `permiso_rol` (Rol↔Permiso)
- **`PermisoMiddleware`** (`app/Http/Middleware/PermisoMiddleware.php`) — guards routes via `middleware('permiso:<slug>')`. Admins (role slug `admin` OR nombre `Administrador`) bypass all checks.
- `User::tienePermiso(string $slug)` queries through the role pivot. `User::esAdmin()` is the shortcut used throughout controllers.
- Discount on sales is **admin-only** (`$esAdmin` flag passed to the create view).

### Stock Management

Stock lives in **lots (lotes)**, not directly on products:

- `Producto` → has many `Lote` (each with `nro_lote`, `stock`, `fecha_vencimiento`, `costo_unitario`)
- Purchases (`CompraController::store`) create or update a `Lote`, increment its `stock`, and write a `MovimientoStock` (`tipo = 'Entrada'`)
- Sales (`VentaController::store`) drain lots **FIFO by expiry date** (`orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento ASC')`), creating one `DetalleVenta` per lot consumed and a `MovimientoStock` (`tipo = 'Salida'`). Both operations run inside `DB::transaction` with `lockForUpdate()`.
- The `DetalleVenta` links to both `venta_id`, `producto_id`, and `lote_id`.

### Layout & Views

All authenticated views extend `resources/views/app.blade.php`, which provides the collapsible sidebar, flash messages (`session('success')`, `session('error')`, `$errors`), and `@stack('modals')` / `@stack('scripts')` stacks.

- `@yield('title')` sets both the `<title>` and the top header.
- `@yield('content')` is the main content area.
- Icons come from Remix Icon CDN (`ri-*` classes).

### Seeding Order

`DatabaseSeeder` calls seeders in dependency order: `PermisoSeeder` → `RolSeeder` → `UserSeeder` → `ProductoSeeder` → `LoteSeeder`. The `ProveedorSeeder` is commented out.

### Key Naming Quirks

- The roles table is `rols` (not `roles`) and the route resource is `rols`.
- The suppliers table/route prefix is `proveedors` (not `proveedores`). Note: `PermisoSeeder` uses slug prefix `proveedores.*` but routes use `proveedors.*` — keep this discrepancy in mind when adding permission checks.
- `User` uses `SoftDeletes`; `Producto` and `Lote` also use `SoftDeletes`.
