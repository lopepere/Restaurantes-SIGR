<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Módulo de Comprobantes de Venta FAC (SC-03, RF-FAC-01..06, CA-31).
     * Nota: Por requerimiento explícito del Prompt Maestro, los comprobantes son INMUTABLES y NO tienen soft deletes.
     */
    public function up(): void
    {
        Schema::create('comprobantes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('pedido_id')->unique()->constrained('pedidos')->restrictOnDelete();
            $table->string('numero'); // Consecutivo por sucursal: FAC-{SUCURSAL}-{YYYYMMDD}-{0001}
            $table->uuid('codigo_verificacion')->unique(); // CUV para consulta pública
            $table->foreignId('cliente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('cliente_nombre');
            $table->string('cliente_documento')->nullable();
            $table->string('cliente_email')->nullable();
            $table->string('cliente_telefono')->nullable();
            $table->decimal('subtotal', 14, 2);
            $table->decimal('descuento', 14, 2)->default(0.00);
            $table->decimal('impuesto_total', 14, 2)->default(0.00);
            $table->decimal('propina', 14, 2)->default(0.00);
            $table->decimal('total', 14, 2);
            $table->string('metodo_pago');
            $table->string('hash_pdf', 64)->nullable(); // SHA-256 inmutable del documento
            $table->string('pdf_path')->nullable();
            $table->string('estado')->default('emitido'); // emitido, anulado
            $table->foreignId('anulado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo_anulacion')->nullable();
            $table->timestamp('anulado_at')->nullable();
            $table->timestamps();

            $table->unique(['sucursal_id', 'numero']);
            $table->index(['sucursal_id', 'created_at']);
            $table->index(['cliente_id', 'created_at']);
        });

        Schema::create('envios_comprobante', function (Blueprint $table) {
            $table->id();
            $table->foreignId('comprobante_id')->constrained('comprobantes')->cascadeOnDelete();
            $table->string('tipo'); // EMAIL_CLIENTE, EMAIL_DESIGNADO, DESCARGA, IMPRESION
            $table->json('destinatarios')->nullable();
            $table->text('mensaje')->nullable();
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('estado')->default('ENCOLADO'); // ENCOLADO, ENVIADO, FALLIDO
            $table->text('error')->nullable();
            $table->timestamps();

            $table->index(['comprobante_id', 'created_at']);
            $table->index(['usuario_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('envios_comprobante');
        Schema::dropIfExists('comprobantes');
    }
};
