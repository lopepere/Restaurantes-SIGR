<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // 1. Categorías del sistema ("Sin categorizar" inmutable)
        if (! Schema::hasColumn('categorias', 'es_sistema')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->boolean('es_sistema')->default(false)->after('activa');
            });
        }

        // 2. Nota adicional en Pagos
        if (! Schema::hasColumn('pagos', 'nota_adicional')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->string('nota_adicional', 500)->nullable()->after('referencia_transaccion');
            });
        }

        // 3. Usuario asignado en Cajas
        if (! Schema::hasColumn('cajas', 'user_id')) {
            Schema::table('cajas', function (Blueprint $table) {
                $table->foreignId('user_id')->nullable()->after('sucursal_id')->constrained('users')->nullOnDelete();
            });
        }

        // 4. Tabla de Estaciones / Monitores de Cocina por Sucursal
        if (! Schema::hasTable('estaciones_cocina')) {
            Schema::create('estaciones_cocina', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->string('nombre', 100);
                $table->string('codigo', 50)->nullable();
                $table->boolean('activa')->default(true);
                $table->timestamps();
            });
        }

        // 5. Tabla de Asignaciones Diarias de Base en Efectivo por Caja
        if (! Schema::hasTable('asignaciones_base_caja')) {
            Schema::create('asignaciones_base_caja', function (Blueprint $table) {
                $table->id();
                $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
                $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
                $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('asignado_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->foreignId('editado_por_id')->nullable()->constrained('users')->nullOnDelete();
                $table->date('fecha');
                $table->decimal('monto', 14, 2)->default(0);
                $table->string('observaciones', 500)->nullable();
                $table->timestamps();

                $table->index(['sucursal_id', 'fecha']);
                $table->index(['caja_id', 'fecha']);
            });
        }

        // 6. Asegurar categoría "Sin categorizar" en todas las sucursales existentes
        if (Schema::hasTable('sucursales') && Schema::hasTable('categorias')) {
            $sucursales = DB::table('sucursales')->get(['id']);
            foreach ($sucursales as $sucursal) {
                $existe = DB::table('categorias')
                    ->where('sucursal_id', $sucursal->id)
                    ->where('slug', 'sin-categorizar')
                    ->exists();

                if (! $existe) {
                    DB::table('categorias')->insert([
                        'sucursal_id' => $sucursal->id,
                        'nombre' => 'Sin categorizar',
                        'slug' => 'sin-categorizar',
                        'descripcion' => 'Categoría por defecto del sistema para productos sin categoría asignada.',
                        'orden' => 999,
                        'icono' => '📦',
                        'activa' => true,
                        'es_sistema' => true,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                } else {
                    DB::table('categorias')
                        ->where('sucursal_id', $sucursal->id)
                        ->where('slug', 'sin-categorizar')
                        ->update(['es_sistema' => true]);
                }
            }
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('asignaciones_base_caja');
        Schema::dropIfExists('estaciones_cocina');

        if (Schema::hasColumn('cajas', 'user_id')) {
            Schema::table('cajas', function (Blueprint $table) {
                $table->dropConstrainedForeignId('user_id');
            });
        }

        if (Schema::hasColumn('pagos', 'nota_adicional')) {
            Schema::table('pagos', function (Blueprint $table) {
                $table->dropColumn('nota_adicional');
            });
        }

        if (Schema::hasColumn('categorias', 'es_sistema')) {
            Schema::table('categorias', function (Blueprint $table) {
                $table->dropColumn('es_sistema');
            });
        }
    }
};
