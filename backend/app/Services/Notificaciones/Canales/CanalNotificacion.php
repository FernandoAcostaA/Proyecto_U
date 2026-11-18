<?php

namespace App\Services\Notificaciones\Canales;

use App\Models\Recordatorio;

interface CanalNotificacion
{
    public function enviar(
        Recordatorio $notificacion,
        string $destino,
        string $asunto,
        string $mensaje,
    ): ResultadoEnvio;
}
