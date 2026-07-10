# Arquitectura del Sistema — Farmacia Katy

## Objetivo del Sistema

Farmacia Katy es un sistema de gestión interna para una farmacia. Su propósito es digitalizar y controlar las operaciones diarias: autenticación de empleados con roles diferenciados, gestión del catálogo de productos farmacéuticos, control de stock por lotes con trazabilidad de fecha de vencimiento y costo, registro de compras a proveedores, registro de ventas a clientes, y auditoría de todos los movimientos de inventario.

El sistema está orientado a un entorno de escritorio interno (no es un e-commerce ni una API pública). El acceso es exclusivo para empleados de la farmacia con credenciales activas.

---

## Stack Tecnológico

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.2, Laravel 12 |
| Autenticación | Laravel Fortify |
| Frontend | Blade Templates (Laravel), CSS propio (`public/css/style.css`) |
| Base de datos | MySQL (XAMPP, puerto 3306) |
| Iconos | Remix Icon CDN (`ri-*`) |
| Herramientas de desarrollo | Laravel Pint (linter), Pest (testing), Laravel Pail (logs) |

No se utiliza ningún framework CSS externo (sin Tailwind ni Bootstrap). El JavaScript presente en las vistas es vanilla JS embebido directamente en los archivos Blade. No hay un framework JS (sin Vue, React ni Alpine).

---

## Estructura de Directorios Relevante

```
farmacia_tfg/
├── app/
│   ├── Http/
│   │   ├── Controllers/       # Controladores de la aplicación
│   │   └── Middleware/        # Middlewares personalizados (auth, permisos)
│   ├── Models/                # Modelos Eloquent
│   ├── Actions/Fortify/       # Acciones de autenticación (Fortify)
│   └── Providers/             # Service providers (Fortify, Routes, App)
├── database/
│   ├── migrations/            # Definición de tablas
│   └── seeders/               # Datos iniciales (roles, permisos, usuarios)
├── resources/views/           # Plantillas Blade
│   ├── app.blade.php          # Layout principal (sidebar + estructura)
│   ├── auth/                  # Vistas de login, registro, recuperar contraseña
│   ├── productos/             # Vista de productos y sublotes
│   ├── ventas/                # Lista y formulario de venta
│   ├── compras/               # Lista y formulario de compra
│   └── [módulo]/index.blade.php
├── routes/
│   └── web.php                # Todas las rutas de la aplicación
└── public/css/style.css       # Hoja de estilos global
```

---

## Patrón Arquitectónico

El sistema sigue el patrón **MVC (Model-View-Controller)** de Laravel con las siguientes particularidades:

- **No hay API REST**: todo el flujo es web clásico (formularios HTML, redirecciones, session flash).
- **Sin SPA**: no hay separación frontend/backend. Las vistas se renderizan en el servidor.
- **Formularios dinámicos con JS puro**: los formularios de compra y venta tienen filas dinámicas gestionadas con JavaScript vanilla directamente en el Blade.
- **Modales in-page**: los formularios de alta rápida (crear cliente, crear proveedor, crear producto desde una venta/compra) se implementan como modales con AJAX y devuelven JSON desde el mismo controlador según la cabecera `Accept: application/json`.

---

## Sistema de Autenticación

Laravel Fortify gestiona el ciclo completo de autenticación:

- Login / Logout
- Registro de usuarios
- Recuperación de contraseña por email
- 2FA (Two-Factor Authentication) — la infraestructura está preparada (migración `add_two_factor_columns_to_users_table` y la acción `RedirectIfTwoFactorAuthenticatable` están configuradas), pero el flujo de activación/verificación no está implementado en las vistas.

La vista de login, registro y recuperación de contraseña están en `resources/views/auth/` y son vistas Blade propias (no las de Fortify por defecto). Fortify está configurado en `app/Providers/FortifyServiceProvider.php` para apuntar a estas vistas personalizadas.

---

## Sistema de Control de Acceso (RBAC)

El sistema implementa un **RBAC (Role-Based Access Control) personalizado**, sin utilizar Laravel Gates ni Policies.

### Componentes

```
users ←──── rol_user ────→ rols ←──── permiso_rol ────→ permisos
```

- **Roles** (`rols`): agrupaciones de permisos con nombre y slug único. Ejemplos: `Administrador` (slug: `admin`), `Vendedor` (slug: `vendedor`).
- **Permisos** (`permisos`): capacidades granulares identificadas por un slug. Ejemplos: `ventas.crear`, `productos.stock`, `usuarios.eliminar`.
- **Asignación**: un usuario puede tener múltiples roles; un rol puede tener múltiples permisos.

### Middleware de Control

`PermisoMiddleware` (`app/Http/Middleware/PermisoMiddleware.php`) se registra con el alias `permiso` en el kernel y se aplica a rutas individuales:

```
middleware('permiso:productos.crear')
```

Lógica del middleware:
1. Si no hay usuario autenticado → `abort(403)`.
2. Si el usuario tiene un rol con nombre `Administrador` o slug `admin` → pasa sin verificación (`esAdmin()` es bypass total).
3. Si el usuario tiene el permiso indicado a través de cualquiera de sus roles → pasa.
4. En caso contrario → `abort(403)`.

### Métodos en el Modelo User

| Método | Descripción |
|---|---|
| `esAdmin()` | Devuelve `true` si algún rol tiene nombre `Administrador` o slug `admin` |
| `tienePermiso($slug)` | Verifica si algún rol del usuario tiene un permiso con ese slug |
| `permisos()` | Consulta directa que devuelve todos los permisos del usuario a través de sus roles |

---

## Layout Principal

Todas las vistas autenticadas extienden `resources/views/app.blade.php`, que provee:

- **Sidebar colapsable** con navegación a todos los módulos (estado persistido en `localStorage`).
- **Avatar de usuario** generado dinámicamente con `ui-avatars.com`.
- **Botón de logout** con formulario POST y CSRF.
- **Flash messages**: maneja `session('success')`, `session('error')` y el objeto `$errors` de validación.
- **Stacks Blade**: `@stack('modals')` para modales y `@stack('scripts')` para JavaScript específico de cada vista.
- **Directivas**: `@yield('title')` para el título de página y `@yield('content')` para el contenido principal.

El sidebar muestra/oculta entradas según si existen las rutas nombradas (`Route::has()`), no según permisos del usuario — la restricción real ocurre en el servidor cuando se intenta acceder a la ruta.

---

## Flujo de una Petición Típica

```
Usuario hace clic en "Guardar"
        │
        ▼
Navegador envía POST /ventas (con CSRF token)
        │
        ▼
Kernel HTTP (middlewares globales: CORS, CSRF, TrimStrings...)
        │
        ▼
Grupo 'web' (EncryptCookies, StartSession, ShareErrors...)
        │
        ▼
Middleware 'auth' → verifica sesión activa
        │
        ▼
Middleware 'permiso:ventas.crear' → verifica rol/permiso
        │
        ▼
VentaController::store(Request $request)
        │
        ▼
Validación (Request::validate)
        │
        ▼
DB::transaction { lógica de negocio }
        │
        ▼
redirect()->route('ventas.index')->with('success', '...')
        │
        ▼
app.blade.php renderiza el flash message
```

---

## Sistema de Configuración (Ajustes del Sistema)

> **Estado:** Implementado (2026-07-07).

El sistema incorpora una tabla de configuración genérica de tipo **clave/valor**, pensada para parámetros ajustables por el administrador en tiempo de ejecución, sin tocar código ni el esquema de la base de datos para cada nuevo ajuste. El detalle de columnas está en `docs/base_de_datos.md` (tabla `configuraciones`).

### Modelo `Configuracion`

- `Configuracion::obtener(string $clave, $default = null)` — único punto de lectura. Devuelve el valor cacheado (usa el store de cache ya configurado, `CACHE_STORE=database`, sin dependencias nuevas) o `$default` si la clave no existe.
- `Configuracion::establecer(string $clave, $valor)` — único punto de escritura. Crea o actualiza la fila y limpia la entrada de cache correspondiente.
- **Regla de diseño:** ningún controlador debe hacer `Configuracion::where(...)` directamente; todo pasa por estos dos métodos, para que el cacheo y la invalidación no se dupliquen en distintos lugares.

### Configuraciones actuales

| Clave | Descripción | Valor por defecto | Consumido por |
|---|---|---|---|
| `dias_alerta_vencimiento` | Días de antelación para marcar un lote como "próximo a vencer" | `90` | `InicioController` (alerta del dashboard); cualquier validación futura que necesite el mismo umbral |

El diseño queda preparado para agregar más ajustes a futuro (ej. `stock_minimo_alerta`, `moneda`, `iva_porcentaje`) sembrando una fila nueva — sin migraciones adicionales.

### Edición de la configuración

Solo el administrador (`User::esAdmin()`) puede modificar valores de configuración, siguiendo el mismo criterio ya usado para el descuento en ventas (`$esAdmin` en `VentaController`) en lugar de crear un permiso granular nuevo en `permisos`. `ConfiguracionController::edit()`/`update()` verifican `esAdmin()` con `abort_unless(...)`; la ruta (`GET/PUT /configuracion`) solo requiere `auth`. El enlace del sidebar (`app.blade.php`) también se oculta para no-admins.

### Control de Vencimientos (scopes en `Lote`)

Para evitar duplicar la comparación de fechas de vencimiento en cada controlador, la regla de negocio vive centralizada como **scopes de Eloquent** en `app/Models/Lote.php`:

| Scope | Regla | Uso previsto |
|---|---|---|
| `Lote::vigentes()` | `fecha_vencimiento IS NULL OR fecha_vencimiento >= hoy` | `VentaController::store()` (reparto FIFO) y `VentaController::create()` (stock disponible mostrado al vendedor) — garantiza que nunca se despache un lote vencido |
| `Lote::vencidos()` | `fecha_vencimiento < hoy` | Reportes futuros (`AUS-01`), alertas |
| `Lote::proximosAVencer($dias)` | Vigente y `fecha_vencimiento` dentro de los próximos `$dias` días | `InicioController` (dashboard), pasando `Configuracion::obtener('dias_alerta_vencimiento', 90)` como `$dias` |

`proximosAVencer($dias)` recibe el umbral como parámetro en vez de leer la configuración internamente, para mantener `Lote` desacoplado de `Configuracion` y reutilizable en cualquier contexto (tests, futuros reportes con un umbral distinto, etc.).

Estos scopes **no alteran el orden FIFO** existente (`orderByRaw('fecha_vencimiento IS NULL, fecha_vencimiento ASC')` en `VentaController::store()`); solo acotan qué lotes son candidatos antes de aplicar ese orden.

### Estado de vencimiento por producto (composición en `Producto`)

El dashboard necesita un estado **por producto** (no por lote suelto), con prioridad: un lote vencido con stock siempre pesa más que un lote próximo a vencer del mismo producto. Esta composición no agrega comparaciones de fecha nuevas — delega enteramente en los scopes de `Lote` a través de la relación `lotes()`:

| Método (`app/Models/Producto.php`) | Descripción |
|---|---|
| `scopeConAlertaVencimiento($dias)` | Filtra productos con al menos un lote (`stock > 0`) vencido, o un lote (`stock > 0`) dentro de `proximosAVencer($dias)` |
| `loteVencidoRelevante()` | El lote vencido con stock más urgente del producto (para mostrar su `nro_lote`/fecha) |
| `loteProximoRelevante($dias)` | El lote vigente más próximo a vencer dentro del umbral |

`InicioController` usa `withExists()` sobre el mismo scope `Lote::vencidos()` para determinar `tiene_vencido` por producto, ordena esos productos primero (`orderByDesc('tiene_vencido')`) y solo entonces los "próximos a vencer" — replicando la prioridad **vencido > próximo > ok**. Los productos sin ninguna alerta no aparecen en el widget (es un widget de alertas, no un listado general).

---

## Configuración del Entorno

El sistema opera sobre XAMPP con la siguiente configuración de base de datos (`.env`):

```
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=farmacia_tfg
DB_USERNAME=root
DB_PASSWORD=
SESSION_DRIVER=database
QUEUE_CONNECTION=database
CACHE_STORE=database
```

La sesión, la cola de trabajos y el caché se almacenan en la base de datos (no en Redis ni archivos). Esto simplifica el entorno de desarrollo pero implica que se deben ejecutar las migraciones antes de que el sistema funcione.
