<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Spatie\Activitylog\Contracts\Activity;
use Spatie\Activitylog\Models\Concerns\LogsActivity;
use Spatie\Activitylog\Support\LogOptions;

trait Auditable
{
    use LogsActivity;

    public function getActivitylogOptions(): LogOptions
    {
        $logName = strtolower(class_basename($this));

        return LogOptions::defaults()
            ->useLogName($logName)
            ->logFillable()
            ->logExcept(['password', 'remember_token', 'invitacion_token'])
            ->logOnlyDirty()
            ->dontLogEmptyChanges();
    }

    /**
     * Intercepta la creación del registro en activity_log para añadir sucursal_id, IP y user agent (DE-12, ERS 8.1).
     */
    public function beforeActivityLogged(Model $activity, string $eventName): void
    {
        $ip = request()->ip();
        $userAgent = request()->userAgent();
        $sucursalId = session('sucursal_id')
            ?? (property_exists($this, 'sucursal_id') ? $this->sucursal_id : null);

        $activity->setAttribute('ip', $ip);
        $activity->setAttribute('user_agent', $userAgent);
        $activity->setAttribute('sucursal_id', $sucursalId);
        $activity->setAttribute('created_at', now());
    }

    public function tapActivity(Activity $activity, string $eventName): void
    {
        if ($activity instanceof Model) {
            $this->beforeActivityLogged($activity, $eventName);
        }
    }
}
