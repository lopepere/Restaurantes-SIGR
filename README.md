# SIGR — Sistema Integral de Gestión de Restaurante

Plataforma web multi-sucursal de alta disponibilidad para restaurantes construida en **Laravel 13**, **Inertia.js 2**, **Vue 3** (Composition API, `<script setup>`), **Tailwind CSS 4** y **Laravel Reverb**.

La especificación formal y el contrato de construcción del sistema se encuentran en:
- `MDs/ERS_SIGR_v1.1.pdf`: Especificación de Requerimientos de Software (IEEE 830 / ISO 29148).
- `MDs/PROMPT_SIGR_Laravel.md`: Prompt Maestro y reglas de arquitectura por iteración.
- Solicitudes de cambio aprobadas: **SC-01** (Registro voluntario de clientes), **SC-02** (Propina opcional y voluntaria en caja), **SC-03** (Módulo de Comprobantes de Venta FAC inmutables con hash SHA-256).

---

## 🚀 Requisitos del Entorno

- **PHP**: 8.3 o superior (verificado en PHP 8.5.12) con extensiones `bcmath`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `sodium`, `zip`.
- **Node.js**: v20 o superior y NPM (verificado en Node v26.13 y NPM 12.2).
- **Base de Datos**: MySQL 8.0+ con codificación `utf8mb4_unicode_ci` (o SQLite en memoria para la suite de pruebas automatizadas).
- **Caché y Colas**: Redis 7.0+.
- **Tiempo Real (WebSockets)**: Laravel Reverb + Laravel Echo.

---

## 🛠️ Instalación y Puesta en Marcha

### Opción A: Entorno Contenerizado con Laravel Sail (Docker)
```bash
# 1. Iniciar servicios (MySQL 8, Redis, Mailpit, Laravel)
./vendor/bin/sail up -d

# 2. Ejecutar migraciones y semilleros
./vendor/bin/sail artisan migrate --seed

# 3. Compilar interfaz gráfica
npm run dev
```

### Opción B: Entorno Local Directo (XAMPP / PHP CLI)
```bash
# 1. Copiar configuración de entorno
cp .env.example .env

# 2. Instalar dependencias backend y frontend
composer install
npm install

# 3. Generar clave de aplicación y enlazar almacenamiento
php artisan key:generate
php artisan storage:link

# 4. Compilar assets frontend
npm run build # o npm run dev para desarrollo con HMR

# 5. Ejecutar migraciones con semilleros oficiales
php artisan migrate --seed
```

---

## 🔑 Cuentas de Acceso de Prueba (Seeders)

Todos los usuarios de prueba creados por los seeders oficiales (`RolesYPermisosSeeder`, `DatabaseSeeder`, `OperacionSeeder`, `ReservaSeeder`) tienen la contraseña predeterminada: `password`.

| Rol | Correo Electrónico | Contraseña | Alcance / Permisos |
| :--- | :--- | :--- | :--- |
| **Super Administrador** | `superadmin@sigr.test` | `password` | Acceso global a todas las sucursales, gestión de administradores, impuestos, configuración general y modo de inventario. |
| **Administrador de Sucursal** | `admin.centro@sigr.test` | `password` | Operación completa de la Sede Centro (menú, compras, inventario, pedidos, caja, arqueos, reservas e informes). |
| **Administrador de Sucursal** | `admin.norte@sigr.test` | `password` | Operación completa de la Sede Norte (aislamiento multi-tenant estricto respecto a Centro). |
| **Mesero** | `mesero.centro@sigr.test` | `password` | Toma ágil de comandas, asignación de mesas en vivo, consulta de pedidos en curso y solicitud de cancelación. |
| **Cliente Registrado** | `cliente@sigr.test` | `password` | Historial de pedidos en vivo, consulta y descarga de comprobantes FAC, reenvío a correos contables y reservas activas. |

---

## ⚙️ Variables de Entorno Clave (`.env`)

```ini
APP_NAME="SIGR"
APP_ENV=local
APP_KEY=base64:...
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# Conexión principal de base de datos
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigr
DB_USERNAME=root
DB_PASSWORD=

# Conexión dedicada para auditoría inmutable (RN-GEN-07, DE-12)
DB_AUDIT_HOST=127.0.0.1
DB_AUDIT_PORT=3306
DB_AUDIT_DATABASE=sigr
DB_AUDIT_USERNAME=sigr_audit
DB_AUDIT_PASSWORD=tu_clave_segura

# Colas y Caché
QUEUE_CONNECTION=redis
CACHE_STORE=redis
SESSION_DRIVER=redis

# Servidor WebSocket (Laravel Reverb)
BROADCAST_CONNECTION=reverb
REVERB_APP_ID=sigr_app
REVERB_APP_KEY=sigr_key
REVERB_APP_SECRET=sigr_secret
REVERB_HOST="localhost"
REVERB_PORT=8080
REVERB_SCHEME=http

VITE_REVERB_APP_KEY="${REVERB_APP_KEY}"
VITE_REVERB_HOST="${REVERB_HOST}"
VITE_REVERB_PORT="${REVERB_PORT}"
VITE_REVERB_SCHEME="${REVERB_SCHEME}"
```

---

## 🛡️ Conexión de Auditoría Inmutable (MySQL GRANT)

En cumplimiento de **RN-GEN-07** y **DE-12**, la bitácora `activity_log` es estrictamente inmutable: no existen rutas HTTP de actualización ni eliminación, y el usuario de base de datos `sigr_audit` carece de permisos `UPDATE` y `DELETE`:

```sql
-- Ejecutar como usuario root / administrador de MySQL en producción y staging
CREATE USER IF NOT EXISTS 'sigr_audit'@'%' IDENTIFIED BY 'tu_clave_segura';
GRANT SELECT, INSERT ON sigr.activity_log TO 'sigr_audit'@'%';
FLUSH PRIVILEGES;
```

---

## 🔄 Servicios en Segundo Plano y Daemons

Para el correcto funcionamiento de eventos en tiempo real, colas de correo y tareas programadas:

```bash
# 1. Servidor WebSocket de Laravel Reverb
php artisan reverb:start

# 2. Procesador de Colas de Notificaciones y Comprobantes
php artisan queue:work --tries=3 --timeout=90

# 3. Programador de Tareas (Cron cada minuto)
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

---

## 🧪 Pruebas Automatizadas y Aseguramiento de Calidad

La suite de pruebas automatizadas está construida con **Pest PHP** y garantiza la cobertura total de los casos de aceptación **CA-01 a CA-40**:

```bash
# Ejecución completa de la suite de pruebas Pest
php artisan test

# Formateador de código PSR-12 (Laravel Pint)
./vendor/bin/pint --test

# Análisis estático de código estricto a Nivel 6 (Larastan / PHPStan)
./vendor/bin/phpstan analyse --level=6

# Compilación de producción de assets frontend (Vite)
npm run build
```

---

## 👥 Matriz de Roles y Accesos

| Rol | Acceso Principal | Alcance |
| :--- | :--- | :--- |
| **Visitante** | `/` (Home, Menú, Reservas) | Consulta pública del menú y promociones en vivo, pedidos y reservas sin cuenta obligatoria, registro voluntario (SC-01). |
| **Cliente** | `/cliente` | «Mis pedidos» con seguimiento en tiempo real, «Mis comprobantes» FAC con descarga e impresión (SC-03), gestión de reservas y perfil. |
| **Mesero** | `/mesero` | Toma de comandas de salón, asignación y cambio de mesa, consulta de comandas activas de la sede y solicitud de cancelación. |
| **Admin de Sucursal** | `/admin` | Administración operativa completa de sus sucursales asignadas: menú, compras, inventario y kárdex, turnos de caja, arqueo, comprobantes e informes. |
| **Super Admin** | `/superadmin` | Gobernanza multi-sucursal, creación de sedes y administradores, catálogo de impuestos, plantillas de correo y alternancia de régimen de inventario. |
