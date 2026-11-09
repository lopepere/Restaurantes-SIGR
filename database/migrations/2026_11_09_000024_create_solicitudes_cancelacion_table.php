<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('solicitudes_cancelacion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('solicitado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('resuelto_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->text('motivo');
            $table->string('estado', 30)->default('pendiente'); // pendiente, aprobada, rechazada
            $table->text('respuesta_admin')->nullable();
            $table->timestamp('solicitada_at')->useCurrent();
            $table->timestamp('resuelta_at')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'estado']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('solicitudes_cancelacion');
    }
};
