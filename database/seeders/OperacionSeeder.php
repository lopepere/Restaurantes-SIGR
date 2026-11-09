<?php

namespace Database\Seeders;

use App\Actions\Caja\AperturaCajaAction;
use App\Actions\Compras\RegistrarCompraAction;
use App\Enums\EstadoSesionCaja;
use App\Models\Caja;
use App\Models\Compra;
use App\Models\ItemMenu;
use App\Models\PlantillaCorreo;
use App\Models\Proveedor;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Models\User;
use App\Services\InventarioService;
use Illuminate\Database\Seeder;

class OperacionSeeder extends Seeder
{
    /**
     * Seeds para el módulo de Operación: Proveedores, Cajas, Inventario Inicial, Compras y Plantillas (I-5).
     */
    public function run(): void
    {
        $sucursales = Sucursal::all();
        $admin = User::role('admin')->first() ?? User::first();

        // 1. Plantillas de correo base (globales y reutilizables)
        $plantillasData = [
            [
                'clave' => 'invitacion',
                'nombre' => 'Invitación a activar cuenta (72 horas)',
                'asunto' => 'Define tu contraseña en {{restaurante.nombre}}',
                'cuerpo_html' => '<div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px;"><h2 style="color: #ea580c;">¡Hola {{cliente.nombre}}!</h2><p>Gracias por tu preferencia en <strong>{{restaurante.nombre}}</strong>. Hemos creado tu cuenta de cliente para que puedas consultar tus pedidos y comprobantes.</p><p>Para definir tu contraseña y acceder, haz clic en el siguiente botón (válido por {{horas_validez}} horas):</p><p style="text-align: center; margin: 30px 0;"><a href="{{enlace_activacion}}" style="background-color: #ea580c; color: #ffffff; padding: 12px 24px; text-decoration: none; border-radius: 8px; font-weight: bold;">Definir Contraseña</a></p><p style="color: #6b7280; font-size: 12px;">Si no solicitaste esta cuenta, puedes ignorar este mensaje.</p></div>',
                'variables_disponibles' => ['cliente.nombre', 'restaurante.nombre', 'enlace_activacion', 'horas_validez'],
            ],
            [
                'clave' => 'verificacion_registro',
                'nombre' => 'Verificación de correo al registrarse',
                'asunto' => 'Verifica tu cuenta en {{restaurante.nombre}}',
                'cuerpo_html' => '<div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px;"><h2 style="color: #ea580c;">Verificación de cuenta</h2><p>Hola {{cliente.nombre}}, por favor confirma tu dirección de correo electrónico:</p><p style="text-align: center;"><a href="{{enlace_verificacion}}" style="background-color: #ea580c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;">Verificar Correo</a></p></div>',
                'variables_disponibles' => ['cliente.nombre', 'restaurante.nombre', 'enlace_verificacion'],
            ],
            [
                'clave' => 'bienvenida',
                'nombre' => 'Bienvenida a nuevo cliente',
                'asunto' => '¡Te damos la bienvenida a {{restaurante.nombre}}!',
                'cuerpo_html' => '<div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px;"><h2 style="color: #ea580c;">¡Bienvenido/a a la familia {{restaurante.nombre}}!</h2><p>Hola {{cliente.nombre}}, nos alegra tenerte con nosotros. Explora nuestra carta digital y descubre nuestras promociones exclusivas.</p><p style="text-align: center;"><a href="{{enlace_menu}}" style="background-color: #ea580c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;">Ver Menú Digital</a></p></div>',
                'variables_disponibles' => ['cliente.nombre', 'restaurante.nombre', 'enlace_menu'],
            ],
            [
                'clave' => 'pedido_recibido',
                'nombre' => 'Notificación de pedido recibido',
                'asunto' => 'Pedido {{pedido.numero}} recibido — {{restaurante.nombre}}',
                'cuerpo_html' => '<div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px;"><h2 style="color: #ea580c;">¡Hemos recibido tu pedido!</h2><p>Hola {{cliente.nombre}}, tu orden <strong>{{pedido.numero}}</strong> ({{pedido.tipo}}) ya se encuentra en cola de cocina.</p><p>Total: <strong>{{pedido.total}}</strong></p></div>',
                'variables_disponibles' => ['cliente.nombre', 'pedido.numero', 'pedido.tipo', 'pedido.total', 'restaurante.nombre'],
            ],
            [
                'clave' => 'cambio_estado',
                'nombre' => 'Actualización del estado del pedido',
                'asunto' => 'Actualización de tu pedido {{pedido.numero}}: {{pedido.estado}}',
                'cuerpo_html' => '<div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px;"><h2 style="color: #ea580c;">Estado de tu Pedido</h2><p>Hola {{cliente.nombre}}, te informamos que tu pedido <strong>{{pedido.numero}}</strong> ahora se encuentra en estado: <strong>{{pedido.estado}}</strong>.</p></div>',
                'variables_disponibles' => ['cliente.nombre', 'pedido.numero', 'pedido.estado'],
            ],
            [
                'clave' => 'comprobante',
                'nombre' => 'Comprobante de venta (FAC)',
                'asunto' => 'Comprobante de venta {{comprobante.numero}} — {{restaurante.nombre}}',
                'cuerpo_html' => '<div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px; border: 1px solid #e5e7eb; border-radius: 12px;"><h2 style="color: #ea580c;">Comprobante de Venta</h2><p>Estimado/a {{cliente.nombre}}, adjuntamos la información de tu comprobante <strong>{{comprobante.numero}}</strong> por valor de <strong>{{comprobante.total}}</strong>.</p><p>Código de verificación (CUV): <code>{{comprobante.cuv}}</code></p><p style="text-align: center;"><a href="{{enlace_comprobante}}" style="background-color: #ea580c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;">Verificar Comprobante</a></p></div>',
                'variables_disponibles' => ['cliente.nombre', 'comprobante.numero', 'comprobante.total', 'comprobante.cuv', 'enlace_comprobante', 'restaurante.nombre'],
            ],
            [
                'clave' => 'reenvio_comprobante',
                'nombre' => 'Reenvío de comprobante de venta',
                'asunto' => 'Copia de tu comprobante de venta {{comprobante.numero}}',
                'cuerpo_html' => '<div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px;"><h2 style="color: #ea580c;">Copia de Comprobante</h2><p>Hola {{cliente.nombre}}, te reenviamos la información de tu comprobante <strong>{{comprobante.numero}}</strong> por <strong>{{comprobante.total}}</strong>.</p><p style="text-align: center;"><a href="{{enlace_comprobante}}" style="background-color: #ea580c; color: white; padding: 12px 24px; text-decoration: none; border-radius: 8px;">Ver Comprobante</a></p></div>',
                'variables_disponibles' => ['cliente.nombre', 'comprobante.numero', 'comprobante.total', 'enlace_comprobante'],
            ],
            [
                'clave' => 'reserva',
                'nombre' => 'Confirmación de reserva de mesa',
                'asunto' => 'Reserva confirmada {{reserva.codigo}} en {{restaurante.nombre}}',
                'cuerpo_html' => '<div style="font-family: sans-serif; max-width: 600px; margin: auto; padding: 20px;"><h2 style="color: #ea580c;">¡Tu mesa está reservada!</h2><p>Hola {{cliente.nombre}}, tu reserva <strong>{{reserva.codigo}}</strong> para {{reserva.personas}} comensales el <strong>{{reserva.fecha}}</strong> a las <strong>{{reserva.hora}}</strong> en la <strong>{{reserva.mesa}}</strong> está confirmada.</p></div>',
                'variables_disponibles' => ['cliente.nombre', 'reserva.codigo', 'reserva.personas', 'reserva.fecha', 'reserva.hora', 'reserva.mesa', 'restaurante.nombre'],
            ],
        ];

        foreach ($plantillasData as $pl) {
            PlantillaCorreo::firstOrCreate(
                [
                    'sucursal_id' => null,
                    'clave' => $pl['clave'],
                ],
                [
                    'nombre' => $pl['nombre'],
                    'asunto' => $pl['asunto'],
                    'cuerpo_html' => $pl['cuerpo_html'],
                    'variables_disponibles' => $pl['variables_disponibles'],
                    'activa' => true,
                ]
            );
        }

        foreach ($sucursales as $sucursal) {
            // 2. Proveedores
            $proveedoresData = [
                [
                    'razon_social' => 'Distribuidora Carnes y Cortes Gourmet SAS',
                    'nit' => '900.123.456-7',
                    'telefono' => '3001234567',
                    'email' => 'ventas@carnesgourmet.co',
                    'direccion' => 'Zona Industrial Calle 13 # 68-20',
                    'contacto_nombre' => 'Carlos Mendoza',
                ],
                [
                    'razon_social' => 'Avícola y Lácteos del Campo SA',
                    'nit' => '901.234.567-8',
                    'telefono' => '3109876543',
                    'email' => 'pedidos@lacteosdelcampo.co',
                    'direccion' => 'Avenida Boyacá # 45-10',
                    'contacto_nombre' => 'Marta Rodríguez',
                ],
                [
                    'razon_social' => 'Bebidas y Licores Premium Ltda',
                    'nit' => '800.345.678-9',
                    'telefono' => '3205556677',
                    'email' => 'distribucion@bebidaspremium.co',
                    'direccion' => 'Carrera 30 # 19-40',
                    'contacto_nombre' => 'Javier Morales',
                ],
            ];

            $provCreados = [];
            foreach ($proveedoresData as $p) {
                $provCreados[] = Proveedor::firstOrCreate(
                    [
                        'sucursal_id' => $sucursal->id,
                        'razon_social' => $p['razon_social'],
                    ],
                    array_merge($p, ['activo' => true])
                );
            }

            // 3. Cajas físicas
            $cajaSalon = Caja::firstOrCreate(
                [
                    'sucursal_id' => $sucursal->id,
                    'nombre' => 'Caja Principal Salón',
                ],
                ['activa' => true]
            );

            Caja::firstOrCreate(
                [
                    'sucursal_id' => $sucursal->id,
                    'nombre' => 'Caja Bar y Domicilios',
                ],
                ['activa' => true]
            );

            // 4. Inventario inicial de productos en la sucursal
            $itemsMenu = ItemMenu::withoutGlobalScopes()
                ->where('sucursal_id', $sucursal->id)
                ->where('es_combo', false)
                ->where('controla_inventario', true)
                ->get();

            foreach ($itemsMenu as $item) {
                try {
                    InventarioService::registrarInventarioInicial($item, 40.0, (float) $item->precio * 0.45, $admin);
                } catch (\Exception) {
                    // Si ya tenía inventario inicial registrado, continuar
                }
            }

            // 5. Compra de demostración registrada
            if (! empty($provCreados) && $itemsMenu->isNotEmpty()) {
                $compraExistente = Compra::withoutGlobalScopes()
                    ->where('sucursal_id', $sucursal->id)
                    ->first();

                if (! $compraExistente) {
                    $item1 = $itemsMenu->first();
                    $registrarCompraAction = app(RegistrarCompraAction::class);
                    $registrarCompraAction->handle([
                        'sucursal_id' => $sucursal->id,
                        'proveedor_id' => $provCreados[0]->id,
                        'numero_factura' => 'FAC-PROV-1001',
                        'fecha_compra' => now()->toDateString(),
                        'incluye_impuesto' => false,
                        'impuesto_porcentaje' => 19.0,
                        'notas' => 'Abastecimiento de insumos y materia prima de la semana.',
                        'items' => [
                            [
                                'item_menu_id' => $item1->id,
                                'descripcion' => "Reposición semanal: {$item1->nombre}",
                                'cantidad' => 25.0,
                                'costo_unitario' => (float) $item1->precio * 0.4,
                                'factor_conversion' => 1.0,
                            ],
                        ],
                    ], $admin);
                }
            }

            // 6. Sesión de caja abierta en la Caja Principal con base de $100,000 COP
            $sesionAbierta = SesionCaja::where('caja_id', $cajaSalon->id)
                ->where('estado', EstadoSesionCaja::ABIERTA)
                ->first();

            if (! $sesionAbierta && $admin) {
                $aperturaAction = app(AperturaCajaAction::class);
                $aperturaAction->handle($cajaSalon, 100000.0, $admin);
            }
        }
    }
}
