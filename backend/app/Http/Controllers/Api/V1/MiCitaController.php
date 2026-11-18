<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReprogramarCitaRequest;
use App\Http\Resources\V1\CitaResource;
use App\Models\Cita;
use App\Services\Citas\CitaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

/**
 * Citas del cliente autenticado (rol cliente).
 */
class MiCitaController extends Controller
{
    public function __construct(protected CitaService $service) {}

    public function index(Request $request): JsonResponse
    {
        $cliente = $request->user()->cliente;

        if (! $cliente) {
            return response()->json(['data' => []]);
        }

        $citas = $cliente->citas()
            ->with(['empleado', 'servicio', 'tipoCorte', 'calificacion'])
            ->orderByDesc('fecha_hora_inicio')
            ->paginate();

        return CitaResource::collection($citas)->response();
    }

    public function cancelar(Request $request, Cita $cita): JsonResponse
    {
        $cliente = $request->user()->cliente;

        if (! $cliente || $cita->cliente_id !== $cliente->id) {
            abort(403, 'No puedes modificar una cita que no te pertenece.');
        }

        if (! in_array($cita->estado, [Cita::ESTADO_PENDIENTE, Cita::ESTADO_CONFIRMADA], true)) {
            throw ValidationException::withMessages([
                'cita' => 'Solo se pueden cancelar citas pendientes o confirmadas.',
            ]);
        }

        $horasMinimas = (int) config('blackhound.cancelacion_horas_minimas', 2);

        if ($cita->fecha_hora_inicio->lt(now()->addHours($horasMinimas))) {
            throw ValidationException::withMessages([
                'cita' => "Solo se puede cancelar con al menos {$horasMinimas} horas de anticipacion.",
            ]);
        }

        $cita = $this->service->cambiarEstado($cita, Cita::ESTADO_CANCELADA);

        return response()->json([
            'message' => 'Cita cancelada.',
            'data' => new CitaResource($cita),
        ]);
    }

    public function reprogramar(ReprogramarCitaRequest $request, Cita $cita): JsonResponse
    {
        $cliente = $request->user()->cliente;

        if (! $cliente || $cita->cliente_id !== $cliente->id) {
            abort(403, 'No puedes modificar una cita que no te pertenece.');
        }

        $cita = $this->service->reprogramar($cita, $request->validated());

        return response()->json([
            'message' => 'Cita reprogramada.',
            'data' => new CitaResource($cita),
        ]);
    }
}
