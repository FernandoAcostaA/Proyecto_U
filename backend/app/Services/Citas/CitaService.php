<?php

namespace App\Services\Citas;

use App\Models\Cita;
use App\Models\Cliente;
use App\Models\Comision;
use App\Models\Empleado;
use App\Models\Servicio;
use App\Models\ServicioProducto;
use App\Models\TipoCorte;
use App\Services\Notificaciones\NotificacionService;
use App\Services\Pagos\PagoService;
use App\Services\Stock\StockService;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

/**
 * Ciclo de vida de las citas: creacion con validacion de disponibilidad,
 * cambio de estado y cierre (completar) con comision + descuento de stock.
 */
class CitaService
{
    public function __construct(
        protected DisponibilidadService $disponibilidad,
        protected StockService $stock,
        protected NotificacionService $notificaciones,
        protected PagoService $pagos,
    ) {}

    /**
     * Crea la cita validando las tres reglas de negocio.
     */
    public function crear(
        array $data,
        string $estado = Cita::ESTADO_PENDIENTE,
        bool $programarRecordatorio = true,
    ): Cita {
        $servicio = Servicio::findOrFail($data['servicio_id']);
        $empleado = Empleado::findOrFail($data['empleado_id']);
        $cliente = Cliente::findOrFail($data['cliente_id']);
        $tipoCorteId = ! empty($data['tipo_corte_id'])
            ? TipoCorte::findOrFail($data['tipo_corte_id'])->id
            : null;
        $inicio = Carbon::parse($data['fecha_hora_inicio']);

        $errores = $this->disponibilidad->validarEmpleadoParaHorario($empleado, $servicio, $inicio);
        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }

        $cita = Cita::create([
            'cliente_id' => $cliente->id,
            'empleado_id' => $empleado->id,
            'servicio_id' => $servicio->id,
            'tipo_corte_id' => $tipoCorteId,
            'fecha_hora_inicio' => $inicio,
            'fecha_hora_fin' => $inicio->copy()->addMinutes($servicio->duracion_min),
            'estado' => $estado,
        ]);

        if ($programarRecordatorio) {
            $this->notificaciones->programarConfirmacion($cita);
            $this->notificaciones->programarRecordatorio($cita);
        }

        return $cita->load(['cliente', 'empleado', 'servicio', 'tipoCorte']);
    }

    public function cambiarEstado(Cita $cita, string $estado): Cita
    {
        $cita->update(['estado' => $estado]);

        if ($estado === Cita::ESTADO_CONFIRMADA) {
            $this->notificaciones->programarConfirmacion($cita);
            $this->notificaciones->programarRecordatorio($cita);
        }

        if (in_array($estado, [Cita::ESTADO_CANCELADA, Cita::ESTADO_NO_ASISTIO], true)) {
            $this->notificaciones->cancelarPendientes($cita);
        }

        return $cita->load(['cliente', 'empleado', 'servicio', 'tipoCorte', 'calificacion']);
    }

    public function reprogramar(Cita $cita, array $data): Cita
    {
        if (! in_array($cita->estado, [Cita::ESTADO_PENDIENTE, Cita::ESTADO_CONFIRMADA], true)) {
            throw ValidationException::withMessages([
                'cita' => 'Solo se pueden reprogramar citas pendientes o confirmadas.',
            ]);
        }

        $horasMinimas = (int) config('blackhound.reprogramacion_horas_minimas', 2);

        if ($cita->fecha_hora_inicio->lt(now()->addHours($horasMinimas))) {
            throw ValidationException::withMessages([
                'cita' => "Solo se puede reprogramar con al menos {$horasMinimas} horas de anticipacion.",
            ]);
        }

        $servicio = isset($data['servicio_id'])
            ? Servicio::findOrFail($data['servicio_id'])
            : $cita->servicio;
        $empleado = isset($data['empleado_id'])
            ? Empleado::findOrFail($data['empleado_id'])
            : $cita->empleado;
        $inicio = Carbon::parse($data['fecha_hora_inicio']);

        $errores = $this->disponibilidad->validarEmpleadoParaHorario($empleado, $servicio, $inicio, $cita->id);

        if ($errores !== []) {
            throw ValidationException::withMessages($errores);
        }

        DB::transaction(function () use ($cita, $data, $servicio, $empleado, $inicio) {
            $cita->update([
                'empleado_id' => $empleado->id,
                'servicio_id' => $servicio->id,
                'tipo_corte_id' => $data['tipo_corte_id'] ?? $cita->tipo_corte_id,
                'fecha_hora_inicio' => $inicio,
                'fecha_hora_fin' => $inicio->copy()->addMinutes($servicio->duracion_min),
            ]);

            $this->notificaciones->cancelarPendientes($cita);
            $this->notificaciones->programarRecordatorio($cita);
        });

        return $cita->fresh(['cliente', 'empleado', 'servicio', 'tipoCorte', 'calificacion']);
    }

    /**
     * Cierra la cita como completada: genera la comision del servicio, descuenta
     * el stock consumido y registra el pago automatico del total.
     */
    public function completar(Cita $cita): Cita
    {
        if ($cita->estado === Cita::ESTADO_COMPLETADA) {
            throw ValidationException::withMessages([
                'cita' => 'La cita ya fue completada.',
            ]);
        }

        DB::transaction(function () use ($cita) {
            $cita->update([
                'estado' => Cita::ESTADO_COMPLETADA,
                'calificacion_token' => $cita->calificacion_token ?? (string) Str::uuid(),
            ]);

            $this->generarComision($cita);
            $this->descontarStock($cita);
            $this->pagos->registrarAutomatico($cita);
            $this->notificaciones->cancelarPendientes($cita);
        });

        return $cita->load(['cliente', 'empleado', 'servicio', 'tipoCorte', 'comision', 'pago', 'calificacion']);
    }

    protected function generarComision(Cita $cita): void
    {
        $servicio = $cita->servicio;
        $porcentajeBarbero = (float) $servicio->porcentaje_comision;

        if ($porcentajeBarbero <= 0) {
            $porcentajeBarbero = (float) config('blackhound.comision_barbero_porcentaje', 85);
        }

        $porcentajeBarbero = min(100, max(0, $porcentajeBarbero));
        $porcentajeBarberia = round(100 - $porcentajeBarbero, 2);
        $montoBarbero = round($servicio->precio * ($porcentajeBarbero / 100), 2);
        $montoBarberia = round((float) $servicio->precio - $montoBarbero, 2);

        Comision::create([
            'cita_id' => $cita->id,
            'empleado_id' => $cita->empleado_id,
            'monto' => $montoBarbero,
            'porcentaje_barbero' => $porcentajeBarbero,
            'porcentaje_barberia' => $porcentajeBarberia,
            'monto_barberia' => $montoBarberia,
            'estado_pago' => Comision::ESTADO_PENDIENTE,
        ]);
    }

    protected function descontarStock(Cita $cita): void
    {
        ServicioProducto::where('servicio_id', $cita->servicio_id)
            ->with('producto')
            ->get()
            ->each(function (ServicioProducto $consumo) use ($cita) {
                $this->stock->salida($consumo->producto, $consumo->cantidad_usada, $cita);
            });
    }
}
