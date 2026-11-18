<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\CalificacionPublicaRequest;
use App\Http\Requests\Api\V1\StoreCalificacionRequest;
use App\Http\Resources\V1\CalificacionResource;
use App\Models\Calificacion;
use App\Models\Cita;
use App\Services\Calificaciones\CalificacionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CalificacionController extends Controller
{
    public function __construct(protected CalificacionService $service) {}

    public function index(Request $request): JsonResponse
    {
        $calificaciones = Calificacion::with(['empleado', 'servicio'])
            ->when($request->filled('empleado_id'), fn ($query) => $query->where('empleado_id', $request->integer('empleado_id')))
            ->when($request->filled('servicio_id'), fn ($query) => $query->where('servicio_id', $request->integer('servicio_id')))
            ->latest()
            ->paginate();

        return CalificacionResource::collection($calificaciones)->response();
    }

    public function store(StoreCalificacionRequest $request): JsonResponse
    {
        $cita = Cita::findOrFail($request->integer('cita_id'));

        $calificacion = $this->service->registrar(
            $cita,
            $request->validated(),
            Calificacion::ORIGEN_PANEL
        );

        return (new CalificacionResource($calificacion))->response()->setStatusCode(201);
    }

    /**
     * Datos de la cita para el formulario publico de calificacion.
     */
    public function publicaInfo(string $token): JsonResponse
    {
        $cita = Cita::with(['empleado', 'servicio', 'tipoCorte', 'calificacion'])
            ->where('calificacion_token', $token)
            ->firstOrFail();

        return response()->json([
            'cita_id' => $cita->id,
            'fecha_hora_inicio' => $cita->fecha_hora_inicio?->toIso8601String(),
            'empleado' => $cita->empleado?->nombre,
            'servicio' => $cita->servicio?->nombre,
            'tipo_corte' => $cita->tipoCorte?->nombre,
            'ya_calificada' => $cita->calificacion !== null,
            'calificacion' => $cita->calificacion
                ? new CalificacionResource($cita->calificacion)
                : null,
        ]);
    }

    /**
     * Calificacion enviada por el cliente sin iniciar sesion (enlace con token).
     */
    public function publicaStore(CalificacionPublicaRequest $request, string $token): JsonResponse
    {
        $cita = Cita::where('calificacion_token', $token)->firstOrFail();

        $calificacion = $this->service->registrar(
            $cita,
            $request->validated(),
            Calificacion::ORIGEN_CLIENTE
        );

        return (new CalificacionResource($calificacion))->response()->setStatusCode(201);
    }
}
