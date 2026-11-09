# Registro de Cambios (CHANGELOG) — SIGR

Todos los cambios notables de este proyecto serán documentados en este archivo siguiendo el formato de [Keep a Changelog](https://keepachangelog.com/es-ES/1.1.0/) y las directrices de [SemVer](https://semver.org/lang/es/).

---

## [Sin publicar]

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
