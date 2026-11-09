<?php

use App\Enums\EstadoCompra;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo de Compras a Proveedores (RF-COM-01..08).
     * Nota: Por requerimiento explícito del Prompt Maestro, las compras son INMUTABLES y NO tienen soft deletes.
     */
    public function up(): void
    {
        Schema::create('compras', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('proveedor_id')->constrained('proveedores')->restrictOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->string('numero_factura');
            $table->date('fecha_compra');
            $table->decimal('subtotal', 14, 2)->default(0.00);
            $table->decimal('impuesto_total', 14, 2)->default(0.00);
            $table->decimal('total', 14, 2)->default(0.00);
            $table->string('estado')->default(EstadoCompra::REGISTRADA->value);
            $table->text('notas')->nullable();
            $table->foreignId('anulado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamp('anulado_at')->nullable();
            $table->timestamps();

            $table->index(['sucursal_id', 'fecha_compra', 'estado']);
            $table->index(['sucursal_id', 'numero_factura']);
        });

        Schema::create('compra_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('item_menu_id')->nullable()->constrained('items_menu')->nullOnDelete();
            $table->string('descripcion');
            $table->decimal('cantidad', 14, 2);
            $table->decimal('costo_unitario', 14, 2);
            $table->decimal('factor_conversion', 10, 4)->default(1.0000);
            $table->decimal('impuesto_porcentaje', 5, 2)->default(0.00);
            $table->decimal('subtotal', 14, 2);
            $table->decimal('impuesto_valor', 14, 2)->default(0.00);
            $table->decimal('total', 14, 2);
            $table->timestamps();

            $table->index(['compra_id', 'item_menu_id']);
        });

        Schema::create('devoluciones_compra', function (Blueprint $table) {
            $table->id();
            $table->foreignId('compra_id')->constrained('compras')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('fecha_devolucion');
            $table->text('motivo');
            $table->decimal('total_devuelto', 14, 2)->default(0.00);
            $table->timestamps();

            $table->index(['sucursal_id', 'compra_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devoluciones_compra');
        Schema::dropIfExists('compra_items');
        Schema::dropIfExists('compras');
    }
};
