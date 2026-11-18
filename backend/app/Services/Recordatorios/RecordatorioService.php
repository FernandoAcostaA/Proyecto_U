<?php

namespace App\Services\Recordatorios;

use App\Models\Cita;
use App\Models\Recordatorio;
use App\Services\Notificaciones\NotificacionService;

/**
 * Compatibilidad: la programacion de recordatorios vive en NotificacionService.
 */
class RecordatorioService
{
    public function __construct(protected NotificacionService $notificaciones) {}

    public function programar(Cita $cita): ?Recordatorio
    {
        return $this->notificaciones->programarRecordatorio($cita);
    }
}
