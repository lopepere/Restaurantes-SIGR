<?php

namespace Database\Seeders;

use App\Enums\EstadoReserva;
use App\Models\CategoriaReserva;
use App\Models\Espacio;
use App\Models\HorarioAtencion;
use App\Models\Reserva;
use App\Models\Sucursal;
use App\Models\TipoMesa;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class ReservaSeeder extends Seeder
{
    /**
     * Run the database seeds for Iteration I-4 (Tipos de Mesa, Horarios, Categorías, y Reservas).
     */
    public function run(): void
    {
        $sucursales = Sucursal::all();

        foreach ($sucursales as $sucursal) {
            // 1. Tipos de Mesa (con opción abierta RF-RES-01, Prompt I-4)
            $tiposMesaData = [
                ['nombre' => 'Barra', 'capacidad_minima' => 1, 'capacidad_maxima' => 1, 'es_abierta' => false],
                ['nombre' => 'Mesa 2 Personas', 'capacidad_minima' => 1, 'capacidad_maxima' => 2, 'es_abierta' => false],
                ['nombre' => 'Mesa 4 Personas', 'capacidad_minima' => 2, 'capacidad_maxima' => 4, 'es_abierta' => false],
                ['nombre' => 'Mesa 6 Personas', 'capacidad_minima' => 4, 'capacidad_maxima' => 6, 'es_abierta' => false],
                ['nombre' => 'Mesa 8 Personas', 'capacidad_minima' => 6, 'capacidad_maxima' => 8, 'es_abierta' => false],
                ['nombre' => 'Opción Abierta / Salón', 'capacidad_minima' => 10, 'capacidad_maxima' => 30, 'es_abierta' => true],
            ];

            $tiposCreados = [];
            foreach ($tiposMesaData as $tipo) {
                $tiposCreados[$tipo['nombre']] = TipoMesa::firstOrCreate(
                    [
                        'sucursal_id' => $sucursal->id,
                        'nombre' => $tipo['nombre'],
                    ],
                    [
                        'capacidad_minima' => $tipo['capacidad_minima'],
                        'capacidad_maxima' => $tipo['capacidad_maxima'],
                        'es_abierta' => $tipo['es_abierta'],
                        'activo' => true,
                    ]
                );
            }

            // 2. Asociar espacios existentes a tipos de mesa
            $espacios = Espacio::where('sucursal_id', $sucursal->id)->get();
            foreach ($espacios as $espacio) {
                $tipoNombre = match (true) {
                    str_contains($espacio->nombre, 'Barra') => 'Barra',
                    $espacio->capacidad <= 2 => 'Mesa 2 Personas',
                    $espacio->capacidad <= 4 => 'Mesa 4 Personas',
                    $espacio->capacidad <= 6 => 'Mesa 6 Personas',
                    $espacio->capacidad <= 8 => 'Mesa 8 Personas',
                    default => 'Opción Abierta / Salón',
                };

                if (isset($tiposCreados[$tipoNombre])) {
                    $espacio->update(['tipo_mesa_id' => $tiposCreados[$tipoNombre]->id]);
                }
            }

            // 3. Horarios de Atención (Lunes a Domingo de 12:00 a 22:00)
            for ($dia = 0; $dia <= 6; $dia++) {
                HorarioAtencion::firstOrCreate(
                    [
                        'sucursal_id' => $sucursal->id,
                        'dia_semana' => $dia,
                    ],
                    [
                        'hora_apertura' => '12:00:00',
                        'hora_cierre' => '22:00:00',
                        'activo' => true,
                    ]
                );
            }

            // 4. Categorías de Reserva (Prompt A.8)
            $categoriasData = [
                ['nombre' => 'Mesa estándar', 'descripcion' => 'Reserva estándar para almuerzo o cena'],
                ['nombre' => 'Cumpleaños', 'descripcion' => 'Celebración de cumpleaños con detalle de la casa'],
                ['nombre' => 'Evento especial', 'descripcion' => 'Reuniones de negocios, aniversarios o eventos corporativos'],
            ];

            $catCreadas = [];
            foreach ($categoriasData as $cat) {
                $catCreadas[$cat['nombre']] = CategoriaReserva::firstOrCreate(
                    [
                        'sucursal_id' => $sucursal->id,
                        'nombre' => $cat['nombre'],
                    ],
                    [
                        'descripcion' => $cat['descripcion'],
                        'activo' => true,
                    ]
                );
            }

            // 5. Reservas de ejemplo para hoy y próximos días
            $cliente = User::role('cliente')->first();
            $mesa4 = Espacio::where('sucursal_id', $sucursal->id)
                ->where('capacidad', '>=', 4)
                ->where('tipo', 'mesa')
                ->first();

            if ($cliente && $mesa4 && isset($catCreadas['Mesa estándar'])) {
                $fechaHoy = Carbon::today()->toDateString();
                Reserva::firstOrCreate(
                    [
                        'codigo' => Reserva::generarCodigo($sucursal->id, $fechaHoy),
                    ],
                    [
                        'sucursal_id' => $sucursal->id,
                        'cliente_id' => $cliente->id,
                        'espacio_id' => $mesa4->id,
                        'categoria_reserva_id' => $catCreadas['Mesa estándar']->id,
                        'fecha' => $fechaHoy,
                        'hora_inicio' => '19:00:00',
                        'hora_fin' => '21:00:00',
                        'personas' => 4,
                        'estado' => EstadoReserva::CONFIRMADA,
                        'nombre_contacto' => $cliente->name,
                        'email_contacto' => $cliente->email,
                        'telefono_contacto' => $cliente->telefono ?? '3001234567',
                        'notas_cliente' => 'Mesa cerca a la ventana si es posible.',
                    ]
                );

                $fechaManana = Carbon::tomorrow()->toDateString();
                Reserva::firstOrCreate(
                    [
                        'codigo' => Reserva::generarCodigo($sucursal->id, $fechaManana),
                    ],
                    [
                        'sucursal_id' => $sucursal->id,
                        'cliente_id' => $cliente->id,
                        'espacio_id' => $mesa4->id,
                        'categoria_reserva_id' => $catCreadas['Cumpleaños']->id,
                        'fecha' => $fechaManana,
                        'hora_inicio' => '13:00:00',
                        'hora_fin' => '15:00:00',
                        'personas' => 4,
                        'estado' => EstadoReserva::PENDIENTE,
                        'nombre_contacto' => $cliente->name,
                        'email_contacto' => $cliente->email,
                        'telefono_contacto' => $cliente->telefono ?? '3001234567',
                        'notas_cliente' => 'Traemos pastel de cumpleaños.',
                    ]
                );
            }
        }
    }
}
