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
        Schema::create('horarios_atencion', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->unsignedTinyInteger('dia_semana'); // 0: Domingo, 1: Lunes, ..., 6: Sábado
            $table->time('hora_apertura')->default('12:00:00');
            $table->time('hora_cierre')->default('22:00:00');
            $table->boolean('activo')->default(true);
            $table->timestamps();

            $table->unique(['sucursal_id', 'dia_semana']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('horarios_atencion');
    }
};
