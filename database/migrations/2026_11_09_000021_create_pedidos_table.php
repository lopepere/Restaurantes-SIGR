<?php

use App\Enums\EstadoPedido;
use App\Enums\PropinaTipo;
use App\Enums\TipoPedido;
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
        Schema::create('pedidos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sucursal_id')->constrained('sucursales')->restrictOnDelete();
            $table->string('numero', 50);
            $table->foreignId('cliente_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('mesero_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('espacio_id')->nullable()->constrained('espacios')->nullOnDelete();
            $table->string('tipo', 30)->default(TipoPedido::EN_SITIO->value);
            $table->string('estado', 30)->default(EstadoPedido::PENDIENTE->value);
            $table->string('direccion_entrega', 255)->nullable();
            $table->string('telefono_contacto', 50)->nullable();
            $table->string('nombre_contacto', 100)->nullable();
            $table->text('notas')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('descuento', 14, 2)->default(0);
            $table->decimal('impuestos', 14, 2)->default(0);
            $table->string('propina_tipo', 30)->default(PropinaTipo::NINGUNA->value);
            $table->decimal('propina_porcentaje', 5, 2)->nullable();
            $table->decimal('propina', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->boolean('solicito_cancelacion')->default(false);
            $table->text('motivo_cancelacion')->nullable();
            $table->timestamp('cancelacion_solicitada_at')->nullable();
            $table->foreignId('cancelado_por_id')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('pagado')->default(false);
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['sucursal_id', 'numero']);
            $table->index(['sucursal_id', 'estado', 'created_at']);
            $table->index(['cliente_id', 'created_at']);
            $table->index(['mesero_id', 'created_at']);
            $table->index(['espacio_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pedidos');
    }
};
