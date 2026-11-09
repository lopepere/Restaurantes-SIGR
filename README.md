# SIGR — Sistema Integral de Gestión de Restaurante

Plataforma web multi-sucursal para restaurantes construida en **Laravel 13**, **Inertia.js 2**, **Vue 3** (Composition API, `<script setup>`), **Tailwind CSS 4** y **Laravel Reverb**.

La especificación formal y el contrato de construcción del sistema se encuentran en:
- `MDs/ERS_SIGR_v1.1.pdf`: Especificación de Requerimientos de Software (IEEE 830 / ISO 29148).
- `MDs/PROMPT_SIGR_Laravel.md`: Prompt Maestro y reglas de arquitectura por iteración.

---

## 🚀 Requisitos del Entorno

- **PHP**: 8.3 o superior (probado y verificado en PHP 8.5.12) con extensiones `bcmath`, `curl`, `dom`, `fileinfo`, `gd`, `intl`, `mbstring`, `openssl`, `pdo_mysql`, `sodium`, `zip`.
- **Node.js**: v20 o superior y NPM.
- **Base de Datos**: MySQL 8.0+ con codificación `utf8mb4_unicode_ci`.
- **Caché y Colas**: Redis.
- **Tiempo Real**: Laravel Reverb + Laravel Echo.

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
npm run build # o npm run dev

# 5. Ejecutar migraciones
php artisan migrate --seed
```

---

## 🛡️ Auditoría Inmutable y Permisos de Base de Datos

El sistema implementa el requerimiento **DE-12** y **RN-GEN-07** mediante el trait `Auditable` y una conexión dedicada `audit`. En entornos de producción y staging, el usuario de auditoría solo tiene permisos de inserción y lectura sobre la bitácora:

```sql
-- Usuario de auditoría inmutable (solo INSERT y SELECT sobre activity_log)
CREATE USER IF NOT EXISTS 'sigr_audit'@'%' IDENTIFIED BY 'tu_clave_segura';
GRANT INSERT, SELECT ON sigr.activity_log TO 'sigr_audit'@'%';
FLUSH PRIVILEGES;
```

---

## 🧪 Pruebas Automatizadas y Calidad de Código

El proyecto exige el cumplimiento estricto de los siguientes estándares en cada iteración:

```bash
# Pruebas unitarias y de integración (Pest PHP)
php artisan test

# Verificación de estilo PSR-12 (Laravel Pint)
./vendor/bin/pint --test

# Análisis estático de código Nivel 6 (Larastan)
./vendor/bin/phpstan analyse --level=6
```

---

## 👥 Matriz de Roles y Accesos

| Rol | Acceso Principal | Alcance |
|---|---|---|
| **Visitante** | `/` (Home, Menú, Reservas) | Información pública, pedidos y reservas sin cuenta, registro voluntario. |
| **Cliente** | `/cliente` | Mis pedidos en vivo, mis comprobantes (impresión/reenvío), reservas, perfil. |
| **Mesero** | `/mesero` | Toma ágil de pedidos, cambio de mesa, consulta de pedidos de sucursal. |
| **Admin** | `/admin` | Gestión integral operativa de sus sucursales asignadas. |
| **Super Admin** | `/superadmin` | Control multi-sucursal, administradores, impuestos y configuración global. |
