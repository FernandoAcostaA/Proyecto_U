<?php

namespace App\Services\Reportes;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\MovimientoStock;
use App\Models\Pago;
use App\Models\Producto;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Consultas agregadas del negocio. Todos los reportes reciben un rango
 * de fechas (default: últimos 30 días) y opcionalmente filtros por
 * empleado/servicio. La agregación se aplica sobre citas, pagos y comisiones.
 */
class ReporteService
{
    public const ESTADOS_VIGENTES = [
        Cita::ESTADO_PENDIENTE,
        Cita::ESTADO_CONFIRMADA,
        Cita::ESTADO_COMPLETADA,
    ];

    /**
     * Ventas "ambos": monto imputado (citas completadas, precio del servicio
     * o pago registrado) y monto realmente cobrado (pagos en estado 'pagado').
     */
    public function ventas(Carbon $desde, Carbon $hasta, ?int $empleadoId = null, ?int $servicioId = null): array
    {
        $base = $this->citasBase($desde, $hasta, $empleadoId, $servicioId);

        $porDia = $base->clone()
            ->where('citas.estado', Cita::ESTADO_COMPLETADA)
            ->leftJoin('pagos as p', 'p.cita_id', '=', 'citas.id')
            ->join('servicios as s', 's.id', '=', 'citas.servicio_id')
            ->selectRaw('DATE(citas.fecha_hora_inicio) as fecha')
            ->selectRaw('COUNT(*) as cantidad_citas')
            ->selectRaw('ROUND(COALESCE(SUM(COALESCE(p.monto, s.precio)), 0), 2) as total_imputado')
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get();

        $resumenImputado = $porDia->reduce(
            fn (array $acc, $fila) => [
                'citas_completadas' => $acc['citas_completadas'] + $fila->cantidad_citas,
                'total_imputado' => $acc['total_imputado'] + (float) $fila->total_imputado,
            ],
            ['citas_completadas' => 0, 'total_imputado' => 0.0]
        );

        $pagos = Pago::where('estado', Pago::ESTADO_PAGADO)
            ->whereBetween('pagado_en', [$desde, $hasta])
            ->get();

        $resumenCobrado = [
            'pagos_cobrados' => $pagos->count(),
            'total_cobrado' => round($pagos->sum('monto'), 2),
        ];

        $porMetodo = $pagos
            ->groupBy('metodo')
            ->map(fn (Collection $g) => [
                'metodo' => $g->first()->metodo,
                'cantidad' => $g->count(),
                'total' => round($g->sum('monto'), 2),
            ])
            ->sortByDesc('total')
            ->values()
            ->all();

        return [
            'tipo' => 'ventas',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filtros' => $this->filtros($empleadoId, $servicioId),
            'resumen' => [
                'citas_completadas' => $resumenImputado['citas_completadas'],
                'total_imputado' => round($resumenImputado['total_imputado'], 2),
                'pagos_cobrados' => $resumenCobrado['pagos_cobrados'],
                'total_cobrado' => $resumenCobrado['total_cobrado'],
            ],
            'por_dia' => $porDia->map(fn ($fila) => [
                'fecha' => $fila->fecha,
                'cantidad_citas' => (int) $fila->cantidad_citas,
                'total_imputado' => (float) $fila->total_imputado,
            ])->values()->all(),
            'por_metodo' => $porMetodo,
        ];
    }

    /**
     * Comisiones acumuladas por empleado, distinguiendo pendientes y pagadas.
     */
    public function comisiones(Carbon $desde, Carbon $hasta, ?int $empleadoId = null, ?int $servicioId = null): array
    {
        $base = $this->citasBase($desde, $hasta, $empleadoId, $servicioId);

        $porEmpleado = $base->clone()
            ->where('citas.estado', Cita::ESTADO_COMPLETADA)
            ->join('comisiones as c', 'c.cita_id', '=', 'citas.id')
            ->join('empleados as e', 'e.id', '=', 'c.empleado_id')
            ->selectRaw('e.id as empleado_id, e.nombre as empleado')
            ->selectRaw('COUNT(*) as cantidad_citas')
            ->selectRaw('ROUND(COALESCE(SUM(c.monto), 0), 2) as monto_total')
            ->selectRaw('ROUND(COALESCE(SUM(c.monto_barberia), 0), 2) as monto_barberia')
            ->selectRaw('ROUND(COALESCE(SUM(c.monto + c.monto_barberia), 0), 2) as total_generado')
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN c.estado_pago = 'pendiente' THEN c.monto ELSE 0 END), 0), 2) as pendiente")
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN c.estado_pago = 'pagado' THEN c.monto ELSE 0 END), 0), 2) as pagado")
            ->groupBy('e.id', 'e.nombre')
            ->orderByDesc('monto_total')
            ->get();

        $totales = [
            'cantidad_citas' => 0,
            'monto_total' => 0.0,
            'monto_barberia' => 0.0,
            'total_generado' => 0.0,
            'pendiente' => 0.0,
            'pagado' => 0.0,
        ];

        foreach ($porEmpleado as $fila) {
            $totales['cantidad_citas'] += $fila->cantidad_citas;
            $totales['monto_total'] += (float) $fila->monto_total;
            $totales['monto_barberia'] += (float) $fila->monto_barberia;
            $totales['total_generado'] += (float) $fila->total_generado;
            $totales['pendiente'] += (float) $fila->pendiente;
            $totales['pagado'] += (float) $fila->pagado;
        }

        return [
            'tipo' => 'comisiones',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filtros' => $this->filtros($empleadoId, $servicioId),
            'resumen' => [
                'cantidad_citas' => $totales['cantidad_citas'],
                'monto_total' => round($totales['monto_total'], 2),
                'monto_barberia' => round($totales['monto_barberia'], 2),
                'total_generado' => round($totales['total_generado'], 2),
                'pendiente' => round($totales['pendiente'], 2),
                'pagado' => round($totales['pagado'], 2),
            ],
            'por_empleado' => $porEmpleado->map(fn ($fila) => [
                'empleado_id' => (int) $fila->empleado_id,
                'empleado' => $fila->empleado,
                'cantidad_citas' => (int) $fila->cantidad_citas,
                'monto_total' => (float) $fila->monto_total,
                'monto_barberia' => (float) $fila->monto_barberia,
                'total_generado' => (float) $fila->total_generado,
                'pendiente' => (float) $fila->pendiente,
                'pagado' => (float) $fila->pagado,
            ])->values()->all(),
        ];
    }

    /**
     * Ranking de servicios más solicitados (por cantidad de citas reservadas).
     */
    public function serviciosMasSolicitados(Carbon $desde, Carbon $hasta, ?int $empleadoId = null, ?int $servicioId = null, int $limite = 10): array
    {
        $base = $this->citasBase($desde, $hasta, $empleadoId, $servicioId);

        $filas = $base->clone()
            ->whereIn('citas.estado', self::ESTADOS_VIGENTES)
            ->join('servicios as s', 's.id', '=', 'citas.servicio_id')
            ->selectRaw('s.id as servicio_id, s.nombre as servicio')
            ->selectRaw('COUNT(*) as cantidad')
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN citas.estado = 'completada' THEN s.precio ELSE 0 END), 0), 2) as ingresos")
            ->groupBy('s.id', 's.nombre')
            ->orderByDesc('cantidad')
            ->limit($limite)
            ->get();

        $total = $filas->sum('cantidad');

        return [
            'tipo' => 'servicios-mas-solicitados',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filtros' => $this->filtros($empleadoId, $servicioId),
            'total_solicitudes' => $total,
            'top' => $filas->map(fn ($fila) => [
                'servicio_id' => (int) $fila->servicio_id,
                'servicio' => $fila->servicio,
                'cantidad' => (int) $fila->cantidad,
                'porcentaje' => $total > 0 ? round($fila->cantidad / $total * 100, 2) : 0,
                'ingresos' => (float) $fila->ingresos,
            ])->values()->all(),
        ];
    }

    /**
     * Ranking de clientes frecuentes: visitas, última visita y gasto acumulado.
     */
    public function clientesFrecuentes(Carbon $desde, Carbon $hasta, ?int $empleadoId = null, ?int $servicioId = null, int $limite = 10): array
    {
        $base = $this->citasBase($desde, $hasta, $empleadoId, $servicioId);

        $filas = $base->clone()
            ->whereIn('citas.estado', self::ESTADOS_VIGENTES)
            ->join('clientes as cl', 'cl.id', '=', 'citas.cliente_id')
            ->join('servicios as s', 's.id', '=', 'citas.servicio_id')
            ->selectRaw('cl.id as cliente_id, cl.nombre as cliente, cl.telefono')
            ->selectRaw('COUNT(DISTINCT citas.id) as cantidad_visitas')
            ->selectRaw('MAX(citas.fecha_hora_inicio) as ultima_visita')
            ->selectRaw("ROUND(COALESCE(SUM(CASE WHEN citas.estado = 'completada' THEN s.precio ELSE 0 END), 0), 2) as total_gastado")
            ->groupBy('cl.id', 'cl.nombre', 'cl.telefono')
            ->orderByDesc('cantidad_visitas')
            ->limit($limite)
            ->get();

        return [
            'tipo' => 'clientes-frecuentes',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filtros' => $this->filtros($empleadoId, $servicioId),
            'top' => $filas->map(fn ($fila) => [
                'cliente_id' => (int) $fila->cliente_id,
                'cliente' => $fila->cliente,
                'telefono' => $fila->telefono,
                'cantidad_visitas' => (int) $fila->cantidad_visitas,
                'ultima_visita' => $fila->ultima_visita,
                'total_gastado' => (float) $fila->total_gastado,
            ])->values()->all(),
        ];
    }

    /**
     * Reporte de citas, inasistencias y ocupacion por barbero.
     */
    public function citas(Carbon $desde, Carbon $hasta, ?int $empleadoId = null): array
    {
        $filas = Cita::query()
            ->whereBetween('citas.fecha_hora_inicio', [$desde, $hasta])
            ->when($empleadoId, fn (Builder $q) => $q->where('citas.empleado_id', $empleadoId))
            ->join('empleados as e', 'e.id', '=', 'citas.empleado_id')
            ->selectRaw('e.id as empleado_id, e.nombre as empleado')
            ->selectRaw('COUNT(*) as total_citas')
            ->selectRaw("SUM(CASE WHEN citas.estado = 'pendiente' THEN 1 ELSE 0 END) as pendientes")
            ->selectRaw("SUM(CASE WHEN citas.estado = 'confirmada' THEN 1 ELSE 0 END) as confirmadas")
            ->selectRaw("SUM(CASE WHEN citas.estado = 'completada' THEN 1 ELSE 0 END) as completadas")
            ->selectRaw("SUM(CASE WHEN citas.estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas")
            ->selectRaw("SUM(CASE WHEN citas.estado = 'no_asistio' THEN 1 ELSE 0 END) as no_asistio")
            ->groupBy('e.id', 'e.nombre')
            ->orderByDesc('total_citas')
            ->get();

        $ocupacion = collect($this->ocupacion($desde, $hasta, $empleadoId)['por_empleado'])
            ->keyBy('empleado_id');

        $detalle = $filas->map(function ($fila) use ($ocupacion) {
            $total = (int) $fila->total_citas;
            $noAsistio = (int) $fila->no_asistio;
            $canceladas = (int) $fila->canceladas;
            $ocupacionEmpleado = $ocupacion->get((int) $fila->empleado_id, []);

            return [
                'empleado_id' => (int) $fila->empleado_id,
                'empleado' => $fila->empleado,
                'total_citas' => $total,
                'pendientes' => (int) $fila->pendientes,
                'confirmadas' => (int) $fila->confirmadas,
                'completadas' => (int) $fila->completadas,
                'canceladas' => $canceladas,
                'no_asistio' => $noAsistio,
                'tasa_inasistencia' => $total > 0 ? round($noAsistio / $total * 100, 2) : 0,
                'tasa_cancelacion' => $total > 0 ? round($canceladas / $total * 100, 2) : 0,
                'capacidad_horas' => $ocupacionEmpleado['capacidad_horas'] ?? 0,
                'horas_ocupadas' => $ocupacionEmpleado['horas_ocupadas'] ?? 0,
                'tasa_ocupacion' => $ocupacionEmpleado['tasa_ocupacion'] ?? 0,
            ];
        })->values()->all();

        $resumen = [
            'total_citas' => array_sum(array_column($detalle, 'total_citas')),
            'pendientes' => array_sum(array_column($detalle, 'pendientes')),
            'confirmadas' => array_sum(array_column($detalle, 'confirmadas')),
            'completadas' => array_sum(array_column($detalle, 'completadas')),
            'canceladas' => array_sum(array_column($detalle, 'canceladas')),
            'no_asistio' => array_sum(array_column($detalle, 'no_asistio')),
        ];

        $resumen['tasa_inasistencia'] = $resumen['total_citas'] > 0
            ? round($resumen['no_asistio'] / $resumen['total_citas'] * 100, 2)
            : 0;
        $resumen['tasa_cancelacion'] = $resumen['total_citas'] > 0
            ? round($resumen['canceladas'] / $resumen['total_citas'] * 100, 2)
            : 0;

        return [
            'tipo' => 'citas',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filtros' => $this->filtros($empleadoId, null),
            'resumen' => $resumen,
            'por_empleado' => $detalle,
        ];
    }

    /**
     * Ocupación: horas ocupadas vs. capacidad (turnos de horarios por
     * empleado y por día del periodo).
     */
    public function ocupacion(Carbon $desde, Carbon $hasta, ?int $empleadoId = null): array
    {
        $empleados = Empleado::query()
            ->with('horariosDisponibilidad')
            ->when($empleadoId, fn (Builder $q) => $q->where('id', $empleadoId))
            ->get();

        $porEmpleado = $empleados->map(function (Empleado $empleado) use ($desde, $hasta) {
            $capacidad = $this->capacidadHoras($empleado, $desde, $hasta);

            $citas = Cita::where('empleado_id', $empleado->id)
                ->whereIn('estado', self::ESTADOS_VIGENTES)
                ->whereBetween('fecha_hora_inicio', [$desde, $hasta])
                ->selectRaw('COUNT(*) as cantidad_citas')
                ->selectRaw('COALESCE(SUM(EXTRACT(EPOCH FROM (fecha_hora_fin - fecha_hora_inicio)) / 3600.0), 0) as horas_ocupadas')
                ->first();

            $horasOcupadas = round((float) $citas->horas_ocupadas, 2);

            return [
                'empleado_id' => $empleado->id,
                'empleado' => $empleado->nombre,
                'capacidad_horas' => round($capacidad, 2),
                'horas_ocupadas' => $horasOcupadas,
                'cantidad_citas' => (int) $citas->cantidad_citas,
                'tasa_ocupacion' => $capacidad > 0 ? round($horasOcupadas / $capacidad * 100, 2) : 0,
            ];
        });

        $totalCapacidad = $porEmpleado->sum('capacidad_horas');
        $totalOcupadas = $porEmpleado->sum('horas_ocupadas');

        return [
            'tipo' => 'ocupacion',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'filtros' => $this->filtros($empleadoId, null),
            'resumen' => [
                'capacidad_horas' => round($totalCapacidad, 2),
                'horas_ocupadas' => round($totalOcupadas, 2),
                'tasa_ocupacion' => $totalCapacidad > 0 ? round($totalOcupadas / $totalCapacidad * 100, 2) : 0,
            ],
            'por_empleado' => $porEmpleado->values()->all(),
        ];
    }

    /**
     * Estado de inventario y movimiento de stock en el periodo.
     */
    public function stock(Carbon $desde, Carbon $hasta): array
    {
        $productos = Producto::query()->orderBy('stock_actual')->get();
        $productoIds = $productos->pluck('id');

        $movimientos = MovimientoStock::whereIn('producto_id', $productoIds)
            ->whereBetween('created_at', [$desde, $hasta])
            ->get()
            ->groupBy('producto_id');

        $entradas = MovimientoStock::whereIn('producto_id', $productoIds)
            ->where('tipo', 'entrada')
            ->whereBetween('created_at', [$desde, $hasta])
            ->sum('cantidad');

        $salidas = MovimientoStock::whereIn('producto_id', $productoIds)
            ->where('tipo', 'salida')
            ->whereBetween('created_at', [$desde, $hasta])
            ->sum('cantidad');

        $detalle = $productos->map(function (Producto $producto) use ($movimientos) {
            $grupo = $movimientos->get($producto->id, collect());

            return [
                'producto_id' => $producto->id,
                'producto' => $producto->nombre,
                'stock_actual' => (int) $producto->stock_actual,
                'stock_minimo' => (int) $producto->stock_minimo,
                'estado' => $this->estadoStock($producto->stock_actual, $producto->stock_minimo),
                'salidas_periodo' => $grupo
                    ->where('tipo', 'salida')
                    ->sum('cantidad'),
            ];
        });

        return [
            'tipo' => 'stock',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'resumen' => [
                'productos' => $productos->count(),
                'unidades_totales' => (int) $productos->sum('stock_actual'),
                'sin_existencias' => $productos->where('stock_actual', 0)->count(),
                'bajo_stock' => $productos->filter(
                    fn (Producto $p) => $p->stock_actual <= $p->stock_minimo
                )->count(),
                'entradas_periodo' => (int) $entradas,
                'salidas_periodo' => (int) $salidas,
            ],
            'detalle' => $detalle->values()->all(),
        ];
    }

    /**
     * KPIs de un vistazo para el panel principal.
     */
    public function dashboard(Carbon $desde, Carbon $hasta): array
    {
        $hoyInicio = Carbon::today()->startOfDay();
        $hoyFin = Carbon::today()->endOfDay();

        $ventas = $this->ventas($desde, $hasta);
        $comisiones = $this->comisiones($desde, $hasta);

        $citasHoy = Cita::whereBetween('fecha_hora_inicio', [$hoyInicio, $hoyFin])
            ->whereIn('estado', self::ESTADOS_VIGENTES)
            ->count();

        $nuevosClientes = Cliente::whereBetween('created_at', [$desde, $hasta])->count();

        $bajoStock = Producto::whereColumn('stock_actual', '<=', 'stock_minimo')->count();

        return [
            'tipo' => 'dashboard',
            'desde' => $desde->toDateString(),
            'hasta' => $hasta->toDateString(),
            'citas' => [
                'pendientes' => Cita::where('estado', Cita::ESTADO_PENDIENTE)->count(),
                'hoy' => $citasHoy,
                'completadas_periodo' => Cita::where('estado', Cita::ESTADO_COMPLETADA)
                    ->whereBetween('fecha_hora_inicio', [$desde, $hasta])
                    ->count(),
            ],
            'ventas' => [
                'imputado_periodo' => $ventas['resumen']['total_imputado'],
                'cobrado_periodo' => $ventas['resumen']['total_cobrado'],
            ],
            'comisiones' => [
                'pendiente' => $comisiones['resumen']['pendiente'],
                'pagado' => $comisiones['resumen']['pagado'],
            ],
            'clientes' => [
                'nuevos_periodo' => $nuevosClientes,
            ],
            'inventario' => [
                'bajo_stock' => $bajoStock,
            ],
        ];
    }

    protected function citasBase(Carbon $desde, Carbon $hasta, ?int $empleadoId, ?int $servicioId): Builder
    {
        return Cita::query()
            ->whereBetween('citas.fecha_hora_inicio', [$desde, $hasta])
            ->when($empleadoId, fn (Builder $q) => $q->where('citas.empleado_id', $empleadoId))
            ->when($servicioId, fn (Builder $q) => $q->where('citas.servicio_id', $servicioId));
    }

    protected function filtros(?int $empleadoId, ?int $servicioId): array
    {
        return [
            'empleado_id' => $empleadoId,
            'servicio_id' => $servicioId,
        ];
    }

    protected function capacidadHoras(Empleado $empleado, Carbon $desde, Carbon $hasta): float
    {
        $capacidad = 0.0;

        foreach ($empleado->horariosDisponibilidad as $horario) {
            $inicio = Carbon::parse($horario->hora_inicio);
            $fin = Carbon::parse($horario->hora_fin);
            $horasDia = $inicio->diffInMinutes($fin) / 60;
            $ocurrencias = $this->ocurrenciasDeDia($desde, $hasta, (int) $horario->dia_semana);

            $capacidad += $horasDia * $ocurrencias;
        }

        return $capacidad;
    }

    protected function ocurrenciasDeDia(Carbon $desde, Carbon $hasta, int $diaSemana): int
    {
        $ocurrencias = 0;
        $cursor = $desde->copy()->startOfDay();

        while ($cursor->lte($hasta)) {
            if ((int) $cursor->dayOfWeek === $diaSemana) {
                $ocurrencias++;
            }
            $cursor->addDay();
        }

        return $ocurrencias;
    }

    protected function estadoStock(int $stockActual, int $stockMinimo): string
    {
        return match (true) {
            $stockActual <= 0 => 'sin_existencias',
            $stockActual <= $stockMinimo => 'bajo_stock',
            default => 'disponible',
        };
    }
}
