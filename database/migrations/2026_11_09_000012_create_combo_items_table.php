<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('combo_id')->constrained('items_menu')->cascadeOnDelete();
            $table->foreignId('item_id')->constrained('items_menu')->restrictOnDelete();
            $table->decimal('cantidad', 10, 2)->default(1.00);
            $table->timestamps();

            $table->unique(['combo_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
