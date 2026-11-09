<?php

namespace App\Services;

class MonedaService
{
    /**
     * Redondea un valor monetario a 2 decimales usando ROUND_HALF_UP.
     */
    public static function redondear(float|int|string $valor): float
    {
        return round((float) $valor, 2, PHP_ROUND_HALF_UP);
    }

    /**
     * Desglosa un precio que ya incluye impuesto (ERS 8.5):
     * base = precio / (1 + tarifa)
     * impuesto = precio - base
     *
     * @return array{base: float, impuesto: float, total: float}
     */
    public static function desglosarImpuestoIncluido(float|int|string $precioConImpuesto, float|int|string $porcentajeTarifa): array
    {
        $precio = self::redondear($precioConImpuesto);
        $tarifa = (float) $porcentajeTarifa;

        if ($tarifa <= 0.0) {
            return [
                'base' => $precio,
                'impuesto' => 0.00,
                'total' => $precio,
            ];
        }

        $factor = 1 + ($tarifa / 100);
        $base = self::redondear($precio / $factor);
        $impuesto = self::redondear($precio - $base);

        return [
            'base' => $base,
            'impuesto' => $impuesto,
            'total' => $precio,
        ];
    }

    /**
     * Calcula el impuesto a partir de una base neta (costo sin impuesto, ej. en compras):
     * impuesto = base * tarifa
     *
     * @return array{base: float, impuesto: float, total: float}
     */
    public static function calcularImpuestoSobreBase(float|int|string $baseSinImpuesto, float|int|string $porcentajeTarifa): array
    {
        $base = self::redondear($baseSinImpuesto);
        $tarifa = (float) $porcentajeTarifa;

        if ($tarifa <= 0.0) {
            return [
                'base' => $base,
                'impuesto' => 0.00,
                'total' => $base,
            ];
        }

        $impuesto = self::redondear($base * ($tarifa / 100));
        $total = self::redondear($base + $impuesto);

        return [
            'base' => $base,
            'impuesto' => $impuesto,
            'total' => $total,
        ];
    }

    /**
     * Calcula la propina sugerida calculada estrictamente sobre la base imponible sin impuestos (DE-17, ERS 8.5).
     */
    public static function calcularPropina(float|int|string $baseSinImpuesto, float|int|string $porcentaje): float
    {
        $base = self::redondear($baseSinImpuesto);
        $pct = (float) $porcentaje;

        if ($pct <= 0.0) {
            return 0.00;
        }

        return self::redondear($base * ($pct / 100));
    }

    /**
     * Calcula la devuelta en pagos en efectivo:
     * devuelta = recibido - a_cobrar
     */
    public static function calcularDevuelta(float|int|string $valorRecibido, float|int|string $valorACobrar): float
    {
        $recibido = self::redondear($valorRecibido);
        $cobrar = self::redondear($valorACobrar);

        $diferencia = $recibido - $cobrar;

        return self::redondear(max(0.00, $diferencia));
    }

    /**
     * Formatea un valor monetario para presentación.
     */
    public static function formatear(float|int|string $valor, string $moneda = 'COP'): string
    {
        $redondeado = self::redondear($valor);

        return '$ '.number_format($redondeado, 2, ',', '.');
    }
}
