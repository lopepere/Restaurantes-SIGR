<?php

namespace App\Services;

class TelefonoService
{
    /**
     * Normaliza un número telefónico a formato internacional E.164 (+573001234567).
     */
    public static function normalizar(?string $telefono, string $pais = 'CO'): string
    {
        if (empty($telefono)) {
            return '';
        }

        $limpio = trim($telefono);

        try {
            return (string) phone($limpio, [$pais, 'AUTO'])->formatE164();
        } catch (\Throwable) {
            // Si ya viene con + y dígitos, devolverlo limpio
            $soloDigitos = preg_replace('/[^\d+]/', '', $limpio) ?? '';
            if (str_starts_with($soloDigitos, '+')) {
                return $soloDigitos;
            }

            return '+'.$soloDigitos;
        }
    }
}
