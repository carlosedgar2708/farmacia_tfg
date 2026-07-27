# Design System — Farmacia Katy

Documento de identidad visual oficial del sistema. Es la fuente de verdad para cualquier vista, componente o estilo nuevo. Ningún color, tipografía, espaciado o sombra debe introducirse fuera de lo que este documento define — si algo no está aquí, se discute y se agrega aquí primero, no se improvisa en una vista.

Este documento **no implica ningún cambio de código**. Es la especificación que guiará los sprints de implementación (`UI-03` en adelante — ver hoja de ruta completa en la sección 22).

> **Checklist de referencia rápida:** la sección 21 ("Reglas estrictas de diseño") resume, en formato de lista corta, las reglas no negociables del sistema. Úsala como checklist antes de cerrar cualquier sprint de UI.

---

## 1. Filosofía del diseño

Farmacia Katy es software administrativo de uso interno: personas que manejan inventario, ventas y compras todos los días, muchas horas seguidas. El diseño no está para impresionar en una demo — está para que esas personas encuentren información rápido, cometan menos errores y no se cansen la vista.

Principios, en orden de prioridad:

1. **La información es el producto.** El color, la tipografía y el espaciado existen para organizar datos, no para decorar. Si un elemento visual no ayuda a leer o a actuar más rápido, no se agrega.
2. **Densidad sobre espectáculo.** Este sistema mueve tablas de ventas, compras, lotes y reportes. Prioriza que quepa información útil en pantalla sobre dejar "aire" decorativo.
3. **Predecible antes que bonito.** Un botón se ve igual en todas las pantallas. Un estado de error se ve igual en todas las pantallas. La consistencia genera confianza más rápido que cualquier efecto visual.
4. **Silencioso por defecto, expresivo cuando importa.** La interfaz base es neutra (grises, blancos, líneas finas). El color se reserva para lo que requiere atención: una acción primaria, un error, un vencimiento crítico.
5. **Inspirado en herramientas reales de trabajo** — ERPNext, Odoo moderno, GitHub, Linear, Notion, Stripe Dashboard — no para copiar su marca, sino porque comparten esta misma prioridad: texto legible, jerarquía clara, color con propósito, cero ruido visual.

### Qué evitamos explícitamente

| Evitar | Por qué |
|---|---|
| Sombras exageradas | Simulan elevación falsa; en una tabla densa, ensucian la lectura. |
| Degradados | Rompen la percepción de "superficie plana" que usan todos los sistemas de referencia; hoy solo aparecen en el login, y ahí es donde más se nota como cuerpo extraño al resto del sistema. |
| Colores excesivos | Cada color adicional compite por atención. Con 5 colores base + 2 semánticos alcanza para todo el sistema. |
| Animaciones innecesarias | Transiciones solo donde comunican estado (hover, focus, apertura de menú) — nunca decorativas. |
| Tarjetas gigantes | Una card no es un contenedor por defecto; se usa cuando agrupa contenido relacionado, no como estética. |
| Estética AdminLTE | Iconos de colores saturados por todos lados, cards con headers de color sólido, sombras pesadas, tipografía genérica — es exactamente la estética "panel de admin genérico" que este sistema busca evitar. |

---

## 2. Identidad visual del sistema

- **Carácter:** clínico, ordenado, confiable — como se espera de software que maneja inventario farmacéutico. Nada "divertido" ni "startup".
- **Superficie:** planas, blancas sobre fondo gris muy claro, separadas por líneas finas (`1px`) o sombras casi imperceptibles — nunca ambas a la vez con intensidad.
- **Jerarquía por peso y tamaño, no por color.** El color no se usa para diferenciar "qué es más importante" en texto — se usa peso tipográfico y tamaño. El color se reserva para estado (éxito, alerta, error) y para acción (botones).
- **Un solo color de marca visible en todo momento:** el azul petróleo aparece en el sidebar, en el logo/marca y en los estados activos de navegación — es lo que ancla la identidad de "Farmacia Katy" en cada pantalla.

---

## 3. Paleta oficial y reglas de uso

Paleta base (5 colores, según lo definido):

| Rol | Nombre | Hex | Uso |
|---|---|---|---|
| Color principal / marca | Azul petróleo | `#357C90` | Sidebar (marca, ítem activo), encabezados de página, texto de énfasis, enlaces, borde de foco secundario. **No se usa para botones de acción** (ver Turquesa). |
| Acciones principales | Turquesa | `#12A594` | Botones primarios, CTA, checkboxes/radios marcados, foco de campos activos, enlaces de acción dentro de tablas. Hover: `#0E8C7F`. Fondo tenue (chips/hover sutil): `#E3F6F3`. |
| Fondos | Gris muy claro | `#F5F7FA` | Fondo general de la aplicación (`body`). Superficies (`cards`, tablas) van en blanco `#FFFFFF` sobre este fondo para crear separación sin sombra. |
| Errores / acciones destructivas | Rojo coral | `#F27D72` | Botones destructivos, texto/ícono de error, badges de "advertencia" (en tono tenue) y "error" (en tono sólido) — ver tabla de severidad en Badges. Hover sólido: `#E15F53`. |
| Uso muy limitado — crítico | Rojo oscuro | `#991B1B` | Únicamente para el nivel de alerta más alto: "VENCIDO", stock en cero, confirmaciones de eliminación irreversible. Nunca para botones ni superficies grandes — solo texto/ícono/badge puntual. |

### Colores neutros de apoyo (escala de grises, no cuentan como "color adicional")

| Token | Hex | Uso |
|---|---|---|
| `--ink` | `#1F2937` | Texto principal |
| `--muted` | `#64748B` | Texto secundario, labels, ayudas |
| `--line` | `#E5E7EB` | Bordes, separadores |
| `--surface` | `#FFFFFF` | Fondo de cards, tablas, modales |
| `--bg` | `#F5F7FA` | Fondo general |

### Excepción semántica justificada: verde de éxito

La paleta de marca (arriba) rige identidad y acción. Para **estado**, se mantiene un verde semántico, separado y de uso deliberadamente limitado — no es un color de marca, es un color funcional:

| Token | Hex | Uso |
|---|---|---|
| `--success` | `#C4E6B0` (fondo tenue) / `#166534` (texto) | Confirmaciones (`alert-success`), badges de "disponible"/"OK"/"aprobado". Nunca en botones grandes ni en elementos de marca. |

**Justificación:** verde = éxito/disponible es una convención universal (GitHub, Linear, Stripe, Notion la usan pese a tener paletas de marca distintas). Forzar el color de marca a representar "éxito" generaría ambigüedad real: turquesa ya significa "acción, haz clic aquí", y usarlo también para "esto salió bien" confundiría dos significados distintos en la misma interfaz. Su uso está acotado a badges/alerts pequeños, nunca a superficies grandes.

### Regla de severidad con un solo hue (coral)

Para no introducir un color de "advertencia" (ámbar/amarillo) fuera de la paleta, la severidad se comunica con **intensidad del mismo hue coral**, más el rojo oscuro como techo:

| Nivel | Ejemplo de uso | Estilo |
|---|---|---|
| Éxito / disponible | Stock ok, venta confirmada | Verde semántico tenue |
| Advertencia | "Vence pronto", "Stock bajo" | Coral **tenue** — fondo `rgba(242,125,114,.12)`, texto `#B45309`→ ver nota abajo |
| Error / destructivo | Botón eliminar, error de validación | Coral **sólido** — fondo `#F27D72`, texto blanco (botón) o texto coral sobre blanco (badge/alert) |
| Crítico | "VENCIDO", stock en 0 | Rojo oscuro `#991B1B`, uso puntual (badge/texto), nunca superficies grandes |

> **Nota de limpieza pendiente:** el CSS actual usa `#B45309` (ámbar) como color de texto en `.chip-warn`, mezclado con fondo coral tenue — es una mezcla de dos hues distintos (coral + ámbar) que no debería coexistir. En la implementación, el texto de "advertencia" debe pasar a un coral oscurecido propio (`#9A3B2E` o similar, derivado del mismo hue), no ámbar. Se detalla en el informe de análisis.

### Reglas de uso — resumen

- **Azul petróleo:** identidad y navegación. Si dudas si algo debe ser petróleo o turquesa, pregunta: "¿esto es parte del andamiaje del sistema (sidebar, marca) o es algo que el usuario puede accionar (botón)?" — lo primero es petróleo, lo segundo es turquesa.
- **Turquesa:** una sola acción primaria por vista/formulario. No hay "dos botones turquesa" compitiendo en la misma pantalla — el resto son `btn-outline` (secundario) o `btn-ghost` (terciario).
- **Coral:** exclusivo de error/destructivo/advertencia. Nunca decorativo.
- **Rojo oscuro:** exclusivo de la severidad máxima. Si se usa en más de 2-3 lugares por vista, es señal de que se está sobreusando.
- **Verde:** exclusivo de éxito/disponibilidad. Nunca como color de marca ni de botón primario.

---

## 4. Tipografía

### Recomendación: cambiar de Poppins a **Inter**

**Justificación:**
- Poppins es una geométrica redondeada, pensada para titulares y branding — es la elección típica de landing pages, no de software de datos denso.
- Inter fue diseñada específicamente para interfaces de pantalla: alta legibilidad en tamaños pequeños (12-14px, que es donde vive el 80% del texto de este sistema: tablas, badges, labels), buen soporte de **cifras tabulares** (crítico para columnas de montos en Ventas/Compras/Reportes, donde los números deben alinearse verticalmente).
- Es, literalmente, la tipografía de Linear y una elección extremadamente común en dashboards tipo Stripe/GitHub — coherente con la filosofía ya definida.
- Gratuita (Google Fonts), pesos necesarios: 400, 500, 600, 700.

**Bug actual a corregir en implementación:** Poppins hoy solo se carga (vía `<link>` a Google Fonts) en `welcome.blade.php`. En todas las vistas autenticadas (`app.blade.php` y todo lo que extiende de ahí — el 100% de las pantallas internas), el CSS pide `font-family: Poppins` pero la fuente nunca se cargó, así que el navegador cae silenciosamente a la fuente del sistema. La marca tipográfica hoy solo existe, por accidente, en la página pública de bienvenida.

### Pila tipográfica

```css
--font-sans: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Arial, sans-serif;
```

Cargar Inter **una sola vez**, en `app.blade.php` (layout raíz de toda vista autenticada) y en `welcome.blade.php`/vistas de auth — no en cada vista individual.

### Escala tipográfica

| Token | Tamaño | Peso | Uso |
|---|---|---|---|
| `--text-display` | 28px | 700 | Título de página (`<h1>` de sección, ej. "Reportes") |
| `--text-h2` | 20px | 700 | Subtítulos de sección, título de card destacada |
| `--text-h3` | 16px | 600 | Título de card estándar, encabezado de bloque |
| `--text-body` | 14px | 400 | Texto de párrafo, contenido general — tamaño base |
| `--text-body-strong` | 14px | 600 | Énfasis dentro de texto de cuerpo (nombres, totales en línea) |
| `--text-small` | 13px | 400 | Texto secundario, ayudas de formulario, celdas de tabla |
| `--text-caption` | 12px | 500 | Labels de formulario, encabezados de tabla, badges |

Line-height base: `1.5` para texto de cuerpo, `1.15` para títulos.

---

## 5. Espaciado oficial

El CSS actual no sigue una escala explícita (usa valores sueltos: 6, 10, 14, 18, 22, 26px). Se formaliza una escala de 4px, la más usada en sistemas de este tipo (fácil de razonar, se alinea con casi cualquier grid):

| Token | Valor | Uso típico |
|---|---|---|
| `--space-1` | 4px | Separación mínima (ícono-texto) |
| `--space-2` | 8px | Padding interno de badges, gap entre elementos inline |
| `--space-3` | 12px | Padding de inputs/botones, gap entre campos de formulario |
| `--space-4` | 16px | Padding estándar de card, gap entre bloques |
| `--space-5` | 24px | Separación entre secciones dentro de una página |
| `--space-6` | 32px | Padding del contenedor principal (`.main`) |
| `--space-8` | 48px | Separación entre bloques grandes (ej. header de página y contenido) |

Regla: cualquier `margin`/`padding` nuevo debe ser uno de estos valores. Si ninguno encaja, es señal de que el layout necesita revisarse, no de que hace falta un valor nuevo.

---

## 6. Border radius

Escala de 3 niveles + "full":

| Token | Valor | Uso |
|---|---|---|
| `--radius-sm` | 8px | Inputs, botones pequeños, badges cuadrados |
| `--radius-md` | 12px | Botones estándar, filas de tabla, items de menú |
| `--radius-lg` | 16px | Cards, modales, contenedores grandes |
| `--radius-full` | 999px | Badges/chips tipo píldora, avatar, botón circular (logout) |

Se elimina el valor suelto de 28px que usa hoy solo el login (no forma parte de ninguna escala) y el 14px que aparece como variante aislada de `.tabla-box.soft`.

---

## 7. Sombras

Máximo 3 niveles, todas de baja opacidad — nunca sombras "flotantes" grandes como las que usa hoy el login (`0 30px 80px`):

| Token | Valor | Uso |
|---|---|---|
| `--shadow-sm` | `0 1px 2px rgba(15,23,42,.06)` | Cards en reposo (opcional — muchas cards solo necesitan borde, sin sombra) |
| `--shadow-md` | `0 4px 12px rgba(15,23,42,.08)` | Dropdowns, sugerencias de autocompletado, popovers |
| `--shadow-lg` | `0 12px 32px rgba(15,23,42,.12)` | Modales |

Regla: una card estándar usa **borde de 1px (`--line`) O sombra sutil, no ambos con intensidad**. Hoy varias definiciones de `.card` en el CSS aplican borde + sombra + radius simultáneamente con valores que se pisan entre sí (detalle en el informe de análisis).

---

## 8. Iconografía

- **Librería:** Remix Icon (ya en uso, vía CDN `remixicon@4.3.0`). Se mantiene — es una librería de línea (outline), consistente, gratuita, con la variedad necesaria (`ri-*`). No se introduce una segunda librería de íconos.
- **Tamaño:** 18px dentro de botones/inputs, 20px en ítems de navegación del sidebar, 16px en badges/texto inline.
- **Color:** hereda el color del texto/contenedor (`currentColor`) salvo en estados explícitos (ícono de error = coral, ícono de éxito = verde semántico).
- **Regla:** todo ícono usado como único contenido de un botón (sin texto) debe llevar `aria-label` — ver Accesibilidad.
- **Corrección pendiente:** el `<link>` de Remix Icon está duplicado en `app.blade.php` (cargado dos veces, una en `<head>` y otra repetida justo después de abrir `<body>`). Se corrige a una sola carga en `<head>`.

---

## 9. Botones

| Variante | Uso | Estilo |
|---|---|---|
| **Primario** | Una sola acción principal por vista/formulario | Fondo turquesa `#12A594`, texto blanco, sin borde |
| **Secundario / outline** | Acciones alternativas, "Cancelar", "Filtrar" | Fondo blanco, borde `--line`, texto `--ink` |
| **Destructivo** | Eliminar, anular, acciones irreversibles | Fondo coral `#F27D72`, texto blanco. Requiere confirmación (modal) para acciones irreversibles. |
| **Ghost / texto** | Acciones terciarias dentro de tablas (ej. "Ver más") | Sin fondo, texto turquesa o `--muted`, subrayado solo en hover |

Reglas:
- Radius `--radius-md` (12px) para botones rectangulares; `--radius-full` solo para el botón de logout circular y acciones tipo FAB (ninguna prevista hoy).
- Un botón siempre tiene un estado `:hover` (oscurecer 5-8%) y `:focus-visible` (anillo de foco turquesa, 2px).
- Padding estándar: `--space-3` vertical / `--space-4` horizontal.
- Nunca más de un botón primario (turquesa) visible al mismo tiempo en una misma vista — si hay dos acciones "importantes", una es primaria y la otra es outline.

---

## 10. Formularios

- Inputs con borde `1px solid --line`, radius `--radius-sm`, padding `--space-3`, fondo blanco.
- Estado `:focus`: borde turquesa + anillo de foco sutil (`box-shadow: 0 0 0 3px rgba(18,165,148,.15)`) — reemplaza el actual `outline: 2px solid var(--success)` (usar turquesa para foco de campo es más correcto: el foco es una señal de interacción/acción, no de éxito).
- Labels: `--text-caption` (12px/500), color `--ink`, siempre visibles (nunca solo placeholder como label).
- Estado de error de campo: borde coral + texto de ayuda coral debajo del campo (`--text-small`).
- Inputs deshabilitados/readonly: fondo `--bg` (gris claro), texto `--muted`.
- Selects, inputs de fecha y checkboxes/radios siguen la misma paleta: turquesa para "marcado"/foco, coral para error.

### 10.1 Implementado por adelantado en `UI-04A` (2026-07-21)

De toda esta sección, **solo el texto de ayuda de error debajo del campo** (`.field-error`, `font-size:12px; color:var(--accent)`) está construido — como parte de la estandarización global de errores de validación, no de un sprint de Formularios. El resto (borde/foco de input, estado deshabilitado, etc.) **sigue sin implementar**; cuando llegue el sprint dedicado a Formularios, reutilizar `.field-error` tal cual en vez de crear una clase nueva.

`@error('campo')<small class="field-error">{{ $message }}</small>@enderror` ya está aplicado en los 7 formularios "estáticos" (con `name` fijo en el Blade): Productos, Proveedores, Clientes, Roles, Usuarios, Configuración, Auth (login/register).

**`PEND-08` (2026-07-24)** extendió `.field-error` a los 3 formularios "dinámicos" (Compras, Ventas, Lotes/stock) — ahí no hay `@error()` de Blade porque las filas no existen como `<input>` fijos, así que el mismo `<small class="field-error">` se genera desde JavaScript (una función por vista construye cada fila, tanto para las agregadas a mano como para las reconstruidas desde `old()` tras un error). Mismo token, mismo aspecto, sin CSS nuevo — ver `docs/pendientes.md` (PEND-08) para el detalle completo del mecanismo.

---

## 11. Tablas

Las tablas son el componente más usado del sistema (productos, ventas, compras, los 6 reportes). Reglas:

- Encabezado (`thead th`): fondo `--bg` o blanco (no ambas variantes que coexisten hoy, ver informe), texto `--text-caption`, peso 600-700, alineado a la izquierda (números a la derecha).
- Filas: separadas por borde inferior de 1px (`--line`), sin "filas flotantes" con `border-spacing` a menos que se decida explícitamente para una tabla concreta (hoy el CSS define dos comportamientos contradictorios para `.table`, ver informe).
- Hover de fila: fondo `--bg` sutil, sin sombra ni cambio de tamaño.
- Celdas numéricas/monetarias: alineadas a la derecha, tipografía con cifras tabulares (por eso Inter).
- Paginación: componente único y consistente (`.pagination`), reutilizado en todas las tablas — hoy ya existe y es reutilizable tal cual.
- Estado vacío: nunca una tabla sin filas — usar el componente `empty-box` (ya existe, se formaliza) con mensaje claro.

---

## 12. Cards

- Fondo blanco, borde `--line` de 1px, radius `--radius-lg` (16px), padding `--space-4` (16px).
- Sombra opcional y sutil (`--shadow-sm`), nunca sombra fuerte.
- Título de card: `--text-h3`, opcionalmente con ícono a la izquierda (color petróleo).
- Una card agrupa contenido relacionado — no se usa como contenedor genérico de toda la página (evitar "todo es una card" si no aporta separación real).

---

## 13. Modales

- Fondo del overlay: negro a 50% de opacidad, sin blur exagerado (el `backdrop-filter: blur(3px)` actual se mantiene, es sutil y está bien).
- Contenedor: fondo blanco, radius `--radius-lg`, `--shadow-lg`, ancho máximo según contenido (formulario simple: ~480px; formulario complejo/dos columnas: ~900px).
- Header con título (`--text-h2`) y botón de cierre (ícono, `aria-label="Cerrar"`).
- Footer de acciones alineado a la derecha: botón secundario ("Cancelar") + botón primario o destructivo según la acción.
- Confirmaciones destructivas (eliminar, anular) **siempre** pasan por un modal de confirmación — nunca se ejecuta una acción irreversible directo desde un click en tabla.

---

## 14. Alertas

**Bug actual a corregir:** el sistema usa `class="alert alert-danger"` en al menos 10 vistas (incluyendo errores de validación en el layout principal, `app.blade.php`), pero `.alert-danger` **no está definido en el CSS**. Hoy esos mensajes de error se renderizan con el estilo de `.alert` genérico (fondo celeste/info), no como error. Se corrige como parte de la implementación (Sprint UI-02), no es un problema de diseño sino un defecto a corregir.

Variantes oficiales:

| Variante | Fondo | Texto | Uso |
|---|---|---|---|
| Info (default) | Azul petróleo tenue | Petróleo oscuro | Mensajes neutros |
| Éxito | Verde tenue | Verde oscuro | Confirmaciones (`session('success')`) |
| Error | Coral tenue | Coral oscuro / rojo oscuro para texto | Errores de validación, `session('error')` |
| Advertencia | Coral muy tenue | Coral oscurecido | Avisos no bloqueantes |

Estructura: ícono a la izquierda (contextual: check, alerta, x), texto, botón de cierre opcional. Radius `--radius-sm`, padding `--space-3`.

---

## 15. Badges

**Unificación necesaria:** el CSS actual tiene **tres sistemas de badge distintos** conviviendo (`.chip`/`.chip-ok`/`.chip-warn`/`.chip-bad`/`.chip-neutral`, `.badge-ok`/`.badge-bad` sueltos, y `.badge`/`.badge.danger`/`.badge.warn`/`.badge.ok`) con nombres distintos para el mismo concepto. Se consolidan en **un solo componente** `.badge` con variantes:

| Clase | Fondo | Texto | Uso |
|---|---|---|---|
| `.badge` (default) | Petróleo tenue | Petróleo | Estado neutro/informativo |
| `.badge--success` | Verde tenue | Verde oscuro | Disponible, confirmada, OK |
| `.badge--warning` | Coral muy tenue | Coral oscurecido | Vence pronto, stock bajo |
| `.badge--danger` | Coral sólido tenue | Coral oscuro | Error, anulada |
| `.badge--critical` | Rojo oscuro tenue | Rojo oscuro | Vencido, stock 0 |

Forma: píldora (`--radius-full`), padding `--space-1` `--space-2`, `--text-caption`, peso 700, mayúsculas opcional solo para severidad alta (ej. "VENCIDO").

---

## 16. Colores para gráficos

El dashboard ya reserva un espacio para gráficos futuros ("Rendimiento del personal"). Reglas para cuando se implementen:

- **Serie primaria:** turquesa `#12A594`.
- **Serie secundaria:** petróleo `#357C90`.
- **Serie terciaria (si hace falta una tercera):** gris medio `#94A3B8` (neutro, nunca compite visualmente con las dos primeras).
- **Valores negativos / alertas dentro de un gráfico:** coral `#F27D72` — nunca como serie positiva.
- No usar rojo oscuro en gráficos (reservado a badges/alertas puntuales, no a series de datos).
- No codificar significado solo por color: todo gráfico con más de una serie lleva leyenda con texto, y los estados críticos (ej. "por debajo del umbral") se refuerzan con un ícono o patrón, no solo con el tono de rojo.

---

## 17. Responsive

Breakpoints oficiales (hoy el CSS usa valores sueltos e inconsistentes: 520, 720, 900, 1024 sin criterio único — se formalizan):

| Token | Ancho | Comportamiento |
|---|---|---|
| `--bp-sm` | 640px | Formularios pasan a una sola columna |
| `--bp-md` | 768px | Grids de 2 columnas colapsan a 1 |
| `--bp-lg` | 1024px | Layouts de 2 paneles (ej. venta nueva) colapsan a 1 columna |
| `--bp-xl` | 1280px | Sidebar puede pasar a modo colapsado por defecto |

Regla de sidebar: por debajo de `--bp-lg`, el sidebar se oculta y se abre como overlay (comportamiento ya implementado, se mantiene).

Las tablas no se convierten a "cards apiladas" en móvil en esta fase (mucho trabajo, bajo uso real del sistema desde móvil) — quedan con scroll horizontal dentro de `.table-wrap` (ya existe).

---

## 18. Reglas de accesibilidad

- **Contraste:** todo texto sobre color debe cumplir WCAG AA (4.5:1 para texto normal, 3:1 para texto grande ≥18px o íconos). El turquesa `#12A594` con texto blanco cumple para botones (texto grande/peso 700); no usar turquesa como color de texto sobre fondo blanco para texto pequeño (falla AA) — para eso usar la variante oscurecida `#0E8C7F` o el petróleo.
- **Nunca color como único indicador:** todo badge de estado lleva texto (ya es la convención actual: "VENCIDO", "STOCK BAJO"), no solo un punto de color.
- **Foco visible siempre:** todo elemento interactivo (botón, link, input, fila clicable) tiene un estado `:focus-visible` perceptible — no se remueve el outline sin reemplazarlo.
- **Íconos sin texto:** requieren `aria-label` (botón de cerrar sidebar, botón de logout, botón de cerrar modal).
- **Formularios:** todo input tiene `<label>` asociado (por `for`/`id`), no solo placeholder.
- **Tamaño mínimo de objetivo táctil:** botones e ítems de menú de al menos 40px de alto en las zonas clicables.
- **Mensajes de error de formulario:** asociados al campo mediante texto visible cercano, no solo color de borde.

---

## 19. Componentes reutilizables previstos

Inventario de componentes que el Design System debe formalizar como parcial/vista Blade reutilizable (`resources/views/components/`), en vez de repetir markup en cada vista:

| Componente | Reemplaza hoy | Prioridad |
|---|---|---|
| `x-badge` | 3 sistemas de badge distintos repetidos inline en cada vista | Alta |
| `x-alert` | Bloques de alerta repetidos manualmente en cada vista (incluye el fix de `alert-danger`) | Alta |
| `x-button` | Botones con clases sueltas y combinaciones inconsistentes (`btn`, `btn.add`, `btn.primary`, `btn-outline`) | Alta |
| `x-empty-state` | `.empty-box` ya repetido con el mismo markup en 3 vistas de Reportes | Media |
| `x-card` | Card con header/título, repetida con pequeñas variaciones | Media |
| `x-table-filters` | El formulario de filtros (fecha/producto/etc.) se repite casi idéntico en los 6 reportes, con estilos inline | Alta |
| `x-pagination-wrap` | Ya hay un patrón consistente (`{{ $x->links() }}` + wrapper), formalizarlo como partial | Baja |
| `x-modal` | Modales con markup manual repetido (crear/editar producto, stock, etc.) | Media |
| `x-form-errors` | ✅ Construido en `UI-04A` (2026-07-21) — banner `$errors->any()` repetido/duplicado en `app.blade.php`, `ventas/create`, `compras/create`, `auth/login`. Ver sección 10.1. | — |
| `x-search-box` | 10 buscadores repartidos en 6 vistas, normalizados (no extraídos todavía) en `UI-06` (2026-07-24). Ver sección 19.1. | Alta — candidato para el próximo refactor, una vez validado en producción |

Esto es catálogo para planificación — no se crea nada en este sprint (salvo `x-form-errors`, ya construido — ver fila arriba).

### 19.1 Patrón unificado de buscadores (`UI-06`, sin componente todavía)

Antes de convertirlo en `x-search-box`, `UI-06` normalizó el HTML/CSS/JS de todos los buscadores del sistema **dentro de cada vista** (código todavía no compartido — ver `docs/pendientes.md` para el porqué de esa secuencia). Referencia: `productos/index.blade.php`.

Dos modos, no uno:

- **Modo "filtro"** (navega vía GET): Proveedores, Usuarios, Roles, Clientes, Productos. Estructura fija:
  ```blade
  <form id="form-buscar" method="GET" action="{{ route('X.index') }}" style="margin:0">
    <div class="search-wrap" style="margin-bottom:12px; position:relative">
      <i class="ri-search-line"></i>
      <input id="q" type="text" name="q" placeholder="..." value="{{ $q }}">
      <x-button type="submit" variant="secondary" icon="ri-filter-2-line">Buscar</x-button>
      <div id="sugg" class="sugg hidden"></div>
    </div>
  </form>
  ```
  `$suggData` se calcula en la vista (`@php`) a partir de la colección ya paginada que el controlador pasa — ningún controlador se modificó para esto.
- **Modo "selector"** (llena un campo oculto, nunca navega): los 4 casos de Compras/Ventas (producto, proveedor, cliente). Mismo `.search-wrap`/`.sugg`, sin botón "Buscar" (no aplica). Único cambio hecho en `UI-06`: reemplazar los íconos emoji (📦/🚚/👤/➕) por íconos Remix (`ri-archive-2-line`/`ri-truck-line`/`ri-user-3-line`/`ri-add-circle-line`) — su lógica de selección (llenar `producto_id`/`proveedor_id`/`cliente_id`) no se tocó.

**Excepciones deliberadamente no generalizadas** (existían antes de `UI-06`, se documentaron y se dejaron igual, no se replicaron a otras vistas ni se quitaron):
- Clientes: filtrado de tabla en vivo (`filterRows`/`showAllRows`) mientras se escribe, sin esperar al servidor.
- Compras: opción "crear nuevo" embebida como ítem del propio dropdown (Ventas la resuelve con un botón aparte — ambas formas conviven, no se unificaron).
- Ventas: badge de disponibilidad ("Disponible"/"Agotado") en la sugerencia de producto.

---

## 20. Reglas para mantener consistencia visual

1. **Cero estilos inline nuevos.** Hoy existen ~200 atributos `style="..."` repartidos en 25 vistas (incluidas las de Reportes, construidas en sprints recientes). A partir de este Design System, cualquier estilo puntual se resuelve con una clase de utilidad definida en el sistema, no con `style=""` en la vista.
2. **Cero `<style>` embebido por vista.** El login y el dashboard hoy definen bloques `<style>` propios dentro del `.blade.php`. Todo estilo vive en la hoja de estilos central (o en un archivo de componente, nunca inline en la vista de negocio).
3. **Un solo lugar para tokens.** Colores, espaciados, radios y sombras se definen una sola vez (variables CSS en `:root`) y se consumen por token — nunca se escribe un hex, un px de spacing o un radius "a mano" en una regla nueva.
4. **No redefinir selectores ya definidos.** Hoy `.card`, `.btn`, `.search-wrap`, `.table thead th`, entre otros, están definidos 2-3 veces en el mismo archivo con pequeñas diferencias que se pisan según el orden de cascada. Cada selector se define una sola vez.
5. **Nombrar por rol, no por color.** Evitar clases como `.btn-turquesa`; usar `.btn-primary`. Si mañana cambia el color de marca, no debería haber que renombrar clases en 20 vistas.
6. **Toda vista nueva reutiliza componentes existentes antes de crear uno.** Si una vista necesita algo que el catálogo de la sección 19 no cubre, se agrega al catálogo primero, se discute, y luego se construye — no se resuelve ad hoc dentro de una sola vista (así fue como se llegó a 3 sistemas de badge distintos).
7. **Revisión visual antes de cerrar un sprint de UI.** Cualquier sprint que toque vistas debe compararse contra este documento antes de darse por cerrado — mismo criterio que ya se usa para verificar lógica de negocio contra `docs/pendientes.md`.
8. **Prohibido crear CSS específico para una sola vista si el problema puede resolverse reutilizando un componente existente del Design System.** Si aparece esa necesidad, el orden correcto es: primero ampliar el componente compartido (agregar la variante que falta), después reutilizarlo en la vista. Nunca al revés. Esta regla es la causa raíz de por qué hoy existen 3 sistemas de badge y un `.table` con comportamiento contradictorio — cada vista resolvió su necesidad puntual en vez de extender lo compartido.

---

## 21. Reglas estrictas de diseño (no negociables)

Checklist corto, para consulta rápida durante cualquier sprint de UI. Cada regla aquí ya está desarrollada con más detalle en alguna sección anterior — esta lista existe para que no haga falta releer todo el documento para verificar que una propuesta cumple.

1. **No usar degradados.**
2. **No usar glassmorphism.**
3. **No usar neumorphism.**
4. **No usar sombras exageradas** (máximo lo definido en la sección 7 — `--shadow-sm/md/lg`).
5. **No usar animaciones innecesarias** — transición solo donde comunica estado (hover, focus, apertura de menú).
6. **No usar más de un color de acento por pantalla** — un solo botón primario (turquesa) visible a la vez por vista/formulario.
7. **Mucho espacio en blanco** — no llenar cada pixel disponible; el espaciado es parte del diseño, no un descuido.
8. **Bordes suaves, 8–12px** (`--radius-sm`/`--radius-md`; 16px solo para cards/modales, ver sección 6).
9. **Jerarquía basada en tamaño y peso tipográfico, no en colores** (ver sección 2 y 4).
10. **Iconos siempre Remix Icons** — ninguna otra librería de íconos, nunca emojis como ícono funcional.
11. **Máximo dos tamaños de botón por vista** (ej. estándar + pequeño/inline — nunca tres o más tamaños conviviendo).
12. **Máximo tres niveles visuales por pantalla** (ej. página → card → contenido; o tabla → fila → celda) — si una vista necesita un cuarto nivel de anidamiento visual, es señal de que hay que simplificar la información, no de agregar otro contenedor.
13. **Todo debe sentirse ligero** — si un componente nuevo "pesa" visualmente (sombra fuerte, bloque de color grande, borde grueso), no encaja en este sistema.
14. **Todas las tablas comparten exactamente la misma estructura** (un solo componente de tabla, sección 11 — no hay "la tabla de ventas" y "la tabla de reportes" con maquetación distinta).
15. **Todos los formularios comparten exactamente el mismo patrón** (un solo componente de formulario/campo, sección 10 — mismo espaciado, mismo estilo de label, mismo estado de error en todos lados).
16. **Todo componente nuevo se agrega primero al Design System, después se reutiliza.** Ninguna vista implementa un componente "solo para esta pantalla" — eso es exactamente lo que produjo, en tres meses, una interfaz con piezas que no combinan entre sí. Ver regla 8 de la sección 20.

**Sobre decisiones de identidad visual:** ninguna decisión que afecte paleta, tipografía, espaciado, iconografía o cualquier regla de esta lista se toma unilateralmente durante la implementación. Si un sprint de código encuentra un caso no cubierto por este documento, se detiene y se propone aquí primero — mismo criterio ya aplicado en los sprints de Reportes para reglas de negocio no definidas.

---

## 22. Hoja de ruta de implementación

Secuencia de sprints acordada para llevar este Design System a código. Cada sprint sigue la misma metodología usada en el módulo de Reportes: **análisis → aprobación → implementación → pruebas → documentación.** Ningún sprint empieza a escribir código sin aprobación explícita del anterior.

| Sprint | Nombre | Alcance |
|---|---|---|
| `UI-01` | Design System (documentación) | ✅ Completo — este documento: filosofía, paleta, tipografía, espaciado, componentes, iconografía, tablas, formularios, estados, responsive, accesibilidad y reglas de UX. Sin cambios de código. |
| `UI-02` | Diseño visual del sistema (mockups/propuesta visual) | ✅ Completo — wireframe del Dashboard aprobado en detalle; Listados (modal vs. página), índice de Reportes (grid vs. lista) y Login (una columna vs. split-screen) quedaron deliberadamente diferidos a resolverse en el turno de migración de cada uno dentro de `UI-05`, no bloquean el resto. |
| `UI-03` | Construcción del Design System en código | ✅ Completo — `public/css/style.css` reescrito desde los tokens de este documento (normalización + tokenización, sin rediseño visual salvo las dos excepciones aprobadas: tipografía Inter y botones primarios turquesa). |
| `UI-04` | Componentes Blade reutilizables | ✅ Completo — `x-badge`, `x-button`, `x-alert`, `x-empty-state`, `x-card` creados con API cerrada y probados (`tests/Feature/ComponentesBladeTest.php`, 19 casos). |
| `UI-05` | Migración de vistas | 🔶 En curso — **Dashboard migrado (2026-07-14). Índice de Reportes migrado (2026-07-14, decisión grid-vs-lista resuelta a favor de lista); las 6 subvistas de Reportes siguen pendientes. Productos migrado (2026-07-14, solo `productos/index.blade.php`; gestión de lotes fuera de alcance, ver `PEND-07`). Compras completo (2026-07-14 índice, 2026-07-24 `create`). Ventas completo (`create` 2026-07-24, `index` 2026-07-24) — chip de estado dejado sin migrar, mismo criterio que "Inyectable" en Productos (`x-badge` sin variante `neutral`); de paso se documentó `BUG-11`. Clientes migrado (2026-07-24) — de paso se eliminaron un `<link>` de `style.css` duplicado y un banner de sesión local que duplicaba el global de `app.blade.php` (mismo criterio de `UI-04A`, ahora extendido a `session('success')`/`session('error')`, no solo `$errors`); a partir de acá, ese chequeo de duplicados se aplica a todos los módulos administrativos restantes. Proveedores migrado (2026-07-24) — mismas dos duplicaciones eliminadas, más un bug de visibilidad real corregido (`style="color:white"` sobre fondo claro en el encabezado) y un typo de texto ("proveedors"→"proveedores"). Roles migrado (2026-07-24) — se eliminó todo el bloque decorativo `.hero`/`.grid`/`.shadow`/`.bubble` (sin ninguna regla CSS real, confirmado por grep) en favor de `<x-card>`; se corrigió un `<td>` anidado inválido en la columna de acciones; se eliminó el `<script src="js/rols.js">` y `window.routesRolsStore` — ese archivo nunca existió en el proyecto (ni el archivo ni el directorio `public/js/`), causaba un 404 silencioso en cada carga, y toda su funcionalidad ya estaba en el `<script>` inline de la misma vista. De paso se corrigió una desactualización de `docs/modulos.md`: su nota de "Bug Crítico" (permisos no sincronizados) ya estaba resuelta desde 2026-07-07 (`BUG-01` en `docs/pendientes.md`), pero la sección del módulo nunca se había actualizado para reflejarlo.** Ambos formularios dinámicos (Compras/Ventas `create`) se migraron recién después de resolver `PEND-08` (preservación de datos/errores por fila). Orden acordado para el resto: Dashboard ✅ → Reportes (índice ✅, subvistas pendientes) → Productos ✅ → Lotes → Compras ✅ → Ventas ✅ → Clientes ✅ → Proveedores ✅ → Roles ✅ → Usuarios → Configuración → Login. |
| `UI-06` | Estandarización global de buscadores | ✅ Completo (2026-07-24) — ver detalle abajo. **Nota de numeración:** este sprint tomó el nombre `UI-06`, que ya estaba reservado para "Limpieza final" en esta tabla desde `UI-01`. Se corrió ese sprint a `UI-07` para no perder el registro; no hubo ninguna ambigüedad durante la implementación, solo se reconcilia acá la numeración. |
| `UI-07` | Limpieza final (antes `UI-06`) | Eliminar CSS muerto, estilos inline restantes, dependencias sin uso (Tailwind/Vite si se confirma), revisar responsive, accesibilidad y rendimiento. |
| `UI-04A` | Estandarización global de errores de validación | ✅ Completo para formularios estáticos (2026-07-21) — `x-form-errors` (banner global, reemplaza el bloque duplicado en `app.blade.php`/`ventas/create`/`compras/create`/`auth/login`), traducción completa a español (`lang/es/validation.php`, `auth.php`, `passwords.php`, `APP_LOCALE=es`), `.field-error` + `@error` en los 7 formularios estáticos (Productos, Proveedores, Clientes, Roles, Usuarios, Configuración, Auth). Corrigió de paso `BUG-10` (`auth/register.blade.php` roto). **Pendiente:** los 3 formularios dinámicos (Compras, Ventas, Lotes/stock) — ver `PEND-08`. |
