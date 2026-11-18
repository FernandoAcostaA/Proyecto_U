<?php

namespace App\Services\Notificaciones\Canales;

use App\Models\Recordatorio;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use RuntimeException;

class WhatsAppCanal implements CanalNotificacion
{
    public function enviar(
        Recordatorio $notificacion,
        string $destino,
        string $asunto,
        string $mensaje,
    ): ResultadoEnvio {
        $driver = (string) config('blackhound.whatsapp.driver', 'log');

        if ($driver === 'http') {
            $endpoint = (string) config('blackhound.whatsapp.endpoint');

            if ($endpoint === '') {
                throw new RuntimeException('No se configuro WHATSAPP_ENDPOINT.');
            }

            $response = Http::withToken((string) config('blackhound.whatsapp.token'))
                ->acceptJson()
                ->post($endpoint, [
                    'from' => config('blackhound.whatsapp.from'),
                    'to' => $destino,
                    'text' => $mensaje,
                ])
                ->throw();

            return new ResultadoEnvio(
                proveedor: 'whatsapp_cloud',
                idExterno: $response->json('messages.0.id'),
                costo: (float) config('blackhound.notificacion_costos.whatsapp', 0.05),
            );
        }

        Log::info('[WhatsApp simulado] Notificacion enviada', [
            'recordatorio_id' => $notificacion->id,
            'tipo' => $notificacion->tipo,
            'destino' => $destino,
            'asunto' => $asunto,
            'mensaje' => $mensaje,
        ]);

        return new ResultadoEnvio(
            proveedor: 'simulado',
            idExterno: 'sim-'.Str::uuid(),
            costo: (float) config('blackhound.notificacion_costos.whatsapp', 0.05),
        );
    }
}
