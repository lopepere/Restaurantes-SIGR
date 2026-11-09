# Arquitectura de Base de Datos — SIGR

Este documento describe el modelo de datos, la estrategia de particionamiento lógico multi-sucursal, los mecanismos de inmutabilidad transaccional y la auditoría forense del **Sistema Integral de Gestión de Restaurante (SIGR)** conforme a la especificación **ERS v1.1** y el **Prompt Maestro**.

---

## 1. Principios de Diseño y Motor de Base de Datos

- **Motor**: MySQL 8.0 o superior (compatible con SQLite en memoria para la suite de pruebas automatizadas Pest).
- **Cotejamiento / Charset**: `utf8mb4` / `utf8mb4_unicode_ci`.
- **Integridad Referencial**: Claves foráneas con restricciones estrictas (`restrictOnDelete`) para prevenir borrados accidentales de entidades que posean relaciones activas (**RN-GEN-04**).
- **Control de Concurrencia**: Bloqueo pesimista de filas (`SELECT ... FOR UPDATE`) en transacciones críticas de inventario y pedidos para evitar sobreventas bajo alta concurrencia (**DE-14**, **CA-05**).

---

## 2. Estrategia Multi-Sucursal (Multi-Tenancy Lógico)

El sistema opera bajo un modelo de base de datos única compartida con aislamiento estricto por fila:
1. **Columna Clave**: Todas las tablas operativas contienen la columna `sucursal_id` como clave foránea a `sucursales.id`.
2. **Global Scope (`BelongsToSucursal`)**:
   - Para administradores y meseros: intercepta automáticamente todas las consultas Eloquent inyectando la cláusula `WHERE sucursal_id IN (...)` correspondiente a las sedes asignadas al usuario en la tabla pivote `sucursal_user`.
   - Para clientes: filtra automáticamente por `cliente_id = auth()->id()` en pedidos, reservas y comprobantes.
   - Para superadministradores: el scope se inhabilita para otorgar visibilidad consolidada global.
3. **Asignación Automática**: El evento `creating` del modelo asigna automáticamente el `sucursal_id` activo en la sesión del usuario cuando el atributo no es provisto explícitamente.

---

## 3. Catálogo de Tablas y Entidades por Módulo

### A. Núcleo, Seguridad y Multi-Tenant
- `sucursales`: Sedes físicas del restaurante (`nombre`, `slug`, `direccion`, `telefono`, `zona_horaria`, `moneda`, `idioma`, `activa`, `deleted_at`).
- `users`: Usuarios y actores del sistema (`nombre`, `email`, `telefono`, `direccion`, `password`, `origen`, `email_verified_at`, `invitacion_token`, `politica_aceptada_at`, `activo`, `deleted_at`).
- `sucursal_user`: Tabla pivote que asocia administradores y meseros a una o más sucursales.
- `configuraciones`: Claves y valores parametrizables en JSON por sucursal o globales (`sucursal_id` nullable, `clave`, `valor`).
- `activity_log`: Bitácora inmutable de auditoría forense con microsegundos, IP, navegador, valores anteriores y nuevos (**DE-12**).

### B. Menú, Recetas y Promociones
- `categorias`: Clasificación de productos del menú (`sucursal_id`, `nombre`, `slug`, `orden`, `activa`, `deleted_at`).
- `items_menu`: Catálogo de platos, combos y bebidas (`sucursal_id`, `categoria_id`, `nombre`, `precio`, `impuesto_tipo`, `impuesto_porcentaje`, `controla_inventario`, `existencia`, `existencia_minima`, `disponible`, `es_combo`, `activo`, `deleted_at`).
- `combo_items`: Desglose de componentes para combos compuestos (`combo_id`, `item_id`, `cantidad`).
- `promociones`: Descuentos y ofertas temporales (`sucursal_id`, `categoria_id`, `titulo`, `tipo_beneficio`, `valor`, `inicia_at`, `termina_at`, `prioridad`, `activa`, `deleted_at`).
- `promocion_item`: Relación muchos a muchos entre promociones e ítems con precio especial opcional.

### C. Salón, Mesas y Reservas
- `tipos_mesa`: Clasificación de capacidades (`sucursal_id`, `nombre`, `capacidad_minima`, `capacidad_maxima`, `es_abierta`, `activo`, `deleted_at`).
- `espacios`: Mesas, barras y salones físicos (`sucursal_id`, `tipo_mesa_id`, `nombre`, `capacidad`, `estado`: LIBRE, OCUPADO, RESERVADO, FUERA_SERVICIO, `deleted_at`).
- `horarios_atencion`: Franjas operativas por día de la semana 0..6 (`sucursal_id`, `dia_semana`, `hora_apertura`, `hora_cierre`, `activo`).
- `categorias_reserva`: Tipificación de ocasión («Mesa estándar», «Cumpleaños», «Evento especial»).
- `reservas`: Reservas de clientes e invitados con correlativo único (`RES-{SUCURSAL_ID}-{YYYYMMDD}-{0001}`), franja horaria, comensales, estado (`EstadoReserva`), mesa asignada y auditoría de cancelación.

### D. Pedidos y Comandas en Tiempo Real
- `pedidos`: Cabecera de pedidos con correlativo único (`PED-{SUCURSAL_ID}-{YYYYMMDD}-{0001}`), modalidad (`en_sitio`, `para_llevar`, `domicilio`), estados de la máquina ERS 8.3, comensal/mesero/espacio, subtotales, impuestos, propina voluntaria (**SC-02**), total y estado de pago.
- `pedido_items`: Snapshot inmutable histórico de precios, impuestos, subtotales, notas de preparación y desglose de combos.
- `pedido_estados`: Bitácora secuencial de transiciones de estado para auditoría y timeline del pedido.
- `solicitudes_cancelacion`: Solicitudes de anulación remitidas por clientes o meseros con motivo y resolución administrativa.

### E. Operación: Inventario, Compras y Caja
- `proveedores`: Catálogo de distribuidores y proveedores (`razon_social`, `nit`, `telefono`, `email`, `direccion`, `contacto_nombre`, `contacto_telefono`, `activo`, `deleted_at`).
- `compras`: Facturas de compra inmutables a proveedores (**sin borrado físico ni soft deletes**), fecha, subtotal, impuestos y estado (`registrada`, `anulada`, `devuelta`).
- `compra_items`: Detalle de compra con factor de conversión para modo dependiente de inventario e impuestos discriminados.
- `devoluciones_compra`: Devoluciones formales de mercancía con motivo y reversión de inventario.
- `movimientos_inventario`: Kárdex inmutable con balance de existencias y costos, con relación polimórfica al documento origen (`compra`, `venta`, `reposicion`, `ajuste`, `inicial`).
- `cajas`: Cajas registradoras físicas por sucursal (`nombre`, `activa`, `deleted_at`).
- `sesiones_caja`: Turnos de caja con base inicial en efectivo, arqueo ciego o asistido (`monto_apertura`, `monto_cierre_declarado`, `monto_cierre_calculado`, `diferencia`, desglose en JSON).
- `pagos`: Transacciones de cobro (efectivo, tarjeta, transferencia), valor recibido, devuelta y propina opcional.
- `comprobantes`: Comprobantes de venta FAC inmutables (**sin deleted_at**), correlativo (`FAC-{SUCURSAL_ID}-{YYYYMMDD}-{0001}`), CUV (UUID) y hash criptográfico SHA-256 de la tirilla térmica de 80 mm (**SC-03**).
- `envios_comprobante`: Historial inmutable de solo inserción con los envíos y reenvíos por correo auditados.
- `plantillas_correo`: 8 plantillas de correo transaccionales con variables dinámicas, sanitizadas con HTMLPurifier.

---

## 4. Estrategia de Inmutabilidad y Auditoría Forense

| Tabla | Estrategia de Borrado | Justificación Técnica / Legal |
| :--- | :--- | :--- |
| `compras` | **Inmutable (Sin borrado)** | Las compras aprobadas no pueden borrarse; se corrigen con `AnularCompraAction` o `DevolverCompraAction`. |
| `comprobantes` | **Inmutable (Sin borrado)** | Comprobantes de venta con hash SHA-256 inmutable (SC-03). |
| `envios_comprobante` | **Solo Inserción** | Registro de auditoría de reenvíos con límite de velocidad (CA-31). |
| `movimientos_inventario`| **Inmutable (Sin borrado)** | Kárdex multicriterio con balance correlativo de existencias. |
| `activity_log` | **Solo Inserción (Conexión audit)** | Usuario de base de datos restringido a `INSERT` y `SELECT` sin permisos de `UPDATE` ni `DELETE`. |
| Entidades maestras | **SoftDeletes (`deleted_at`)** | Recuperables desde la papelera de reciclaje administrativa (**CA-21**). |

