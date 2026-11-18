<?php

namespace App\Services\Citas;

use App\Models\Cita;
use App\Models\Empleado;
use App\Models\Servicio;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

/**
 * Reglas de disponibilidad de empleados.
 *
 * Encapsula la logica exigida por el negocio:
 *   1. Empleado capacitado para el servicio (servicio_empleado).
 *   2. Empleado dentro de su horario de trabajo (horarios_disponibilidad).
 *   3. Sin citas solapadas del mismo empleado.
 *
 * Ninguna de estas reglas vive en el controller.
 */
class DisponibilidadService
{
    public const GRANULARIDAD_MIN = 15;

    public function empleadosDisponibles(int $servicioId, string $fecha): array
    {
        Cita::expirarVerificacionesPendientes();

        $servicio = Servicio::findOrFail($servicioId);
        $diaSemana = (int) Carbon::parse($fecha)->dayOfWeek;

        $empleados = $servicio->empleados()
            ->whereHas('horariosDisponibilidad', fn ($q) => $q->where('dia_semana', $diaSemana))
            ->with(['horariosDisponibilidad' => fn ($q) => $q->where('dia_semana', $diaSemana)])
            ->get();

        $resultado = $empleados
            ->map(fn (Empleado $empleado) => [
                'empleado' => $empleado,
                'horarios' => $this->horariosLibres($empleado, $servicio, $fecha),
            ])
            ->filter(fn (array $item) => count($item['horarios']['turnos']) > 0)
            ->values();

        return [
            'servicio' => $servicio,
            'fecha' => $fecha,
            'empleados' => $resultado,
        ];
    }

    /**
     * Valida si el empleado puede atender el servicio justo en ese horario.
     *
     * @return array<string, string> lista de errores (vacía si es válido)
     */
    public function validarEmpleadoParaHorario(Empleado $empleado, Servicio $servicio, Carbon $inicio, ?int $ignorarCitaId = null): array
    {
        Cita::expirarVerificacionesPendientes();

        $errores = [];

        if (! $this->esCapacitado($empleado, $servicio)) {
            $errores['empleado_id'] = 'El empleado seleccionado no esta capacitado para el servicio elegido.';
        }

        $diaSemana = (int) $inicio->dayOfWeek;
        $minutoInicio = $inicio->hour * 60 + $inicio->minute;
        $minutoFin = $minutoInicio + (int) $servicio->duracion_min;

        $turnos = $empleado->horariosDisponibilidad
            ->where('dia_semana', $diaSemana)
            ->map(fn ($h) => [
                'inicio' => $this->aMinutos($h->hora_inicio),
                'fin' => $this->aMinutos($h->hora_fin),
            ])
            ->values();

        $dentroDeTurno = $turnos->contains(
            fn (array $turno) => $minutoInicio >= $turno['inicio'] && $minutoFin <= $turno['fin']
        );

        if (! $dentroDeTurno) {
            $errores['fecha_hora_inicio'] = 'El horario solicitado esta fuera del turno de trabajo del empleado.';
        }

        if ($this->tieneSolapamiento($empleado, $inicio, $servicio->duracion_min, $ignorarCitaId)) {
            $errores['fecha_hora_inicio'] = 'El empleado ya tiene una cita en ese horario.';
        }

        return $errores;
    }

    /**
     * Bloques libres y turnos (horas de inicio) donde cabe el servicio completo.
     */
    public function horariosLibres(Empleado $empleado, Servicio $servicio, string $fecha): array
    {
        $dia = Carbon::parse($fecha);
        $diaSemana = (int) $dia->dayOfWeek;
        $duracion = (int) $servicio->duracion_min;

        $turnosDelDia = $empleado->horariosDisponibilidad
            ->where('dia_semana', $diaSemana)
            ->map(fn ($h) => [
                'inicio' => $this->aMinutos($h->hora_inicio),
                'fin' => $this->aMinutos($h->hora_fin),
            ])
            ->values();

        if ($turnosDelDia->isEmpty()) {
            return ['bloques_libres' => [], 'turnos' => []];
        }

        $ocupadas = $this->citasOcupadasDelDia($empleado, $dia)
            ->map(fn (Cita $cita) => [
                $cita->fecha_hora_inicio->hour * 60 + $cita->fecha_hora_inicio->minute,
                $cita->fecha_hora_fin->hour * 60 + $cita->fecha_hora_fin->minute,
            ]);

        $bloquesLibres = collect();
        foreach ($turnosDelDia as $turno) {
            $bloquesLibres->push(...$this->bloquesLibresEnTurno($turno, $ocupadas->all()));
        }
        $bloquesLibres = $bloquesLibres->values();

        $turnos = collect();
        foreach ($bloquesLibres as [$ini, $fin]) {
            $cursor = $ini;
            while ($cursor + $duracion <= $fin) {
                $turnos->push($this->aHora($cursor));
                $cursor += self::GRANULARIDAD_MIN;
            }
        }

        return [
            'bloques_libres' => $bloquesLibres->map(fn ($b) => [
                'inicio' => $this->aHora($b[0]),
                'fin' => $this->aHora($b[1]),
            ])->all(),
            'turnos' => $turnos->all(),
        ];
    }

    protected function esCapacitado(Empleado $empleado, Servicio $servicio): bool
    {
        return $empleado->servicios()->where('servicios.id', $servicio->id)->exists();
    }

    protected function tieneSolapamiento(Empleado $empleado, Carbon $inicio, int $duracionMin, ?int $ignorarCitaId = null): bool
    {
        $fin = $inicio->copy()->addMinutes($duracionMin);

        return $empleado->citas()
            ->when($ignorarCitaId, fn ($q) => $q->where('id', '!=', $ignorarCitaId))
            ->whereIn('estado', [
                Cita::ESTADO_PENDIENTE,
                Cita::ESTADO_PENDIENTE_VERIFICACION,
                Cita::ESTADO_CONFIRMADA,
            ])
            ->where(function ($q) use ($inicio, $fin) {
                $q->whereBetween('fecha_hora_inicio', [$inicio, $fin])
                    ->orWhereBetween('fecha_hora_fin', [$inicio, $fin])
                    ->orWhere(function ($q) use ($inicio, $fin) {
                        $q->where('fecha_hora_inicio', '<=', $inicio)
                            ->where('fecha_hora_fin', '>=', $fin);
                    });
            })
            ->exists();
    }

    protected function citasOcupadasDelDia(Empleado $empleado, Carbon $dia): Collection
    {
        return $empleado->citas()
            ->whereDate('fecha_hora_inicio', $dia->toDateString())
            ->whereIn('estado', [
                Cita::ESTADO_PENDIENTE,
                Cita::ESTADO_PENDIENTE_VERIFICACION,
                Cita::ESTADO_CONFIRMADA,
            ])
            ->get(['fecha_hora_inicio', 'fecha_hora_fin']);
    }

    /**
     * @param  array{inicio:int, fin:int}  $turno
     * @param  array<int, array{0:int, 1:int}>  $ocupadas
     */
    protected function bloquesLibresEnTurno(array $turno, array $ocupadas): array
    {
        usort($ocupadas, fn ($a, $b) => $a[0] <=> $b[0]);

        $inicioTurno = $turno['inicio'];
        $finTurno = $turno['fin'];
        $libres = [];
        $cursor = $inicioTurno;

        foreach ($ocupadas as [$iniOcu, $finOcu]) {
            if ($finOcu <= $cursor) {
                continue;
            }
            if ($iniOcu > $cursor) {
                $libres[] = [$cursor, min($iniOcu, $finTurno)];
            }
            $cursor = max($cursor, $finOcu);

            if ($cursor >= $finTurno) {
                break;
            }
        }

        if ($cursor < $finTurno) {
            $libres[] = [$cursor, $finTurno];
        }

        return array_values(array_filter($libres, fn ($b) => $b[1] > $b[0]));
    }

    protected function aMinutos(string $hora): int
    {
        [$h, $m] = array_map('intval', explode(':', $hora));

        return $h * 60 + $m;
    }

    protected function aHora(int $minutos): string
    {
        return sprintf('%02d:%02d', intdiv($minutos, 60), $minutos % 60);
    }
}
