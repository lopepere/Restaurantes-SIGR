<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolesYPermisosSeeder extends Seeder
{
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // Permisos del sistema
        $permisos = [
            // Sucursales y Configuración
            'sucursales.ver',
            'sucursales.crear',
            'sucursales.editar',
            'sucursales.eliminar',
            'configuracion.gestionar',
            // Usuarios y Personal
            'usuarios.ver',
            'usuarios.crear',
            'usuarios.editar',
            'usuarios.eliminar',
            'meseros.gestionar',
            'administradores.gestionar',
            // Menú y Promociones
            'menu.ver',
            'menu.gestionar',
            'promociones.gestionar',
            // Pedidos
            'pedidos.ver',
            'pedidos.crear',
            'pedidos.editar_propios',
            'pedidos.editar_todos',
            'pedidos.cambiar_estado',
            'pedidos.solicitar_cancelacion',
            'pedidos.resolver_cancelacion',
            'pedidos.asignar_mesa',
            // Reservas y Espacios
            'reservas.ver',
            'reservas.crear',
            'reservas.gestionar',
            'espacios.gestionar',
            // Caja y Comprobantes
            'caja.abrir',
            'caja.cobrar',
            'caja.cerrar',
            'comprobantes.ver',
            'comprobantes.reenviar',
            'comprobantes.imprimir',
            // Compras e Inventario
            'compras.gestionar',
            'inventario.gestionar',
            'inventario.ajustar',
            // Informes y Auditoría
            'informes.ver',
            'bitacora.ver',
            'papelera.ver',
            'papelera.restaurar',
        ];

        foreach ($permisos as $permiso) {
            Permission::firstOrCreate(['name' => $permiso, 'guard_name' => 'web']);
        }

        // Crear Roles Base
        $rolSuperAdmin = Role::firstOrCreate(['name' => 'super_admin', 'guard_name' => 'web']);
        $rolAdmin = Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
        $rolMesero = Role::firstOrCreate(['name' => 'mesero', 'guard_name' => 'web']);
        $rolCajero = Role::firstOrCreate(['name' => 'cajero', 'guard_name' => 'web']);
        $rolCocinero = Role::firstOrCreate(['name' => 'cocinero', 'guard_name' => 'web']);
        $rolCliente = Role::firstOrCreate(['name' => 'cliente', 'guard_name' => 'web']);

        // Asignación de permisos al rol Admin
        $rolAdmin->syncPermissions([
            'usuarios.ver',
            'meseros.gestionar',
            'menu.ver',
            'menu.gestionar',
            'promociones.gestionar',
            'pedidos.ver',
            'pedidos.crear',
            'pedidos.editar_todos',
            'pedidos.cambiar_estado',
            'pedidos.resolver_cancelacion',
            'pedidos.asignar_mesa',
            'reservas.ver',
            'reservas.crear',
            'reservas.gestionar',
            'espacios.gestionar',
            'caja.abrir',
            'caja.cobrar',
            'caja.cerrar',
            'comprobantes.ver',
            'comprobantes.reenviar',
            'comprobantes.imprimir',
            'compras.gestionar',
            'inventario.gestionar',
            'inventario.ajustar',
            'informes.ver',
            'bitacora.ver',
            'papelera.ver',
            'papelera.restaurar',
        ]);

        // Asignación de permisos al rol Mesero
        $rolMesero->syncPermissions([
            'menu.ver',
            'pedidos.ver',
            'pedidos.crear',
            'pedidos.editar_propios',
            'pedidos.cambiar_estado',
            'pedidos.solicitar_cancelacion',
            'pedidos.asignar_mesa',
            'reservas.ver',
            'espacios.gestionar',
        ]);

        // Asignación de permisos al rol Cajero
        $rolCajero->syncPermissions([
            'pedidos.ver',
            'caja.abrir',
            'caja.cobrar',
            'caja.cerrar',
            'comprobantes.ver',
            'comprobantes.reenviar',
            'comprobantes.imprimir',
        ]);

        // Asignación de permisos al rol Cocinero
        $rolCocinero->syncPermissions([
            'pedidos.ver',
            'pedidos.cambiar_estado',
        ]);

        // Asignación de permisos al rol Cliente
        $rolCliente->syncPermissions([
            'pedidos.crear',
            'pedidos.solicitar_cancelacion',
            'reservas.crear',
            'comprobantes.ver',
            'comprobantes.imprimir',
            'comprobantes.reenviar',
        ]);
    }
}
