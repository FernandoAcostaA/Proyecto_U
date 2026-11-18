<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\V1\CitaResource;
use App\Models\Cita;
use App\Models\Recordatorio;
use App\Services\Citas\CitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class PublicNotificacionController extends Controller
{
    public function __construct(protected CitaService $citas) {}

    public function show(string $token): JsonResponse
    {
        $notificacion = $this->buscar($token);

        return response()->json([
            'data' => [
                'tipo' => $notificacion->tipo,
                'estado' => $notificacion->estado,
                'respuesta' => $notificacion->respuesta,
                'respondido_en' => $notificacion->respondido_en?->toIso8601String(),
                'puede_responder' => $this->puedeResponder($notificacion),
            ],
            'cita' => new CitaResource(
                $notificacion->cita->load(['cliente', 'empleado', 'servicio', 'tipoCorte'])
            ),
        ]);
    }

    public function responderPorEnlace(string $token, string $accion): JsonResponse
    {
        return $this->procesar($token, $accion);
    }

    public function responder(Request $request, string $token): JsonResponse
    {
        $data = $request->validate([
            'accion' => ['required', Rule::in([
                Recordatorio::RESPUESTA_CONFIRMAR,
                Recordatorio::RESPUESTA_CANCELAR,
            ])],
        ]);

        return $this->procesar($token, $data['accion']);
    }

    protected function procesar(string $token, string $accion): JsonResponse
    {
        $notificacion = $this->buscar($token);
        $cita = $notificacion->cita;

        if ($notificacion->respondido_en) {
            throw ValidationException::withMessages([
                'token' => 'Esta notificacion ya fue respondida.',
            ]);
        }

        if (! $this->puedeResponder($notificacion)) {
            throw ValidationException::withMessages([
                'token' => 'La cita ya no admite respuestas.',
            ]);
        }

        if ($accion === Recordatorio::RESPUESTA_CANCELAR) {
            $horasMinimas = (int) config('blackhound.cancelacion_horas_minimas', 2);

            if ($cita->fecha_hora_inicio->lt(now()->addHours($horasMinimas))) {
                throw ValidationException::withMessages([
                    'cita' => "Solo se puede cancelar con al menos {$horasMinimas} horas de anticipacion.",
                ]);
            }

            $cita = $this->citas->cambiarEstado($cita, Cita::ESTADO_CANCELADA);
        } elseif ($cita->estado === Cita::ESTADO_PENDIENTE) {
            $cita = $this->citas->cambiarEstado($cita, Cita::ESTADO_CONFIRMADA);
        }

        $notificacion->update([
            'respuesta' => $accion,
            'respondido_en' => now(),
        ]);

        return response()->json([
            'message' => $accion === Recordatorio::RESPUESTA_CONFIRMAR
                ? 'Cita confirmada por el cliente.'
                : 'Cita cancelada por el cliente.',
            'data' => new CitaResource($cita->fresh(['cliente', 'empleado', 'servicio', 'tipoCorte'])),
        ]);
    }

    protected function buscar(string $token): Recordatorio
    {
        return Recordatorio::where('respuesta_token', $token)
            ->with(['cita.cliente', 'cita.empleado', 'cita.servicio', 'cita.tipoCorte'])
            ->firstOrFail();
    }

    protected function puedeResponder(Recordatorio $notificacion): bool
    {
        if ($notificacion->respondido_en) {
            return false;
        }

        return in_array($notificacion->cita->estado, [
            Cita::ESTADO_PENDIENTE,
            Cita::ESTADO_CONFIRMADA,
        ], true);
    }
}
