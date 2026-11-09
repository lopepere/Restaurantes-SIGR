<?php

use App\Enums\TipoImpuesto;
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
        Schema::create('pedido_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('pedido_id')->constrained('pedidos')->cascadeOnDelete();
            $table->foreignId('item_menu_id')->nullable()->constrained('items_menu')->nullOnDelete();
            $table->string('nombre_item', 255);
            $table->decimal('precio_unitario', 14, 2);
            $table->decimal('cantidad', 10, 2)->default(1);
            $table->decimal('subtotal', 14, 2);
            $table->string('impuesto_tipo', 30)->default(TipoImpuesto::INC->value);
            $table->decimal('impuesto_porcentaje', 5, 2)->default(8.00);
            $table->decimal('impuesto_monto', 14, 2)->default(0);
            $table->decimal('descuento_aplicado', 14, 2)->default(0);
            $table->decimal('total', 14, 2);
            $table->text('notas')->nullable();
            $table->json('combo_desglose')->nullable();
            $table->timestamps();

            $table->index(['pedido_id', 'item_menu_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedido_items');
    }
};
