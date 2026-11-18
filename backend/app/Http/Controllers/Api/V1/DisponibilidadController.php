<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\DisponibilidadRequest;
use App\Http\Resources\V1\EmpleadoResource;
use App\Http\Resources\V1\ServicioResource;
use App\Services\Citas\DisponibilidadService;
use Illuminate\Http\JsonResponse;

class DisponibilidadController extends Controller
{
    public function __construct(protected DisponibilidadService $service) {}

    /**
     * Empleados capacitados y disponibles para un servicio en una fecha,
     * con sus horarios libres (bloques + turnos).
     */
    public function porDia(DisponibilidadRequest $request): JsonResponse
    {
        $resultado = $this->service->empleadosDisponibles(
            $request->integer('servicio_id'),
            $request->input('fecha'),
        );

        return response()->json([
            'servicio' => new ServicioResource($resultado['servicio']),
            'fecha' => $resultado['fecha'],
            'empleados_disponibles' => $resultado['empleados']->map(
                fn (array $item) => [
                    'empleado' => (new EmpleadoResource($item['empleado']))->resolve(),
                    'horarios_libres' => $item['horarios'],
                ]
            ),
        ]);
    }
}
