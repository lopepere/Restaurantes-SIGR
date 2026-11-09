<?php

namespace App\Observers;

use App\Events\EspacioActualizado;
use App\Models\Espacio;

class EspacioObserver
{
    /**
     * Handle the Espacio "saved" event.
     */
    public function saved(Espacio $espacio): void
    {
        if ($espacio->wasChanged('estado')) {
            event(new EspacioActualizado($espacio));
        }
    }
}
