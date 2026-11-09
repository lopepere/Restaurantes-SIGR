<?php

use App\Enums\TipoBeneficioPromocion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promociones', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->string('tipo_beneficio')->default(TipoBeneficioPromocion::PORCENTAJE->value);
            $table->decimal('valor_beneficio', 14, 2);
            $table->dateTime('fecha_inicio');
            $table->dateTime('fecha_fin');
            $table->json('dias_semana')->nullable();
            $table->time('hora_inicio')->nullable();
            $table->time('hora_fin')->nullable();
            $table->boolean('activa')->default(true);
            $table->boolean('publicada')->default(false);
            $table->string('imagen')->nullable();
            $table->boolean('aplica_a_todo')->default(false);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sucursal_id', 'activa', 'publicada', 'fecha_inicio', 'fecha_fin'], 'promociones_sucursal_vigencia_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promociones');
    }
};
