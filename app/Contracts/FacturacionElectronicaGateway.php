<?php

namespace App\Contracts;

use App\Models\Comprobante;

interface FacturacionElectronicaGateway
{
    /**
     * Transmite un comprobante a la autoridad tributaria correspondiente (DIAN u homólogo).
     *
     * @return array{exitoso: bool, mensaje: string, cufe: ?string, qr: ?string}
     */
    public function emitir(Comprobante $comprobante): array;

    /**
     * Anula una factura electrónica previamente emitida.
     *
     * @return array{exitoso: bool, mensaje: string}
     */
    public function anular(Comprobante $comprobante, string $motivo): array;

    /**
     * Consulta el estado de emisión tributaria.
     */
    public function consultarEstado(Comprobante $comprobante): string;
}
