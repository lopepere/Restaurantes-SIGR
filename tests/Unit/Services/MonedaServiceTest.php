<?php

use App\Services\MonedaService;

describe('MonedaService', function () {
    test('redondea con PHP_ROUND_HALF_UP a dos decimales', function () {
        expect(MonedaService::redondear(10.554))->toBe(10.55)
            ->and(MonedaService::redondear(10.555))->toBe(10.56)
            ->and(MonedaService::redondear(10.556))->toBe(10.56)
            ->and(MonedaService::redondear('100.005'))->toBe(100.01)
            ->and(MonedaService::redondear('0'))->toBe(0.00);
    });

    test('desglosa correctamente impuesto incluido según ejemplo ERS 8.5 (INC 8 % sobre $32.400)', function () {
        // En ERS 8.5: Plato de $32.400 con INC 8 %:
        // base = 32400 / 1.08 = 30000.00
        // impuesto = 32400 - 30000 = 2400.00
        $desglose = MonedaService::desglosarImpuestoIncluido(32400, 8);

        expect($desglose['base'])->toBe(30000.00)
            ->and($desglose['impuesto'])->toBe(2400.00)
            ->and($desglose['total'])->toBe(32400.00);
    });

    test('desglosa tarifa 0 % o sin impuesto devolviendo base igual a total', function () {
        $desglose = MonedaService::desglosarImpuestoIncluido(15000, 0);

        expect($desglose['base'])->toBe(15000.00)
            ->and($desglose['impuesto'])->toBe(0.00)
            ->and($desglose['total'])->toBe(15000.00);
    });

    test('desglosa IVA 19 % incluido (ej. $119.000 -> base $100.000, IVA $19.000 CA-18)', function () {
        $desglose = MonedaService::desglosarImpuestoIncluido(119000, 19);

        expect($desglose['base'])->toBe(100000.00)
            ->and($desglose['impuesto'])->toBe(19000.00)
            ->and($desglose['total'])->toBe(119000.00);
    });

    test('calcula propina sugerida calculada estrictamente sobre la base sin impuestos (DE-17, ERS 8.5)', function () {
        // En ERS 8.5: Plato base $30.000 con propina del 10 % sobre la base:
        // propina = 30000 * 10% = 3000.00
        $propina = MonedaService::calcularPropina(30000, 10);
        expect($propina)->toBe(3000.00);

        $propinaCinco = MonedaService::calcularPropina(30000, 5);
        expect($propinaCinco)->toBe(1500.00);

        $propinaCero = MonedaService::calcularPropina(30000, 0);
        expect($propinaCero)->toBe(0.00);
    });

    test('calcula devuelta en pagos en efectivo (CA-14: $47.000 con billete de $50.000 -> $3.000 devuelta)', function () {
        $devuelta = MonedaService::calcularDevuelta(50000, 47000);
        expect($devuelta)->toBe(3000.00);

        $devueltaExacta = MonedaService::calcularDevuelta(47000, 47000);
        expect($devueltaExacta)->toBe(0.00);
    });

    test('formatea valores en formato moneda legible COP', function () {
        expect(MonedaService::formatear(32400))->toBe('$ 32.400,00')
            ->and(MonedaService::formatear(3000.50))->toBe('$ 3.000,50');
    });
});
