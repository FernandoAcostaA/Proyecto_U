<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\EstadoPagoRequest;
use App\Http\Requests\Api\V1\StorePagoRequest;
use App\Http\Resources\V1\PagoResource;
use App\Models\Pago;
use App\Services\Pagos\PagoService;
use Illuminate\Http\JsonResponse;

class PagoController extends Controller
{
    public function __construct(protected PagoService $service) {}

    public function index(): JsonResponse
    {
        $pagos = Pago::with('cita')->latest()->paginate();

        return PagoResource::collection($pagos)->response();
    }

    /**
     * Registro manual del pago de una cita completada.
     */
    public function store(StorePagoRequest $request): JsonResponse
    {
        $pago = $this->service->registrar($request->validated());

        return (new PagoResource($pago->load('cita')))->response()->setStatusCode(201);
    }

    public function show(Pago $pago): JsonResponse
    {
        return (new PagoResource($pago->load('cita')))->response();
    }

    /**
     * Cambia el estado del pago (p. ej. marcar pagado o reembolsado).
     */
    public function cambiarEstado(EstadoPagoRequest $request, Pago $pago): JsonResponse
    {
        $pago = $this->service->cambiarEstado(
            $pago,
            $request->input('estado'),
            $request->input('metodo')
        );

        return (new PagoResource($pago->load('cita')))->response();
    }
}
