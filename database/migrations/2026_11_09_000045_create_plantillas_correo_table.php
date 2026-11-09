<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Plantillas de Correo Personalizables (RF-CFG-04..09, CA-34..36).
     */
    public function up(): void
    {
        Schema::create('plantillas_correo', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->nullable()->constrained('sucursales')->cascadeOnDelete();
            $table->string('clave'); // invitacion, verificacion_registro, bienvenida, pedido_recibido, cambio_estado, comprobante, reenvio_comprobante, reserva
            $table->string('nombre');
            $table->string('asunto');
            $table->longText('cuerpo_html');
            $table->json('variables_disponibles')->nullable();
            $table->boolean('activa')->default(true);
            $table->timestamps();

            $table->unique(['sucursal_id', 'clave']);
            $table->index(['clave', 'activa']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plantillas_correo');
    }
};
