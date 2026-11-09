<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kárdex de Inventario Inmutable (RF-INV-01..09, CA-27, CA-28).
     */
    public function up(): void
    {
        Schema::create('movimientos_inventario', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('item_menu_id')->constrained('items_menu')->cascadeOnDelete();
            $table->string('tipo'); // entrada_compra, salida_venta, ajuste_positivo, ajuste_negativo, inventario_inicial, devolucion_compra, merma
            $table->decimal('cantidad', 14, 2);
            $table->decimal('costo_unitario', 14, 2)->default(0.00);
            $table->decimal('costo_total', 14, 2)->default(0.00);
            $table->decimal('saldo_cantidad', 14, 2)->default(0.00);
            $table->decimal('saldo_costo', 14, 2)->default(0.00);
            $table->string('referencia_tipo')->nullable();
            $table->unsignedBigInteger('referencia_id')->nullable();
            $table->string('motivo')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['sucursal_id', 'item_menu_id', 'created_at']);
            $table->index(['referencia_tipo', 'referencia_id']);
            $table->index(['sucursal_id', 'tipo']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('movimientos_inventario');
    }
};
