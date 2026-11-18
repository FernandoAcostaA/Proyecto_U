<?php

namespace App\Console\Commands;

use App\Models\Recordatorio;
use App\Services\Notificaciones\NotificacionService;
use Illuminate\Console\Command;

class ProcesarNotificacionesPendientes extends Command
{
    protected $signature = 'notificaciones:procesar {--limit=100 : Maximo de notificaciones a procesar}';

    protected $description = 'Envia las notificaciones pendientes cuya fecha programada ya vencio.';

    public function handle(NotificacionService $notificaciones): int
    {
        $limite = (int) $this->option('limit');
        $procesadas = 0;

        Recordatorio::pendientes()
            ->orderBy('fecha_envio_programada')
            ->limit($limite)
            ->get()
            ->each(function (Recordatorio $notificacion) use ($notificaciones, &$procesadas) {
                $notificaciones->enviar($notificacion);
                $procesadas++;
            });

        $this->info("Notificaciones procesadas: {$procesadas}");

        return self::SUCCESS;
    }
}
