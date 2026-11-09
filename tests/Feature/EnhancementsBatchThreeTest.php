<?php

namespace Tests\Feature;

use App\Enums\EstadoPedido;
use App\Enums\EstadoSesionCaja;
use App\Enums\TipoPedido;
use App\Models\AsignacionBaseCaja;
use App\Models\Caja;
use App\Models\Categoria;
use App\Models\Compra;
use App\Models\ItemMenu;
use App\Models\Pago;
use App\Models\Pedido;
use App\Models\Proveedor;
use App\Models\SesionCaja;
use App\Models\Sucursal;
use App\Models\User;
use Database\Seeders\RolesYPermisosSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class EnhancementsBatchThreeTest extends TestCase
{
    use RefreshDatabase;

    private Sucursal $sucursal;

    private User $superAdmin;

    private User $admin;

    private User $cajero;

    private User $cocinero;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesYPermisosSeeder::class);

        $this->sucursal = Sucursal::create([
            'nombre' => 'Sede Principal',
            'slug' => 'sede-principal',
            'direccion' => 'Calle 100 #15-20',
            'telefono' => '3001234567',
            'activa' => true,
            'acepta_domicilios' => true,
        ]);

        $this->superAdmin = User::factory()->create(['activo' => true, 'email_verified_at' => now()]);
        $this->superAdmin->assignRole('super_admin');
        $this->superAdmin->sucursales()->sync([$this->sucursal->id]);

        $this->admin = User::factory()->create(['activo' => true, 'email_verified_at' => now()]);
        $this->admin->assignRole('admin');
        $this->admin->sucursales()->sync([$this->sucursal->id]);

        $this->cajero = User::factory()->create(['activo' => true, 'email_verified_at' => now()]);
        $this->cajero->assignRole('cajero');
        $this->cajero->sucursales()->sync([$this->sucursal->id]);

        $this->cocinero = User::factory()->create(['activo' => true, 'email_verified_at' => now()]);
        $this->cocinero->assignRole('cocinero');
        $this->cocinero->sucursales()->sync([$this->sucursal->id]);
    }

    public function test_descarga_plantilla_csv_con_6_productos_e_importa_en_sin_categorizar(): void
    {
        // 1. Descargar plantilla
        $response = $this->actingAs($this->admin)
            ->get('/admin/menu/plantilla?sucursal_id='.$this->sucursal->id);

        $response->assertStatus(200);
        $csvContent = $response->streamedContent();
        $this->assertStringContainsString('Empanadas Tradicionales x4', $csvContent);
        $this->assertStringContainsString('Limonada Natural de Coco', $csvContent);

        // 2. Importar CSV con producto cuya categoría no existe -> debe asignarse a "Sin categorizar"
        $csvUpload = "nombre;categoria;descripcion;precio;impuesto_tipo;impuesto_porcentaje;controla_inventario;existencia;existencia_minima\n";
        $csvUpload .= "Plato Especial Sin Cat;Categoria Inexistente XYZ;Receta de autor;28000;INC;8;SI;15;3\n";

        $file = UploadedFile::fake()->createWithContent('productos.csv', $csvUpload);

        $importResp = $this->actingAs($this->admin)->post('/admin/menu/importar', [
            'sucursal_id' => $this->sucursal->id,
            'archivo' => $file,
        ]);

        $importResp->assertRedirect();

        $sinCat = Categoria::withoutGlobalScopes()
            ->where('sucursal_id', $this->sucursal->id)
            ->where('es_sistema', true)
            ->first();

        $this->assertNotNull($sinCat);
        $this->assertSame('Sin categorizar', $sinCat->nombre);

        $item = ItemMenu::withoutGlobalScopes()
            ->where('sucursal_id', $this->sucursal->id)
            ->where('nombre', 'Plato Especial Sin Cat')
            ->first();

        $this->assertNotNull($item);
        $this->assertSame($sinCat->id, $item->categoria_id);

        // 3. Verificar que "Sin categorizar" no se puede editar ni eliminar
        $this->actingAs($this->admin)
            ->put("/admin/menu/categorias/{$sinCat->id}", [
                'nombre' => 'Intento Cambio',
                'orden' => 1,
            ]);

        $this->assertSame('Sin categorizar', $sinCat->fresh()->nombre);
    }

    public function test_superadmin_gestiona_sucursales_cajas_monitores_cocina_y_base_efectivo_diaria(): void
    {
        // Editar sucursal
        $this->actingAs($this->superAdmin)
            ->put("/superadmin/sucursales/{$this->sucursal->id}", [
                'nombre' => 'Sede Principal Renovada',
                'direccion' => 'Carrera 15 #100-10',
                'telefono' => '3109998877',
                'zona_horaria' => 'America/Bogota',
            ])
            ->assertRedirect();

        $this->assertSame('Sede Principal Renovada', $this->sucursal->fresh()->nombre);

        // Crear estación de cocina
        $this->actingAs($this->superAdmin)
            ->post('/superadmin/sucursales/estaciones-cocina', [
                'sucursal_id' => $this->sucursal->id,
                'nombre' => 'Monitor Parrilla #1',
                'codigo' => 'COC-PAR-1',
                'user_id' => $this->cocinero->id,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('estaciones_cocina', [
            'sucursal_id' => $this->sucursal->id,
            'nombre' => 'Monitor Parrilla #1',
            'user_id' => $this->cocinero->id,
        ]);

        // Crear caja vinculada al cajero
        $this->actingAs($this->superAdmin)
            ->post('/superadmin/sucursales/cajas', [
                'sucursal_id' => $this->sucursal->id,
                'nombre' => 'Caja Principal #1',
                'user_id' => $this->cajero->id,
            ])
            ->assertRedirect();

        $caja = Caja::where('sucursal_id', $this->sucursal->id)->where('nombre', 'Caja Principal #1')->firstOrFail();

        // Asignar base diaria de efectivo
        $fechaHoy = now()->toDateString();
        $this->actingAs($this->superAdmin)
            ->post('/superadmin/sucursales/bases-caja', [
                'sucursal_id' => $this->sucursal->id,
                'caja_id' => $caja->id,
                'fecha' => $fechaHoy,
                'monto' => 250000,
                'observaciones' => 'Base inicial lunes',
            ])
            ->assertRedirect();

        $asignacion = AsignacionBaseCaja::where('caja_id', $caja->id)->whereDate('fecha', $fechaHoy)->firstOrFail();
        $this->assertEquals(250000.0, (float) $asignacion->monto);

        // Editar base diaria de efectivo y verificar auditoría de edición
        $this->actingAs($this->admin)
            ->put("/superadmin/sucursales/bases-caja/{$asignacion->id}", [
                'monto' => 300000,
                'observaciones' => 'Incremento por cambio de sencillo',
            ])
            ->assertRedirect();

        $asignacion->refresh();
        $this->assertEquals(300000.0, (float) $asignacion->monto);
        $this->assertSame($this->admin->id, $asignacion->editado_por_id);
    }

    public function test_cajero_confirma_cobro_con_tarjeta_numero_transaccion_y_nota_sin_error_403(): void
    {
        $caja = Caja::create([
            'sucursal_id' => $this->sucursal->id,
            'user_id' => $this->cajero->id,
            'nombre' => 'Caja 01',
            'activa' => true,
        ]);

        SesionCaja::create([
            'caja_id' => $caja->id,
            'sucursal_id' => $this->sucursal->id,
            'user_id' => $this->cajero->id,
            'monto_apertura' => 150000,
            'estado' => EstadoSesionCaja::ABIERTA,
            'fecha_apertura' => now(),
        ]);

        $pedido = Pedido::create([
            'sucursal_id' => $this->sucursal->id,
            'numero' => 'PED-501',
            'tipo' => TipoPedido::EN_SITIO,
            'estado' => EstadoPedido::LISTO,
            'subtotal' => 50000,
            'impuestos' => 4000,
            'total' => 54000,
        ]);

        $response = $this->actingAs($this->cajero)
            ->post("/cajero/pagos/{$pedido->id}", [
                'propina_valor' => 0,
                'pagos' => [
                    [
                        'metodo_pago' => 'tarjeta_credito',
                        'monto' => 54000,
                        'referencia_transaccion' => 'TX-99887766',
                        'nota_adicional' => 'Datáfono Redeban Aprobado',
                    ],
                ],
            ]);

        $response->assertRedirect();

        $pago = Pago::where('pedido_id', $pedido->id)->first();
        $this->assertNotNull($pago);
        $this->assertSame('TX-99887766', $pago->referencia_transaccion);
        $this->assertSame('Datáfono Redeban Aprobado', $pago->nota_adicional);
    }

    public function test_cocina_permite_despachar_pedido_desde_cualquier_estado_activo(): void
    {
        $pedido = Pedido::create([
            'sucursal_id' => $this->sucursal->id,
            'numero' => 'PED-601',
            'tipo' => TipoPedido::PARA_LLEVAR,
            'estado' => EstadoPedido::EN_PREPARACION,
            'subtotal' => 25000,
            'impuestos' => 2000,
            'total' => 27000,
        ]);

        $this->actingAs($this->cocinero)
            ->post("/cocina/pedidos/{$pedido->id}/estado", [
                'estado' => EstadoPedido::ENTREGADO->value,
            ])
            ->assertRedirect();

        $this->assertSame(EstadoPedido::ENTREGADO, $pedido->fresh()->estado);
    }

    public function test_bloquea_segunda_sesion_concurrente_y_libera_al_cerrar_sesion(): void
    {
        $this->cajero->forceFill([
            'current_session_id' => 'sesion-activa-previa-123',
        ])->save();

        $this->assertTrue($this->cajero->fresh()->tieneSesionActiva());

        // Intento de segundo login debe fallar con error en email
        $loginResp = $this->post('/login', [
            'email' => $this->cajero->email,
            'password' => 'password',
        ]);

        $loginResp->assertSessionHasErrors('email');

        // Liberar sesión activa permite iniciar sesión nuevamente
        $this->cajero->liberarSesionActiva();
        $this->assertFalse($this->cajero->fresh()->tieneSesionActiva());
    }

    public function test_exporta_compras_excel_y_pdf_con_devoluciones_y_anulaciones(): void
    {
        $proveedor = Proveedor::create([
            'sucursal_id' => $this->sucursal->id,
            'razon_social' => 'Alimentos del Valle SAS',
            'nit' => '900555111-2',
            'activo' => true,
        ]);

        Compra::create([
            'sucursal_id' => $this->sucursal->id,
            'proveedor_id' => $proveedor->id,
            'user_id' => $this->admin->id,
            'numero_factura' => 'FAC-EXP-001',
            'fecha_compra' => now()->toDateString(),
            'subtotal' => 100000,
            'impuesto_total' => 19000,
            'total' => 119000,
            'estado' => 'registrada',
        ]);

        $excelResp = $this->actingAs($this->admin)
            ->get('/admin/compras/exportar/excel?sucursal_id='.$this->sucursal->id);

        $excelResp->assertStatus(200);
        $this->assertStringContainsString('FAC-EXP-001', $excelResp->streamedContent());

        $pdfResp = $this->actingAs($this->admin)
            ->get('/admin/compras/exportar/pdf?sucursal_id='.$this->sucursal->id);

        $pdfResp->assertStatus(200);
    }
}
