<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('promocion_item', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocion_id')->constrained('promociones')->cascadeOnDelete();
            $table->foreignId('item_menu_id')->constrained('items_menu')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['promocion_id', 'item_menu_id']);
        });

        Schema::create('promocion_categoria', function (Blueprint $table) {
            $table->id();
            $table->foreignId('promocion_id')->constrained('promociones')->cascadeOnDelete();
            $table->foreignId('categoria_id')->constrained('categorias')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['promocion_id', 'categoria_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('promocion_categoria');
        Schema::dropIfExists('promocion_item');
    }
};
