<?php

namespace App\Services\Caja;

use App\Models\CierreCaja;
use App\Models\Usuario;
use App\Services\Reportes\ReporteService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CierreCajaService
{
    public function __construct(protected ReporteService $reportes) {}

    public function resumen(Carbon $fecha): array
    {
        $desde = $fecha->copy()->startOfDay();
        $hasta = $fecha->copy()->endOfDay();

        $ventas = $this->reportes->ventas($desde, $hasta);
        $comisiones = $this->reportes->comisiones($desde, $hasta);

        return [
            'fecha' => $fecha->toDateString(),
            'cantidad_pagos' => $ventas['resumen']['pagos_cobrados'],
            'cantidad_citas' => $comisiones['resumen']['cantidad_citas'],
            'total_cobrado' => $ventas['resumen']['total_cobrado'],
            'total_comisiones' => $comisiones['resumen']['monto_total'],
            'total_barberia' => $comisiones['resumen']['monto_barberia'] ?? 0,
            'total_pendiente_comisiones' => $comisiones['resumen']['pendiente'],
            'por_metodo' => $ventas['por_metodo'],
            'por_empleado' => $comisiones['por_empleado'],
        ];
    }

    public function cerrar(Carbon $fecha, ?Usuario $usuario = null, ?string $notas = null): CierreCaja
    {
        return DB::transaction(function () use ($fecha, $usuario, $notas) {
            $fecha = $fecha->copy()->startOfDay();

            if (CierreCaja::whereDate('fecha', $fecha->toDateString())->exists()) {
                throw ValidationException::withMessages([
                    'fecha' => 'La caja de esa fecha ya fue cerrada.',
                ]);
            }

            $resumen = $this->resumen($fecha);

            return CierreCaja::create([
                'fecha' => $fecha->toDateString(),
                'cantidad_pagos' => $resumen['cantidad_pagos'],
                'cantidad_citas' => $resumen['cantidad_citas'],
                'total_cobrado' => $resumen['total_cobrado'],
                'total_comisiones' => $resumen['total_comisiones'],
                'total_barberia' => $resumen['total_barberia'],
                'total_pendiente_comisiones' => $resumen['total_pendiente_comisiones'],
                'detalle' => [
                    'por_metodo' => $resumen['por_metodo'],
                    'por_empleado' => $resumen['por_empleado'],
                ],
                'notas' => $notas,
                'cerrado_por' => $usuario?->id,
            ]);
        });
    }
}
