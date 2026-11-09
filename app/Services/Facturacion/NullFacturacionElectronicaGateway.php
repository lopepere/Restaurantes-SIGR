<?php

namespace App\Services\Facturacion;

use App\Contracts\FacturacionElectronicaGateway;
use App\Models\Comprobante;

class NullFacturacionElectronicaGateway implements FacturacionElectronicaGateway
{
    /**
     * Implementación nula para comprobante interno de venta (SC-03).
     * Permite desacoplar el sistema y habilitar facturación electrónica formal en fases posteriores.
     */
    public function emitir(Comprobante $comprobante): array
    {
        return [
            'exitoso' => true,
            'mensaje' => 'Comprobante interno emitido satisfactoriamente (modo FAC sin transmisión externa DIAN).',
            'cufe' => null,
            'qr' => null,
        ];
    }

    public function anular(Comprobante $comprobante, string $motivo): array
    {
        return [
            'exitoso' => true,
            'mensaje' => 'Comprobante interno anulado (modo FAC).',
        ];
    }

    public function consultarEstado(Comprobante $comprobante): string
    {
        return 'comprobante_interno_valido';
    }
}
