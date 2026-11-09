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
        // 1. users: current_session_id para validar sesión única concurrente (Item 7)
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'current_session_id')) {
                $table->string('current_session_id')->nullable()->after('remember_token');
            }
        });

        // 2. items_menu: excluir_descuentos_categoria (Item 8)
        Schema::table('items_menu', function (Blueprint $table) {
            if (! Schema::hasColumn('items_menu', 'excluir_descuentos_categoria')) {
                $table->boolean('excluir_descuentos_categoria')->default(false)->after('es_combo');
            }
        });

        // 3. sucursales: parametrización de modalidades de pedidos (Item 9)
        Schema::table('sucursales', function (Blueprint $table) {
            if (! Schema::hasColumn('sucursales', 'acepta_domicilios')) {
                $table->boolean('acepta_domicilios')->default(true)->after('activa');
            }
            if (! Schema::hasColumn('sucursales', 'acepta_para_llevar')) {
                $table->boolean('acepta_para_llevar')->default(true)->after('acepta_domicilios');
            }
            if (! Schema::hasColumn('sucursales', 'acepta_en_sitio')) {
                $table->boolean('acepta_en_sitio')->default(true)->after('acepta_para_llevar');
            }
        });

        // 4. devoluciones_compra: soporte para devoluciones parciales y detalle de items devueltos (Item 4)
        Schema::table('devoluciones_compra', function (Blueprint $table) {
            if (! Schema::hasColumn('devoluciones_compra', 'tipo')) {
                $table->string('tipo', 30)->default('total')->after('motivo');
            }
            if (! Schema::hasColumn('devoluciones_compra', 'items_devueltos')) {
                $table->json('items_devueltos')->nullable()->after('tipo');
            }
        });

        // 5. pedido_items: soporte para anulación de ítems mal facturados con auditoría (Item 11)
        Schema::table('pedido_items', function (Blueprint $table) {
            if (! Schema::hasColumn('pedido_items', 'anulado')) {
                $table->boolean('anulado')->default(false)->after('total');
            }
            if (! Schema::hasColumn('pedido_items', 'motivo_anulacion')) {
                $table->string('motivo_anulacion')->nullable()->after('anulado');
            }
            if (! Schema::hasColumn('pedido_items', 'anulado_por_id')) {
                $table->foreignId('anulado_por_id')->nullable()->after('motivo_anulacion')->constrained('users')->nullOnDelete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pedido_items', function (Blueprint $table) {
            if (Schema::hasColumn('pedido_items', 'anulado_por_id')) {
                $table->dropForeign(['anulado_por_id']);
                $table->dropColumn('anulado_por_id');
            }
            if (Schema::hasColumn('pedido_items', 'motivo_anulacion')) {
                $table->dropColumn('motivo_anulacion');
            }
            if (Schema::hasColumn('pedido_items', 'anulado')) {
                $table->dropColumn('anulado');
            }
        });

        Schema::table('devoluciones_compra', function (Blueprint $table) {
            if (Schema::hasColumn('devoluciones_compra', 'items_devueltos')) {
                $table->dropColumn('items_devueltos');
            }
            if (Schema::hasColumn('devoluciones_compra', 'tipo')) {
                $table->dropColumn('tipo');
            }
        });

        Schema::table('sucursales', function (Blueprint $table) {
            if (Schema::hasColumn('sucursales', 'acepta_en_sitio')) {
                $table->dropColumn('acepta_en_sitio');
            }
            if (Schema::hasColumn('sucursales', 'acepta_para_llevar')) {
                $table->dropColumn('acepta_para_llevar');
            }
            if (Schema::hasColumn('sucursales', 'acepta_domicilios')) {
                $table->dropColumn('acepta_domicilios');
            }
        });

        Schema::table('items_menu', function (Blueprint $table) {
            if (Schema::hasColumn('items_menu', 'excluir_descuentos_categoria')) {
                $table->dropColumn('excluir_descuentos_categoria');
            }
        });

        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'current_session_id')) {
                $table->dropColumn('current_session_id');
            }
        });
    }
};

