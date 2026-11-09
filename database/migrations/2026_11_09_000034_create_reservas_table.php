<?php

use App\Enums\EstadoReserva;
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
        Schema::create('reservas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->string('codigo', 50);
            $table->foreignId('cliente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('espacio_id')->nullable()->constrained('espacios')->nullOnDelete();
            $table->foreignId('categoria_reserva_id')->nullable()->constrained('categorias_reserva')->nullOnDelete();
            $table->date('fecha');
            $table->time('hora_inicio');
            $table->time('hora_fin');
            $table->unsignedSmallInteger('personas')->default(2);
            $table->string('estado', 30)->default(EstadoReserva::PENDIENTE->value);
            $table->string('nombre_contacto', 100);
            $table->string('email_contacto', 255);
            $table->string('telefono_contacto', 50);
            $table->text('notas')->nullable();
            $table->text('motivo_cancelacion')->nullable();
            $table->foreignId('cancelado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sucursal_id', 'codigo']);
            $table->index(['sucursal_id', 'fecha', 'estado']);
            $table->index(['cliente_id', 'fecha']);
            $table->index(['espacio_id', 'fecha', 'hora_inicio', 'hora_fin']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('reservas');
    }
};
