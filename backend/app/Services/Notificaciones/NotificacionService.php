<?php

namespace App\Services\Notificaciones;

use App\Jobs\EnviarNotificacionJob;
use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Recordatorio;
use App\Services\Notificaciones\Canales\CanalNotificacion;
use App\Services\Notificaciones\Canales\EmailCanal;
use App\Services\Notificaciones\Canales\ResultadoEnvio;
use App\Services\Notificaciones\Canales\WhatsAppCanal;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

class NotificacionService
{
    public function programarConfirmacion(Cita $cita): Recordatorio
    {
        return $this->crearSiNoExiste($cita, Recordatorio::TIPO_CONFIRMACION);
    }

    public function programarRecordatorio(Cita $cita): Recordatorio
    {
        return $this->crearSiNoExiste(
            $cita,
            Recordatorio::TIPO_RECORDATORIO,
            $cita->fecha_hora_inicio->copy()->subHours($this->horasRecordatorio($cita)),
        );
    }

    public function programarVerificacion(Cita $cita, string $url): Recordatorio
    {
        return $this->crearSiNoExiste(
            $cita,
            Recordatorio::TIPO_VERIFICACION,
            metadata: ['url_verificacion' => $url],
        );
    }

    public function enviar(Recordatorio $notificacion): Recordatorio
    {
        if ($notificacion->estado !== Recordatorio::ESTADO_PENDIENTE) {
            return $notificacion;
        }

        $notificacion->loadMissing('cita.cliente', 'cita.empleado', 'cita.servicio');
        $cita = $notificacion->cita;

        if (! $cita) {
            return $this->marcarError($notificacion, 'La notificacion no tiene una cita asociada.');
        }

        [$canal, $destino] = $this->resolverCanal($cita->cliente, $notificacion->canal);

        if (! $destino) {
            return $this->marcarError($notificacion, 'El cliente no tiene un destino de contacto configurado.');
        }

        [$asunto, $mensaje] = $this->construirMensaje($notificacion);

        try {
            $resultado = $this->canal($canal)->enviar($notificacion, $destino, $asunto, $mensaje);

            $notificacion->update([
                'canal' => $canal,
                'destino' => $destino,
                'proveedor' => $resultado->proveedor,
                'id_externo' => $resultado->idExterno,
                'costo_estimado' => $resultado->costo,
                'asunto' => $asunto,
                'mensaje' => $mensaje,
                'estado' => Recordatorio::ESTADO_ENVIADO,
                'fecha_envio_real' => now(),
                'error' => null,
            ]);
        } catch (Throwable $e) {
            Log::error('No se pudo enviar la notificacion', [
                'recordatorio_id' => $notificacion->id,
                'canal' => $canal,
                'error' => $e->getMessage(),
            ]);

            $this->marcarError($notificacion, $e->getMessage());
        }

        return $notificacion->fresh();
    }

    public function cancelarPendientes(Cita $cita): int
    {
        return $cita->recordatorios()
            ->where('estado', Recordatorio::ESTADO_PENDIENTE)
            ->update([
                'estado' => Recordatorio::ESTADO_CANCELADO,
                'error' => 'Cancelada porque la cita ya no esta activa.',
            ]);
    }

    public function horasRecordatorio(Cita $cita): int
    {
        $horas = config('blackhound.recordatorio_horas_antes', 24);

        if (is_callable($horas)) {
            return (int) $horas($cita);
        }

        return (int) $horas;
    }

    protected function crearSiNoExiste(
        Cita $cita,
        string $tipo,
        ?Carbon $programada = null,
        array $metadata = [],
    ): Recordatorio {
        $existente = $cita->recordatorios()
            ->where('tipo', $tipo)
            ->where('estado', '!=', Recordatorio::ESTADO_CANCELADO)
            ->first();

        if ($existente) {
            return $existente;
        }

        $cita->loadMissing('cliente');
        [$canal, $destino] = $this->resolverCanal($cita->cliente);

        $programada ??= now();
        $programada = $programada->isPast() ? now() : $programada;

        $notificacion = Recordatorio::create([
            'cita_id' => $cita->id,
            'tipo' => $tipo,
            'canal' => $canal,
            'destino' => $destino,
            'fecha_envio_programada' => $programada,
            'estado' => Recordatorio::ESTADO_PENDIENTE,
            'respuesta_token' => $tipo === Recordatorio::TIPO_RECORDATORIO ? Str::random(64) : null,
            'metadata' => $metadata === [] ? null : $metadata,
        ]);

        EnviarNotificacionJob::dispatch($notificacion->fresh())
            ->delay($programada);

        return $notificacion;
    }

    /**
     * @return array{0:string, 1:?string}
     */
    protected function resolverCanal(?Cliente $cliente, ?string $canalPreferido = null): array
    {
        $preferido = $canalPreferido ?: (string) config('blackhound.notificacion_canal_default', Recordatorio::CANAL_EMAIL);
        $email = $cliente?->email;
        $telefono = $cliente?->telefono;

        if ($preferido === Recordatorio::CANAL_EMAIL) {
            if ($email) {
                return [Recordatorio::CANAL_EMAIL, $email];
            }

            if ($telefono) {
                return [Recordatorio::CANAL_WHATSAPP, $telefono];
            }
        }

        if ($preferido === Recordatorio::CANAL_WHATSAPP) {
            if ($telefono) {
                return [Recordatorio::CANAL_WHATSAPP, $telefono];
            }

            if ($email) {
                return [Recordatorio::CANAL_EMAIL, $email];
            }
        }

        return [$preferido, null];
    }

    protected function canal(string $canal): CanalNotificacion
    {
        return match ($canal) {
            Recordatorio::CANAL_EMAIL => new EmailCanal,
            Recordatorio::CANAL_WHATSAPP => new WhatsAppCanal,
            default => throw new \InvalidArgumentException("Canal de notificacion no soportado: {$canal}"),
        };
    }

    /**
     * @return array{0:string, 1:string}
     */
    protected function construirMensaje(Recordatorio $notificacion): array
    {
        $cita = $notificacion->cita;
        $cliente = $cita->cliente?->nombre ?? 'cliente';
        $servicio = $cita->servicio?->nombre ?? 'servicio';
        $empleado = $cita->empleado?->nombre ?? 'barbero';
        $fecha = $cita->fecha_hora_inicio?->format('d/m/Y H:i') ?? '';

        return match ($notificacion->tipo) {
            Recordatorio::TIPO_CONFIRMACION => [
                'Cita confirmada - Blackhound',
                "Hola {$cliente}, tu cita de {$servicio} con {$empleado} fue confirmada para el {$fecha}.",
            ],
            Recordatorio::TIPO_RECORDATORIO => [
                'Recordatorio de tu cita - Blackhound',
                $this->mensajeRecordatorio($notificacion, $cliente, $servicio, $empleado, $fecha),
            ],
            Recordatorio::TIPO_VERIFICACION => [
                'Confirma tu reserva - Blackhound',
                "Hola {$cliente}, confirma tu reserva de {$servicio} con {$empleado} para el {$fecha}. ".
                'Enlace: '.($notificacion->metadata['url_verificacion'] ?? ''),
            ],
            default => [
                'Notificacion - Blackhound',
                "Hola {$cliente}, tienes una novedad sobre tu cita de {$servicio} con {$empleado} para el {$fecha}.",
            ],
        };
    }

    protected function mensajeRecordatorio(
        Recordatorio $notificacion,
        string $cliente,
        string $servicio,
        string $empleado,
        string $fecha,
    ): string {
        $base = "Hola {$cliente}, te recordamos tu cita de {$servicio} con {$empleado} para el {$fecha}.";

        if (! $notificacion->respuesta_token) {
            return $base;
        }

        $confirmar = route('publico.citas.notificacion.responder', [
            'token' => $notificacion->respuesta_token,
            'accion' => Recordatorio::RESPUESTA_CONFIRMAR,
        ]);
        $cancelar = route('publico.citas.notificacion.responder', [
            'token' => $notificacion->respuesta_token,
            'accion' => Recordatorio::RESPUESTA_CANCELAR,
        ]);

        return $base." Confirma aqui: {$confirmar} Cancela aqui: {$cancelar}";
    }

    protected function marcarError(Recordatorio $notificacion, string $error): Recordatorio
    {
        $notificacion->update([
            'estado' => Recordatorio::ESTADO_ERROR,
            'error' => $error,
        ]);

        return $notificacion->fresh();
    }
}
