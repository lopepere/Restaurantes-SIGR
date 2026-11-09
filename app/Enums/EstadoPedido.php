<?php

namespace App\Enums;

enum EstadoPedido: string
{
    case PENDIENTE = 'pendiente';
    case CONFIRMADO = 'confirmado';
    case EN_PREPARACION = 'en_preparacion';
    case LISTO = 'listo';
    case ENTREGADO = 'entregado';
    case PAGADO = 'pagado';
    case CANCELACION_SOLICITADA = 'cancelacion_solicitada';
    case CANCELADO = 'cancelado';

    /**
     * Devuelve la lista de estados hacia los cuales es válido transicionar (ERS 8.3).
     *
     * @return array<int, EstadoPedido>
     */
    public function transicionesPermitidas(): array
    {
        return match ($this) {
            self::PENDIENTE => [self::CONFIRMADO, self::CANCELACION_SOLICITADA, self::CANCELADO],
            self::CONFIRMADO => [self::EN_PREPARACION, self::CANCELACION_SOLICITADA, self::CANCELADO],
            self::EN_PREPARACION => [self::LISTO, self::CANCELACION_SOLICITADA],
            self::LISTO => [self::ENTREGADO],
            self::ENTREGADO => [self::PAGADO],
            self::CANCELACION_SOLICITADA => [self::CANCELADO, self::PENDIENTE, self::CONFIRMADO, self::EN_PREPARACION],
            self::PAGADO => [], // Estado final
            self::CANCELADO => [], // Estado final
        };
    }

    public function puedeTransicionarHacia(self $nuevoEstado): bool
    {
        return in_array($nuevoEstado, $this->transicionesPermitidas(), true);
    }

    /**
     * Devuelve la etiqueta que ve el cliente: «En curso», «Completado» o «Entregado» (ERS 8.3).
     */
    public function etiquetaCliente(?TipoPedido $tipo = null): string
    {
        return match ($this) {
            self::PENDIENTE, self::CONFIRMADO, self::EN_PREPARACION, self::LISTO => 'En curso',
            self::ENTREGADO => ($tipo === TipoPedido::EN_SITIO) ? 'Completado' : 'Entregado',
            self::PAGADO => 'Completado',
            self::CANCELACION_SOLICITADA => 'En curso – cancelación en revisión',
            self::CANCELADO => 'Cancelado',
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::PENDIENTE => 'Pendiente',
            self::CONFIRMADO => 'Confirmado',
            self::EN_PREPARACION => 'En preparación',
            self::LISTO => 'Listo',
            self::ENTREGADO => 'Entregado',
            self::PAGADO => 'Pagado',
            self::CANCELACION_SOLICITADA => 'Cancelación solicitada',
            self::CANCELADO => 'Cancelado',
        };
    }
}
