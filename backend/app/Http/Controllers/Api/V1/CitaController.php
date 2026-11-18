<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EstadoCitaRequest;
use App\Http\Requests\Api\V1\ReprogramarCitaRequest;
use App\Http\Requests\Api\V1\StoreCitaRequest;
use App\Http\Resources\V1\CitaResource;
use App\Models\Cita;
use App\Services\Citas\CitaService;
use Illuminate\Http\JsonResponse;

class CitaController extends Controller
{
    public function __construct(protected CitaService $service) {}

    public function index(): JsonResponse
    {
        $citas = Cita::with(['cliente', 'empleado', 'servicio', 'tipoCorte', 'calificacion'])
            ->latest()
            ->paginate();

        return CitaResource::collection($citas)->response();
    }

    /**
     * Creacion de cita con validacion de capacitacion, horario y solapamiento.
     */
    public function store(StoreCitaRequest $request): JsonResponse
    {
        $cita = $this->service->crear($request->validated());

        return (new CitaResource($cita))->response()->setStatusCode(201);
    }

    public function show(Cita $cita): JsonResponse
    {
        return (new CitaResource(
            $cita->load(['cliente', 'empleado', 'servicio', 'tipoCorte', 'comision', 'pago', 'calificacion'])
        ))->response();
    }

    public function cambiarEstado(EstadoCitaRequest $request, Cita $cita): JsonResponse
    {
        $cita = $this->service->cambiarEstado($cita, $request->input('estado'));

        return (new CitaResource($cita))->response();
    }

    public function reprogramar(ReprogramarCitaRequest $request, Cita $cita): JsonResponse
    {
        $cita = $this->service->reprogramar($cita, $request->validated());

        return (new CitaResource($cita))->response();
    }

    /**
     * Marca la cita como completada: comision + descuento de stock.
     */
    public function completar(Cita $cita): JsonResponse
    {
        $cita = $this->service->completar($cita);

        return (new CitaResource($cita))->response();
    }

    public function destroy(Cita $cita): JsonResponse
    {
        $cita->delete();

        return response()->json(['message' => 'Cita eliminada.']);
    }
}
