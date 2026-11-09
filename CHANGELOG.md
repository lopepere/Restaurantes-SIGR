# Registro de Cambios (CHANGELOG) — SIGR

Todos los cambios notables de este proyecto serán documentados en este archivo siguiendo el formato de [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y las directrices de [SemVer](https://semver.org/lang/es/).

---

## [Sin publicar]

### Añadido (Iteración I-1 · Núcleo: sucursales, usuarios, auditoría y papelera) [LB-01]
- **Migraciones de Base de Datos**:
  - `users`: soporte de `nombre`, `email`, `telefono` (E.164), `direccion`, `avatar` (SVG local), `idioma`, `activo`, `origen` (`OrigenUsuario`), campos de invitación firmada (`invitacion_token`, `invitacion_enviada_at`, `invitacion_aceptada_at`), `politica_aceptada_at` (Ley 1581) y `deleted_at`.
  - `sucursales`: gestión multisede con `slug`, `zona_horaria`, `moneda`, `idioma`, `activa` y `deleted_at`.
  - `sucursal_user`: tabla pivote con restricción de clave foránea `restrictOnDelete`.
  - `configuraciones`: parámetros clave-valor JSON con `sucursal_id` nulable para resolución jerárquica.
  - `permission_tables`: adaptación Spatie Permission con soporte de sucursal (`team_foreign_key`) nullable para roles globales (`super_admin`).
  - `activity_log`: auditoría inmutable con `ip`, `user_agent`, `sucursal_id`, cambios viejos/nuevos en `attribute_changes` y precisión de microsegundos.
- **Modelos y Traits Arquitectónicos**:
  - `App\Models\User`, `App\Models\Sucursal`, `App\Models\Configuracion`.
  - `App\Traits\BelongsToSucursal`: Scope global multi-tenant para aislamiento automático por sede asignada, respetando acceso global a `super_admin`.
  - `App\Traits\Auditable`: Integración contextual con Spatie Activitylog v5 con registro de IP, User-Agent, sede y microsegundos.
- **Políticas de Autorización (RBAC y Multisede)**:
  - `App\Policies\UserPolicy`: control granular de edición/eliminación de usuarios restringido a la sede del administrador.
  - `App\Policies\SucursalPolicy`: protección contra accesos cruzados entre sucursales ajenas (CA-30).
- **Servicios y Acciones de Negocio**:
  - `App\Services\ConfiguracionService`: resolución jerárquica sede-primero con respaldo global y caché invalidada por evento.
  - `App\Services\PapeleraService`: listado paginado en servidor y restauración segura de modelos borrados lógicamente (CA-21).
  - `App\Services\TelefonoService`: normalización telefónica internacional E.164 con `propaganistas/laravel-phone`.
  - `App\Services\AvatarService`: generación de avatares SVG basados en iniciales con `laravolt/avatar`.
  - `App\Actions\Comun\EliminarConValidacionAction`: verificación previa de dependencias activas antes de permitir borrado lógico (RF-AUD-05, RN-GEN-04).
  - `App\Actions\Usuarios\InvitarClienteAction`: generación de enlace firmado de 72 horas para definir contraseña (`invitacion.aceptar`) y notificación en cola.
  - `App\Actions\Usuarios\CrearClienteAction`: aprovisionamiento implícito de cuenta al pedir/reservar por primera vez (RF-USR-01, CA-01).
  - `App\Actions\Usuarios\RegistrarClienteAction`: registro voluntario desde home con consentimiento de Ley 1581 (SC-01, CA-32) y reenvío de invitación si existía cuenta previa no activada (CA-37).
  - `App\Actions\Usuarios\CrearPersonalAction`: creación de meseros y administradores con asignación de roles por sede.
  - `App\Actions\Usuarios\EliminarUsuarioAction`: protección del último superadministrador activo contra desactivación o borrado (RF-USR-11).
- **Autenticación Fortify y Flujo de Sesión**:
  - `App\Http\Responses\LoginResponse`: redirección por rol (`/superadmin`, `/admin`, `/mesero`, `/cliente`) y fijado de la sede activa en la sesión (CA-04).
  - Limitador de tasa estricto en `/login`: 5 intentos por minuto por IP + usuario con HTTP 429 Too Many Requests (RF-USR-06, RNF-SEG-03).
  - Protección de acceso a clientes mediante verificación obligatoria de correo electrónico (`MustVerifyEmail` / `verified` middleware).
- **Controladores y Vistas Inertia.js (Vue 3 + Tailwind CSS 4)**:
  - Rutas de autenticación: `Login.vue`, `Register.vue`, `ForgotPassword.vue`, `ResetPassword.vue`, `VerifyEmail.vue`, `AceptarInvitacion.vue`.
  - Paneles por rol: `Cliente/Dashboard.vue`, `Cliente/Perfil.vue`, `Mesero/Dashboard.vue`.
  - Administración de sede: `Admin/Dashboard.vue`, `Admin/Meseros/Index.vue`, `Admin/Bitacora/Index.vue` (solo lectura inmutable, 403 en update/delete), `Admin/Papelera/Index.vue` (restauración server-side).
  - Superadministración: `SuperAdmin/Dashboard.vue`, `SuperAdmin/Administradores/Index.vue`, `SuperAdmin/Sucursales/Index.vue`.
- **Seeders del Núcleo**:
  - `RolesYPermisosSeeder`: roles base (`super_admin`, `admin`, `mesero`, `cliente`) y catálogo completo de permisos del sistema.
  - `SucursalSeeder`: «Sede Centro».
  - `ConfiguracionSeeder`: parámetros base del sistema.
  - `UserSeeder`: 5 cuentas de prueba activas.
- **Pruebas Automatizadas de Aceptación (Pest Feature Tests)**:
  - `tests/Feature/Auth/LoginTest.php`: redirecciones por rol (CA-04) y limitador de tasa de 5 intentos por minuto (RF-USR-06).
  - `tests/Feature/Auth/RegistroTest.php`: registro voluntario con verificación en cola y bloqueo hasta verificar (CA-32), validación de duplicados por correo (CA-02) y por teléfono E.164 (CA-03), reenvío de invitación en cuentas previas no activadas (CA-37) y aceptación obligatoria de política de datos.
  - `tests/Feature/Admin/PersonalTest.php`: creación y actualización de meseros (RF-USR-09), creación de administradores multi-sucursal (RF-USR-10) y bloqueo de eliminación del último superadministrador (RF-USR-11).
  - `tests/Feature/Admin/AuditoriaYPapeleraTest.php`: bitácora de auditoría inmutable con IP, usuario y cambios (CA-19), restauración desde papelera (CA-21) y rechazo de borrado con dependencias activas (RF-AUD-05).
  - `tests/Feature/Multisitio/MultiSucursalTest.php`: aislamiento estricto de registros y accesos entre sedes (CA-30).
  - Suite completa: 33 pruebas, 147 aserciones, 100% pasando en tiempo subsegundo.

### Añadido (Iteración I-0 · Fundación) [LB-01]
- Inicialización del proyecto Laravel 13 con arquitectura de monolito modular (`app/Actions`, `app/Services`, `app/Enums`, `app/Policies`).
- Configuración de entorno dual: **Laravel Sail** (`docker-compose.yml` con MySQL 8, Redis y Mailpit) y soporte nativo local (XAMPP PHP 8.5 / MySQL).
- Integración de **Inertia.js 2**, **Vue 3** (Composition API, `<script setup>`), **Tailwind CSS 4** y **Vite**.
- Instalación y registro de paquetes del núcleo:
  - `laravel/fortify` (Autenticación sin interfaz)
  - `laravel/reverb` (Servidor WebSocket oficial para tiempo real)
  - `spatie/laravel-permission` (Control de roles y permisos con teams = sucursal)
  - `spatie/laravel-activitylog` (Bitácora inmutable con conexión dedicada `audit`)
  - `barryvdh/laravel-dompdf` y `maatwebsite/excel` (Generación de comprobantes y exportaciones)
  - `mews/purifier` (Saneamiento HTML de plantillas)
  - `laravolt/avatar` (Generación local de avatares SVG sin servicios externos)
  - `propaganistas/laravel-phone` (Normalización y validación E.164)
  - `spatie/laravel-backup` (Copias de seguridad)
  - `pestphp/pest`, `laravel/pint`, `larastan/larastan` (Herramientas de calidad y pruebas)
- Configuración de `APP_TIMEZONE=UTC`, `locale` a `es` con fallback a `en`, y `Model::preventLazyLoading(!app()->isProduction())`.
- Creación de los Enums de dominio respaldados por cadenas: `TipoPedido`, `EstadoPedido` (con máquina de estados y transiciones permitidas según ERS 8.3), `EstadoReserva`, `MetodoPago`, `ModoInventario`, `TipoMovimiento`, `TipoImpuesto`, `PropinaTipo` (SC-02), `OrigenUsuario`, `EstadoEspacio`, `TipoBeneficioPromocion`, `TipoEnvioComprobante` (SC-03) y `EstadoEnvioComprobante` (SC-03).
- Implementación de `MonedaService` con operaciones `decimal(14,2)`, redondeo `PHP_ROUND_HALF_UP`, desglose de impuestos incluidos y cálculo de propinas sobre base imponible.
- Pruebas unitarias completas en Pest para `MonedaService` y `EstadoPedido` (14 pruebas, 48 aserciones, 100% pasando).
- Scaffolding de 5 Layouts responsivos por rol: `PublicLayout` (con campanita de servicio de restaurante para el botón flotante del pedido), `ClienteLayout`, `MeseroLayout`, `AdminLayout` y `SuperAdminLayout`.
- Diccionarios de idioma en `lang/es` y `lang/en` (`general`, `pedidos`, `comprobantes`).
- Pipeline de Integración Continua: `Jenkinsfile` y GitHub Actions workflow `.github/workflows/ci.yml`.
- Plantillas de incidencias en `.github/ISSUE_TEMPLATE/` (`bug_report.md`, `feature_request.md`, `change_request.md`).
- Configuración de Pint (`pint.json`) y PHPStan nivel 6 (`phpstan.neon`).

