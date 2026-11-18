<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreReservaPublicaRequest;
use App\Http\Resources\V1\CitaResource;
use App\Services\Reservas\ReservaPublicaService;
use Illuminate\Http\JsonResponse;

class PublicCitaController extends Controller
{
    public function __construct(protected ReservaPublicaService $service) {}

    public function store(StoreReservaPublicaRequest $request): JsonResponse
    {
        $result = $this->service->crear($request->validated());

        return response()->json([
            'message' => 'Reserva creada. Confirma tu telefono para completarla.',
            'data' => new CitaResource($result['cita']),
            'verificacion' => [
                'token' => $result['token'],
                'url' => $result['url'],
                'expira_en' => $result['expira_en']->toIso8601String(),
            ],
        ], 201);
    }

    public function confirmar(string $token): JsonResponse
    {
        $cita = $this->service->confirmar($token);

        return response()->json([
            'message' => 'Reserva confirmada correctamente.',
            'data' => new CitaResource($cita),
        ]);
    }
}
