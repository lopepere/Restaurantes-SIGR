# PROMPT MAESTRO — Construcción de SIGR en Laravel 13

> **Cómo usarlo:** pega la **Parte A** completa al iniciar la sesión con el agente (Claude Code, Copilot Agent, Cursor). Adjunta también `ERS_SIGR_v1.1.pdf` si la herramienta lo permite. Después envía, una a la vez, los prompts de iteración de la **Parte B**, revisando y haciendo *merge* antes de pasar a la siguiente.

---

## PARTE A — Contexto y reglas permanentes

### A.1 Rol y objetivo

Actúa como desarrollador senior de Laravel. Vas a construir **SIGR (Sistema Integral de Gestión de Restaurante)**, una aplicación web multi-sucursal para gestionar pedidos, reservas, menú digital, promociones, caja, comprobantes, compras, inventario e informes, con actualizaciones en tiempo real.

La fuente de verdad es la **ERS v1.1** (adjunta). Este prompt resume lo esencial. Si algo de este prompt contradice la ERS, gana la ERS. Si encuentras una ambigüedad que ninguno de los dos resuelve, **no inventes**: detente, explica la duda y propón dos opciones.

Trabaja **por iteraciones**. No avances a la siguiente sin que te lo indique. Al terminar cada iteración entrega: lista de archivos creados o modificados, migraciones, comandos para probar, resultado de `php artisan test`, `pint --test` y `phpstan`, y los requerimientos (RF) y casos de aceptación (CA) cubiertos.

### A.2 Stack obligatorio

| Capa | Tecnología |
|---|---|
| Backend | PHP 8.3+, **Laravel 13** |
| Frontend | Inertia.js 2 + Vue 3 (Composition API, `<script setup>`) + Tailwind CSS 4 + Vite |
| Base de datos | MySQL 8, `utf8mb4_unicode_ci` |
| Tiempo real | Laravel Reverb + Laravel Echo (`pusher-js`) |
| Colas, caché y sesiones | Redis |
| Autenticación | Laravel Fortify (sin interfaz; las vistas son páginas Inertia) |
| Roles y permisos | `spatie/laravel-permission` con *teams* = sucursal |
| Auditoría | `spatie/laravel-activitylog` |
| PDF / Excel | `barryvdh/laravel-dompdf`, `maatwebsite/excel` |
| HTML seguro | `mews/purifier` |
| Teléfonos | `propaganistas/laravel-phone` (formato E.164) |
| Avatares | `laravolt/avatar` (generación local, sin servicios externos) |
| Respaldo | `spatie/laravel-backup` |
| Calidad | Pest, Laravel Pint (PSR-12), Larastan nivel 6 |
| Entorno | Laravel Sail (PHP 8.3, MySQL 8, Redis, Mailpit) |

Si una versión de paquete no es compatible con Laravel 13, usa la última estable compatible e infórmalo.

### A.3 Arquitectura y convenciones

- **Monolito modular.** La lógica de negocio va en `app/Actions` (una clase por caso de uso con método `handle()`) y `app/Services`. Los controladores solo validan (con *Form Requests*), autorizan (con *Policies*), llaman a la Action y devuelven `Inertia::render()` o `redirect()`.
- **Estructura:**
  ```
  app/Actions/{Modulo}/   app/Enums/   app/Events/   app/Http/Controllers/{Public,Cliente,Mesero,Admin,SuperAdmin}/
  app/Http/Requests/      app/Models/  app/Notifications/  app/Observers/  app/Policies/  app/Services/
  resources/js/Pages/{Public,Auth,Cliente,Mesero,Admin,SuperAdmin}/   resources/js/Components/
  resources/views/{mail,pdf}/   lang/{es,en}/
  ```
- **Nombres:** tablas en plural y en español (`pedidos`, `pedido_items`); modelos en singular (`Pedido`); columnas en `snake_case`; Enums PHP respaldados por `string`.
- **Enums:** `TipoPedido` (EN_SITIO, PARA_LLEVAR, DOMICILIO), `EstadoPedido`, `EstadoReserva`, `MetodoPago` (EFECTIVO, TRANSFERENCIA, TARJETA_DEBITO, TARJETA_CREDITO), `ModoInventario` (AUTONOMO, DEPENDIENTE), `TipoMovimiento`, `TipoImpuesto` (IVA, INC, OTRO), `PropinaTipo` (NINGUNA, PORCENTAJE, ABIERTA), `OrigenUsuario` (REGISTRO, PEDIDO, RESERVA, PERSONAL).
- **Dinero:** `decimal(14,2)`; los cálculos se hacen en una sola clase (`App\Services\MonedaService`) con redondeo `ROUND_HALF_UP` a 2 decimales. Los precios del menú **incluyen impuesto**.
- **Fechas:** `APP_TIMEZONE=UTC`. Se guarda siempre en UTC y se muestra en la zona horaria de la sucursal (*accessor* o *helper* `fechaLocal()`).
- **Idioma:** todos los textos de la interfaz salen de `lang/es` y `lang/en`. Nada de textos fijos en las vistas.
- **Git:** *Conventional Commits* con el ID de la ERS: `feat(pedidos): ventana de edición de 5 minutos [RF-PED-09]`. Una rama por iteración: `feature/I-3-pedidos`.

### A.4 Reglas transversales (no negociables)

1. **Multi-sitio (DE-09).** Toda tabla operativa tiene `sucursal_id`. Crea el trait `BelongsToSucursal` con un *global scope*: el superadministrador ve todo; los demás solo las sucursales asignadas en `sucursal_user`. Al crear, `sucursal_id` toma por defecto la sucursal activa de la sesión. El acceso a datos de otra sucursal debe devolver 403 o 404.
2. **Borrado lógico (DE-11).** Todos los modelos principales usan `SoftDeletes`. **Nunca** hay borrado físico desde la aplicación. Las claves foráneas usan `restrictOnDelete()`. Antes de eliminar (incluso de forma lógica), una Action genérica `EliminarConValidacionAction` revisa las relaciones activas y, si existen, rechaza con un mensaje que diga cuáles y cuántas son. Cada módulo tiene una **papelera** con opción de restaurar, solo para administradores.
3. **Auditoría inmutable (DE-12).** Trait `Auditable` sobre `spatie/laravel-activitylog`: registra crear, editar (valores antes y después), eliminar, restaurar y cambiar estado, más inicio y cierre de sesión. Guarda usuario, rol, sucursal, módulo (`log_name`), IP, *user agent* y `created_at` con precisión de 6 decimales. No existen rutas de edición ni borrado de la bitácora. Usa una conexión `audit` y documenta en el README el `GRANT` de solo `INSERT, SELECT` sobre `activity_log`.
4. **Unicidad (DE-05).** `users.email` y `users.telefono` son **únicos cada uno por separado**, en todos los roles, **incluidos los eliminados lógicamente** (no excluyas `deleted_at` en la regla `unique`). El teléfono se normaliza a E.164 antes de validar.
5. **Autorización.** Toda acción pasa por una *Policy*. Los permisos se asignan a roles (`super_admin`, `admin`, `mesero`, `cliente`), de forma que en el futuro se puedan crear roles nuevos (cajero, cocina) sin tocar código.
6. **Tiempo real (DE-22).** Eventos `ShouldBroadcast` en canales privados `sucursal.{id}.pedidos`, `sucursal.{id}.mesas`, `sucursal.{id}.menu`, `user.{id}` y el canal público `menu.{sucursalId}`. Autoriza los canales en `routes/channels.php`. Si el WebSocket no está disponible, el *composable* de Vue consulta cada 15 segundos.
7. **Colas.** Todo correo, PDF y exportación implementa `ShouldQueue`.
8. **Seguridad.** Limita los intentos (`RateLimiter`) en login, registro, formularios públicos y reenvío de comprobantes (10 por hora por usuario). Las plantillas de correo guardadas en la base de datos se sanean con HTMLPurifier y las variables `{{cliente.nombre}}` se reemplazan con `strtr()`/regex. **Nunca compiles Blade desde la base de datos** (riesgo de inyección de código). Las subidas se validan por tipo (jpg, png, webp) y tamaño (2 MB).
9. **Rendimiento.** Activa `Model::preventLazyLoading()` fuera de producción. Usa paginación del servidor (25 registros) y *eager loading*.
10. **Configuración.** Tabla `configuraciones` (`sucursal_id` nullable, `clave`, `valor` JSON). `ConfiguracionService::get($clave, $sucursal)` devuelve el valor de la sucursal o, si no existe, el global, con caché que se invalida al guardar.

### A.5 Roles

| Rol | Puede |
|---|---|
| Visitante | Ver home, menú, promociones y disponibilidad; pedir y reservar sin cuenta; **registrarse voluntariamente**. |
| Cliente | Sus pedidos (crear, notas dentro de la ventana, solicitar cancelación, repetir último), reservas, **sus comprobantes** (ver, imprimir, descargar, reenviar), perfil y avatar. |
| Mesero | Crear pedidos de cualquier tipo; editar **solo los suyos** en estado Pendiente y dentro de la ventana; ver todos los de su sucursal; asignar o cambiar mesa sin restricción; cambiar estados; actualizar espacios. |
| Admin | Todo lo operativo de sus sucursales: pedidos, cancelaciones, menú, precios y costos, promociones, reservas, caja, comprobantes, compras, inventario, informes, bitácora, papelera y meseros. |
| Super admin | Todo en todas las sucursales, más sucursales, administradores, impuestos, parámetros globales, idiomas y plantillas de correo. No se puede desactivar ni eliminar al último superadministrador. |

### A.6 Reglas de negocio clave por módulo

**Usuarios (USR)**
- Datos obligatorios: nombre, email, teléfono y dirección (todos los roles).
- Hay dos formas de crear la cuenta de un cliente: **(a)** registro voluntario desde «Registrarse» en el home (con contraseña, aceptación de la política de datos y verificación del email; `MustVerifyEmail` y middleware `verified` en `/cliente`), o **(b)** automáticamente al completar su primer pedido o reserva sin cuenta. En el caso (b) se envía una invitación con `URL::temporarySignedRoute` válida 72 horas para definir la contraseña. **Registrarse nunca es requisito para pedir ni reservar.**
- Si en un pedido o reserva sin sesión el email o el teléfono ya existen, se pide iniciar sesión.
- Si al registrarse el email o el teléfono pertenecen a una cuenta creada por un pedido y aún no activada, se reenvía la invitación y no se crea otra cuenta. Si la cuenta ya está activa, se ofrece iniciar sesión o recuperar la contraseña.
- Guarda `origen` (REGISTRO, PEDIDO, RESERVA, PERSONAL) y `politica_aceptada_at`.
- Cada cliente recibe un avatar aleatorio generado localmente, que puede regenerar.

**Menú (MEN) y promociones (PRO)**
- Toda sucursal nueva recibe las categorías Entradas, Platos principales, Bebidas y Menú infantil (Observer de `Sucursal`).
- No se elimina una categoría con productos, ni un producto con pedidos, compras, combos o promociones.
- Un producto con `controla_inventario = true` y existencia 0 se muestra **agotado en gris** y no se puede pedir; se oculta si la sucursal activa `ocultar_agotados`.
- Combo = producto de tipo `combo` con componentes (`combo_items`): está disponible solo si todos sus componentes lo están, y al venderse descuenta el inventario de cada componente.
- Promoción: vigencia (`inicia_at`, `termina_at`), categoría asociada, productos con descuento o precio especial, prioridad y sucursales. Un Job programado cada minuto publica y retira promociones y difunde el cambio. Si hay varias vigentes, se muestran en carrusel y siempre de primeras en el home y el dashboard del cliente.
- `pedido_items` guarda una copia del nombre, el precio y el impuesto al momento de la venta.

**Pedidos (PED)**
- Estados: PENDIENTE → CONFIRMADO → EN_PREPARACION → LISTO → ENTREGADO → PAGADO; CANCELACION_SOLICITADA; CANCELADO. Implementa `EstadoPedido::transicionesPermitidas()` y rechaza cualquier otra transición. Registra cada cambio en `pedido_estados`. El cliente ve tres etiquetas: «En curso», «Completado» y «Entregado».
- Ventana de edición parametrizable (5 minutos por defecto): el **cliente** no edita productos, solo agrega notas dentro de la ventana; el **mesero** edita solo sus pedidos en PENDIENTE y dentro de la ventana; fuera de ella, solo el **admin**. Implementado en `PedidoPolicy@update`.
- Cancelación: el cliente o el mesero la **solicitan** con un motivo. El pedido pasa a CANCELACION_SOLICITADA y los admins reciben una alerta en tiempo real con nombre y teléfono del solicitante. El admin aprueba (CANCELADO + reposición de inventario) o rechaza (vuelve al estado anterior). Existe el parámetro opcional `cancelacion_automatica`, desactivado por defecto.
- El inventario se **descuenta al crear el pedido** dentro de `DB::transaction()` con `lockForUpdate()`, sin dejar existencias negativas. Si se agota, se emite `ProductoAgotado`.
- En pedidos a domicilio la dirección de entrega es obligatoria (por defecto, la del perfil). En pedidos en sitio tomados por un mesero, el cliente es opcional.
- La mesa (`espacio_id`) es opcional y solo la asignan o cambian meseros y admins. La mesa queda ocupada mientras tenga pedidos activos y se libera sola cuando todos se pagan o cancelan (`PedidoObserver`).
- Número consecutivo por sucursal. Los listados se ordenan por `created_at` descendente.
- Vistas del admin en pestañas: En curso, Solicitudes de cancelación, Domicilios, Para llevar, En sitio. Los pedidos se muestran en acordeón con nombre, teléfono y dirección. Filtros: estado, nombre, número, teléfono, tipo, mesero, mesa, rango de fechas, método de pago y sucursal (solo superadmin).
- «Repetir último pedido» arma el carrito con los productos todavía disponibles, a precios vigentes, e informa cuáles no lo están.

**Reservas (RES)**
- Espacios (mesa, barra, salón) con tipo de mesa y capacidad. Tipos de mesa: barra, 1, 2, 4, 6 y 8 personas, y opción abierta (exige un mínimo de personas).
- Horario de atención por día de la semana. Parámetros por sucursal: duración de la reserva (120 min), intervalo entre franjas (30 min), anticipación mínima (2 h) y máxima (60 días), y `aprobacion_automatica`.
- `DisponibilidadService::franjas($sucursal, $fecha, $personas)` devuelve las franjas en las que hay capacidad, descontando reservas que se solapan (pendientes o confirmadas) y espacios ocupados por pedidos activos. La última franja termina antes del cierre.
- Estados: PENDIENTE, CONFIRMADA, CANCELADA, CUMPLIDA, NO_ASISTIO. Las categorías de reserva son parametrizables.

**Caja (CAJ)**
- Solo se registran pagos con una sesión de caja abierta (base de efectivo). Un pedido admite varios pagos (pago mixto).
- En efectivo: valor recibido, denominación del billete y devuelta calculada.
- **La propina es opcional:** opciones «Sin propina» (seleccionada por defecto, salvo que el parámetro `propina.preseleccionar` esté activo), porcentaje sugerido (5 % o 10 %, editables) o valor abierto. La sugerencia siempre se muestra. La propina se calcula sobre la base sin impuestos y no forma parte de la base gravable.
- El pedido queda PAGADO cuando la suma de los pagos cubre `subtotal − descuentos + propina aceptada`. **Un pago por el total sin propina es válido.**
- Cierre: efectivo esperado = base + cobros en efectivo − devueltas. Se compara con el efectivo contado y, si hay diferencia, se exige una observación. Totales por método de pago y propinas (total y por mesero). Una sesión cerrada no se reabre.
- Al completar el pago se genera el comprobante en PDF (Job) y se envía por correo si el cliente tiene email.

**Comprobantes (FAC) — módulo nuevo**
- Un solo comprobante por pedido pagado, con número consecutivo por sucursal. Es **inmutable**: se guarda el PDF y su `hash_pdf`.
- Listado con filtros (número, cliente por nombre, email o teléfono, número de pedido, rango de fechas, método de pago, estado de envío y sucursal), vista previa, impresión en A4 o en tirilla de 80 mm, descarga en PDF idéntico al emitido, reenvío al email del cliente o hasta 3 correos designados con un mensaje opcional, y exportación a Excel.
- Tabla `envios_comprobante` (solo inserción): tipo (EMAIL_CLIENTE, EMAIL_DESIGNADO, DESCARGA, IMPRESION), destinatarios (JSON), mensaje, usuario, estado (ENCOLADO, ENVIADO, FALLIDO) y error.
- El cliente ve solo los suyos en «Mis comprobantes»; el admin, los de su sucursal; el superadmin, todos.
- Si el pedido no tiene email (cliente de mesa), el comprobante se puede imprimir o enviar a un correo designado.
- Se llama «comprobante de venta», **no factura electrónica**. Deja creada la interfaz `FacturacionElectronicaGateway` con una implementación nula.

**Compras (COM) e inventario (INV)**
- Las compras **no se editan ni se eliminan**. Se corrigen con una **anulación total** (con motivo) o una **devolución parcial** (por líneas), que generan movimientos inversos. Estados: REGISTRADA, DEVUELTA_PARCIAL, ANULADA.
- Línea de compra: nombre, código, proveedor, NIT (opcional), cantidad, costo unitario, indicador de si incluye impuesto e impuesto elegido con **radio button** («Sin impuesto» por defecto). Si incluye impuesto: `base = costo / (1 + tarifa)`; si no: `impuesto = costo × tarifa`.
- La tabla `impuestos` viene precargada con: Sin impuesto 0 %, IVA 5 %, INC 8 %, Otro 10 % e IVA 19 %. Solo el superadmin la edita.
- Modo de inventario por sucursal (lo cambia solo el superadmin):
  - **AUTÓNOMO:** las existencias se registran a mano, por producto o por lote; las compras sirven solo como referencia.
  - **DEPENDIENTE:** cada línea de compra se vincula a un producto con `factor_conversion` y suma existencias.
- El inventario inicial se hace una sola vez por sucursal y luego se bloquea. Los ajustes positivos o negativos exigen motivo.
- Todo cambio de existencias es un registro en `movimientos_inventario` (polimórfico al documento de origen) con el saldo resultante. Incluye kárdex por producto y un tablero con existentes, agotados, existencia baja y últimos editados.

**Informes (INF)**
- Tablero en tiempo real: ventas del día, número de pedidos, ticket promedio, pedidos por estado, reservas del día y agotados.
- Ventas por día, semana, mes, año y rango; por mesero (pedidos, ventas, propinas y % de pedidos con propina); productos más y menos vendidos; compras por proveedor; cancelaciones; consolidado por sucursales (superadmin). Exportación a PDF y Excel.

**Configuración (CFG)**
- Sucursales (zona horaria, moneda —COP por defecto— e idioma), parámetros globales y por sucursal, impuestos, propinas, idiomas (es y en).
- Editor visual de plantillas de correo (Tiptap o TinyMCE autoalojado) con imágenes, logo, emojis y variables, más vista previa y envío de prueba. Plantillas: invitación, verificación de registro, bienvenida, pedido recibido, cambio de estado, comprobante, reenvío de comprobante y reserva.

### A.7 Modelo de datos (tablas)

Crea las migraciones en este orden, con claves foráneas `restrictOnDelete()` y los índices indicados:

`sucursales`, `configuraciones`, `users` (+ `origen`, `telefono` único, `email_verified_at`, `invitacion_aceptada_at`, `politica_aceptada_at`, `avatar`, `idioma`, `activo`, *soft deletes*), `sucursal_user`, tablas de spatie (permission con *teams*, activitylog), `impuestos`, `categorias`, `productos` (único `sucursal_id`+`codigo`), `combo_items`, `promociones`, `promocion_producto`, `tipos_mesa`, `espacios`, `horarios_atencion`, `categorias_reserva`, `reservas`, `pedidos` (único `sucursal_id`+`numero`; índice `sucursal_id, estado, created_at`), `pedido_items`, `pedido_estados`, `solicitudes_cancelacion`, `cajas`, `sesiones_caja`, `pagos` (`propina_valor` por defecto 0), `comprobantes` (único `pedido_id`; único `sucursal_id`+`numero`; `hash_pdf`; sin *soft deletes*), `envios_comprobante`, `proveedores`, `compras` (sin *soft deletes*), `compra_items`, `devoluciones_compra`, `movimientos_inventario`, `plantillas_correo`, `notifications`, `jobs`, `failed_jobs`.

Los campos detallados están en la sección 8.1 de la ERS.

### A.8 Datos de prueba (`DatabaseSeeder`)

- Sucursal «Sede Centro» (America/Bogota, COP, es).
- Usuarios con contraseña `password`: `superadmin@sigr.test`, `admin@sigr.test`, `mesero@sigr.test`, `cliente@sigr.test` (origen PEDIDO) y `registrado@sigr.test` (origen REGISTRO, email verificado).
- Impuestos precargados, 3 cajas, 12 espacios (barra, mesas de 2, 4, 6 y 8, y salón de eventos), horario de 12:00 a 22:00 todos los días, categorías de reserva (mesa estándar, cumpleaños, evento especial) y 2 proveedores.
- Menú con 5 productos por categoría base (precios con INC 8 % incluido):
  - Entradas: Patacones con hogao $14.000, Empanadas x3 $12.000, Ceviche de camarón $26.000, Arepitas con suero $11.000, Sopa de tortilla $15.000.
  - Platos principales: Bandeja paisa $38.000, Pescado frito con arroz de coco $42.000, Churrasco 300 g $46.000, Pollo a la plancha $32.400, Pasta marinera $39.000.
  - Bebidas: Limonada de coco $12.000, Jugo natural $8.000, Gaseosa 400 ml $6.000, Agua 600 ml $4.500, Café americano $5.000.
  - Menú infantil: Nuggets con papas $19.000, Mini hamburguesa $21.000, Pasta con queso $18.000, Salchipapa infantil $17.000, Jugo pequeño $5.000.
- «Combo Ejecutivo» (plato principal + bebida), una promoción vigente y 30 pedidos históricos pagados (con y sin propina) generados con *factories*, con sus comprobantes.

### A.9 Definición de terminado (por iteración)

- [ ] `php artisan migrate:fresh --seed` sin errores.
- [ ] `php artisan test` en verde, con al menos una prueba *Feature* por cada CA de la iteración (el nombre del test incluye el ID, por ejemplo `it('CA-07 bloquea la edición fuera de la ventana')`).
- [ ] `./vendor/bin/pint --test` y `./vendor/bin/phpstan analyse` (nivel 6) sin errores.
- [ ] Las *Policies* cubren cada acción y hay pruebas de acceso denegado por rol y por sucursal.
- [ ] Textos en `lang/es` y `lang/en`.
- [ ] Sección del README actualizada y entrada en `CHANGELOG.md` (sección «Sin publicar»).
- [ ] Commits con *Conventional Commits* e ID de la ERS.

### A.10 Prohibido

- Borrado físico de registros desde la aplicación, o rutas que editen o borren la bitácora, las compras o los comprobantes.
- Lógica de negocio dentro de controladores o componentes Vue.
- Compilar Blade o evaluar código a partir de contenido guardado en la base de datos.
- Llamadas a servicios externos para avatares, fuentes o analítica.
- Valores de configuración fijos en el código (ventana de edición, porcentajes de propina, duración de reservas): todo sale de `ConfiguracionService`.
- Inventar requerimientos fuera de la ERS. Si propones una mejora, márcala como «Propuesta» y no la implementes sin aprobación.

---

## PARTE B — Prompts por iteración

Envía cada uno por separado, después de que la iteración anterior esté revisada y fusionada.

### I-0 · Fundación

```
Iteración I-0 (Fundación). Crea el proyecto Laravel 13 con Sail (mysql, redis, mailpit), Inertia 2 + Vue 3 + Tailwind 4, Reverb, Fortify, spatie/laravel-permission (teams), spatie/laravel-activitylog, Pest, Pint y Larastan nivel 6. Configura APP_TIMEZONE=UTC, locale es con fallback en, Model::preventLazyLoading() fuera de producción y la estructura de carpetas de A.3. Crea los Enums de A.3, MonedaService con pruebas unitarias de redondeo, un layout base por rol, un Jenkinsfile y un workflow de GitHub Actions equivalente (pint, phpstan, test), y las plantillas de Issues (bug, mejora, solicitud de cambio). No implementes módulos de negocio todavía. Entrega lo indicado en A.9.
```

### I-1 · Núcleo: sucursales, usuarios, auditoría y papelera

```
Iteración I-1 (Núcleo). Implementa: sucursales y configuraciones con ConfiguracionService (global + por sucursal, con caché); trait BelongsToSucursal con global scope y middleware que fija la sucursal activa; roles y permisos con seeder; Fortify (login con limitación de 5 intentos por minuto, recuperación de contraseña, redirección por rol a /cliente, /mesero, /admin, /superadmin); unicidad de email y teléfono por separado incluyendo eliminados (E.164); REGISTRO VOLUNTARIO desde el home (RF-USR-14 a 17: MustVerifyEmail, middleware verified, aceptación de política, detección de cuenta creada por pedido y no activada para reenviar la invitación); invitación con URL firmada de 72 horas (CrearClienteAction, InvitarClienteAction); perfil editable con avatar local; CRUD de meseros (admin) y administradores (superadmin), con protección del último superadmin; trait Auditable con conexión audit; EliminarConValidacionAction; papelera genérica con restauración; vista de bitácora de solo lectura con filtros. Cubre RF-USR-01..17, RF-AUD-01..06, RF-CFG-01..03 y los CA-02, 03, 04, 19, 21, 30, 32 y 37.
```

### I-2 · Menú, promociones y sitio público

```
Iteración I-2 (Menú y sitio público). Implementa: categorías (con las 4 base creadas por el Observer de Sucursal), productos (con controla_inventario, existencia y disponible), combos con combo_items, CopiarMenuAction, promociones con PublicarPromocionesJob cada minuto y eventos PromocionPublicada/Retirada; el home público con selector de sucursal, carrusel de promociones primero, menú por categorías con filtro y buscador en vivo, productos agotados en gris u ocultos según el parámetro, carrito, y los botones Ingresar y Registrarse; actualización en tiempo real del menú por el canal público menu.{sucursal}. Agrega los seeders del menú de A.8. Cubre RF-PUB-01..05, 08, 09, 10, RF-MEN-01..11, RF-PRO-01..05 y los CA-20, 25 y 26.
```

### I-3 · Pedidos en tiempo real

```
Iteración I-3 (Pedidos). Implementa: CrearPedidoAction (cliente autenticado, visitante sin cuenta —crea la cuenta e invita, o pide iniciar sesión si los datos existen— y mesero con cliente opcional en sitio); descuento de inventario con transacción y lockForUpdate; consecutivo por sucursal; máquina de estados con historial; ventana de edición en PedidoPolicy; notas del cliente dentro de la ventana; SolicitarCancelacionAction y ResolverCancelacionAction con reposición de inventario y el parámetro de cancelación automática; RepetirPedidoAction; asignación y cambio de mesa con PedidoObserver; dashboards de cliente (promociones primero, pedidos en vivo, historial), mesero (propios destacados, domicilios y para llevar) y admin (pestañas, acordeón y todos los filtros de A.6); eventos PedidoCreado, PedidoActualizado y CancelacionSolicitada con alerta sonora y visual, más el fallback de polling a 15 s; notificaciones por correo encoladas. Confirma que pedir sin registrarse sigue funcionando (CA-33). Cubre RF-PUB-06, 11, RF-PED-01..22, RF-NOT-01..04 y los CA-01, 05..11, 29 y 33.
```

### I-4 · Reservas y mesas

```
Iteración I-4 (Reservas). Implementa: tipos de mesa (incluida la opción abierta con mínimo de personas), espacios, horarios de atención, categorías de reserva y parámetros de reserva por sucursal; DisponibilidadService::franjas con solapamiento, capacidad y espacios ocupados por pedidos activos; calendario público con las franjas disponibles; CrearReservaAction con aprobación automática o manual y creación de cuenta igual que en pedidos; aprobar, cancelar o reprogramar (admin); cancelar reservas propias (cliente); marcar como Cumplida o No asistió; mapa o listado de espacios en tiempo real (canal sucursal.{id}.mesas). Cubre RF-PUB-07, RF-RES-01..13 y los CA-12, 22, 23 y 24.
```

### I-5 · Operación: inventario, compras, caja, comprobantes, plantillas e informes

```
Iteración I-5 (Operación). Implementa en este orden:
1) Inventario: InventarioService (descontar, reponer, ajustar, inicial), movimientos polimórficos con saldo, inventario inicial único, ajustes con motivo, modos AUTONOMO/DEPENDIENTE (solo superadmin), actualización por lote, kárdex, tablero y alerta de existencia mínima.
2) Compras: proveedores, RegistrarCompraAction con impuesto por radio button ("Sin impuesto" por defecto) e indicador de si incluye impuesto, vínculo a producto con factor de conversión en modo dependiente, AnularCompraAction y DevolverCompraAction sin edición ni borrado, impuestos precargados (A.6).
3) Caja: cajas y sesiones con base, RegistrarPagoAction con pagos mixtos, billete recibido y devuelta; PROPINA OPCIONAL ("Sin propina" por defecto, sugerida 5 % o 10 % o valor abierto, parámetro propina.preseleccionar); el pedido queda pagado si los pagos cubren el total con o sin propina; CierreCajaAction con arqueo, propinas por mesero y PDF.
4) Comprobantes (módulo FAC): GenerarComprobanteJob (PDF inmutable con hash, membrete de la sucursal), listado con filtros, vista previa, impresión A4 y tirilla 80 mm, descarga, ReenviarComprobanteAction/Job al cliente o hasta 3 correos designados con mensaje, historial en envios_comprobante, "Mis comprobantes" en el dashboard del cliente, límite de 10 envíos por hora, exportación a Excel e interfaz FacturacionElectronicaGateway con implementación nula.
5) Plantillas de correo: editor visual, variables, imágenes y emojis, HTMLPurifier, PlantillaRenderer sin Blade, vista previa y envío de prueba para las 8 plantillas.
6) Informes: tablero en tiempo real e informes de A.6 con exportación PDF/Excel.
Cubre RF-INV, RF-COM, RF-CAJ, RF-FAC, RF-CFG-04..09, RF-INF y los CA-13..18, 27, 28, 31, 34..36 y 38..40.
```

### I-6 · Estabilización y línea base

```
Iteración I-6 (Estabilización). Revisa la cobertura de todos los casos de aceptación de la ERS (CA-01 a CA-40), con especial atención a los críticos (CA-01 a CA-20 y CA-32 a CA-36): genera una tabla CA → nombre del test → estado. Ejecuta pruebas de autorización cruzadas por rol y sucursal, revisa N+1 y la cobertura (mínimo 60 % en app/Actions y app/Services), completa el README (instalación, variables, usuarios de prueba, GRANT de la conexión audit), docs/base-de-datos.md, docs/despliegue.md y el CHANGELOG de la versión 1.0.0. Deja listos los comandos para etiquetar la línea base: git tag -a v1.0.0 -m "LB-01: línea base inicial SIGR". No etiquetes tú: solo prepara y reporta.
```

---

## PARTE C — Prompt de control de cambios (para usar después de la línea base)

```
Solicitud de cambio SC-XX: [describe el cambio]. Antes de escribir código, entrega el análisis de impacto: reglas de negocio, RF y RNF afectados (con sus IDs de la ERS), módulos impactados de forma directa e indirecta, migraciones, archivos, pruebas y casos de aceptación nuevos o modificados, esfuerzo estimado en horas y riesgos. Espera mi aprobación. Cuando la apruebe, implementa en la rama feature/SC-XX, actualiza la ERS (indica los cambios de texto), el CHANGELOG en «Sin publicar» y propone el número de versión según SemVer.
```
