<?php

use App\Enums\EstadoSesionCaja;
use App\Enums\MetodoPago;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo de Cajas, Sesiones/Turnos y Pagos (RF-CAJ-01..08, CA-29, CA-30).
     */
    public function up(): void
    {
        Schema::create('cajas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->string('nombre');
            $table->boolean('activa')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sucursal_id', 'activa']);
        });

        Schema::create('sesiones_caja', function (Blueprint $table) {
            $table->id();
            $table->foreignId('caja_id')->constrained('cajas')->cascadeOnDelete();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->restrictOnDelete();
            $table->timestamp('fecha_apertura');
            $table->decimal('monto_apertura', 14, 2)->default(0.00);
            $table->timestamp('fecha_cierre')->nullable();
            $table->decimal('monto_cierre_declarado', 14, 2)->nullable();
            $table->decimal('monto_cierre_calculado', 14, 2)->nullable();
            $table->decimal('diferencia', 14, 2)->default(0.00);
            $table->string('estado')->default(EstadoSesionCaja::ABIERTA->value);
            $table->json('arqueo_desglose')->nullable(); // Desglose de billetes y monedas
            $table->text('observaciones')->nullable();
            $table->timestamps();

            $table->index(['sucursal_id', 'caja_id', 'estado']);
            $table->index(['user_id', 'estado']);
        });

        Schema::create('pagos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('sesion_caja_id')->nullable()->constrained('sesiones_caja')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->decimal('monto', 14, 2);
            $table->decimal('propina_valor', 14, 2)->default(0.00);
            $table->string('metodo_pago')->default(MetodoPago::EFECTIVO->value);
            $table->decimal('monto_recibido', 14, 2)->default(0.00);
            $table->decimal('cambio_devuelto', 14, 2)->default(0.00);
            $table->string('referencia_transaccion')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'created_at']);
            $table->index(['sesion_caja_id', 'metodo_pago']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pagos');
        Schema::dropIfExists('sesiones_caja');
        Schema::dropIfExists('cajas');
    }
};
