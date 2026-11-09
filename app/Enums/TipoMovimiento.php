<?php

namespace App\Enums;

enum TipoMovimiento: string
{
    case INICIAL = 'inicial';
    case COMPRA = 'compra';
    case VENTA = 'venta';
    case CANCELACION = 'cancelacion';
    case AJUSTE_POSITIVO = 'ajuste_positivo';
    case AJUSTE_NEGATIVO = 'ajuste_negativo';
    case DEVOLUCION = 'devolucion';
    case ANULACION = 'anulacion';

    public function esIngreso(): bool
    {
        return match ($this) {
            self::INICIAL, self::COMPRA, self::CANCELACION, self::AJUSTE_POSITIVO => true,
            self::VENTA, self::AJUSTE_NEGATIVO, self::DEVOLUCION, self::ANULACION => false,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::INICIAL => 'Inventario inicial',
            self::COMPRA => 'Compra a proveedor',
            self::VENTA => 'Venta / Pedido',
            self::CANCELACION => 'Reposición por cancelación de pedido',
            self::AJUSTE_POSITIVO => 'Ajuste positivo manual',
            self::AJUSTE_NEGATIVO => 'Ajuste negativo manual',
            self::DEVOLUCION => 'Devolución de compra a proveedor',
            self::ANULACION => 'Anulación de compra',
        };
    }
}
