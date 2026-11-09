<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravolt\Avatar\Facade as Avatar;

class AvatarService
{
    /**
     * Genera un avatar SVG local para el usuario y lo almacena en disk public/avatars/.
     */
    public static function generar(string $nombre): string
    {
        $filename = 'avatars/'.Str::uuid().'.svg';
        $svgContent = Avatar::create($nombre)->toSvg();

        Storage::disk('public')->put($filename, $svgContent);

        return $filename;
    }

    /**
     * Regenera el avatar local de un usuario (RF-USR-08).
     */
    public static function regenerar(string $nombre, ?string $antiguoPath = null): string
    {
        if ($antiguoPath && Storage::disk('public')->exists($antiguoPath)) {
            Storage::disk('public')->delete($antiguoPath);
        }

        return self::generar($nombre);
    }
}
