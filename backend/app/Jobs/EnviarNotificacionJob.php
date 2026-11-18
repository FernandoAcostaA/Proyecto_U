<?php

namespace App\Jobs;

use App\Models\Recordatorio;
use App\Services\Notificaciones\NotificacionService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class EnviarNotificacionJob implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly Recordatorio $notificacion,
    ) {}

    public function handle(NotificacionService $notificaciones): void
    {
        $notificaciones->enviar($this->notificacion->fresh() ?? $this->notificacion);
    }
}
