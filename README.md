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

### Opción A: Despliegue en VPS / Contenedores Docker (Producción / Staging)
La arquitectura de contenedores utiliza **Nginx 1.27 + PHP 8.4-FPM** (sin `php artisan serve`), compilación multi-etapa de activos frontend con **Node 22 + Vite**, **MySQL 8.0**, **Redis 7**, **Worker de Colas**, **Laravel Reverb (WebSockets)** y **Scheduler**, cargando todas las variables desde el archivo `.env`:

```bash
# 1. Preparar el archivo de variables de entorno
cp .env.example .env

# 2. Construir y levantar todos los contenedores en segundo plano
docker compose up -d --build

# 3. (Opcional) Cargar datos semilla de prueba en la primera instalación
docker compose exec app php artisan db:seed --force
```

### Opción B: Entorno Local Directo (Apache / Nginx / XAMPP)

#### B.1. Conexión MySQL en XAMPP
Permite visualizar y administrar todas las tablas desde **phpMyAdmin** (`http://localhost/phpmyadmin`).

**Datos de Conexión de Prueba en MySQL:**
- **Servidor / Host:** `127.0.0.1` (o `localhost`)
- **Puerto:** `3306`
- **Base de Datos:** `sigr`
- **Juego de caracteres:** `utf8mb4`
- **Cotejamiento:** `utf8mb4_unicode_ci`
- **Usuario:** `root`
- **Contraseña:** *(vacía)*
- **Panel phpMyAdmin:** [http://localhost/phpmyadmin/index.php?route=/database/structure&db=sigr](http://localhost/phpmyadmin/index.php?route=/database/structure&db=sigr)

```bash
# 1. Asegurar que Apache y MySQL estén iniciados en el panel de XAMPP

# 2. Configurar .env para MySQL
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigr
DB_USERNAME=root
DB_PASSWORD=

# 3. Ejecutar migraciones y semilleros en MySQL
php artisan migrate:fresh --seed

# 4. Compilar los activos de producción para servir desde Apache / Nginx
npm run build
```

---

#### B.2. Conexión SQLite (Autocontenida / Sin requerir XAMPP)
Ideal para desarrollo ligero y portable sin necesidad de iniciar servicios de MySQL. También es el motor predeterminado usado por la suite de pruebas automatizadas en memoria (`:memory:`).

**Datos de Conexión de Prueba en SQLite:**
- **Controlador:** `sqlite`
- **Archivo Físico:** `database/database.sqlite` (626 KB con todos los seeders cargados)
- **Ruta Absoluta:** `C:\xampp\htdocs\SIGR\database\database.sqlite`

Para alternar a SQLite, modifica temporalmente tu archivo `.env`:
```ini
DB_CONNECTION=sqlite
# DB_HOST=127.0.0.1
# DB_PORT=3306
# DB_DATABASE=sigr
# DB_USERNAME=root
# DB_PASSWORD=
```

Y ejecuta:
```bash
# Si el archivo no existe, crearlo
touch database/database.sqlite

# Migrar y sembrar en SQLite
php artisan migrate:fresh --seed
```

---

## 🔑 Cuentas de Acceso de Prueba (Seeders)

Todos los usuarios de prueba creados por los seeders oficiales (`RolesYPermisosSeeder`, `DatabaseSeeder`, `OperacionSeeder`, `ReservaSeeder`) se encuentran registrados tanto en **MySQL** como en **SQLite**. La contraseña predeterminada para todos ellos es: **`password`**.

| Rol | Correo Electrónico Principal | Correo Alternativo / Alias | Contraseña | Alcance / Permisos |
| :--- | :--- | :--- | :--- | :--- |
| **Super Administrador** | `superadmin@sigr.test` | — | `password` | Acceso global a todas las sedes, gestión de administradores, impuestos, configuración general e inventario. |
| **Administrador Sede Centro** | `admin.centro@sigr.test` | `admin@sigr.test` | `password` | Operación completa de la Sede Centro (menú, compras, kárdex, pedidos, turnos de caja, arqueos, reservas e informes). |
| **Administrador Sede Norte** | `admin.norte@sigr.test` | — | `password` | Operación completa de la Sede Norte (aislamiento multi-tenant estricto respecto a Centro, bloqueo 403 cruzado). |
| **Mesero Sede Centro** | `mesero.centro@sigr.test` | `mesero@sigr.test` | `password` | Toma ágil de comandas, cambio de mesa, mapa de salón en vivo, consulta de órdenes y solicitud de cancelación. |
| **Cajero Sede Centro** | `cajero@sigr.test` | — | `password` | Vista dedicada de caja (`/cajero`): apertura y cierre de turno, cobro de comandas pendientes, anulación de ítems mal facturados y búsqueda/reenvío de comprobantes. |
| **Cocina (KDS) Sede Centro** | `cocina@sigr.test` | — | `password` | Monitor KDS en tiempo real (`/cocina`) de pedidos entrantes, en preparación y listos para despacho de la sede asignada. |
| **Cliente Registrado (Pedidos)** | `cliente@sigr.test` | — | `password` | Historial de pedidos en vivo, descarga de comprobantes FAC en tirilla 80 mm, reenvío contable y reservas activas. |
| **Cliente Registrado (Voluntario)** | `registrado@sigr.test` | — | `password` | Cliente con auto-registro voluntario desde Home, verificación de email activa y consentimiento explícito de Ley 1581 (SC-01). |

---

## ⚙️ Variables de Entorno Clave (`.env`)

### Configuración Activa (MySQL de XAMPP):
```ini
APP_NAME="SIGR"
APP_ENV=local
APP_KEY=base64:y7fZ5gT+Fbw78yDBEjTvjRPNmALHLKyIDQWcBfemKAw=
APP_DEBUG=true
APP_TIMEZONE=UTC
APP_URL=http://localhost:8000

# Conexión principal de base de datos MySQL (Activa)
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=sigr
DB_USERNAME=root
DB_PASSWORD=

# Alternativa SQLite (Comentada):
# DB_CONNECTION=sqlite

# Conexión dedicada para auditoría inmutable (RN-GEN-07, DE-12)
DB_AUDIT_HOST=127.0.0.1
DB_AUDIT_PORT=3306
DB_AUDIT_DATABASE=sigr
DB_AUDIT_USERNAME=root
DB_AUDIT_PASSWORD=

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
