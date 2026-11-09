<?php

namespace App\Services;

class PlantillaRenderer
{
    /**
     * Renderiza una plantilla de correo sustituyendo de forma segura variables tipo {{variable}}
     * tras sanear el HTML con HTMLPurifier, SIN compilar Blade (RNF-SEG / Prompt Maestro).
     *
     * @param  array<string, mixed>  $variables
     */
    public static function render(string $cuerpoHtml, array $variables = []): string
    {
        // 1. Sanitizar el HTML para evitar inyección XSS usando HTMLPurifier (mews/purifier)
        $htmlLimpio = function_exists('clean') ? clean($cuerpoHtml) : strip_tags($cuerpoHtml, '<p><br><b><strong><i><em><u><a><img><table><thead><tbody><tr><th><td><h1><h2><h3><h4><div><span><ul><ol><li>');

        // 2. Mapear variables para reemplazo seguro con strtr
        $reemplazos = [];
        foreach ($variables as $clave => $valor) {
            $valStr = is_scalar($valor) ? (string) $valor : json_encode($valor, JSON_UNESCAPED_UNICODE);
            $reemplazos['{{'.$clave.'}}'] = $valStr;
            $reemplazos['{{ '.$clave.' }}'] = $valStr;
        }

        return strtr($htmlLimpio, $reemplazos);
    }

    /**
     * Sanea código HTML recibido del editor visual.
     */
    public static function sanitizar(string $cuerpoHtml): string
    {
        return function_exists('clean') ? clean($cuerpoHtml) : strip_tags($cuerpoHtml, '<p><br><b><strong><i><em><u><a><img><table><thead><tbody><tr><th><td><h1><h2><h3><h4><div><span><ul><ol><li>');
    }

    /**
     * Variables de prueba para previsualización y envío de prueba de las 8 plantillas del sistema.
     *
     * @return array<string, string>
     */
    public static function variablesPrueba(string $clave): array
    {
        $base = [
            'restaurante.nombre' => 'SIGR Gourmet',
            'sucursal.nombre' => 'Sede Centro Gourmet',
            'sucursal.direccion' => 'Carrera 7 # 32-16',
            'sucursal.telefono' => '+57 300 123 4567',
            'año_actual' => (string) date('Y'),
        ];

        $especificas = match ($clave) {
            'invitacion' => [
                'cliente.nombre' => 'Alejandro Morales',
                'enlace_activacion' => 'http://sigr.test/invitacion/aceptar/sample-token-uuid',
                'horas_validez' => '72',
            ],
            'verificacion_registro' => [
                'cliente.nombre' => 'María Camila Gómez',
                'enlace_verificacion' => 'http://sigr.test/verificar-correo/sample-uuid',
            ],
            'bienvenida' => [
                'cliente.nombre' => 'Carlos Restrepo',
                'enlace_menu' => 'http://sigr.test/#menu',
            ],
            'pedido_recibido' => [
                'cliente.nombre' => 'Sofía Vergara',
                'pedido.numero' => 'PED-1-20261109-0012',
                'pedido.tipo' => 'En sitio',
                'pedido.total' => '$68.000',
            ],
            'cambio_estado' => [
                'cliente.nombre' => 'Andrés Cepeda',
                'pedido.numero' => 'PED-1-20261109-0015',
                'pedido.estado' => 'En preparación',
            ],
            'comprobante', 'reenvio_comprobante' => [
                'cliente.nombre' => 'Diana Marcela López',
                'comprobante.numero' => 'FAC-1-20261109-0004',
                'comprobante.cuv' => '550e8400-e29b-41d4-a716-446655440000',
                'comprobante.total' => '$112.500',
                'enlace_comprobante' => 'http://sigr.test/comprobante/verificar/550e8400-e29b-41d4-a716-446655440000',
            ],
            'reserva' => [
                'cliente.nombre' => 'Gabriel García',
                'reserva.codigo' => 'RES-1-20261109-0003',
                'reserva.fecha' => '15/11/2026',
                'reserva.hora' => '19:30',
                'reserva.personas' => '4',
                'reserva.mesa' => 'Mesa 4 VIP',
            ],
            default => [
                'cliente.nombre' => 'Cliente de Prueba',
            ],
        };

        return array_merge($base, $especificas);
    }
}
