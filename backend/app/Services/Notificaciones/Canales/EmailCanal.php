<?php

namespace App\Services\Notificaciones\Canales;

use App\Models\Recordatorio;
use Illuminate\Support\Facades\Mail;

class EmailCanal implements CanalNotificacion
{
    public function enviar(
        Recordatorio $notificacion,
        string $destino,
        string $asunto,
        string $mensaje,
    ): ResultadoEnvio {
        Mail::raw($mensaje, function ($mail) use ($destino, $asunto) {
            $mail->to($destino)->subject($asunto);
        });

        return new ResultadoEnvio(
            proveedor: (string) config('mail.default', 'mail'),
            costo: (float) config('blackhound.notificacion_costos.email', 0),
        );
    }
}
