<?php

use App\Enums\TipoImpuesto;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items_menu', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->restrictOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->decimal('precio', 14, 2);
            $table->string('impuesto_tipo')->default(TipoImpuesto::INC->value);
            $table->decimal('impuesto_porcentaje', 5, 2)->default(8.00);
            $table->string('imagen')->nullable();
            $table->integer('orden')->default(0);
            $table->boolean('es_combo')->default(false);
            $table->boolean('controla_inventario')->default(true);
            $table->decimal('existencia', 14, 2)->default(0.00);
            $table->decimal('existencia_minima', 14, 2)->default(5.00);
            $table->boolean('disponible')->default(true);
            $table->boolean('activo')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sucursal_id', 'categoria_id', 'activo', 'disponible']);
            $table->unique(['sucursal_id', 'slug', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('items_menu');
    }
};
