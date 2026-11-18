<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\ReporteFechasRequest;
use App\Services\Reportes\ReporteService;
use Illuminate\Http\JsonResponse;

class ReporteController extends Controller
{
    public function __construct(
        protected ReporteService $reportes,
    ) {}

    public function ventas(ReporteFechasRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportes->ventas(
                ...$this->parametros($request)
            )
        );
    }

    public function comisiones(ReporteFechasRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportes->comisiones(
                ...$this->parametros($request)
            )
        );
    }

    public function serviciosMasSolicitados(ReporteFechasRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportes->serviciosMasSolicitados(
                ...$this->parametros($request)
            )
        );
    }

    public function clientesFrecuentes(ReporteFechasRequest $request): JsonResponse
    {
        return response()->json(
            $this->reportes->clientesFrecuentes(
                ...$this->parametros($request)
            )
        );
    }

    public function ocupacion(ReporteFechasRequest $request): JsonResponse
    {
        $rango = $request->rango();

        return response()->json(
            $this->reportes->ocupacion(
                $rango['desde'],
                $rango['hasta'],
                $request->empleadoId(),
            )
        );
    }

    public function citas(ReporteFechasRequest $request): JsonResponse
    {
        $rango = $request->rango();

        return response()->json(
            $this->reportes->citas(
                $rango['desde'],
                $rango['hasta'],
                $request->empleadoId(),
            )
        );
    }

    public function stock(ReporteFechasRequest $request): JsonResponse
    {
        $rango = $request->rango();

        return response()->json(
            $this->reportes->stock($rango['desde'], $rango['hasta'])
        );
    }

    public function dashboard(ReporteFechasRequest $request): JsonResponse
    {
        $rango = $request->rango();

        return response()->json(
            $this->reportes->dashboard($rango['desde'], $rango['hasta'])
        );
    }

    protected function parametros(ReporteFechasRequest $request): array
    {
        $rango = $request->rango();

        return [
            'desde' => $rango['desde'],
            'hasta' => $rango['hasta'],
            'empleadoId' => $request->empleadoId(),
            'servicioId' => $request->servicioId(),
        ];
    }
}
