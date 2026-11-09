<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categorias', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->cascadeOnDelete();
            $table->string('nombre');
            $table->string('slug');
            $table->text('descripcion')->nullable();
            $table->integer('orden')->default(0);
            $table->string('icono')->nullable();
            $table->boolean('activa')->default(true);
            $table->softDeletes();
            $table->timestamps();

            $table->index(['sucursal_id', 'activa', 'orden']);
            $table->unique(['sucursal_id', 'slug', 'deleted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categorias');
    }
};
