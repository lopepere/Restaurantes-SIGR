<?php

use App\Enums\EstadoPedido;
use App\Enums\TipoPedido;

describe('EstadoPedido Enum', function () {
    test('transiciones permitidas desde PENDIENTE son CONFIRMADO, CANCELACION_SOLICITADA y CANCELADO', function () {
        $permitidas = EstadoPedido::PENDIENTE->transicionesPermitidas();

        expect($permitidas)->toContain(EstadoPedido::CONFIRMADO)
            ->and($permitidas)->toContain(EstadoPedido::CANCELACION_SOLICITADA)
            ->and($permitidas)->toContain(EstadoPedido::CANCELADO)
            ->and($permitidas)->not->toContain(EstadoPedido::PAGADO)
            ->and($permitidas)->not->toContain(EstadoPedido::ENTREGADO);
    });

    test('transición desde ENTREGADO solo permite PAGADO', function () {
        $permitidas = EstadoPedido::ENTREGADO->transicionesPermitidas();

        expect($permitidas)->toEqual([EstadoPedido::PAGADO]);
    });

    test('estados PAGADO y CANCELADO son finales sin transiciones', function () {
        expect(EstadoPedido::PAGADO->transicionesPermitidas())->toBeEmpty()
            ->and(EstadoPedido::CANCELADO->transicionesPermitidas())->toBeEmpty();
    });

    test('puedeTransicionarHacia valida correctamente transiciones legales e ilegales', function () {
        expect(EstadoPedido::PENDIENTE->puedeTransicionarHacia(EstadoPedido::CONFIRMADO))->toBeTrue()
            ->and(EstadoPedido::CONFIRMADO->puedeTransicionarHacia(EstadoPedido::EN_PREPARACION))->toBeTrue()
            ->and(EstadoPedido::EN_PREPARACION->puedeTransicionarHacia(EstadoPedido::LISTO))->toBeTrue()
            ->and(EstadoPedido::LISTO->puedeTransicionarHacia(EstadoPedido::ENTREGADO))->toBeTrue()
            ->and(EstadoPedido::ENTREGADO->puedeTransicionarHacia(EstadoPedido::PAGADO))->toBeTrue()
            // Transición ilegal directa:
            ->and(EstadoPedido::PENDIENTE->puedeTransicionarHacia(EstadoPedido::PAGADO))->toBeFalse()
            ->and(EstadoPedido::PAGADO->puedeTransicionarHacia(EstadoPedido::CANCELADO))->toBeFalse();
    });

    test('etiquetaCliente muestra En curso, Completado o Entregado según ERS 8.3', function () {
        expect(EstadoPedido::PENDIENTE->etiquetaCliente())->toBe('En curso')
            ->and(EstadoPedido::CONFIRMADO->etiquetaCliente())->toBe('En curso')
            ->and(EstadoPedido::EN_PREPARACION->etiquetaCliente())->toBe('En curso')
            ->and(EstadoPedido::LISTO->etiquetaCliente())->toBe('En curso')
            ->and(EstadoPedido::ENTREGADO->etiquetaCliente(TipoPedido::EN_SITIO))->toBe('Completado')
            ->and(EstadoPedido::ENTREGADO->etiquetaCliente(TipoPedido::DOMICILIO))->toBe('Entregado')
            ->and(EstadoPedido::ENTREGADO->etiquetaCliente(TipoPedido::PARA_LLEVAR))->toBe('Entregado')
            ->and(EstadoPedido::PAGADO->etiquetaCliente())->toBe('Completado')
            ->and(EstadoPedido::CANCELACION_SOLICITADA->etiquetaCliente())->toBe('En curso – cancelación en revisión')
            ->and(EstadoPedido::CANCELADO->etiquetaCliente())->toBe('Cancelado');
    });
});
